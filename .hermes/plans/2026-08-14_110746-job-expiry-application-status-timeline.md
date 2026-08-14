# Job Expiry and Application Status Timeline Implementation Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Automatically remove closed jobs from every public/apply path and show candidates and recruiters a trustworthy application status history.

**Architecture:** Add one nullable `closes_at` date to jobs and make the existing `Job::published()` scope the single source of truth for public visibility. Add one append-only `application_status_events` table and record transitions through Eloquent model events so web, API, withdrawal, factories, and future callers cannot bypass history accidentally. Reuse existing Blade pages, enums, notifications, localized routes, and PHPUnit/Docker conventions; add no packages, scheduler, workflow engine, or calendar library.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Eloquent, Tailwind CSS, PHPUnit 11, MySQL in production, SQLite in feature tests, existing Docker test image.

---

## Scope and deliberate limits

Implement:

1. Recruiters can optionally choose a job closing date with native `<input type="date">` controls.
2. A published job is publicly visible through the end of its closing date, then disappears from listings, search, metrics, company pages, saved jobs, direct public/API detail pages, and application endpoints.
3. Recruiters still see expired jobs in Manage Jobs with an Expired badge and can extend the date and republish.
4. Every application gets an initial Submitted/Pending event and one immutable event for each real status transition.
5. Existing applications receive one backfilled event representing their current known status.
6. Candidates and recruiters see the timeline in the existing application cards; API resources expose it when loaded.

Deliberately skip:

- Cron jobs that rewrite job status. Query-time visibility is enough and avoids scheduler operations.
- Reminder emails, auto-renewal, recurring vacancies, and configurable “closing soon” thresholds.
- A generic audit-log package or workflow engine.
- Editing or deleting status events.
- Reconstructing historical transitions that were never stored. Existing rows get one honest baseline event only.

## Current context and assumptions

- `Job::published()` is already used by home, jobs, search, companies, API listings, metrics, and saved jobs. Strengthening it fixes most public surfaces once.
- Direct job detail and application controllers currently check only `status === Published`; they must use the same model visibility rule.
- `JobStatus` remains Draft/Published. Expired is derived from `closes_at`, not a third persisted status.
- A nullable closing date means “no automatic expiry.” This preserves all existing jobs.
- A date closes at the end of that local calendar day. `closes_at < today()` means expired.
- `Application::applyStatusUpdate()` is used by recruiter web/API updates, but future withdrawal work may update status through a different controller. Eloquent `created`/`updated` model events are therefore the smallest root-level capture point.
- `ApplicationStatus` may gain Withdrawn while this plan waits. Timeline rendering must use enum-driven translation keys and store status strings without a database enum.
- Status-event rows are append-only and cascade when their application is deleted.
- The repository has many unrelated changes. Do not commit unless the user explicitly asks; if later authorized, stage only files from the completed task.
- Execute this plan after the current saved-jobs/interview/withdrawal implementation is green, then re-read shared files before editing.

## Test and verification commands

Use the verified Docker harness:

```bash
/tmp/recruivo-test.sh tests/Feature/JobExpiryTest.php
/tmp/recruivo-test.sh tests/Feature/ApplicationStatusTimelineTest.php
/tmp/recruivo-test.sh tests/Feature/RecruiterJobCreationTest.php
/tmp/recruivo-test.sh tests/Feature/RecruiterApplicationPipelineTest.php
/tmp/recruivo-test.sh tests/Feature/CandidateApplicationExperienceTest.php
/tmp/recruivo-test.sh tests/Feature/Api/JobApplicationTest.php
/tmp/recruivo-test.sh tests/Feature/Api/Recruiter/ApplicationStatusManagementTest.php
/tmp/recruivo-test.sh
```

Final gates:

```bash
npm run build
npm audit --audit-level=high
git diff --check
docker compose --env-file .env.docker up -d --build laravel
```

Expected final result: all PHPUnit tests pass, Vite builds, audit reports no high-severity vulnerabilities, `git diff --check` prints nothing, the rebuilt app is healthy, and desktop/mobile live checks pass.

---

