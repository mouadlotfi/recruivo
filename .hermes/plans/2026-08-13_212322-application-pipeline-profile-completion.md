# Application Pipeline and Profile Completion Implementation Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Add Shortlisted and Interview application stages, and show candidates a useful profile-completion score with direct links to missing profile fields.

**Architecture:** Keep the current `applications.status` string column and extend the existing `ApplicationStatus` enum, request validation, filters, and Blade views. Accepted and Rejected remain final decisions; Pending, Shortlisted, and Interview remain reversible workflow stages. Compute profile completion from existing user/profile fields at request time, with no new table, package, background job, or cached score.

**Tech Stack:** Laravel 12, PHP 8.2 enums and Eloquent, Blade, Tailwind CSS, PHPUnit feature tests, existing mail/database notifications.

---

## Scope and acceptance criteria

### Application pipeline

- Statuses are: Pending, Shortlisted, Interview, Accepted, Rejected.
- A recruiter may move an application among Pending, Shortlisted, and Interview.
- Accepted and Rejected are final and cannot be changed afterward, preserving the current decision lock.
- Notes remain optional for workflow-stage changes and required for Accepted or Rejected.
- Every real status change notifies the candidate through the existing notification.
- Recruiter filters, recruiter review controls, candidate filters, status badges, dashboard summaries, API responses, factories, and English/French labels understand all five statuses.
- No kanban board, interview calendar, scheduling model, or messaging system is added.

### Profile completion

- Completion uses five existing signals, worth 20% each:
  1. professional headline
  2. profile summary
  3. skills
  4. uploaded profile resume
  5. at least one structured experience
- Candidate dashboard shows the percentage, a progress bar, and only the missing checklist items.
- Candidate profile edit page shows the same completion summary near the top.
- Each missing item links to an existing field/section anchor on the profile edit page.
- A complete profile shows a concise completion message rather than an empty checklist.
- The score is calculated in PHP from existing data. No database migration is needed.

## Current context and assumptions

- `app/Enums/ApplicationStatus.php` currently has Pending, Accepted, and Rejected.
- `applications.status` is a string, so adding enum cases does not require a schema change.
- `app/Http/Requests/UpdateApplicationStatusRequest.php` currently hard-codes allowed values and locks Accepted/Rejected decisions.
- `app/Models/Application.php::applyStatusUpdate()` currently marks `status_changed` only for final decisions. Preserve that behavior because those columns represent the one-time final decision, not ordinary workflow movement.
- Candidate and recruiter application pages currently hard-code three status tabs and badge colors.
- Profile data already exists across `users.profile_summary` and `candidate_profiles` fields. Structured experience is stored in `candidate_profiles.experiences`.
- The working tree is already heavily modified. Do not commit, reset, clean, or reformat unrelated files unless the user explicitly asks. The task-level commit steps below are handoff checkpoints only; skip them during implementation unless commit permission is given.
- Follow TDD: add one focused failing contract, run it RED, make the minimum central change, run it GREEN, then continue.

---

### Task 1: Define the two workflow statuses centrally

**Objective:** Make Shortlisted and Interview valid enum-backed application statuses without changing storage.

**Files:**
- Modify: `app/Enums/ApplicationStatus.php`
- Test: `tests/Feature/Api/Recruiter/ApplicationStatusManagementTest.php`

**Step 1: Write the failing enum contract**

Add a test that asserts the exact status values exposed by `ApplicationStatus::cases()`:

```php
public function test_application_statuses_include_the_recruiting_pipeline(): void
{
    $this->assertSame(
        ['pending', 'shortlisted', 'interview', 'accepted', 'rejected'],
        array_map(fn (ApplicationStatus $status) => $status->value, ApplicationStatus::cases()),
    );
}
```

**Step 2: Run the focused test and verify RED**

Run the repository's Docker PHPUnit wrapper for:

```text
tests/Feature/Api/Recruiter/ApplicationStatusManagementTest.php
```

Expected: FAIL because `shortlisted` and `interview` are absent.

**Step 3: Add the minimal enum cases**

Update the enum order to:

