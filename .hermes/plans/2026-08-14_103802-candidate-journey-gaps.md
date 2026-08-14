# Candidate Journey Gaps Implementation Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Add saved jobs, concrete interview details, and safe candidate application withdrawal without adding packages or separate subsystems.

**Architecture:** Reuse the existing localized Blade routes, candidate/recruiter application pages, Eloquent relationships, status enum, database notifications, Alpine.js, and PHPUnit feature-test conventions. Saved jobs use one unique pivot table. Interview details live on the application they describe. Withdrawal is a terminal application status, not deletion, so the history remains visible to both sides.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Eloquent, Alpine.js, Tailwind CSS 3, PHPUnit 11, MySQL/SQLite test database, existing Docker test image.

---

## Scope and deliberate limits

Implement these three complete web journeys:

1. Candidates can save and unsave published jobs and browse a Saved Jobs page.
2. Recruiters moving an application to Interview must provide a date/time and either a location or meeting URL; candidates see those details and receive them in the existing status notification.
3. Candidates can withdraw their own Pending, Shortlisted, or Interview application after confirmation; the record becomes Withdrawn and is not deleted.

Deliberately skip:

- Saved-search alerts and scheduled email jobs.
- Calendar providers, `.ics` generation, reminders, rescheduling history, and timezone preferences.
- Candidate/recruiter chat.
- New API endpoints. The current request is for the Blade product; add API parity only when a real API client needs these actions.
- A generic workflow engine or status-transition abstraction.

## Current context and assumptions

- `ApplicationStatus` currently contains Pending, Shortlisted, Interview, Accepted, and Rejected.
- Accepted and Rejected are final recruiter decisions. Withdrawn will also be terminal, but only candidates may initiate it.
- The applications table uses strings for status, so adding an enum case does not require altering the status column.
- `ApplicationStatusUpdatedNotification` already sends mail and database notifications when recruiters change status.
- Demo accounts are read-only. Saving jobs and withdrawing applications must be blocked through `DemoAccountGuard`.
- EN and FR translations must stay in sync.
- Job cards use a stretched detail link. Bookmark controls must use `relative z-10` so clicking them does not open the job.
- The repository is heavily dirty. Do not commit unless the user explicitly requests it. If commits are later authorized, stage only files named by the completed task.

## Test commands

Use the existing verified Docker harness:

```bash
/tmp/recruivo-test.sh tests/Feature/SavedJobsTest.php
/tmp/recruivo-test.sh tests/Feature/CandidateApplicationWithdrawalTest.php
/tmp/recruivo-test.sh tests/Feature/RecruiterInterviewDetailsTest.php
/tmp/recruivo-test.sh
```

Frontend and static gates:

```bash
npm run build
npm audit --audit-level=high
git diff --check
```

Expected final result: all PHPUnit tests pass, Vite builds successfully, audit reports no high-severity vulnerabilities, and `git diff --check` prints nothing.

---

### Task 1: Add saved-job persistence and relationships

**Objective:** Store one candidate/job bookmark pair without duplicate rows.

**Files:**
- Create: `database/migrations/2026_08_14_000000_create_saved_jobs_table.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/Job.php`
- Create: `tests/Feature/SavedJobsTest.php`

**Step 1: Write failing relationship and uniqueness tests**

Create `SavedJobsTest` with `RefreshDatabase`. Create Candidate and Recruiter roles in `setUp()`. Add tests proving:

```php
public function test_candidate_can_have_saved_jobs(): void
{
    $candidate = $this->candidate();
    $job = Job::factory()->create(['status' => JobStatus::Published->value]);

    $candidate->savedJobs()->attach($job);

    $this->assertTrue($candidate->savedJobs->contains($job));
    $this->assertDatabaseHas('saved_jobs', [
        'user_id' => $candidate->id,
        'job_id' => $job->id,
    ]);
}
```

Also assert that inserting the same pair twice raises a database query exception. This verifies the database constraint rather than controller behavior.

**Step 2: Run the focused test and verify RED**

```bash
/tmp/recruivo-test.sh tests/Feature/SavedJobsTest.php
```

Expected: failure because `saved_jobs` and `savedJobs()` do not exist.

**Step 3: Add the pivot migration**

Use no pivot model. The table is data, not a domain object:

```php
Schema::create('saved_jobs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['user_id', 'job_id']);
});
```

