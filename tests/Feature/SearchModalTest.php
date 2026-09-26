<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SearchModalTest extends TestCase
{
    public function test_search_modal_has_dialog_focus_and_scroll_lock_contracts(): void
    {
        $modal = File::get(resource_path('js/Components/Layout/SearchModal.vue'));
        // The Tab trap, Escape handling, body-scroll lock and focus restore
        // moved into the shared focus-trap composable; the assertions that
        // named them now read the file that owns them.
        $trap = File::get(resource_path('js/composables/useFocusTrap.ts'));

        $this->assertStringContainsString('<Teleport to="body">', $modal);
        $this->assertStringContainsString('v-if="open"', $modal);
        $this->assertStringContainsString('role="dialog"', $modal);
        $this->assertStringContainsString('aria-modal="true"', $modal);
        $this->assertStringContainsString(':aria-labelledby="titleId"', $modal);
        $this->assertStringContainsString(':aria-describedby="hintId"', $modal);
        $this->assertStringContainsString(':id="hintId"', $modal);
        $this->assertStringContainsString('@click.self="close"', $modal);
        $this->assertStringContainsString('useFocusTrap(isOpen, dialog', $modal);
        $this->assertStringContainsString('trigger: () => props.trigger ?? null', $modal);
        $this->assertStringContainsString("event.key === 'Escape'", $trap);
        $this->assertStringContainsString("event.key !== 'Tab'", $trap);
        $this->assertStringContainsString("window.addEventListener('focusin', onWindowFocusin, true)", $modal);
        $this->assertStringContainsString("window.removeEventListener('focusin', onWindowFocusin, true)", $modal);
        $this->assertStringContainsString('dialog.value.contains(target)', $modal);
        $this->assertStringContainsString('event.preventDefault()', $modal);
        $this->assertStringContainsString("document.body.style.overflow = 'hidden'", $trap);
        $this->assertStringContainsString('previousBodyOverflow', $trap);
        $this->assertStringContainsString('firstInput() ?? focusables()[0] ?? dialog.value', $modal);
        $this->assertStringContainsString('nextTick(() => element.focus())', $trap);
        $this->assertStringContainsString("const close = () => {\n    query.value = ''", $modal);
        $this->assertStringContainsString('p-5', $modal);
        $this->assertStringContainsString('sm:p-6', $modal);
    }

    public function test_search_modal_submits_a_localized_inertia_search_and_closes(): void
    {
        $modal = File::get(resource_path('js/Components/Layout/SearchModal.vue'));

        $this->assertStringContainsString("import { router, usePage } from '@inertiajs/vue3'", $modal);
        $this->assertStringContainsString('router.get(searchUrl.value', $modal);
        $this->assertStringContainsString('preserveState: true', $modal);
        $this->assertStringContainsString('preserveScroll: true', $modal);
        $this->assertStringContainsString('const searchUrl = computed(() => `/${page.props.locale}/search`)', $modal);
        $this->assertStringContainsString('close()', $modal);
        $this->assertStringNotContainsString('window.location', $modal);
        $this->assertStringNotContainsString("from 'vue-router'", $modal);
        $this->assertStringContainsString('@submit="submitSearch"', $modal);
    }

    public function test_both_layouts_use_the_shared_modal_button_and_controlled_state(): void
    {
        // The trigger button and the modal it opens live in the shared header,
        // which both layouts render instead of wiring their own copy.
        $header = File::get(resource_path('js/Components/Layout/SiteHeader.vue'));

        foreach ([
            "import SearchModal from './SearchModal.vue'",
            'ref="searchTrigger"',
            'id="mobile-search-toggle"',
            '<button',
            'type="button"',
            'aria-haspopup="dialog"',
            ':aria-expanded="searchOpen"',
            'h-11 w-11',
            '@click="searchOpen = true"',
            '<SearchModal v-model:open="searchOpen" :trigger="searchTrigger" />',
        ] as $contract) {
            $this->assertStringContainsString($contract, $header, 'SiteHeader.vue');
        }

        foreach (['AppLayout.vue', 'GuestLayout.vue'] as $layoutFile) {
            $layout = File::get(resource_path("js/Layouts/{$layoutFile}"));

            $this->assertStringContainsString("import SiteHeader from '../Components/Layout/SiteHeader.vue'", $layout, $layoutFile);
            $this->assertStringContainsString('<SiteHeader :logo-href="logoHref"', $layout, $layoutFile);
            $this->assertSame(
                0,
                preg_match('/<Link\b[^>]*\bid=["\']mobile-search-toggle["\'][^>]*>/s', $layout),
                "{$layoutFile} must not use a direct-search Link trigger.",
            );
        }
    }

    public function test_search_modal_copy_is_available_through_both_locale_shell_translations(): void
    {
        $middleware = File::get(app_path('Http/Middleware/HandleInertiaRequests.php'));

        foreach (['search', 'search_placeholder', 'search_hint', 'clear_search', 'close_search'] as $key) {
            $this->assertStringContainsString("'{$key}' => 'common.{$key}'", $middleware, "Missing shell source for {$key}.");
        }

        foreach (['en', 'fr'] as $locale) {
            $translations = File::get(resource_path("lang/{$locale}/common.php"));

            foreach (['search', 'search_placeholder', 'search_hint', 'clear_search', 'close_search'] as $key) {
                $this->assertStringContainsString("'{$key}' =>", $translations, "Missing {$locale} translation for {$key}.");
            }
        }
    }
}