## Feature A: Job closing dates and automatic expiry

### Task 1: Add nullable closing-date persistence

**Objective:** Store an optional closing date without changing existing job behavior.

**Files:**
- Create: `database/migrations/2026_08_14_110800_add_closes_at_to_jobs_table.php`
- Modify: `app/Models/Job.php`
- Modify: `database/factories/JobFactory.php`
- Create: `tests/Feature/JobExpiryTest.php`

**Step 1: Write the failing model contract**

Create `JobExpiryTest` using `RefreshDatabase` and assert:

```php
public function test_job_casts_closing_date_and_reports_expiry(): void
{
    $active = Job::factory()->create(['closes_at' => today()]);
    $expired = Job::factory()->create(['closes_at' => today()->subDay()]);
    $openEnded = Job::factory()->create(['closes_at' => null]);

    $this->assertInstanceOf(CarbonInterface::class, $active->closes_at);
    $this->assertFalse($active->isExpired());
    $this->assertTrue($expired->isExpired());
    $this->assertFalse($openEnded->isExpired());
}
```

**Step 2: Run RED**

```bash
/tmp/recruivo-test.sh tests/Feature/JobExpiryTest.php
```

Expected: failure because `closes_at` and `isExpired()` do not exist.

**Step 3: Add the migration**

```php
Schema::table('jobs', function (Blueprint $table) {
    $table->date('closes_at')->nullable()->after('published_at');
});
```

`down()` drops `closes_at`. Do not add a new status column or scheduler.

**Step 4: Update the model and factory**

Add `closes_at` to `$fillable`, cast it as `date`, and default it to `null` in `JobFactory`:

```php
public function isExpired(): bool
{
    return $this->closes_at?->isBefore(today()) ?? false;
}

public function isPubliclyVisible(): bool
{
    return $this->status === JobStatus::Published
        && $this->published_at !== null
        && !$this->isExpired();
}

public function isClosingSoon(): bool
{
    return !$this->isExpired()
        && $this->closes_at !== null
        && $this->closes_at->lte(today()->addDays(7));
}
```

The seven-day value stays inline because it has one use and no product setting exists.

**Step 5: Run GREEN**

Expected: the focused model test passes.

---

### Task 2: Make `published()` exclude expired jobs everywhere

**Objective:** Turn the existing scope into the single public-list visibility rule.

**Files:**
- Modify: `app/Models/Job.php`
- Modify: `tests/Feature/JobExpiryTest.php`

**Step 1: Add failing scope tests**

Create published jobs with `closes_at` equal to yesterday, today, tomorrow, and null. Assert `Job::published()->pluck('id')`:

- excludes yesterday;
- includes today;
- includes tomorrow;
- includes null;
- still excludes Draft jobs.

**Step 2: Run RED**

Expected: yesterday’s published job is still returned.

**Step 3: Strengthen the existing scope**

```php
public function scopePublished(Builder $builder): Builder
{
    return $builder
        ->where('status', JobStatus::Published->value)
        ->whereNotNull('published_at')
        ->where(function (Builder $query) {
            $query->whereNull('closes_at')
                ->orWhereDate('closes_at', '>=', today());
        });
}
```

Do not add an `active()` alias. Existing callers already use `published()` and changing them creates needless churn.

**Step 4: Run GREEN and discovery regressions**

```bash
/tmp/recruivo-test.sh tests/Feature/JobExpiryTest.php
/tmp/recruivo-test.sh tests/Feature/JobDiscoveryExperienceTest.php
/tmp/recruivo-test.sh tests/Feature/SmartSearchTest.php
```

Expected: expired jobs disappear from every scope-backed list/search and current discovery tests remain green.

---

### Task 3: Close direct-detail and application bypasses

**Objective:** Prevent expired jobs from being opened or applied to through known URLs.

**Files:**
- Modify: `app/Http/Controllers/JobController.php:57-60`
- Modify: `app/Http/Controllers/Api/JobController.php:75-78`
- Modify: `app/Http/Controllers/ApplicationController.php:20`
- Modify: `app/Http/Controllers/Api/ApplicationController.php:31-34`
- Modify: `app/Policies/JobPolicy.php:25-26`
- Modify: `tests/Feature/JobExpiryTest.php`
- Modify: `tests/Feature/Api/JobApplicationTest.php`