Drop `saved_jobs` in `down()`.

**Step 4: Add direct Eloquent relationships**

In `User`:

```php
public function savedJobs()
{
    return $this->belongsToMany(Job::class, 'saved_jobs')->withTimestamps();
}
```

In `Job`:

```php
public function savedByCandidates()
{
    return $this->belongsToMany(User::class, 'saved_jobs')->withTimestamps();
}
```

**Step 5: Run focused test and verify GREEN**

Expected: saved relationship and unique constraint tests pass.

---

### Task 2: Add secure save, unsave, and Saved Jobs routes

**Objective:** Allow only mutable candidate accounts to manage their own saved jobs.

**Files:**
- Create: `app/Http/Controllers/Candidate/SavedJobController.php`
- Modify: `app/Services/DemoAccountGuard.php`
- Modify: `routes/web.php:115-120`
- Modify: `tests/Feature/SavedJobsTest.php`

**Step 1: Add failing HTTP tests**

Cover these contracts:

- Candidate `POST /en/candidate/saved-jobs/{job}` stores one row and redirects back with success.
- Repeated POST remains idempotent and keeps one row.
- Candidate `DELETE` removes only their own bookmark.
- Candidate Saved Jobs index contains only their saved published jobs.
- Recruiters receive 403 from all three routes through role middleware.
- Demo candidates receive a validation error and create/delete nothing.
- Draft jobs cannot be newly saved. Return 404 to match public job visibility.

**Step 2: Run focused test and verify RED**

Expected: route/controller not found.

**Step 3: Extend the existing demo guard minimally**

Add one general method rather than one method per new action:

```php
public function ensureCandidateActionsAreMutable(User $user): void
{
    if (! $user->is_demo) {
        return;
    }

    throw ValidationException::withMessages([
        'candidate_action' => __('common.demo_account_read_only'),
    ]);
}
```

Reuse it for save/unsave/withdraw. Do not refactor the existing profile/apply guard methods in this feature.

**Step 4: Add localized candidate routes**

Inside the existing Candidate group:

```php
Route::get('/saved-jobs', [SavedJobController::class, 'index'])->name('saved-jobs.index');
Route::post('/saved-jobs/{job}', [SavedJobController::class, 'store'])->name('saved-jobs.store');
Route::delete('/saved-jobs/{job}', [SavedJobController::class, 'destroy'])->name('saved-jobs.destroy');
```

**Step 5: Implement the minimum controller**

- `index`: query `$request->user()->savedJobs()->published()->with('company')->latest('saved_jobs.created_at')->paginate(12)->withQueryString()`.
- `store`: run demo guard; abort unless published; call `syncWithoutDetaching([$job->id])`; redirect back with translated success.
- `destroy`: run demo guard; call `detach($job->id)`; redirect back with translated success.

Do not introduce a repository, service, request class, or pivot model.

**Step 6: Run focused test and verify GREEN**

---

### Task 3: Render saved jobs and bookmark controls

**Objective:** Make saved jobs reachable and usable without introducing per-card queries.

**Files:**
- Create: `resources/views/candidate/saved-jobs.blade.php`
- Modify: `resources/views/components/job-card.blade.php`
- Modify: `resources/views/jobs/show.blade.php`
- Modify: `app/Http/Controllers/HomeController.php`
- Modify: `app/Http/Controllers/JobController.php`
- Modify: `app/Services/SmartSearchService.php`
- Modify: `resources/views/partials/header.blade.php`
- Modify: `resources/views/partials/mobile-nav.blade.php`
- Modify: `resources/lang/en/jobs.php`
- Modify: `resources/lang/fr/jobs.php`
- Modify: `resources/lang/en/common.php`
- Modify: `resources/lang/fr/common.php`
- Modify: `tests/Feature/SavedJobsTest.php`
- Modify: `tests/Feature/RoleNavigationTest.php`

**Step 1: Add failing rendering/navigation tests**

Assert:

- Candidate header contains `candidate.saved-jobs.index`; recruiter header does not.
- Saved page has an accessible heading, empty state, and saved job cards.
- Candidate job cards render either a Save or Remove bookmark form with an accessible label and a 44px target.
- Guests do not see a mutating form.
- Job detail page renders the same action for candidates.
- Demo candidate button is disabled/read-only and no mutating action is offered.

