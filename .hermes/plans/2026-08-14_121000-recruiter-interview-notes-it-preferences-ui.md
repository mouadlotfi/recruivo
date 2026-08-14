# Recruiter Interview Modes, Note Templates, IT-Focused Recommendations, and UI Polish — Implementation Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Make interviews explicit (online/onsite with the right required field), make recruiter notes optional and templated, make the product visibly IT-focused with candidate-driven job recommendations (including a first-login preference popup), collapse cover letters by default on both sides, enlarge the two small textareas, and make company locations clickable.

**Architecture:** Extend the existing per-application interview fields with an explicit `interview_mode` (online/onsite). Notes become optional everywhere; note templates are a small per-recruiter CRUD (one table, no package). Candidate preferences are a JSON array of IT category slugs on the existing `candidate_profiles` table; recommendation is a query-time category boost on the home and jobs pages — no recommendation engine, no new search backend. Cover-letter collapse uses native `<details>`/`<summary>` (no JS). Textarea growth uses a tiny shared Alpine `x-data` autosize (already using Alpine). Company location becomes a link to the existing location-filtered search route.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS 3, PHPUnit 11, existing Docker test harness, EN/FR localization.

---

## Scope and deliberate limits

Implement:

1. Interview mode selection (online/onsite) with conditional required field: online → meeting URL required; onsite → location required (a URL is accepted as a location). Notes optional in all transitions.
2. Recruiter note templates: create/edit/delete personal templates, insert into the notes textarea with one click. No sharing, no organization-wide templates.
3. IT focus: the seeded category vocabulary is already IT-heavy; expose a curated IT interest list (multi-select) on candidate profile settings, a first-login popup for candidates to choose interests, and preference-boosted ordering of jobs on `/` and `/jobs` (matching categories first; everything else still visible after).
4. Cover letters collapsed by default in candidate "My Applications" and recruiter application/applicant views.
5. Larger cover-letter textarea on the apply form and recruiter notes textarea (autosize-to-content).
6. Clickable company locations on `/companies` linking to the existing location-filtered search.

Deliberately skip:

- Interview rescheduling history, calendar/ICS, timezone conversion, reminders.
- Shared/organizational note templates, template variables, or per-job template assignment.
- A separate "recommendations engine", scoring model, ML, or hiding non-matching jobs.
- Chat, salary negotiation tools, or anything beyond the listed surfaces.
- New API endpoints unless the existing API resource/controllers are trivially extended by the same model changes (add only when a listed web feature has an API twin already present).

## Current context and assumptions

- `Application` already stores `interview_at`, `interview_location`, `interview_url`, `interview_instructions`; `UpdateApplicationStatusRequest` currently cross-requires location-or-URL when moving to Interview and REQUIRES notes for Accepted/Rejected transitions. The review panel shows a `fieldset` when status is interview.
- Notes are currently required only when transitioning to accepted/rejected. The user wants notes optional — remove that `required` rule while keeping notes editable.
- `Application` has Eloquent `booted()` hooks (created/updated) that append status events; any new field writes flow through the same `applyStatusUpdate`/`update` path, so no event changes are needed.
- Jobs carry `category` strings (Cloud Computing, Software Development, Cybersecurity, Data Analytics, Artificial Intelligence, DevOps, Hardware Systems, Networking, Quantum Computing, Information Security, Web Development, IoT Systems, Full-Stack Development, Interactive Software, IT Consulting, Engineering, Information Technology).
- `CandidateProfile` already stores JSON arrays (`languages_data`, `profile_links`, `experiences`, `educations`) — adding a `preferred_categories` JSON column matches the established pattern.
- Home and `/jobs` both query `Job::published()` and paginate; recommendation = order matched categories first, then the rest by published date, with an optional "Recommended for you" heading when the user has preferences.
- Registration auto-logins and redirects to the verification notice page; a candidate preference popup can mount on the layout/guest page gated by session flag + `preferences_picked` column or by "profile has no preferred_categories yet".
- The app is localized `en`/`fr`; every new visible string needs both files. `localized_route()` is the route helper.
- Company cards use `x-company-card`; job location links already link to `/search?location=...` (pattern exists in job cards — reuse it).
- Demo accounts are immutable server-side; note templates are recruiter-owned CRUD (recruiters are not demo-candidates; verify no DemoAccountGuard restriction applies).
- The repo is intentionally dirty. Do NOT commit unless the user explicitly asks. If commits are later authorized, stage only files named by the completed task.
- Two orchestrator sessions may touch this repo concurrently; check `/home/crate/.hermes/profiles/dev/cache/delegation/live/*/manifest.json` for live foreign delegations before assuming file corruption.

## Test and verification commands

Use the existing verified Docker harness:

```bash
/tmp/recruivo-test.sh tests/Feature/InterviewModeTest.php
/tmp/recruivo-test.sh tests/Feature/RecruiterNoteTemplateTest.php
/tmp/recruivo-test.sh tests/Feature/CandidatePreferencesTest.php
/tmp/recruivo-test.sh tests/Feature/ApplicationUiPolishTest.php
/tmp/recruivo-test.sh tests/Feature/RecruiterApplicationPipelineTest.php
/tmp/recruivo-test.sh tests/Feature/CandidateApplicationExperienceTest.php
/tmp/recruivo-test.sh tests/Feature/SmartSearchTest.php
/tmp/recruivo-test.sh
```

