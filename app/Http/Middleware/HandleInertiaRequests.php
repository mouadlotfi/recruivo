<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'inertia';

    /**
     * Shell translation key => lang file key. Only strings the app shell
     * (header / mobile nav / footer / dropdowns) renders are shared.
     *
     * @var array<string, string>
     */
    private const SHELL_TRANSLATION_SOURCES = [
        'home' => 'common.home',
        'jobs' => 'common.jobs',
        'companies' => 'common.companies',
        'dashboard' => 'common.dashboard',
        'explore' => 'common.explore',
        'manage' => 'common.manage',
        'manage_jobs' => 'recruiter.manage_jobs',
        'settings' => 'common.settings',
        'saved_jobs_short' => 'common.saved_jobs_short',
        'my_applications_short' => 'common.my_applications_short',
        'my_applications' => 'common.my_applications',
        'saved_jobs' => 'common.saved_jobs',
        'log_in' => 'common.log_in',
        'sign_up' => 'common.sign_up',
        'sign_out' => 'common.sign_out',
        'profile_settings' => 'common.profile_settings',
        'company_profile' => 'common.company_profile',
        'post_job' => 'recruiter.post_new_job',
        'notifications' => 'common.notifications',
        'dismiss' => 'common.dismiss',
        'scroll_to_top' => 'common.scroll_to_top',
        'contact' => 'common.contact',
        'footer_text' => 'common.footer_text',
        'search' => 'common.search',
        'language' => 'common.language',
        'close' => 'common.close',
        'cancel' => 'common.cancel',
        'done' => 'common.done',
        'expand' => 'common.expand',
        'toggle_theme' => 'common.toggle_theme',
        'primary_navigation' => 'common.primary_navigation',
        'mark_all_as_read' => 'common.mark_all_as_read',
        'no_notifications' => 'common.no_notifications',
        'unread_notifications' => 'common.unread_notifications',
    ];

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'supportedLocales' => config('locales.supported', ['en', 'fr']),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->all(),
                    'is_demo' => $user->is_demo,
                    'company_id' => $user->company_id,
                    'candidate_profile_id' => $user->candidateProfile?->id,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'notificationCount' => fn () => $user
                ? $user->unreadNotifications()->count()
                : 0,
            'translations' => $this->shellTranslations(),
        ];
    }

    /**
     * Shell strings for both locales, keyed by locale so the frontend can
     * switch without a round trip.
     *
     * @return array<string, array<string, string>>
     */
    private function shellTranslations(): array
    {
        $build = fn (string $locale): array => collect(self::SHELL_TRANSLATION_SOURCES)
            ->mapWithKeys(fn (string $source, string $key) => [$key => $this->shellString($source, $locale)])
            ->all();

        return [
            'en' => $build('en'),
            'fr' => $build('fr'),
        ];
    }

    private function shellString(string $source, string $locale): string
    {
        $value = __($source, ['year' => (string) date('Y')], $locale);

        return is_string($value) ? $value : $source;
    }
}