**Step 2: Run focused tests and verify RED**

**Step 3: Mark saved state in job queries without N+1 queries**

Add a small scope on `Job`:

```php
public function scopeWithSavedStateFor(Builder $query, ?User $user): Builder
{
    if (! $user?->hasRole('Candidate')) {
        return $query;
    }

    return $query->withExists([
        'savedByCandidates as is_saved' => fn (Builder $saved) => $saved->whereKey($user->id),
    ]);
}
```

Apply it before materializing jobs in `HomeController`, `JobController::index`, `JobController::show` similar-job query, and `SmartSearchService::jobs`. For the main bound job in `show`, call `loadExists` with the same constrained relationship. Preserve `SmartSearchService`; do not add external search infrastructure.

**Step 4: Add the bookmark form to the existing card**

- Only candidates see it.
- Use POST when unsaved and DELETE when saved.
- Use `relative z-10 inline-flex h-11 w-11` so the stretched job link does not intercept it.
- Use a text alternative through `aria-label`; SVG is `aria-hidden="true"`.
- Keep the Applied badge readable when both controls exist; use a small top-right action row instead of overlapping absolute elements.

**Step 5: Build the Saved Jobs page from existing cards**

Use the same `x-job-card`, existing pagination/infinite-scroll conventions only if needed, and a simple empty state linking to `jobs.index`. Do not create a second card design.

**Step 6: Add candidate navigation and EN/FR strings**

Add Saved Jobs to desktop candidate navigation. On the four-slot mobile bar, keep the current layout and replace the least important candidate-only slot only if necessary; do not expand beyond four columns or create horizontal overflow.

**Step 7: Run focused tests and verify GREEN**

---

### Task 4: Add interview detail columns and model casts

**Objective:** Persist one current interview appointment on its application.

**Files:**
- Create: `database/migrations/2026_08_14_000001_add_interview_details_to_applications_table.php`
- Modify: `app/Models/Application.php`
- Modify: `database/factories/ApplicationFactory.php`
- Create: `tests/Feature/RecruiterInterviewDetailsTest.php`

**Step 1: Write a failing persistence test**

Create an Interview application with:

```php
[
    'interview_at' => '2026-08-20 14:30:00',
    'interview_location' => 'Northstar Office, Floor 3',
    'interview_url' => 'https://meet.example.com/interview',
    'interview_instructions' => 'Ask for Sam at reception.',
]
```

Assert `interview_at` is a Carbon instance and fields round-trip.

**Step 2: Run test and verify RED**

**Step 3: Add nullable application columns**

```php
$table->dateTime('interview_at')->nullable()->after('status');
$table->string('interview_location')->nullable()->after('interview_at');
$table->string('interview_url', 2048)->nullable()->after('interview_location');
$table->text('interview_instructions')->nullable()->after('interview_url');
```

The down migration drops all four columns.

**Step 4: Add fields and cast**

Add the four fields to `$fillable`; cast `interview_at` to `datetime`. Factory defaults all four to `null` so unrelated tests remain deterministic.

**Step 5: Run focused test and verify GREEN**

---

### Task 5: Validate and save interview details in the recruiter workflow

**Objective:** Prevent an Interview status without actionable appointment details.

**Files:**
- Modify: `app/Http/Requests/UpdateApplicationStatusRequest.php`
- Modify: `app/Models/Application.php`
- Modify: `tests/Feature/RecruiterInterviewDetailsTest.php`
- Modify: `tests/Feature/RecruiterApplicationPipelineTest.php`

**Step 1: Add failing validation tests**

Test that changing to Interview:

- requires `interview_at`;
- requires at least one of `interview_location` or `interview_url`;
- rejects malformed/non-HTTP(S) meeting URLs;
- accepts date + location;
- accepts date + HTTPS URL;
- persists optional instructions;
- remains reversible to Pending/Shortlisted;
- clears all interview fields when moved away from Interview, preventing stale details.

Use exact expectations such as:

```php
->assertSessionHasErrors(['interview_at', 'interview_location']);
```

**Step 2: Run focused tests and verify RED**

**Step 3: Extend the existing Form Request**

Add conditional rules without a second request class:

```php
'interview_at' => ['nullable', 'required_if:status,interview', 'date', 'after:now'],
'interview_location' => ['nullable', 'string', 'max:255', 'required_without:interview_url'],
'interview_url' => ['nullable', 'url:http,https', 'max:2048', 'required_without:interview_location'],
'interview_instructions' => ['nullable', 'string', 'max:2000'],
```