```php
enum ApplicationStatus: string
{
    case Pending = 'pending';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
```

**Step 4: Run the focused test and verify GREEN**

Expected: the new enum contract passes.

**Step 5: Checkpoint**

If commits are explicitly authorized:

```bash
git add app/Enums/ApplicationStatus.php tests/Feature/Api/Recruiter/ApplicationStatusManagementTest.php
git commit -m "feat: add recruiting pipeline statuses"
```

---

### Task 2: Permit reversible workflow transitions and preserve final decisions

**Objective:** Validate all enum statuses centrally while keeping Accepted and Rejected final.

**Files:**
- Modify: `app/Http/Requests/UpdateApplicationStatusRequest.php`
- Test: `tests/Feature/Api/Recruiter/ApplicationStatusManagementTest.php`

**Step 1: Write failing transition tests**

Add narrow API tests for:

```php
public function test_recruiter_can_move_an_application_through_workflow_stages_without_notes(): void
{
    Notification::fake();
    [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();
    Sanctum::actingAs($recruiter);

    $this->patchJson("/api/recruiter/applications/{$application->id}", [
        'status' => ApplicationStatus::Shortlisted->value,
    ])->assertOk()->assertJsonPath('data.status', 'shortlisted');

    $this->patchJson("/api/recruiter/applications/{$application->id}", [
        'status' => ApplicationStatus::Interview->value,
    ])->assertOk()->assertJsonPath('data.status', 'interview');

    $this->patchJson("/api/recruiter/applications/{$application->id}", [
        'status' => ApplicationStatus::Pending->value,
    ])->assertOk()->assertJsonPath('data.status', 'pending');

    Notification::assertSentToTimes($candidate, ApplicationStatusUpdatedNotification::class, 3);
}
```

Also add a test proving Interview to Accepted still requires notes, and keep the existing Accepted-to-Rejected rejection test.

**Step 2: Run the focused test and verify RED**

Expected: 422 validation failure for `shortlisted` or `interview`.

**Step 3: Replace hard-coded allowed status values**

In `UpdateApplicationStatusRequest`, import `App\Enums\ApplicationStatus` and build the validation rule from the enum:

```php
use Illuminate\Validation\Rule;

'status' => ['required', Rule::enum(ApplicationStatus::class)],
```

Keep the existing final-status logic but express the final values once:

```php
$finalStatuses = [
    ApplicationStatus::Accepted->value,
    ApplicationStatus::Rejected->value,
];
```

Use `$finalStatuses` for:

- requiring notes when a change moves to Accepted or Rejected
- preventing changes when the current status is Accepted or Rejected
- preventing notes from being added to non-final workflow changes

Do not lock Shortlisted or Interview. Do not require notes for those stages.

**Step 4: Run the focused test and verify GREEN**

Expected: workflow transitions pass, final-decision tests remain green, and candidate notifications are sent only when status changes.

**Step 5: Checkpoint**

If commits are authorized:

```bash
git add app/Http/Requests/UpdateApplicationStatusRequest.php tests/Feature/Api/Recruiter/ApplicationStatusManagementTest.php
git commit -m "feat: support reversible application workflow stages"
```

---

### Task 3: Keep factories and final-decision bookkeeping consistent

**Objective:** Ensure generated applications and status metadata remain valid with the expanded enum.

**Files:**
- Modify only if needed: `app/Models/Application.php`
- Modify: `database/factories/ApplicationFactory.php`
- Test: `tests/Feature/Api/Recruiter/ApplicationStatusManagementTest.php`

**Step 1: Add the bookkeeping assertion**

Extend the workflow transition test to assert that moving to Shortlisted or Interview does not set final-decision metadata:

```php
$this->assertDatabaseHas('applications', [
    'id' => $application->id,
    'status_changed' => false,
    'status_changed_at' => null,
]);
```

Then assert that moving from Interview to Accepted with notes sets `status_changed` and `status_changed_at`.

**Step 2: Run the test**

Expected: this may already pass. If it passes, do not change `Application::applyStatusUpdate()`.

**Step 3: Make the factory default deterministic**