**Step 1: Add failing HTTP tests**

Prove:

- `GET /en/jobs/{expired}` returns 404.
- `GET /api/jobs/{expired}` returns 404.
- Candidate web application POST to an expired job returns 404 and creates no application.
- Candidate API application POST to an expired job returns 404 and creates no application.
- A recruiter/admin can still manage the expired job through recruiter/admin routes.
- A candidate policy check cannot view it.

**Step 2: Run RED**

Expected: direct detail/application paths accept the published-but-expired row.

**Step 3: Replace repeated status-only guards**

Use the model method:

```php
abort_unless($job->isPubliclyVisible(), 404);
```

Apply it in both public detail controllers and both application controllers. Change the candidate branch in `JobPolicy::view()` to `$job->isPubliclyVisible()`; preserve recruiter/admin ownership behavior.

**Step 4: Run GREEN**

Run focused web/API tests. Confirm no application row or uploaded-file side effect is created on rejection.

---

### Task 4: Validate and save closing dates through web and API

**Objective:** Let recruiters create and extend closing dates using existing request classes.

**Files:**
- Modify: `app/Http/Requests/StoreJobRequest.php`
- Modify: `app/Http/Requests/UpdateJobRequest.php`
- Modify: `app/Http/Controllers/Recruiter/JobController.php`
- Modify: `app/Http/Controllers/Api/Recruiter/JobController.php`
- Modify: `app/Http/Resources/JobResource.php`
- Modify: `tests/Feature/RecruiterJobCreationTest.php`
- Modify: `tests/Feature/JobExpiryTest.php`

**Step 1: Add failing request/API tests**

Cover:

- `closes_at` is optional.
- A valid date today or later persists.
- A new job cannot be created with a past closing date.
- Updating an expired job requires clearing or extending the date; it cannot retain a submitted past date.
- API create/update returns `closes_at` as `YYYY-MM-DD` or null.
- Toggling an expired job to Published is rejected with an actionable error until the date is extended/cleared.

**Step 2: Run RED**

Expected: field is discarded or past dates are accepted.

**Step 3: Add minimal validation**

Store:

```php
'closes_at' => ['nullable', 'date', 'after_or_equal:today'],
```

Update:

```php
'closes_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
```

Both web and API already reuse these requests. No separate API validator is needed.

**Step 4: Guard both toggle implementations**

Before Draft → Published:

```php
if ($job->isExpired()) {
    return back()->with('error', __('recruiter.extend_closing_date_before_publishing'));
}
```

Return HTTP 422 with the same meaning in the API controller. Do not silently clear the recruiter’s date.

**Step 5: Expose the field in `JobResource`**

```php
'closes_at' => $this->closes_at?->toDateString(),
'is_expired' => $this->isExpired(),
```

**Step 6: Run GREEN**

Expected: web/API persistence, validation, toggle protection, and resource serialization pass.

---

### Task 5: Add native date fields and expiry UI

**Objective:** Make closing dates understandable without JavaScript or a date-picker package.

**Files:**
- Modify: `resources/views/recruiter/jobs/create.blade.php`
- Modify: `resources/views/recruiter/jobs/edit.blade.php`
- Modify: `resources/views/recruiter/jobs/index.blade.php`
- Modify: `resources/views/recruiter/jobs/show.blade.php`
- Modify: `resources/views/jobs/show.blade.php`
- Modify: `resources/views/components/job-card.blade.php` or the current shared job-card partial found at execution time
- Modify: `resources/lang/en/recruiter.php`
- Modify: `resources/lang/fr/recruiter.php`
- Modify: `resources/lang/en/jobs.php`
- Modify: `resources/lang/fr/jobs.php`
- Modify: `tests/Feature/JobExpiryTest.php`

**Step 1: Add failing rendering tests**

Assert:

- Create/edit forms contain `name="closes_at"`, `type="date"`, and `min="{{ today()->toDateString() }}"`.
- Edit form shows the saved date.
- Recruiter index labels expired jobs Expired even though persisted status remains Published.
- A job closing within seven days shows localized “Closing soon” copy.
- An open-ended job does not render closing copy.

**Step 2: Run RED**

Expected: fields and labels are absent.

**Step 3: Add native controls**

Use:

```blade
<input
    id="closes_at"
    name="closes_at"
    type="date"
    min="{{ today()->toDateString() }}"
    value="{{ old('closes_at', isset($job) ? $job->closes_at?->toDateString() : '') }}"
    class="...existing input classes..."
>
```

Add helper copy: blank means the job stays open until manually unpublished.

**Step 4: Render derived states**

Priority on recruiter cards:

1. Expired when `$job->isExpired()`.
2. Published when persisted status is Published.
3. Draft otherwise.

On public card/detail pages, show the date and a stronger Closing Soon treatment only when `$job->isClosingSoon()`.

**Step 5: Add EN/FR translations and run GREEN**

Do not hard-code English in Blade. Keep touch targets and responsive layout unchanged.

---

## Feature B: Application status timeline

### Task 6: Add append-only status-event persistence and backfill

**Objective:** Store one immutable row per known application state.

**Files:**
- Create: `database/migrations/2026_08_14_110900_create_application_status_events_table.php`
- Create: `app/Models/ApplicationStatusEvent.php`
- Create: `database/factories/ApplicationStatusEventFactory.php` only if tests need it; otherwise skip it
- Modify: `app/Models/Application.php`
- Create: `tests/Feature/ApplicationStatusTimelineTest.php`

**Step 1: Write failing relationship/backfill tests**

Test the relationship contract after migration:

```php
$this->assertSame(
    ['pending'],
    $application->statusEvents()->pluck('to_status')->all()
);
```

Also assert event rows are deleted when the application is deleted and cannot point to a missing application.

For migration behavior, add a migration-level test only if the harness supports migrating to a named step reliably. Otherwise verify the migration code directly in review and cover new-row behavior with feature tests.

**Step 2: Run RED**

Expected: table/model/relation missing.

**Step 3: Create the event table**

```php
Schema::create('application_status_events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('application_id')->constrained()->cascadeOnDelete();
    $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('from_status')->nullable();
    $table->string('to_status');
    $table->timestamp('created_at');

    $table->index(['application_id', 'created_at']);
});
```

No `updated_at`: events are append-only. Do not store copied job/candidate names or notes.

**Step 4: Backfill existing applications honestly**

After creating the table, chunk current applications and insert exactly one baseline row each:

```php
DB::table('applications')->orderBy('id')->chunkById(500, function ($applications) {
    $rows = $applications->map(fn ($application) => [
        'application_id' => $application->id,
        'changed_by_user_id' => null,
        'from_status' => null,
        'to_status' => $application->status,
        'created_at' => $application->created_at ?? now(),
    ])->all();

    DB::table('application_status_events')->insert($rows);
});
```

This says “known state at submission time” without inventing transitions. `down()` drops the event table.

**Step 5: Add model and relationship**

`ApplicationStatusEvent` has no public update flow, casts `created_at`, belongs to Application and optional User. `Application` adds:

```php
public function statusEvents()
{
    return $this->hasMany(ApplicationStatusEvent::class)->oldest('created_at')->oldest('id');
}
```

**Step 6: Run GREEN**

Expected: relationship, foreign keys, ordering, and cascade pass.

---

### Task 7: Capture initial and changed statuses at the model boundary

**Objective:** Record history once for every creation/transition regardless of controller path.

**Files:**
- Modify: `app/Models/Application.php`
- Modify: `tests/Feature/ApplicationStatusTimelineTest.php`

**Step 1: Add failing lifecycle tests**

Cover:

- Creating an application creates one event: null → Pending.
- Pending → Shortlisted creates a second event.
- Shortlisted → Interview creates a third event.
- Saving unrelated notes/data creates no event.
- Saving the same status creates no duplicate event.
- Web and API recruiter update paths identify the authenticated recruiter in `changed_by_user_id`.
- Candidate withdrawal, if already implemented, records the authenticated candidate.
- A status transition executed without an authenticated user still records an event with null actor.