Apply the `required_without` conditions only when requested status is Interview, using `Rule::requiredIf` if unconditional rules would affect other stages. Preserve final Accepted/Rejected behavior and note locking.

**Step 4: Clear stale appointment data centrally**

In `Application::applyStatusUpdate()`, if the validated target status is not Interview, set the four interview fields to null. This single root-cause rule covers web and API recruiter status updates.

**Step 5: Run pipeline and interview tests and verify GREEN**

---

### Task 6: Add recruiter interview form controls

**Objective:** Let recruiters enter interview details using native controls already supported by the browser.

**Files:**
- Modify: `resources/views/recruiter/applications/index.blade.php`
- Modify: `resources/lang/en/recruiter.php`
- Modify: `resources/lang/fr/recruiter.php`
- Modify: `tests/Feature/RecruiterInterviewDetailsTest.php`

**Step 1: Add failing UI tests**

Assert the review form contains:

- `input type="datetime-local" name="interview_at"`;
- location text input;
- URL input with `type="url"`;
- instructions textarea;
- an Alpine state initialized from the current status;
- details shown for existing Interview applications.

**Step 2: Run focused test and verify RED**

**Step 3: Extend the existing review form**

Use installed Alpine.js only:

```html
<form x-data="{ status: @js(old('status', $application->status->value)) }" ...>
    <select name="status" x-model="status">...</select>
    <fieldset x-show="status === 'interview'" x-cloak>...</fieldset>
</form>
```

Use native `datetime-local` and `url` inputs. Format stored time as `Y-m-d\TH:i`. Keep error messages next to each field. Do not add a date picker library.

**Step 4: Add EN/FR labels and help text**

Include interview date/time, location, meeting link, instructions, and “date plus location or link required” wording.

**Step 5: Run focused test and verify GREEN**

---

### Task 7: Show interview details to candidates and include them in notifications

**Objective:** Make an Interview stage useful after the recruiter saves it.

**Files:**
- Modify: `resources/views/candidate/applications.blade.php`
- Modify: `app/Notifications/ApplicationStatusUpdatedNotification.php`
- Modify: `resources/views/partials/notification-center.blade.php`
- Modify: `resources/lang/en/applications.php`
- Modify: `resources/lang/fr/applications.php`
- Modify: `resources/lang/en/common.php`
- Modify: `resources/lang/fr/common.php`
- Modify: `tests/Feature/RecruiterInterviewDetailsTest.php`
- Modify: `tests/Feature/NotificationCenterTest.php`

**Step 1: Add failing candidate and notification tests**

Assert an Interview application shows formatted date/time, location, safe HTTPS link with `target="_blank" rel="noopener noreferrer"`, and instructions. Assert other statuses do not show the interview panel.

Build `ApplicationStatusUpdatedNotification` and assert its mail lines/database array include the interview date and whichever of location/link exists.

**Step 2: Run focused tests and verify RED**

**Step 3: Render a candidate interview panel**

Render only when status is Interview and `interview_at` exists. Escape all text. Render the URL through Blade’s escaped `href`; server validation limits its scheme to HTTP(S).

Display the server-formatted date using the application locale conventions already used by Carbon. Do not build timezone preferences in this change; label the date in the application/server timezone if needed.

**Step 4: Extend the existing status notification**

- Add interview fields to `toArray()` only for Interview.
- Add clear mail lines for time, location/link, and instructions.
- Update notification-center matching so Shortlisted/Interview/Withdrawn are not labeled as “Application rejected.” Derive the translated title from the stored status.

**Step 5: Run focused tests and verify GREEN**

---

### Task 8: Add the Withdrawn status and transition rules

**Objective:** Preserve an application record while preventing further recruiter decisions after candidate withdrawal.

**Files:**
- Modify: `app/Enums/ApplicationStatus.php`
- Modify: `app/Http/Requests/UpdateApplicationStatusRequest.php`
- Modify: `app/Models/Application.php`
- Modify: `app/Policies/ApplicationPolicy.php`
- Create: `tests/Feature/CandidateApplicationWithdrawalTest.php`
- Modify: `tests/Feature/RecruiterApplicationPipelineTest.php`