Change `database/factories/ApplicationFactory.php` to default new applications to Pending:

```php
'status' => ApplicationStatus::Pending->value,
```

Tests that need another state already set it explicitly. This avoids random final states and keeps generated data aligned with the real creation path.

**Step 4: Run the focused test and factory-dependent feature tests**

Expected: all pass; no production model change is needed unless the bookkeeping assertion exposed a real defect.

**Step 5: Checkpoint**

If commits are authorized:

```bash
git add database/factories/ApplicationFactory.php tests/Feature/Api/Recruiter/ApplicationStatusManagementTest.php
git commit -m "test: keep application factory at initial status"
```

---

### Task 4: Expose all stages in recruiter application management

**Objective:** Let recruiters filter and assign Pending, Shortlisted, Interview, Accepted, and Rejected in the existing list UI.

**Files:**
- Modify: `resources/views/recruiter/applications/index.blade.php`
- Modify: `resources/lang/en/recruiter.php`
- Modify: `resources/lang/fr/recruiter.php`
- Test: `tests/Feature/RecruiterApplicantDirectoryTest.php` or a new focused test in `tests/Feature/RecruiterApplicationPipelineTest.php`

**Step 1: Write the failing recruiter UI test**

Create `tests/Feature/RecruiterApplicationPipelineTest.php` if no existing test cleanly owns the job-specific applications page. The test should create a verified recruiter, owned job, and applications in all five statuses, then assert:

- `?status=shortlisted` returns 200
- the Shortlisted tab has `aria-current="page"`
- the shortlisted candidate appears and candidates in other statuses do not
- the page contains options for `pending`, `shortlisted`, `interview`, `accepted`, and `rejected`

Use the existing role and factory setup patterns from `RecruiterApplicantDirectoryTest`.

**Step 2: Run the test and verify RED**

Expected: missing label/tab/select option.

**Step 3: Add translations**

Add to both recruiter locale files:

```php
'shortlisted' => 'Shortlisted',
'interview' => 'Interview',
```

French:

```php
'shortlisted' => 'Présélectionnée',
'interview' => 'Entretien',
```

**Step 4: Build tabs from enum cases**

In the Blade view, keep the All tab and derive the remaining tabs from `ApplicationStatus::cases()` rather than maintaining another hard-coded list. Counts still come from `$statusCounts`.

**Step 5: Expand review controls**

For non-final applications, include all five `<option>` values and select the current status:

```blade
@foreach(\App\Enums\ApplicationStatus::cases() as $optionStatus)
    <option value="{{ $optionStatus->value }}" @selected($application->status === $optionStatus)>
        {{ __('recruiter.'.$optionStatus->value) }}
    </option>
@endforeach
```

Keep the final-decision read-only panel for Accepted and Rejected.

**Step 6: Use an explicit badge color map**

Add a local Blade map for the five badge styles. Avoid nested ternaries. Suggested colors:

- Pending: yellow
- Shortlisted: blue
- Interview: violet
- Accepted: green
- Rejected: red

**Step 7: Run the recruiter UI test and verify GREEN**

Expected: filters, counts, current status selection, and all labels render correctly.

**Step 8: Checkpoint**

If commits are authorized:

```bash
git add resources/views/recruiter/applications/index.blade.php resources/lang/en/recruiter.php resources/lang/fr/recruiter.php tests/Feature/RecruiterApplicationPipelineTest.php
git commit -m "feat: expose application pipeline to recruiters"
```

---

### Task 5: Show the new stages to candidates

**Objective:** Make candidate filters, application cards, and dashboard summaries understand Shortlisted and Interview.

**Files:**
- Modify: `app/Http/Controllers/Candidate/DashboardController.php`
- Modify: `resources/views/candidate/applications.blade.php`
- Modify: `resources/views/candidate/dashboard.blade.php`
- Modify: `resources/lang/en/applications.php`
- Modify: `resources/lang/fr/applications.php`
- Modify: `resources/lang/en/candidate.php`
- Modify: `resources/lang/fr/candidate.php`
- Test: `tests/Feature/CandidateApplicationExperienceTest.php`

