<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Canonical frontend boot architecture contracts.
 *
 * These tests pin the TypeScript entrypoint, Vite/Tailwind config formats,
 * Inertia v3 root shell, and global type contracts that the Blade/JS cleanup
 * unit can rely on. They read repository root files, so the test harness must
 * mount the current versions (see /tmp/recruivo-test.sh).
 */
class FrontendArchitectureTest extends TestCase
{
    private function appTs(): string
    {
        return File::get(resource_path('js/app.ts'));
    }

    private function rootShell(): string
    {
        return File::get(resource_path('views/inertia.blade.php'));
    }

    private function tsconfig(): array
    {
        return json_decode(File::get(base_path('tsconfig.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    private function packageJson(): array
    {
        return json_decode(File::get(base_path('package.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_vite_config_is_typescript_with_a_single_app_entry(): void
    {
        $this->assertFileExists(base_path('vite.config.ts'));
        $this->assertFileDoesNotExist(base_path('vite.config.js'), 'vite.config.js must be converted to vite.config.ts.');

        $config = File::get(base_path('vite.config.ts'));

        $applicationEntries = preg_match_all('/resources\/js\/app\.(js|ts)/', $config);
        $this->assertSame(
            1,
            $applicationEntries,
            'vite.config.ts must declare exactly one application entry (resources/js/app.ts).'
        );
        $this->assertStringContainsString('resources/js/app.ts', $config);
        $this->assertStringNotContainsString('resources/js/app.js', $config, 'Only app.ts may be a Vite entry.');
        $this->assertStringNotContainsString(
            'resources/css/app.css',
            $config,
            'CSS must be imported by app.ts, not declared as a separate Laravel Vite entry.'
        );
    }

    public function test_app_typescript_imports_global_css_instead_of_a_vite_entry(): void
    {
        $app = $this->appTs();

        $this->assertMatchesRegularExpression(
            '/import\s+[\'"][^"\']*app\.css[\'"]/',
            $app,
            'app.ts must import the global stylesheet.'
        );
        $this->assertStringContainsString('../css/app.css', $app);
    }

    public function test_package_json_defines_a_vue_tsc_typecheck_script(): void
    {
        $package = $this->packageJson();

        $this->assertSame('vue-tsc --noEmit', $package['scripts']['typecheck'] ?? null);
    }

    public function test_inertia_root_uses_v3_components_and_the_app_entry(): void
    {
        $root = $this->rootShell();

        $this->assertMatchesRegularExpression('/<x-inertia::head>|<x-inertia:head>/', $root);
        $this->assertMatchesRegularExpression('/<x-inertia::app\s*\/?>|<x-inertia:app\s*\/?>/', $root);

        $this->assertStringContainsString('@vite', $root);
        $this->assertStringContainsString('resources/js/app.ts', $root);
        $this->assertStringNotContainsString('resources/js/app.js', $root, 'The root must not reference legacy app.js.');
        $this->assertStringNotContainsString('resources/css/app.css', $root, 'CSS ships through app.ts, not a Vite entry.');
        $this->assertStringNotContainsString('@inertiaHead', $root, 'Use <x-inertia::head> instead of the legacy directive.');
        $this->assertStringNotContainsString('@inertia', $root, 'Use <x-inertia::app> instead of the legacy directive.');
    }

    public function test_app_ts_uses_a_typed_page_resolver_without_casts(): void
    {
        $app = $this->appTs();

        $this->assertStringContainsString(
            'import.meta.glob<DefineComponent>',
            $app,
            'The page resolver must use the typed import.meta.glob<DefineComponent> generics.'
        );
        $this->assertStringNotContainsString('as unknown as', $app, 'No unknown-to-Component casts are allowed.');
        $this->assertStringNotContainsString('as any', $app, 'No any casts are allowed.');
        $this->assertStringNotContainsString(': any', $app, 'No any annotations are allowed.');
        $this->assertStringNotContainsString('<any>', $app, 'No any type arguments are allowed.');
    }

    public function test_inertia_global_types_augment_inertia_config_and_are_included(): void
    {
        $declaration = resource_path('js/inertia.d.ts');

        $this->assertFileExists($declaration);

        $types = File::get($declaration);
        $this->assertStringContainsString("declare module '@inertiajs/core'", $types);
        $this->assertStringContainsString('interface InertiaConfig', $types);
        $this->assertStringContainsString('sharedPageProps', $types);

        $include = implode("\n", $this->tsconfig()['include'] ?? []);
        $this->assertStringContainsString('resources/js/**/*.d.ts', $include, 'The .d.ts declaration must be type-checked.');
    }

    public function test_config_files_are_type_checked_with_node_types(): void
    {
        $tsconfig = $this->tsconfig();

        $this->assertArrayNotHasKey(
            'baseUrl',
            $tsconfig['compilerOptions'],
            'Deprecated baseUrl must be removed, not suppressed.'
        );
        $this->assertTrue($tsconfig['compilerOptions']['strict'] ?? false, 'Strict mode must stay enabled.');

        $include = implode("\n", $tsconfig['include'] ?? []);
        $this->assertStringContainsString('vite.config.ts', $include, 'vite.config.ts must be type-checked.');
        $this->assertStringContainsString('tailwind.config.ts', $include, 'tailwind.config.ts must be type-checked.');

        $types = $tsconfig['compilerOptions']['types'] ?? [];
        $this->assertContains('node', $types, 'Node types must be available for the config files.');
    }

    public function test_tailwind_config_is_typescript_and_scans_vue_ts_and_the_root_only(): void
    {
        $this->assertFileExists(base_path('tailwind.config.ts'));
        $this->assertFileDoesNotExist(base_path('tailwind.config.js'), 'tailwind.config.js must be converted to tailwind.config.ts.');

        $config = File::get(base_path('tailwind.config.ts'));

        $this->assertMatchesRegularExpression(
            '/import\s+type\s*\{[^}]*\bConfig\b[^}]*\}\s+from\s+[\'"]tailwindcss[\'"]/',
            $config,
            'tailwind.config.ts must be typed with Tailwind\'s official Config type.'
        );

        $this->assertStringContainsString('darkMode: \'class\'', $config);
        $this->assertStringContainsString('Plus Jakarta Sans', $config, 'The font theme must be preserved.');
        $this->assertStringContainsString('Space Grotesk', $config, 'The display font theme must be preserved.');
        $this->assertStringContainsString('forms', $config, 'The forms plugin must be preserved.');

        $this->assertStringContainsString('./resources/js/**/*.vue', $config);
        $this->assertStringNotContainsString(
            './resources/**/*.blade.php',
            $config,
            'Legacy Blade content globs must be removed from Tailwind scanning.'
        );
        $this->assertStringNotContainsString(
            './resources/**/*.js',
            $config,
            'Legacy JS content globs must be removed from Tailwind scanning.'
        );
        $this->assertStringContainsString(
            'inertia.blade.php',
            $config,
            'The minimal Inertia root shell must stay scanned so body classes are generated.'
        );
    }

    public function test_postcss_config_is_kept_as_javascript(): void
    {
        $this->assertFileExists(base_path('postcss.config.js'));
    }

    public function test_dockerfile_references_the_typescript_config_names(): void
    {
        $dockerfile = File::get(base_path('Dockerfile'));

        $this->assertStringContainsString('vite.config.ts', $dockerfile);
        $this->assertStringContainsString('tailwind.config.ts', $dockerfile);
        $this->assertStringNotContainsString('vite.config.js', $dockerfile);
        $this->assertStringNotContainsString('tailwind.config.js', $dockerfile);
    }
}