**Step 1: Write failing status and authorization tests**

Assert:

- Enum order is Pending, Shortlisted, Interview, Accepted, Rejected, Withdrawn.
- Candidate may withdraw only their own Pending, Shortlisted, or Interview application.
- Candidate cannot withdraw Accepted, Rejected, or already Withdrawn applications.
- Candidate cannot withdraw another candidate’s application.
- Recruiter cannot change a Withdrawn application.
- Withdrawal clears interview details and does not delete the row.

**Step 2: Run focused tests and verify RED**

**Step 3: Add `ApplicationStatus::Withdrawn`**

```php
case Withdrawn = 'withdrawn';
```

Keep it last so existing workflow tabs retain their order.

**Step 4: Add a narrow policy ability**

```php
public function withdraw(User $user, Application $application): bool
{
    return $user->hasRole('Candidate')
        && $user->id === $application->candidate_id
        && in_array($application->status, [
            ApplicationStatus::Pending,
            ApplicationStatus::Shortlisted,
            ApplicationStatus::Interview,
        ], true);
}
```

Do not use the existing `delete` ability; withdrawal is explicitly not deletion.

**Step 5: Make Withdrawn terminal for recruiter updates**

Add Withdrawn to the existing terminal-status check in `UpdateApplicationStatusRequest`, but keep note requirements limited to Accepted/Rejected. Do not show Withdrawn as a recruiter-selectable option; the candidate owns this transition.

**Step 6: Run focused tests and verify GREEN**

---

### Task 9: Add the candidate withdrawal action and UI

**Objective:** Let candidates safely withdraw with clear confirmation and feedback.

**Files:**
- Modify: `app/Http/Controllers/Candidate/ApplicationController.php`
- Modify: `app/Services/DemoAccountGuard.php`
- Modify: `routes/web.php:115-120`
- Modify: `resources/views/candidate/applications.blade.php`
- Modify: `resources/views/recruiter/applications/index.blade.php`
- Modify: `resources/lang/en/applications.php`
- Modify: `resources/lang/fr/applications.php`
- Modify: `resources/lang/en/recruiter.php`
- Modify: `resources/lang/fr/recruiter.php`
- Modify: `tests/Feature/CandidateApplicationWithdrawalTest.php`
- Modify: `tests/Feature/CandidateApplicationExperienceTest.php`

**Step 1: Add failing endpoint/UI tests**

Assert:

- `PATCH /en/candidate/applications/{application}/withdraw` changes eligible status to Withdrawn.
- The application count remains unchanged.
- Demo candidate is blocked by the shared demo guard.
- Candidate page shows a 44px Withdraw button only for eligible statuses.
- Button uses an explicit confirmation prompt.
- Withdrawn appears in candidate and recruiter filters/counts and has a neutral gray badge.
- Recruiter panel shows a read-only “Withdrawn by candidate” state and no status form.

**Step 2: Run focused tests and verify RED**

**Step 3: Add the route and controller action**

Inside the Candidate route group:

```php
Route::patch('/applications/{application}/withdraw', [ApplicationController::class, 'withdraw'])
    ->name('applications.withdraw');
```

Controller action:

1. Call `$this->authorize('withdraw', $application)`.
2. Call `DemoAccountGuard::ensureCandidateActionsAreMutable()`.
3. Call existing `applyStatusUpdate(['status' => ApplicationStatus::Withdrawn->value])` so interview data is cleared centrally.
4. Redirect back with translated success.

No delete and no extra withdrawal table.

**Step 4: Add the candidate confirmation form**

Use the browser’s native confirmation because this is one destructive-looking action and no modal subsystem is needed:

```html
<form method="POST" action="..." onsubmit="return confirm(@js(__('applications.withdraw_confirm')))">
    @csrf
    @method('PATCH')
    <button type="submit" class="min-h-11 ...">...</button>
</form>
```

**Step 5: Keep recruiter controls candidate-owned**

Build recruiter select options from `ApplicationStatus::cases()` excluding Withdrawn. Treat Withdrawn like a terminal read-only state in the review panel.

**Step 6: Update dashboard counts**

`Candidate\DashboardController` total count includes Withdrawn because it is historical. In-progress remains only Pending/Shortlisted/Interview. Accepted/Rejected remain unchanged.

**Step 7: Run focused tests and verify GREEN**

---