**Step 1: Expand the failing candidate status test**

Update `test_candidate_application_tabs_filter_by_status_and_show_counts()` to create Shortlisted and Interview applications and assert:

- All count is 5
- Shortlisted and Interview tabs show count 1
- filtering `?status=interview` shows only the interview job
- all badge labels render without translation-key fallback text

**Step 2: Run the test and verify RED**

Expected: filter redirects or labels/tabs are missing.

**Step 3: Add English and French status labels**

Add Shortlisted and Interview to `applications.php` and `candidate.php` in both locales.

**Step 4: Derive candidate tabs from the enum**

Keep All first, then generate status tabs from `ApplicationStatus::cases()`. Continue using the controller-provided `$statusCounts`.

**Step 5: Replace three-way card status branches**

Use a local badge-class map and `__('applications.'.$application->status->value)` so every enum case renders consistently.

**Step 6: Make dashboard progress truthful without adding more stat cards**

In `Candidate\DashboardController`, replace the Pending-only count with an in-progress count covering Pending, Shortlisted, and Interview:

```php
$inProgressApplications = $user->applications()
    ->whereIn('status', [
        ApplicationStatus::Pending->value,
        ApplicationStatus::Shortlisted->value,
        ApplicationStatus::Interview->value,
    ])
    ->count();
```

Pass `$inProgressApplications` and label the existing second stat card “In progress.” Keep Total, Accepted, and Rejected, preserving the four-card layout.

Update recent-application badges to render all enum states through a map rather than an `if/elseif` chain.

**Step 7: Run candidate tests and verify GREEN**

Expected: all five status filters and badges work; the dashboard shows a correct in-progress total.

**Step 8: Checkpoint**

If commits are authorized:

```bash
git add app/Http/Controllers/Candidate/DashboardController.php resources/views/candidate/applications.blade.php resources/views/candidate/dashboard.blade.php resources/lang/en/applications.php resources/lang/fr/applications.php resources/lang/en/candidate.php resources/lang/fr/candidate.php tests/Feature/CandidateApplicationExperienceTest.php
git commit -m "feat: show application pipeline to candidates"
```

---

### Task 6: Update recruiter dashboard status badges

**Objective:** Prevent Shortlisted and Interview applications from appearing with the rejected badge style on the recruiter dashboard.

**Files:**
- Modify: `resources/views/recruiter/dashboard.blade.php`
- Test: `tests/Feature/RecruiterApplicationPipelineTest.php`

**Step 1: Write the failing dashboard assertion**

Create a recent Interview application, request `/en/recruiter/dashboard`, and assert the translated Interview label plus the violet badge class appears.

**Step 2: Run the test and verify RED**

Expected: the current fallback branch uses rejected styling.

**Step 3: Add the same explicit five-status color map**

Use the same status-to-class values as the recruiter applications page. Do not introduce a component solely to share five strings across two views.

**Step 4: Run the test and verify GREEN**

Expected: Interview and Shortlisted have their own labels and colors.

**Step 5: Checkpoint**

If commits are authorized:

```bash
git add resources/views/recruiter/dashboard.blade.php tests/Feature/RecruiterApplicationPipelineTest.php
git commit -m "fix: render workflow stages on recruiter dashboard"
```

---

### Task 7: Add one model-level profile-completion calculation

**Objective:** Compute a candidate's completion score and missing items from existing fields in one reusable place.

**Files:**
- Modify: `app/Models/User.php`
- Test: `tests/Feature/CandidateProfileCompletionTest.php`

**Step 1: Write the failing model behavior test**

Create `tests/Feature/CandidateProfileCompletionTest.php` with two tests:

1. Empty candidate profile returns 0 and all five missing keys.
2. A user with summary plus a profile containing headline, skills, resume, and one structured experience returns 100 and no missing keys.

Expected result shape:

```php
[
    'percentage' => 40,
    'completed' => 2,
    'total' => 5,
    'missing' => ['skills', 'resume', 'experience'],
]
```

**Step 2: Run the focused test and verify RED**

Expected: `User::profileCompletion()` is undefined.