Final gates:

```bash
npm run build
npm audit --audit-level=high
git diff --check
docker compose --env-file .env.docker up -d --build laravel
docker exec recruivo-laravel-1 php artisan migrate --force
```

Expected final result: all PHPUnit tests pass, Vite builds, audit reports no high-severity vulnerabilities, `git diff --check` prints nothing, the app is healthy, and desktop/mobile live checks pass.

---

## Feature A: Interview mode and optional notes

### Task 1: Add interview_mode persistence

**Objective:** Store whether an interview is online or onsite without breaking existing rows.

**Files:**
- Create: `database/migrations/2026_08_14_120000_add_interview_mode_to_applications_table.php`
- Modify: `app/Models/Application.php`
- Modify: `app/Http/Controllers/Api/Recruiter/ApplicationController.php` (only if the API resource serializes interview fields)
- Modify: `app/Http/Resources/ApplicationResource.php`
- Create: `tests/Feature/InterviewModeTest.php`

**Step 1: Write the failing model/migration contract**

Create `InterviewModeTest` (`RefreshDatabase`, Candidate+Recruiter roles in setUp) asserting:

```php
public function test_application_casts_interview_mode(): void
{
    $application = Application::factory()->create(['interview_mode' => 'online']);

    $this->assertSame('online', $application->interview_mode);
}

public function test_interview_mode_defaults_to_onsite_for_existing_rows(): void
{
    // Factory default should be 'onsite' so existing applications remain valid.
    $application = Application::factory()->create();

    $this->assertSame('onsite', $application->interview_mode);
}
```

**Step 2: Run RED**

```bash
/tmp/recruivo-test.sh tests/Feature/InterviewModeTest.php
```

Expected: column missing.

**Step 3: Add the migration**

```php
Schema::table('applications', function (Blueprint $table) {
    $table->string('interview_mode')->default('onsite')->after('interview_at');
});
```

`down()` drops the column. Do not add an enum class; a validated string is enough.

**Step 4: Model and factory**

- Add `'interview_mode'` to `Application::$fillable`.
- Add `'interview_mode' => 'string'` to casts (or leave uncast; match `interview_location` style).
- Add `'interview_mode' => 'onsite'` to `ApplicationFactory` definition.

**Step 5: GREEN**

Expected: both tests pass; existing pipeline tests still pass.

---

### Task 2: Validate interview mode with conditional required fields; make notes optional

**Objective:** Online interviews require a URL; onsite interviews require a location (URL allowed as location); notes are never required.

**Files:**
- Modify: `app/Http/Requests/UpdateApplicationStatusRequest.php`
- Modify: `resources/lang/en/validation.php`
- Modify: `resources/lang/fr/validation.php`
- Modify: `tests/Feature/InterviewModeTest.php`

**Step 1: Write failing validation tests**

Cover:

```php
public function test_online_interview_requires_meeting_url(): void
{
    // actingAs recruiter; application pending
    $this->patch(route('recruiter.applications.update', $application), [
        'status' => 'interview',
        'interview_mode' => 'online',
        'interview_at' => now()->addDays(2)->format('Y-m-d H:i'),
        'interview_location' => 'Somewhere',
        'interview_url' => '',
    ])->assertSessionHasErrors('interview_url');
}

public function test_onsite_interview_requires_location(): void
{
    $this->patch(route('recruiter.applications.update', $application), [
        'status' => 'interview',
        'interview_mode' => 'onsite',
        'interview_at' => now()->addDays(2)->format('Y-m-d H:i'),
        'interview_url' => 'https://meet.example.com/x',
        'interview_location' => '',
    ])->assertSessionHasErrors('interview_location');
}

public function test_onsite_interview_accepts_url_as_location(): void
{
    $this->patch(route('recruiter.applications.update', $application), [
        'status' => 'interview',
        'interview_mode' => 'onsite',
        'interview_at' => now()->addDays(2)->format('Y-m-d H:i'),
        'interview_location' => 'https://maps.example.com/office',
    ])->assertSessionHasNoErrors();
}

public function test_notes_are_optional_when_accepting_or_rejecting(): void
{
    $this->patch(route('recruiter.applications.update', $application), [
        'status' => 'accepted',
        'notes' => '',
    ])->assertSessionHasNoErrors();
}
```

Note: check the route name/URL shape used by the existing pipeline test (`recruiter.applications.update`) and the CSRF convention in that file. `interview_at` input format is `datetime-local` in the form; the validator uses `date` + `after:now` — pass a `Y-m-d\TH:i` value matching the form.

**Step 2: Run RED**

Expected: cross-field rules still require either field, and notes required for accepted/rejected.

**Step 3: Rewrite the interview rules**

Replace the current location/URL cross-require block with mode-driven rules:

```php
$isInterview = $this->input('status') === ApplicationStatus::Interview->value;

$rules['interview_mode'] = ['nullable', 'in:online,onsite'];
$rules['interview_at'] = ['nullable', 'date', 'after:now'];
$rules['interview_location'] = ['nullable', 'string', 'max:255'];
$rules['interview_url'] = ['nullable', 'url:http,https', 'max:2048'];
$rules['interview_instructions'] = ['nullable', 'string', 'max:2000'];

if ($isInterview) {
    $mode = $this->input('interview_mode', 'onsite');
    $rules['interview_at'] = ['required', 'date', 'after:now'];

    if ($mode === 'online') {
        $rules['interview_url'] = ['required', 'url:http,https', 'max:2048'];
        $rules['interview_location'] = ['nullable', 'string', 'max:255'];
    } else {
        $rules['interview_location'] = ['required', 'string', 'max:255'];
        $rules['interview_url'] = ['nullable', 'url:http,https', 'max:2048'];
    }
}
```