**Step 2: Run RED**

Expected: no events are generated automatically.

**Step 3: Add Eloquent lifecycle hooks**

Use `booted()` in `Application` so all callers converge here:

```php
protected static function booted(): void
{
    static::created(function (Application $application) {
        $application->statusEvents()->create([
            'changed_by_user_id' => auth()->id() ?: $application->candidate_id,
            'from_status' => null,
            'to_status' => $application->statusValue(),
            'created_at' => $application->created_at ?? now(),
        ]);
    });

    static::updated(function (Application $application) {
        if (!$application->wasChanged('status')) {
            return;
        }

        $application->statusEvents()->create([
            'changed_by_user_id' => auth()->id(),
            'from_status' => $application->getRawOriginal('status'),
            'to_status' => $application->statusValue(),
            'created_at' => now(),
        ]);
    });
}
```

Add one small helper only if needed to normalize enum/string status:

```php
private function statusValue(): string
{
    return $this->status instanceof \BackedEnum ? $this->status->value : $this->status;
}
```

If private methods cause lifecycle callback access issues, make it public or inline twice; do not create a service.

**Step 4: Ensure migration backfill does not double-fire**

Backfill uses `DB::table`, not Eloquent, so model events do not run. Fresh migrations create the events table after applications; existing factory-created rows during feature tests happen after all migrations and get exactly one event.

**Step 5: Run GREEN and existing pipeline/API tests**

Expected: one row per real transition, no duplicate rows, and existing status/notification behavior stays unchanged.

---

### Task 8: Load and expose timelines without N+1 queries

**Objective:** Make timeline data available to current web and API views efficiently.

**Files:**
- Modify: `app/Http/Controllers/Recruiter/ApplicationController.php`
- Modify: `app/Http/Controllers/Candidate/ApplicationController.php`
- Modify: `app/Http/Controllers/Api/Recruiter/ApplicationController.php`
- Modify: `app/Http/Controllers/Api/ApplicationController.php` where application collections/resources are returned
- Modify: `app/Http/Resources/ApplicationResource.php`
- Modify: `tests/Feature/ApplicationStatusTimelineTest.php`

**Step 1: Add failing eager-load/resource tests**

Assert candidate and recruiter pages can access `statusEvents.changedBy` and API output contains ordered history:

```json
"status_history": [
  {
    "from_status": null,
    "to_status": "pending",
    "changed_at": "..."
  },
  {
    "from_status": "pending",
    "to_status": "shortlisted",
    "changed_at": "..."
  }
]
```

Do not expose actor email or private account fields.

**Step 2: Run RED**

Expected: relation not loaded/resource key absent.

**Step 3: Eager-load the existing relation**

Add `statusEvents.changedBy:id,name` alongside current application relationships in recruiter/candidate controllers. For API resources, use `whenLoaded` so callers that do not need history do not trigger hidden queries.

**Step 4: Add minimal resource mapping**

```php
'status_history' => $this->whenLoaded('statusEvents', fn () =>
    $this->statusEvents->map(fn ($event) => [
        'from_status' => $event->from_status,
        'to_status' => $event->to_status,
        'changed_at' => $event->created_at?->toIso8601String(),
        'changed_by' => $event->changedBy?->name,
    ])
),
```

Actor name is useful to the owning recruiter/candidate; omit IDs and email.

**Step 5: Run GREEN**

Add a query-count assertion only if the project already has a stable convention. Otherwise inspect eager-loading in review and avoid brittle global query counts.

---

### Task 9: Render accessible timelines for candidates and recruiters

**Objective:** Show the same ordered history on both existing application cards.

**Files:**
- Modify: `resources/views/recruiter/applications/index.blade.php`
- Modify: `resources/views/candidate/applications.blade.php`
- Modify: `resources/lang/en/recruiter.php`
- Modify: `resources/lang/fr/recruiter.php`
- Modify: `resources/lang/en/applications.php`
- Modify: `resources/lang/fr/applications.php`
- Modify: `tests/Feature/ApplicationStatusTimelineTest.php`