**Step 3: Add the minimal method to `User`**

Add a typed method that loads `candidateProfile` only if needed and evaluates the five booleans:

```php
public function profileCompletion(): array
{
    $profile = $this->relationLoaded('candidateProfile')
        ? $this->candidateProfile
        : $this->candidateProfile()->first();

    $items = [
        'headline' => filled($profile?->headline),
        'summary' => filled($this->profile_summary),
        'skills' => filled($profile?->skills),
        'resume' => filled($profile?->resume_path),
        'experience' => ! empty($profile?->experiences),
    ];

    $completed = count(array_filter($items));

    return [
        'percentage' => $completed * 20,
        'completed' => $completed,
        'total' => count($items),
        'missing' => array_keys(array_filter($items, fn (bool $complete) => ! $complete)),
    ];
}
```

Do not add a database column, observer, service, cache, or accessor.

**Step 4: Run the focused test and verify GREEN**

Expected: empty, partial, and complete profiles calculate deterministically.

**Step 5: Checkpoint**

If commits are authorized:

```bash
git add app/Models/User.php tests/Feature/CandidateProfileCompletionTest.php
git commit -m "feat: calculate candidate profile completion"
```

---

### Task 8: Show completion on the candidate dashboard

**Objective:** Give candidates one visible next action without building a separate onboarding flow.

**Files:**
- Modify: `app/Http/Controllers/Candidate/DashboardController.php`
- Modify: `resources/views/candidate/dashboard.blade.php`
- Modify: `resources/lang/en/candidate.php`
- Modify: `resources/lang/fr/candidate.php`
- Test: `tests/Feature/CandidateProfileCompletionTest.php`

**Step 1: Write the failing dashboard test**

Create a verified Candidate with only a headline completed, request `/en/candidate/dashboard`, and assert:

- `data-profile-completion` exists
- `20%` appears
- missing labels for summary, skills, resume, and experience appear
- headline is not listed as missing
- the edit-profile URL contains a useful anchor

**Step 2: Run the test and verify RED**

Expected: completion UI is absent.

**Step 3: Pass the model result from the controller**

Load `candidateProfile` once and call:

```php
$user->loadMissing('candidateProfile');
$profileCompletion = $user->profileCompletion();
```

Pass `$profileCompletion` to the existing dashboard view.

**Step 4: Add translations**

Add concise English/French keys for:

- profile completion
- complete-profile helper text
- profile complete
- headline, summary, skills, resume, experience missing labels
- complete profile action
- in-progress application stat

**Step 5: Add one dashboard card**

Place a full-width completion card before Recent Applications. Include:

- percentage text
- native `role="progressbar"` semantics with `aria-valuenow`, `aria-valuemin="0"`, `aria-valuemax="100"`
- a CSS width based on the server-calculated integer
- missing checklist links to `/profile#headline`, `/profile#profile_summary`, `/profile#skills`, `/profile#resume`, and `/profile#experience`
- a single “Complete profile” action

If complete, show the completion message and no missing list.

**Step 6: Run the focused test and verify GREEN**

Expected: accessible progress markup and only missing items are shown.

**Step 7: Checkpoint**

If commits are authorized:

```bash
git add app/Http/Controllers/Candidate/DashboardController.php resources/views/candidate/dashboard.blade.php resources/lang/en/candidate.php resources/lang/fr/candidate.php tests/Feature/CandidateProfileCompletionTest.php
git commit -m "feat: guide candidates through profile completion"
```

---

### Task 9: Reuse the completion summary on profile edit

**Objective:** Keep the completion feedback visible where candidates can fix it.

**Files:**
- Modify: `app/Http/Controllers/ProfileController.php`
- Modify: `resources/views/profile/edit.blade.php`
- Modify: `resources/views/profile/partials/structured-profile-builder.blade.php`
- Modify: `resources/lang/en/profile.php`
- Modify: `resources/lang/fr/profile.php`
- Test: `tests/Feature/CandidateProfileCompletionTest.php`

**Step 1: Write the failing profile-edit test**

Request `/en/profile` as a verified Candidate and assert:

- completion percentage appears
- progressbar semantics appear
- each targeted existing control/section has the expected anchor id

Also request the same route as a Recruiter and assert candidate completion markup is absent.

**Step 2: Run the test and verify RED**

Expected: summary and/or section anchors are missing.

**Step 3: Pass completion only for candidates**

In `ProfileController::edit()`, after the existing relation load:

```php
$profileCompletion = $user->hasRole('Candidate')
    ? $user->profileCompletion()
    : null;
```

Pass it to the view.

**Step 4: Add the compact summary**

Inside the existing Candidate branch in `profile/edit.blade.php`, render the same percentage and progress semantics. Keep this version compact; the dashboard remains the checklist surface.

**Step 5: Add stable anchors to existing elements**

Reuse existing input IDs where available. Add only missing section IDs:

- `headline`
- `profile_summary`
- `skills`
- `resume`
- `experience` on the structured experience section

Do not add JavaScript scrolling logic. Native URL fragments are sufficient.

**Step 6: Run the focused test and verify GREEN**

Expected: Candidate sees completion; Recruiter does not; all fragment targets exist.

**Step 7: Checkpoint**

If commits are authorized:

```bash
git add app/Http/Controllers/ProfileController.php resources/views/profile/edit.blade.php resources/views/profile/partials/structured-profile-builder.blade.php resources/lang/en/profile.php resources/lang/fr/profile.php tests/Feature/CandidateProfileCompletionTest.php
git commit -m "feat: show completion on candidate profile"
```

---

### Task 10: Verify notifications for all five statuses

**Objective:** Ensure existing email/database notification rendering does not break for the new enum cases.

**Files:**
- Modify only if a test exposes a defect: `app/Notifications/ApplicationStatusUpdatedNotification.php`
- Test: `tests/Feature/NotificationCenterTest.php`

**Step 1: Add a focused notification test**

For Shortlisted and Interview applications, instantiate or send `ApplicationStatusUpdatedNotification` and assert:

- database payload contains the exact lower-case status
- mail renders the human-readable status without exception
- action URL remains localized

**Step 2: Run the test**

Expected: likely GREEN because the notification uses `ucfirst($status->value)`. If green, make no production change.

**Step 3: Fix only if needed**

If localization is required by the existing notification contract, replace `ucfirst()` with a translation lookup. Do not build separate notification classes per stage.

**Step 4: Run the test and verify GREEN**

Expected: all five statuses serialize and render.

---

### Task 11: Full validation and live UI check

**Objective:** Prove both features work across tests, production assets, Docker runtime, and responsive UI.

**Files:**
- No source changes unless validation exposes a confirmed defect.

**Step 1: Run focused suites**

Run the Docker PHPUnit wrapper for:

```text
tests/Feature/Api/Recruiter/ApplicationStatusManagementTest.php
tests/Feature/RecruiterApplicationPipelineTest.php
tests/Feature/CandidateApplicationExperienceTest.php
tests/Feature/CandidateProfileCompletionTest.php
tests/Feature/NotificationCenterTest.php
```

Expected: all pass.

**Step 2: Run the full feature suite**

Run all `tests/Feature` tests through the established `recruivo:test` image with fresh SQLite migrations.

Expected: all pass with no enum-cast, translation, authorization, or route regressions.

**Step 3: Run static/build gates**

```bash
npm run build
npm audit --audit-level=high
git diff --check
```

Expected: build passes, no high-severity audit findings, no whitespace errors.

**Step 4: Rebuild the live Laravel container**

```bash
docker compose --env-file .env.docker up -d --build laravel
```

Wait for `/api/health` to return success. Compare the host and container Vite manifests so the runtime is not serving stale assets.

**Step 5: Browser-verify recruiter workflow**

At desktop and mobile widths:

1. Sign in as a mutable recruiter account.
2. Open a job's applications.
3. Move Pending to Shortlisted, Shortlisted to Interview, and Interview back to Pending without notes.
4. Move Interview to Accepted with a note.
5. Confirm Accepted is read-only afterward.
6. Confirm all five filters, counts, badge labels, focus states, and mobile overflow behavior.