Remove the `notes` required rule for final statuses: change `$notesRules = ['required', ...]` back to always `['nullable', 'string', 'max:2000']` and delete the `if ($isStatusBeingChanged && in_array($newStatus, self::FINAL_STATUSES))` block that forces notes. Keep the "notes cannot be modified after final decision" guard intact.

**Step 4: Messages**

Keep `interview_url.required`/`interview_location.required` mapping; add `interview_mode.in` message if desired (EN/FR). Update the `notes_required_status` key usage only if tests reference it; the key may become unused — leave the key in place (harmless) or remove if lint tests complain.

**Step 5: GREEN + regressions**

```bash
/tmp/recruivo-test.sh tests/Feature/InterviewModeTest.php
/tmp/recruivo-test.sh tests/Feature/RecruiterApplicationPipelineTest.php
/tmp/recruivo-test.sh tests/Feature/Api/Recruiter/ApplicationStatusManagementTest.php
```

Note: the API status-management test may assert notes-required behavior for accepted/rejected — read it first; if it does, update those assertions to the new optional-notes contract (the user's request overrides the old behavior; document the change in the report).

---

### Task 3: Interview mode UI in the recruiter review panel

**Objective:** Recruiter picks online/onsite; the right field becomes required and visible.

**Files:**
- Modify: `resources/views/recruiter/applications/index.blade.php`
- Modify: `resources/lang/en/recruiter.php`
- Modify: `resources/lang/fr/recruiter.php`
- Modify: `tests/Feature/InterviewModeTest.php`

**Step 1: Write failing rendering tests**

Assert:

- The interview fieldset contains a mode selector (`name="interview_mode"`, options `online` and `onsite`).
- The mode selector is shown only when status is interview (`x-show="status === 'interview'"` on the same fieldset).
- The online branch shows the URL input labeled `recruiter.interview_url` and hides location (or marks it optional); the onsite branch shows location and hides URL (use Alpine `x-show` on `mode === 'online'` / `mode === 'onsite'`).
- Existing location/URL inputs still render.

**Step 2: RED**

Expected: no mode selector.

**Step 3: Blade changes**

Inside the existing `fieldset x-show="status === 'interview'"` block, add at the top:

```blade
<div class="space-y-2">
    <label for="interview_mode-{{ $application->id }}" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
        {{ __('recruiter.interview_mode') }}
    </label>
    <div class="grid grid-cols-2 gap-2">
        <label class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm {{ old('interview_mode', $application->interview_mode ?? 'onsite') === 'onsite' ? 'border-amber-400 bg-amber-50 dark:bg-amber-500/10' : 'border-stone-300 dark:border-stone-600' }}">
            <input type="radio" name="interview_mode" value="onsite" x-model="interviewMode" class="sr-only">
            <svg ...building icon...></svg>
            {{ __('recruiter.interview_onsite') }}
        </label>
        <label class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm ...online active classes...">
            <input type="radio" name="interview_mode" value="online" x-model="interviewMode" class="sr-only">
            <svg ...video icon...></svg>
            {{ __('recruiter.interview_online') }}
        </label>
    </div>
</div>
```

Extend the form's `x-data` to include `interviewMode: @js(old('interview_mode', $application->interview_mode ?? 'onsite'))`.

Wrap the location input with `x-show="interviewMode === 'onsite'"` and the URL input with `x-show="interviewMode === 'online'"`. Keep both `name` attributes present so server-side validation can still fail on the non-visible field with a clear error (no JS-only validation). The existing `@error` paragraphs stay.

**Step 4: EN/FR keys**

- `interview_mode` (EN "Interview mode", FR "Mode d'entretien")
- `interview_onsite` (EN "On-site", FR "Sur place")
- `interview_online` (EN "Online", FR "En ligne")

**Step 5: GREEN**

Run InterviewModeTest + pipeline regression.

---

### Task 4: Show interview mode to candidates and in API

**Objective:** Candidates see whether the interview is online or onsite, with the correct detail shown.

**Files:**
- Modify: `resources/views/candidate/applications.blade.php`
- Modify: `resources/lang/en/applications.php`
- Modify: `resources/lang/fr/applications.php`
- Modify: `app/Http/Resources/ApplicationResource.php`
- Modify: `tests/Feature/InterviewModeTest.php`

**Step 1: Failing tests**

- Candidate application page shows a mode label (`Online`/`On-site`) when interview fields exist, and shows the URL for online or the location for onsite.
- API resource includes `interview_mode`.

**Step 2: RED**

**Step 3: Implement**

In the candidate view's interview-details block (near `interview_when`), add:

```blade
@if($application->interview_mode === 'online' && $application->interview_url)
    <p><strong>{{ __('applications.interview_online') }}</strong> <a href="{{ $application->interview_url }}" target="_blank" rel="noopener" class="text-amber-600 hover:underline">{{ $application->interview_url }}</a></p>
@elseif($application->interview_location)
    <p><strong>{{ __('applications.interview_onsite') }}</strong> {{ $application->interview_location }}</p>
@endif
```

In `ApplicationResource`, add `'interview_mode' => $this->interview_mode` (and `interview_location`, `interview_url`, `interview_instructions`, `interview_at` if not already present — check the current resource; add only what is missing).

Add EN/FR keys: `interview_online` ("Online"/"En ligne"), `interview_onsite` ("On-site"/"Sur place") in `applications.php`.

**Step 4: GREEN**

Run InterviewModeTest + CandidateApplicationExperienceTest.

---

## Feature B: Recruiter note templates

### Task 5: Note-template persistence and CRUD

**Objective:** Each recruiter can store reusable note snippets and insert them into the notes textarea.

**Files:**
- Create: `database/migrations/2026_08_14_120100_create_recruiter_note_templates_table.php`
- Create: `app/Models/RecruiterNoteTemplate.php`
- Create: `app/Http/Controllers/Recruiter/NoteTemplateController.php`
- Modify: `routes/web.php` (recruiter group)
- Create: `tests/Feature/RecruiterNoteTemplateTest.php`

**Step 1: Failing tests**

Cover:

- Recruiter can create a template (name + body) and it persists scoped to their user.
- Recruiter sees only their own templates (another recruiter's template is not listed).
- Recruiter can update and delete their template.
- Guest/other roles cannot create (authorization).
- Route list contains `recruiter.note-templates.{index,store,update,destroy}`.

**Step 2: RED**

**Step 3: Implement**

Migration:

```php
Schema::create('recruiter_note_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
    $table->string('name', 100);
    $table->text('body');
    $table->timestamps();

    $table->index('recruiter_id');
});
```

Model `RecruiterNoteTemplate`: fillable `recruiter_id, name, body`; `recruiter()` belongsTo User.

Controller: resource-ish but no full `Route::resource` needed — add named routes under the existing `role:Recruiter` group:

```php
Route::get('/note-templates', [NoteTemplateController::class, 'index'])->name('note-templates.index');
Route::post('/note-templates', [NoteTemplateController::class, 'store'])->name('note-templates.store');
Route::put('/note-templates/{template}', [NoteTemplateController::class, 'update'])->name('note-templates.update');
Route::delete('/note-templates/{template}', [NoteTemplateController::class, 'destroy'])->name('note-templates.destroy');
```

Controller methods: `index` lists the recruiter's templates (view or JSON depending on usage), `store` validates `name` required max:100 and `body` required string max:2000, `update` same, `destroy` deletes. All scoped `where('recruiter_id', $request->user()->id)`; 404/403 otherwise. Check whether `RoleNavigationTest` asserts exact recruiter route names — if it asserts absence of unknown routes, add assertions only if the test demands it; otherwise routes are additive and safe.

**Step 4: GREEN**

Run RecruiterNoteTemplateTest.

---

### Task 6: Template picker in the review panel

**Objective:** One click fills the notes textarea from a saved template.

**Files:**
- Modify: `resources/views/recruiter/applications/index.blade.php`
- Modify: `app/Http/Controllers/Recruiter/ApplicationController.php` (index: load templates)
- Modify: `resources/lang/en/recruiter.php`
- Modify: `resources/lang/fr/recruiter.php`
- Modify: `tests/Feature/RecruiterNoteTemplateTest.php`

**Step 1: Failing test**

The recruiter applications page contains `data-note-template-picker` and the recruiter's template names when templates exist.

**Step 2: RED**

**Step 3: Implement**

In `Recruiter\ApplicationController::index`, eager-load or fetch:

```php
$noteTemplates = $request->user()->noteTemplates()->orderBy('name')->get();
```

and pass to the view. (Add `noteTemplates()` hasMany on User or query directly — prefer the relationship, one line.)

In the review-panel form, above the notes textarea, add a compact picker when templates exist:

```blade
@if(isset($noteTemplates) && $noteTemplates->isNotEmpty())
    <div data-note-template-picker class="space-y-1">
        <label class="block text-xs font-semibold text-stone-500 dark:text-stone-400">{{ __('recruiter.note_templates') }}</label>
        <div class="flex flex-wrap gap-1">
            @foreach($noteTemplates as $template)
                <button type="button" data-note-template-id="{{ $template->id }}" @click="notes = {{ Js::from($template->body) }}"
                    class="min-h-9 rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-600 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700">
                    {{ $template->name }}
                </button>
            @endforeach
        </div>
    </div>
@endif
```

Add `notes` to the form's `x-data`. Keep the textarea bound with `x-model="notes"` (currently it is a plain textarea with server-side value; change to `x-model="notes"` with initial value from `$application->notes`). Ensure the template button does not submit the form (`type="button"`).

**Step 4: Manage templates UI**

Add a small "Manage templates" link to the recruiter applications page or the recruiter dashboard linking to `recruiter.note-templates.index`, with a simple Blade page (list + create form + inline edit/delete with Alpine or small forms) — keep it minimal: a table/list of templates, one create form, per-row edit via a simple prompt-free inline form and a delete button with native confirm. No modal library.

**Step 5: EN/FR keys**

- `note_templates` (EN "Note templates", FR "Modèles de notes")
- `new_template` (EN "New template", FR "Nouveau modèle")
- `template_name` (EN "Template name", FR "Nom du modèle")
- `template_body` (EN "Template body", FR "Contenu du modèle")
- `save_template` (EN "Save template", FR "Enregistrer le modèle")
- `manage_templates` (EN "Manage templates", FR "Gérer les modèles")
- `delete_template` / `delete_template_confirm`

**Step 6: GREEN**

Run RecruiterNoteTemplateTest + pipeline regression.

---

## Feature C: IT focus and candidate preferences

### Task 7: Preference persistence on candidate profiles

**Objective:** Store chosen IT job interests as a JSON array on the existing profile.

**Files:**
- Create: `database/migrations/2026_08_14_120200_add_preferred_categories_to_candidate_profiles_table.php`
- Modify: `app/Models/CandidateProfile.php`
- Create: `app/Enums/ItCategory.php` (or a config/constant array)
- Modify: `tests/Feature/CandidatePreferencesTest.php` (new)

**Step 1: Failing test**

```php
public function test_candidate_profile_casts_preferred_categories(): void
{
    $profile = CandidateProfile::factory()->create(['preferred_categories' => ['Software Development', 'DevOps']]);

    $this->assertIsArray($profile->preferred_categories);
    $this->assertSame(['Software Development', 'DevOps'], $profile->preferred_categories);
}

public function test_interest_list_contains_only_it_categories(): void
{
    $categories = \App\Enums\ItCategory::values();

    $this->assertNotEmpty($categories);
    $this->assertNotContains('Engineering', $categories); // generic fallback category excluded
}
```

**Step 2: RED**

**Step 3: Implement**

Migration:

```php
Schema::table('candidate_profiles', function (Blueprint $table) {
    $table->json('preferred_categories')->nullable()->after('educations');
});
```

`down()` drops the column.

`CandidateProfile`: add `preferred_categories` to fillable and `'preferred_categories' => 'array'` to casts.

`ItCategory` enum (string-backed) listing the seeded IT categories:

```php
enum ItCategory: string
{
    case SoftwareDevelopment = 'Software Development';
    case CloudComputing = 'Cloud Computing';
    case Cybersecurity = 'Cybersecurity';
    case DataAnalytics = 'Data Analytics';
    case ArtificialIntelligence = 'Artificial Intelligence';
    case DevOps = 'DevOps';
    case WebDevelopment = 'Web Development';
    case FullStackDevelopment = 'Full-Stack Development';
    case Networking = 'Networking';
    case InformationSecurity = 'Information Security';
    case HardwareSystems = 'Hardware Systems';
    case IoT = 'IoT Systems';
    case QuantumComputing = 'Quantum Computing';
    case InteractiveSoftware = 'Interactive Software';
    case ItConsulting = 'IT Consulting';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

Do not include the generic `Engineering` fallback in the curated list.

**Step 4: GREEN**

---

### Task 8: Preference editing in candidate profile settings

**Objective:** Candidates choose their IT interests in profile settings (multi-select checkboxes or chips).

**Files:**
- Modify: `resources/views/profile/edit.blade.php`
- Modify: `app/Http/Controllers/ProfileController.php`
- Modify: `app/Http/Requests/UpdateProfileRequest.php`
- Modify: `resources/lang/en/profile.php`
- Modify: `resources/lang/fr/profile.php`
- Modify: `tests/Feature/CandidatePreferencesTest.php`

**Step 1: Failing test**

- Profile edit page renders `name="preferred_categories[]"` checkboxes for candidate users when the user has a candidate profile.
- Saving via `PUT /profile` with `preferred_categories` persists to the profile.

**Step 2: RED**

**Step 3: Implement**

- `UpdateProfileRequest`: add `'preferred_categories' => ['nullable', 'array']` plus `'preferred_categories.*' => ['string', 'in:' . implode(',', \App\Enums\ItCategory::values())]`. Read the existing request file first to match structure (it currently validates `languages`, `links`, etc.).
- `ProfileController::update` candidate branch: when `array_key_exists('preferred_categories', $data)`, set `$profileData['preferred_categories'] = $data['preferred_categories'] ?? []`.
- Profile edit view: add a "Job interests" card (candidate only) rendering a chip/checkbox grid from `ItCategory::cases()`, checked state from `$user->candidateProfile?->preferred_categories ?? []`. Use the existing input/checkbox styling conventions (44px targets).

**Step 4: EN/FR keys**

- `job_interests` (EN "Job interests", FR "Intérêts professionnels")
- `job_interests_help` (EN "Choose the IT fields you're looking for. We'll surface matching jobs first.", FR "Choisissez les domaines informatiques qui vous intéressent. Nous afficherons les offres correspondantes en premier.")
- `select_interests` (EN "Select your interests", FR "Sélectionnez vos intérêts")

**Step 5: GREEN**

Run CandidatePreferencesTest + profile regressions (check existing `ProductionReadinessTest`/profile tests).

---

### Task 9: First-login preference popup for candidates

**Objective:** Right after account creation, candidates get a modal to pick IT interests.

**Files:**
- Modify: `resources/views/auth/verify-email.blade.php` (post-registration landing) or `resources/views/layouts/guest.blade.php` / a new partial
- Modify: `app/Http/Controllers/Auth/RegisterController.php` (set a session flag)
- Modify: `resources/views/profile/partials/preference-modal.blade.php` (new)
- Modify: `routes/web.php` (a quick-save POST route, e.g. `profile.preferences.quick` inside auth+candidate middleware)
- Modify: `resources/lang/en/profile.php`, `fr/profile.php`
- Modify: `tests/Feature/CandidatePreferencesTest.php`

**Step 1: Failing tests**

- After registration, the verify-email page (or layout) contains `data-preferences-modal` and the modal is visible for candidates who have no `preferred_categories` (session flag `show_preferences_picker` or profile emptiness).
- POSTing the quick route saves preferences and hides the modal next load (session flag cleared / profile populated).
- Recruiters never see the modal.

**Step 2: RED**

**Step 3: Implement**

- In `RegisterController::register`, after login, for candidate accounts set `session()->put('show_preferences_picker', true);` (only when `$user->hasRole('Candidate')`).
- Create `resources/views/profile/partials/preference-modal.blade.php`: an Alpine modal (matches the existing delete-modal teleport pattern) with the same checkbox grid as profile settings, a skip button (clears the flag, no save) and a save button (POST to the quick route with `preferred_categories[]`). Include it in `verify-email.blade.php` (or the guest layout) when `session('show_preferences_picker')` and the user is a candidate.
- Add route: inside the auth group, candidate-role group (or reuse `candidate.` prefix):

```php
Route::post('/preferences', [ProfileController::class, 'saveQuickPreferences'])
    ->name('candidate.preferences.quick');
```

Controller method: `demoAccountGuard->ensureProfileIsMutable($user)`; validate `preferred_categories` array in `ItCategory` values; `updateOrCreate` the profile's `preferred_categories`; `session()->forget('show_preferences_picker')`; redirect back.

**Step 4: EN/FR keys**

- `preferences_modal_title` (EN "What IT jobs are you looking for?", FR "Quels emplois informatiques recherchez-vous ?")
- `preferences_modal_help` (EN "Pick the fields you're interested in. We'll show matching jobs first.", FR "Choisissez les domaines qui vous intéressent. Nous afficherons les offres correspondantes en premier.")
- `save_preferences` (EN "Save preferences", FR "Enregistrer les préférences")
- `skip_for_now` (EN "Skip for now", FR "Passer pour l'instant")

**Step 5: GREEN**

Run CandidatePreferencesTest + Auth/RegistrationTest.

---

### Task 10: Preference-boosted job ordering on home and /jobs

**Objective:** Jobs matching the candidate's chosen categories appear first; everything else remains visible.

**Files:**
- Modify: `app/Http/Controllers/HomeController.php`
- Modify: `app/Http/Controllers/JobController.php` (index)
- Modify: `resources/views/home.blade.php` (optional "Recommended for you" heading)
- Modify: `resources/views/jobs/index.blade.php` (optional heading)
- Modify: `resources/lang/en/jobs.php`, `fr/jobs.php`
- Modify: `tests/Feature/CandidatePreferencesTest.php`

**Step 1: Failing tests**

- A candidate with `preferred_categories => ['Software Development']` sees a matching job before a non-matching job on `/` and `/jobs`.
- A candidate without preferences sees normal `latest('published_at')` ordering.
- A recruiter sees normal ordering.

**Step 2: RED**

**Step 3: Implement**

Add a scope or query helper on `Job`:

```php
public function scopeOrderByPreference(Builder $query, ?array $preferredCategories): Builder
{
    if (!$preferredCategories) {
        return $query->latest('published_at');
    }

    return $query
        ->orderByRaw('CASE WHEN category IN (?) THEN 0 ELSE 1 END', [$preferredCategories])
        ->latest('published_at');
}
```

(Check SQLite compatibility of `orderByRaw` with a bound array — Laravel binds arrays as a comma-joined string via `in (?)` when using a single placeholder; if that fails on the test DB, use `implode(',', array_fill(0, count(...), '?'))`.)

In `HomeController::index` and `JobController::index`, when the authenticated user is a Candidate with a profile:

```php
$preferred = $user?->candidateProfile?->preferred_categories;
$jobs = Job::published()->with('company')->withSavedStateFor($user)
    ->orderByPreference($preferred)
    ->paginate(12)->withQueryString();
```

Pass `$hasPreferences = filled($preferred)` to the views; when true, show the "Recommended for you" heading above the list. Keep the existing search/filter flow untouched (filters still apply on top; recommendation ordering applies to the default listing). For `/jobs` with explicit search/location/category filters, do not reorder (user intent wins) — apply preference ordering only when no search/location/category query params are present.

**Step 4: EN/FR keys**

- `recommended_for_you` (EN "Recommended for you", FR "Recommandé pour vous")

**Step 5: GREEN**

Run CandidatePreferencesTest + JobDiscoveryExperienceTest + SmartSearchTest.

---

## Feature D: UI polish

### Task 11: Collapse cover letters by default (candidate + recruiter)

**Objective:** Long cover letters are hidden behind an expander on both sides.

**Files:**
- Modify: `resources/views/candidate/applications.blade.php`
- Modify: `resources/views/recruiter/applications/index.blade.php`
- Modify: `resources/views/recruiter/applicants/show.blade.php` (same treatment)
- Modify: `resources/lang/en/applications.php`, `fr/applications.php`, `recruiter.php`
- Modify: `tests/Feature/ApplicationUiPolishTest.php` (new)

**Step 1: Failing tests**

- Candidate application page contains a `<details data-cover-letter-collapsible>` with the cover letter inside and no `open` attribute (collapsed by default).
- Recruiter application page same.
- The summary text is translated.

**Step 2: RED**

**Step 3: Implement**

Replace the cover-letter block with native details/summary:

```blade
@if($application->cover_letter)
    <details data-cover-letter-collapsible class="mb-3 group">
        <summary class="flex min-h-11 cursor-pointer items-center justify-between rounded-lg bg-stone-50 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700">
            {{ __('applications.your_cover_letter') }}
            <svg class="h-4 w-4 transition group-open:rotate-180" ...chevron...></svg>
        </summary>
        <div class="mt-2 rounded-lg bg-stone-50 p-4 text-sm text-stone-700 dark:bg-stone-800 dark:text-stone-300">
            {{ $application->cover_letter }}
        </div>
    </details>
@endif
```

Use the same structure on the recruiter applications index and the applicants show page (with `recruiter.cover_letter` label). This is native HTML — keyboard accessible, no JS.

**Step 4: EN/FR keys** (reuse existing cover-letter keys; add nothing unless a "Show/Hide" hint is wanted — skip extra keys, YAGNI).

**Step 5: GREEN**

Run ApplicationUiPolishTest + CandidateApplicationExperienceTest + RecruiterApplicantDirectoryTest.

---

### Task 12: Autosize the cover-letter and notes textareas

**Objective:** Both small textareas grow with content instead of showing a tiny box with scrollbar.

**Files:**
- Modify: `resources/views/jobs/show.blade.php` (apply cover letter)
- Modify: `resources/views/recruiter/applications/index.blade.php` (notes textarea)
- Create: `resources/js/textarea-autosize.js` (tiny Alpine plugin or plain handler) — or extend `resources/js/app.js`
- Modify: `resources/js/app.js`
- Modify: `tests/Feature/ApplicationUiPolishTest.php` (assert `data-autosize` attribute present)

**Step 1: Failing test**

The apply form's cover-letter textarea and the recruiter notes textarea carry `data-autosize` and a starting `rows` of at least 4 (cover letter) / 4 (notes).

**Step 2: RED**

**Step 3: Implement**

Add a small Alpine directive in `resources/js/app.js` (the app already loads Alpine):

```js
document.addEventListener('alpine:init', () => {
    Alpine.directive('autosize', (el) => {
        const resize = () => {
            el.style.height = 'auto';
            el.style.height = el.scrollHeight + 'px';
        };
        el.addEventListener('input', resize);
        resize();
    });
});
```

Apply `x-autosize` to the cover-letter textarea (`rows="4"`) and the notes textarea (`rows="4"`, keep `min-h-24`). The cover-letter form already has `x-data="{ submitting: false, resumeSource: ... }"` — the directive works on any element regardless. For the recruiter notes form, ensure the textarea is inside the form's `x-data` scope (it already is).

If adding the directive to `app.js` risks build issues, a simpler CSS-only fallback is `field-sizing: content` — but browser support is uneven; prefer the Alpine directive. (Ponytail note: the directive is ~8 lines and reuses Alpine already present.)

**Step 4: GREEN + build**

Run ApplicationUiPolishTest; `npm run build` must pass.

---

### Task 13: Clickable company locations on /companies

**Objective:** Company location text links to the location-filtered search page.

**Files:**
- Modify: `resources/views/components/company-card.blade.php`
- Modify: `tests/Feature/ApplicationUiPolishTest.php` (or a new assertion in an existing companies test)

**Step 1: Failing test**

`/companies` page contains a location link (`data-company-location-link`) whose href points to `/search?location=<encoded>&filter=jobs` when the company has a location.

**Step 2: RED**

**Step 3: Implement**

In `company-card.blade.php`, replace the location `<span>` with a link (inside the card's existing stretched-link context — the card itself is an `<a>`, so use `relative z-10` on the inner link to keep it clickable):

```blade
@if($company->location)
    <a href="{{ localized_route('search', ['location' => $company->location, 'filter' => 'jobs']) }}"
       data-company-location-link
       class="relative z-10 flex items-center gap-1 transition hover:text-amber-600 dark:hover:text-amber-400">
        <svg ...location pin...></svg>
        {{ $company->location }}
    </a>
@endif
```

Match the existing job-location-link pattern (`/en/search?location=...&filter=jobs`) used in job cards — verify the exact route params by reading `resources/views/components/job-card.blade.php` first and mirror them.

**Step 4: GREEN**

Run ApplicationUiPolishTest + existing companies tests (find the file covering `/companies`; likely in `JobDiscoveryExperienceTest` or a companies test — locate and run it).

---

## Task 14: Full regression, migration, and live verification

**Objective:** Prove all six features work together on both database engines and real rendered pages.

**Files:**
- Modify only files required to fix failures caused by this plan.
- No drive-by formatting.

**Step 1: Run focused suites**

```bash
/tmp/recruivo-test.sh tests/Feature/InterviewModeTest.php
/tmp/recruivo-test.sh tests/Feature/RecruiterNoteTemplateTest.php
/tmp/recruivo-test.sh tests/Feature/CandidatePreferencesTest.php
/tmp/recruivo-test.sh tests/Feature/ApplicationUiPolishTest.php
```

**Step 2: Run the full suite**

```bash
/tmp/recruivo-test.sh
```

Expected: all pass; record actual counts.

**Step 3: Frontend/static gates**

```bash
npm run build
npm audit --audit-level=high
git diff --check
```

**Step 4: Rebuild + migrate the live app**

```bash
docker compose --env-file .env.docker up -d --build laravel
docker exec recruivo-laravel-1 php artisan migrate --force
```

Poll `/api/health`; confirm host/container `public/build/manifest.json` match.

**Step 5: Live verification (temp accounts, cleanup after)**

Using temp recruiter/candidate accounts and CDP (port 9223) or curl where sufficient:

1. Recruiter moves an application to Interview: mode selector appears; choosing Online requires URL (server error shown if blank); choosing On-site requires location; notes can be empty on accept.
2. Recruiter creates a note template, sees it in the review panel, clicks it, and the notes textarea fills.
3. Candidate registers (or temp candidate logs in): preference modal appears first; selecting interests persists; `/` and `/jobs` show the matching job first with the "Recommended for you" heading.
4. Candidate profile settings show the same interest grid and save.
5. Cover letter is collapsed by default on candidate and recruiter application pages; expands on click.
6. Apply-page cover-letter textarea and recruiter notes textarea grow as text is typed (390px mobile too).
7. `/companies` location links navigate to the location-filtered search.
8. EN and FR labels render on all new surfaces; 390px mobile has no horizontal overflow.
9. Clean up temp accounts/jobs/templates; confirm no residue.

**Step 6: Final review**

- No interview path accepts invalid mode values or missing required fields.
- Notes remain optional on every transition; final-decision immutability still enforced.
- Templates are recruiter-scoped; demo candidates still immutable.
- Recommendation ordering only affects default listings (no interference with filters/search).
- No new package, no scheduler, no generic workflow engine.
- Temp data removed; no commits made.

---

## Files likely to change

**Create:**

- `database/migrations/2026_08_14_120000_add_interview_mode_to_applications_table.php`
- `database/migrations/2026_08_14_120100_create_recruiter_note_templates_table.php`
- `database/migrations/2026_08_14_120200_add_preferred_categories_to_candidate_profiles_table.php`
- `app/Models/RecruiterNoteTemplate.php`
- `app/Enums/ItCategory.php`
- `app/Http/Controllers/Recruiter/NoteTemplateController.php`
- `resources/views/profile/partials/preference-modal.blade.php`
- `resources/views/recruiter/note-templates/index.blade.php`
- `tests/Feature/InterviewModeTest.php`
- `tests/Feature/RecruiterNoteTemplateTest.php`
- `tests/Feature/CandidatePreferencesTest.php`
- `tests/Feature/ApplicationUiPolishTest.php`

**Modify:**

- `app/Models/Application.php`
- `app/Models/CandidateProfile.php`
- `app/Models/User.php` (noteTemplates() relationship)
- `app/Http/Requests/UpdateApplicationStatusRequest.php`
- `app/Http/Requests/UpdateProfileRequest.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Controllers/Auth/RegisterController.php`
- `app/Http/Controllers/HomeController.php`
- `app/Http/Controllers/JobController.php`
- `app/Http/Controllers/Recruiter/ApplicationController.php`
- `app/Http/Controllers/Recruiter/ApplicantController.php` (only if applicants/show needs templates context — check)
- `app/Http/Resources/ApplicationResource.php`
- `routes/web.php`
- `database/factories/ApplicationFactory.php`
- `resources/views/recruiter/applications/index.blade.php`
- `resources/views/candidate/applications.blade.php`
- `resources/views/recruiter/applicants/show.blade.php`
- `resources/views/jobs/show.blade.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/auth/verify-email.blade.php`
- `resources/views/components/company-card.blade.php`
- `resources/views/home.blade.php`
- `resources/views/jobs/index.blade.php`
- `resources/js/app.js`
- EN/FR: `validation.php`, `recruiter.php`, `applications.php`, `profile.php`, `jobs.php`

## Risks and tradeoffs

- **Interview mode default:** defaulting to `onsite` keeps existing rows valid but may surprise if most interviews are online. The radio defaults to the stored value; recruiters change it consciously.
- **Notes optional:** this changes the existing accepted/rejected notes-required contract. Existing API tests may assert the old behavior; the plan explicitly updates them to the new contract (user request overrides).
- **Recommendation ordering:** SQL `CASE WHEN category IN (...)` on a string list is simple and testable on SQLite/MySQL. It is not a ranking engine — matching jobs float to the top, nothing is hidden. If the user later wants scoring, that is a separate plan.
- **Template picker:** template bodies are inserted verbatim into the textarea (user-editable before save) — no auto-submit, so misclicks are harmless.
- **Preference popup:** gating on a session flag set at registration avoids forcing existing candidates; skippable. The modal uses the existing teleport/Alpine modal pattern.
- **Concurrent sessions:** the repo may be touched by another orchestrator session; implementers should re-read files before editing and never run git operations.
- **Company-card inner link:** the card is a stretched `<a>`; the location link needs `relative z-10` so it does not open the card. Verify visually on desktop and mobile.

## Open questions resolved by default

- Interview mode is stored per application, default onsite.
- Notes are optional on every transition; final decisions remain immutable.
- Templates are personal to each recruiter.
- "IT focus" = curated category list + preference-boosted ordering; generic categories (Engineering) remain in existing data but are not offered as interests.
- Popup shows only to newly registered candidates who have not yet chosen preferences (session flag), and can be skipped.
- Cover letters collapse via native `<details>`; textareas autosize via a tiny Alpine directive.
- Company location links reuse the existing search location-filter route.