**Step 1: Add failing rendering tests**

Create an application with Pending → Shortlisted → Interview events and assert both pages:

- contain `data-application-status-timeline`;
- render Submitted/Pending, Shortlisted, Interview in chronological order;
- render human-readable timestamps;
- render only translation-backed labels;
- remain usable when an event actor is null;
- do not expose recruiter/candidate email in the timeline;
- do not show duplicate entries after a no-op save.

**Step 2: Run RED**

Expected: timeline markup absent.

**Step 3: Render a semantic ordered list**

Use the same compact structure in both views without introducing a component until a third use appears:

```blade
<section class="..." aria-labelledby="status-timeline-{{ $application->id }}">
    <h3 id="status-timeline-{{ $application->id }}">{{ __('applications.status_timeline') }}</h3>
    <ol data-application-status-timeline class="mt-3 space-y-3">
        @foreach($application->statusEvents as $event)
            <li class="relative pl-6">
                <span aria-hidden="true" class="absolute left-0 top-1.5 h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                <p class="text-sm font-semibold">{{ __('applications.status_'.$event->to_status) }}</p>
                <time datetime="{{ $event->created_at->toIso8601String() }}" class="text-xs text-stone-500">
                    {{ $event->created_at->format('M j, Y H:i') }}
                </time>
            </li>
        @endforeach
    </ol>
</section>
```

Use existing status label keys when possible. Add a special Submitted label for the first null → Pending event if product copy needs to distinguish it. Keep color supplemental; text carries the meaning.

**Step 4: Add EN/FR translations and run GREEN**

Expected: both roles see ordered, localized, accessible timelines with no new JavaScript.

---

### Task 10: Full regression, migration, and live verification

**Objective:** Prove both features work together on real rendered pages and both database engines.

**Files:**
- Modify only files required to fix failures caused by this plan
- Do not perform drive-by formatting or refactors

**Step 1: Run focused suites**

```bash
/tmp/recruivo-test.sh tests/Feature/JobExpiryTest.php
/tmp/recruivo-test.sh tests/Feature/ApplicationStatusTimelineTest.php
/tmp/recruivo-test.sh tests/Feature/RecruiterJobCreationTest.php
/tmp/recruivo-test.sh tests/Feature/RecruiterApplicationPipelineTest.php
/tmp/recruivo-test.sh tests/Feature/CandidateApplicationExperienceTest.php
/tmp/recruivo-test.sh tests/Feature/Api/JobApplicationTest.php
/tmp/recruivo-test.sh tests/Feature/Api/Recruiter/ApplicationStatusManagementTest.php
```

Expected: all focused suites pass.

**Step 2: Run the full feature suite**

```bash
/tmp/recruivo-test.sh
```

Expected: all tests pass. Record actual test/assertion counts; do not predict them.

**Step 3: Run frontend/static gates**

```bash
npm run build
npm audit --audit-level=high
git diff --check
```

Expected: build passes, no high vulnerabilities, clean whitespace check.

**Step 4: Rebuild the Docker app**

```bash
docker compose --env-file .env.docker up -d --build laravel
```

Poll `/api/health` until healthy. Confirm host and container `public/build/manifest.json` reference the same assets.

**Step 5: Live-verify job expiry**

Using temporary mutable recruiter/candidate accounts and cleanup afterward:

1. Recruiter creates a published job with today’s closing date; public listing/detail and application form are visible.
2. Recruiter edit page preserves the date.
3. Recruiter creates or updates a test row to yesterday directly for verification; it disappears from home/jobs/search/company/saved-job public lists.
4. Direct web/API detail and application requests return 404.
5. Recruiter Manage Jobs still shows the row as Expired.
6. Recruiter extends the date to tomorrow; the job becomes visible again without changing status.

**Step 6: Live-verify timeline**

