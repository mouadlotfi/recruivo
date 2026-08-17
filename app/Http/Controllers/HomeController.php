<?php

namespace App\Http\Controllers;

use App\Enums\ItCategory;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Page strings travel with the home page instead of expanding the shared shell translations.
     *
     * @var array<string, string>
     */
    private const PAGE_LABELS = [
        'title' => 'common.home',
        'hero_title_guest' => 'home.hero_title_guest',
        'hero_description_guest' => 'home.hero_description_guest',
        'get_started' => 'home.get_started',
        'browse_jobs' => 'home.browse_jobs',
        'hero_description_candidate' => 'home.hero_description_candidate',
        'hero_description_recruiter' => 'home.hero_description_recruiter',
        'post_a_job' => 'home.post_a_job',
        'view_dashboard' => 'home.view_dashboard',
        'hero_title' => 'home.hero_title',
        'hero_description' => 'home.hero_description',
        'active_roles' => 'home.active_roles',
        'remote_jobs' => 'home.remote_jobs',
        'new_this_week' => 'home.new_this_week',
        'companies_hiring' => 'home.companies_hiring',
        'no_roles_title' => 'home.no_roles_title',
        'no_roles_description' => 'home.no_roles_description',
        'show_all_opportunities' => 'home.show_all_opportunities',
        'recommended_for_you' => 'jobs.recommended_for_you',
        'remote' => 'jobs.remote',
        'hybrid' => 'jobs.hybrid',
        'onsite' => 'jobs.onsite',
        'closing_soon' => 'jobs.closing_soon',
        'save_job' => 'jobs.save_job',
        'remove_saved_job' => 'jobs.remove_saved_job',
        'demo_cannot_save_jobs' => 'jobs.demo_cannot_save_jobs',
        'applied' => 'common.applied',
        'show_more' => 'common.show_more',
        'loading_more' => 'common.loading_more',
        'load_more_failed' => 'common.load_more_failed',
        'preferences_modal_title' => 'profile.preferences_modal_title',
        'preferences_modal_help' => 'profile.preferences_modal_help',
        'save_preferences' => 'profile.save_preferences',
        'skip_for_now' => 'profile.skip_for_now',
    ];

    public function index()
    {
        if (auth()->user()?->hasRole('Recruiter')) {
            return redirect(localized_route('recruiter.dashboard'));
        }

        $user = auth()->user();
        $isCandidate = $user?->hasRole('Candidate') ?? false;
        $preferred = $isCandidate ? $user->candidateProfile?->preferred_categories : null;
        $hasPreferences = filled($preferred);

        $query = Job::published()
            ->with('company')
            ->withSavedStateFor($user);

        if ($isCandidate) {
            $query->withExists([
                'applications as has_applied' => fn (Builder $applications) => $applications->where('candidate_id', $user->id),
            ]);
        }

        $jobs = $query
            ->orderByPreference($preferred)
            ->paginate(12)
            ->withQueryString();

        $heroName = $user?->hasRole('Recruiter')
            ? ($user->company?->name ?? $user->name)
            : $user?->name;

        $labels = collect(self::PAGE_LABELS)
            ->mapWithKeys(fn (string $source, string $key) => [$key => __($source)])
            ->all();
        $labels['hero_title_candidate'] = __('home.hero_title_candidate', ['name' => $user?->name ?? '']);
        $labels['hero_title_candidate_first'] = __('home.hero_title_candidate_first', ['name' => $user?->name ?? '']);
        $labels['hero_title_recruiter'] = __('home.hero_title_recruiter', ['name' => $heroName ?? '']);

        $selectedPreferences = $user?->candidateProfile?->preferred_categories ?? [];
        $showPreferenceModal = $isCandidate
            && $user->hasVerifiedEmail()
            && session('show_preferences_picker');

        return Inertia::render('Home/Index', [
            'jobs' => array_map(fn (Job $job) => $this->serializeJobCard($job), $jobs->items()),
            'metrics' => [
                'total_roles' => Job::published()->count(),
                'remote_roles' => Job::published()->where('remote_type', 'remote')->count(),
                'new_this_week' => Job::published()->where('published_at', '>=', now()->subWeek())->count(),
                'active_companies' => Company::whereHas('jobs', fn (Builder $jobs) => $jobs->published())->count(),
            ],
            'hasPreferences' => $hasPreferences,
            'pagination' => [
                'total' => $jobs->total(),
                'per_page' => $jobs->perPage(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'next_page_url' => $jobs->nextPageUrl(),
                'prev_page_url' => $jobs->previousPageUrl(),
            ],
            'preferenceModal' => [
                'show' => $showPreferenceModal,
                'categories' => ItCategory::values(),
                'selected' => $selectedPreferences,
            ],
            'firstLogin' => (bool) session('first_login'),
            'labels' => $labels,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeJobCard(Job $job): array
    {
        return [
            'id' => $job->id,
            'title' => $job->title,
            'location' => $job->location,
            'remote_type' => $job->remote_type,
            'category' => $job->category,
            'salary_min' => $job->salary_min,
            'salary_max' => $job->salary_max,
            'closes_at' => $job->closes_at?->toIso8601String(),
            'is_closing_soon' => $job->isClosingSoon(),
            'closes_label' => $job->closes_at
                ? __('jobs.closes_on', ['date' => $job->closes_at->translatedFormat('M j, Y')])
                : null,
            'is_saved' => (bool) ($job->is_saved ?? false),
            'has_applied' => (bool) ($job->has_applied ?? false),
            'published_at' => $job->published_at?->toIso8601String(),
            'company' => $job->company ? [
                'id' => $job->company->id,
                'name' => $job->company->name,
                'slug' => $job->company->slug,
                'logo_url' => $job->company->logo_url,
            ] : null,
        ];
    }
}