**Step 6: Browser-verify candidate workflow**

1. Sign in as the affected candidate.
2. Confirm stage notifications and status badges display Shortlisted/Interview correctly.
3. Confirm candidate status filters work.
4. Confirm dashboard in-progress count includes Pending, Shortlisted, and Interview.
5. Confirm profile completion percentage and missing checklist are correct.
6. Follow every checklist anchor and verify it lands at the right profile control.
7. Complete a missing field, save, reload, and confirm the percentage rises.
8. Verify English and French pages.

**Step 7: Review the final diff**

Confirm there is:

- no application-status schema migration
- no completion-score column
- no new package
- no kanban/calendar/chat implementation
- no unrelated formatting or file changes
- no accidental commit of existing unrelated work

---

## Files likely to change

### Application pipeline

- `app/Enums/ApplicationStatus.php`
- `app/Http/Requests/UpdateApplicationStatusRequest.php`
- `database/factories/ApplicationFactory.php`
- `app/Http/Controllers/Candidate/DashboardController.php`
- `resources/views/recruiter/applications/index.blade.php`
- `resources/views/recruiter/dashboard.blade.php`
- `resources/views/candidate/applications.blade.php`
- `resources/views/candidate/dashboard.blade.php`
- `resources/lang/en/recruiter.php`
- `resources/lang/fr/recruiter.php`
- `resources/lang/en/applications.php`
- `resources/lang/fr/applications.php`
- `resources/lang/en/candidate.php`
- `resources/lang/fr/candidate.php`
- `tests/Feature/Api/Recruiter/ApplicationStatusManagementTest.php`
- `tests/Feature/RecruiterApplicationPipelineTest.php` (new)
- `tests/Feature/CandidateApplicationExperienceTest.php`
- `tests/Feature/NotificationCenterTest.php`

### Profile completion

- `app/Models/User.php`
- `app/Http/Controllers/Candidate/DashboardController.php`
- `app/Http/Controllers/ProfileController.php`
- `resources/views/candidate/dashboard.blade.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/profile/partials/structured-profile-builder.blade.php`
- `resources/lang/en/candidate.php`
- `resources/lang/fr/candidate.php`
- `resources/lang/en/profile.php`
- `resources/lang/fr/profile.php`
- `tests/Feature/CandidateProfileCompletionTest.php` (new)

## Risks and tradeoffs

- **Existing final-decision contract:** The current system intentionally locks Accepted and Rejected and requires notes. The plan preserves that. Changing final decisions later requires a separate audited-history design, not a relaxed validator.
- **Meaning of `status_changed`:** It currently means a final decision occurred, despite its generic name. Renaming columns would create migration and compatibility cost with no user value, so leave them unchanged and cover the behavior with tests.
- **Notifications may be noisy:** Reversible workflow movement sends one notification per actual status change because that is current behavior. Add notification preferences or batching only if users report noise.
- **Completion is deliberately simple:** Five equal criteria are understandable and deterministic. Do not add weighting, education/language/link requirements, or cache invalidation until usage shows the checklist is too weak.
- **Free-text skills:** `skills` is currently a string. Completion checks only whether it is filled; restructuring skills is outside this work.
- **No migration required:** Status is a string and completion is derived. Adding migrations would increase deployment risk without adding correctness.
- **Dirty working tree:** Implementation must avoid broad formatting, cleanup, reset, or commits unless the user explicitly authorizes them.

## Open questions with recommended defaults

1. **Should candidates see recruiter-only workflow notes at Shortlisted/Interview?** Default: no new notes behavior. Notes remain tied to final Accepted/Rejected decisions.
2. **Should Interview include a date/time?** Default: no. A status is enough until scheduling is a measured need.
3. **Should education count toward completion?** Default: no. Keep five equal 20% checks. Add education only if recruiters treat it as essential across most roles.
4. **Should profile completion block applications?** Default: no. It is guidance, not a gate; the current resume/cover-letter application validation remains authoritative.
5. **Should a kanban board be added now?** Default: no. Filters and status controls provide the workflow with less code and no drag-and-drop accessibility burden.