1. Candidate applies; both candidate and recruiter see Submitted/Pending.
2. Recruiter moves it to Shortlisted and Interview; both timelines show all three stages in order.
3. Refresh pages; history persists and no duplicate event appears.
4. If withdrawal is implemented, candidate withdraws and both timelines show Withdrawn.
5. Verify EN and FR labels.
6. Verify 390px mobile width has no horizontal overflow and timeline remains readable.

**Step 7: Final review**

Check:

- No public visibility caller still checks only persisted Published status.
- No status-update path bypasses model lifecycle events.
- No invented history was added for old applications.
- No N+1 relation access in timeline views/resources.
- No new dependency, scheduler, generic audit package, or duplicated status configuration.
- Demo-account immutability and existing final-decision rules remain intact.
- Temporary live data/accounts are removed.

---

## Files likely to change

**Create:**

- `database/migrations/2026_08_14_110800_add_closes_at_to_jobs_table.php`
- `database/migrations/2026_08_14_110900_create_application_status_events_table.php`
- `app/Models/ApplicationStatusEvent.php`
- `tests/Feature/JobExpiryTest.php`
- `tests/Feature/ApplicationStatusTimelineTest.php`

**Modify:**

- `app/Models/Job.php`
- `app/Models/Application.php`
- `database/factories/JobFactory.php`
- `app/Http/Requests/StoreJobRequest.php`
- `app/Http/Requests/UpdateJobRequest.php`
- `app/Http/Controllers/JobController.php`
- `app/Http/Controllers/ApplicationController.php`
- `app/Http/Controllers/Candidate/ApplicationController.php`
- `app/Http/Controllers/Recruiter/JobController.php`
- `app/Http/Controllers/Recruiter/ApplicationController.php`
- `app/Http/Controllers/Api/JobController.php`
- `app/Http/Controllers/Api/ApplicationController.php`
- `app/Http/Controllers/Api/Recruiter/JobController.php`
- `app/Http/Controllers/Api/Recruiter/ApplicationController.php`
- `app/Http/Resources/JobResource.php`
- `app/Http/Resources/ApplicationResource.php`
- `app/Policies/JobPolicy.php`
- `resources/views/recruiter/jobs/create.blade.php`
- `resources/views/recruiter/jobs/edit.blade.php`
- `resources/views/recruiter/jobs/index.blade.php`
- `resources/views/recruiter/jobs/show.blade.php`
- `resources/views/recruiter/applications/index.blade.php`
- `resources/views/jobs/show.blade.php`
- current shared job-card view/partial
- `resources/views/candidate/applications.blade.php`
- EN/FR `recruiter.php`, `jobs.php`, and `applications.php`
- focused existing feature/API tests where regression coverage belongs

## Risks and tradeoffs

- **Date boundary:** Date-only expiry intentionally uses application `today()`. If users later need timezone-specific deadlines, add a timezone plus timestamp then; do not solve it now.
- **Derived status:** An expired job remains Published in storage. This avoids a scheduler and preserves recruiter intent, but UI must clearly label it Expired.
- **Backfill accuracy:** Existing applications have only their current status and timestamps. One baseline event is honest; fabricated intermediate events are not.
- **Model events:** Bulk `DB::table()->update()` bypasses Eloquent events. Existing product status changes use Eloquent controllers. Document this in the event model test; add a service only if bulk status updates become a real feature.
- **Concurrent updates:** Each successful Eloquent status change appends one event. Existing final-state validation remains authoritative. A database transaction around update + event is optional only if testing reveals partial writes; Eloquent `updated` runs synchronously in the same request.
- **Shared files:** The in-progress candidate-journey work touches `ApplicationStatus`, application controllers/views, and translations. Re-read and reconcile those files before implementation rather than applying this plan against stale line numbers.

## Open questions resolved by default

- Closing date is optional.
- Today remains visible; expiry starts tomorrow.
- Expired is derived, not persisted in `JobStatus`.
- Existing jobs remain open-ended.
- Existing applications get one current-state baseline event.
- Timeline events are immutable and contain statuses, actor reference, and timestamp only.
- Candidates and recruiters both see the timeline.
- API reads expose history when eager-loaded; no new endpoint is added.