### Task 10: Final integration, regression, and live UI verification

**Objective:** Prove all three journeys work together and do not regress search, localization, demo immutability, or mobile layout.

**Files:**
- Modify only if a failing regression reveals a root cause.

**Step 1: Run all focused suites**

```bash
/tmp/recruivo-test.sh tests/Feature/SavedJobsTest.php
/tmp/recruivo-test.sh tests/Feature/RecruiterInterviewDetailsTest.php
/tmp/recruivo-test.sh tests/Feature/CandidateApplicationWithdrawalTest.php
/tmp/recruivo-test.sh tests/Feature/CandidateApplicationExperienceTest.php
/tmp/recruivo-test.sh tests/Feature/RecruiterApplicationPipelineTest.php
/tmp/recruivo-test.sh tests/Feature/NotificationCenterTest.php
/tmp/recruivo-test.sh tests/Feature/RoleNavigationTest.php
```

Expected: all pass.

**Step 2: Run the full feature suite**

```bash
/tmp/recruivo-test.sh
```

Expected: all tests pass. Do not accept warnings that hide failures.

**Step 3: Run frontend/static gates**

```bash
npm run build
npm audit --audit-level=high
git diff --check
```

**Step 4: Rebuild the live container**

Use the repository’s established Docker rebuild command:

```bash
docker compose --env-file .env.docker up -d --build laravel
```

Wait for `/api/health` to return healthy. Confirm host and container Vite manifests reference the same assets.

**Step 5: Live-verify desktop candidate journey**

Using temporary mutable test accounts only:

1. Candidate saves a job from a card.
2. Saved Jobs page lists it.
3. Candidate removes it and empty state returns.
4. Candidate applies to a job.
5. Recruiter moves it to Interview with native date/time plus location/link.
6. Candidate sees all interview details and a correctly labeled notification.
7. Candidate withdraws after confirmation.
8. Both candidate and recruiter see Withdrawn; recruiter cannot alter it.

Delete temporary verification data afterward.

**Step 6: Live-verify responsive and accessibility basics**

At 390px and desktop widths:

- No horizontal overflow.
- Bookmark and Withdraw controls are at least 44px.
- Bookmark labels announce Save/Remove correctly.
- Interview fields have visible labels and field errors.
- Status/filter tabs remain scrollable and readable with Withdrawn added.
- Keyboard focus remains visible.

**Step 7: Review the final diff**

Confirm:

- No dependency or package changes.
- No API routes were added.
- No saved-search/calendar/chat scaffolding exists.
- No application rows are deleted by withdrawal.
- No N+1 saved-state query was introduced in job-card loops.
- No secrets, temporary credentials, or generated verification artifacts are tracked.

## Risks and tradeoffs

- **Timezone ambiguity:** The app has no user timezone preference. Store one application datetime and display consistently; add account timezone support only if users in multiple zones report confusion.
- **Withdrawn in enum-driven UI:** Existing views derive tabs from enum cases. Candidate/recruiter tabs should include Withdrawn, but recruiter select options must explicitly exclude it.
- **Search collection path:** `SmartSearchService` materializes collections. Saved-state existence must be attached before ranking/pagination so card rendering does not query per job.
- **Demo accounts:** Both save/unsave and withdrawal mutate data. Reuse one new guard method and verify server-side blocking; disabled UI alone is insufficient.
- **Notification labels:** The current notification center treats every non-Accepted update as Rejected. Fix the status-derived title while touching interview/withdrawal notifications.
- **Final decisions:** Accepted/Rejected remain immutable. Withdrawal is permitted only before either final decision.

## Acceptance criteria

- Candidate can save/unsave a published job idempotently and browse saved jobs.
- Bookmark controls work on cards and job details without triggering the stretched job link.
- Job lists do not issue one saved-state query per card.
- Recruiter cannot choose Interview without future date/time and location or HTTP(S) meeting link.
- Candidate sees the current interview details in UI and notification.
- Moving away from Interview clears stale interview details.
- Candidate can withdraw only their own non-final application.
- Withdrawal changes status to Withdrawn and never deletes the application.
- Recruiter cannot modify a Withdrawn application.
- Demo accounts cannot save, unsave, or withdraw server-side.
- EN/FR translations, dark mode, mobile layout, and accessible control sizes remain intact.
- Full PHPUnit suite, build, audit, and diff checks pass.
