# 2026-08-14 — Job Expiry & Application Status Timeline

## What was done
Implemented two features via subagent-driven development (TDD, spec + quality reviews per unit):

### Job closing dates and automatic expiry
- `closes_at` nullable date column on jobs; `Job::isExpired()` / `isPubliclyVisible()` / `isClosingSoon()`; `published()` scope now excludes expired jobs (query-time, no scheduler).
- All public surfaces guarded: web/API detail, web/API apply, JobPolicy candidate branch → 404 for expired jobs; recruiters/admin still manage them.
- Validation `nullable|date|after_or_equal:today` on Store/UpdateJobRequest; toggle to Published blocked for expired jobs (web flash + API 422); `JobResource` exposes `closes_at` + `is_expired`.
- UI: native `type="date"` + `min=today` in create/edit; Expired badge priority on recruiter index/show; "Closing soon" chip on public cards + job detail; EN/FR keys (recruiter.php, jobs.php, validation.php).
- Live verified: expired job → web/API 404, absent from public listing; extend date → visible again; Expired badge on recruiter index.

### Application status timeline
- `application_status_events` append-only table (id, application_id FK cascade, changed_by_user_id FK nullOnDelete, from_status, to_status, created_at, index [application_id, created_at]); DB-facade backfill of one baseline event per existing application.
- `ApplicationStatusEvent` model; `Application::statusEvents()` ordered oldest; Eloquent `booted()` hooks: created → initial event (actor = auth or candidate fallback), updated → event only when status actually changed; null-safe `statusValue()` helper (fixes API apply 500 from firstOrCreate's in-memory null status).
- Eager-load `statusEvents.changedBy:id,name` in recruiter/candidate web + API controllers; `ApplicationResource` exposes `status_history` (whenLoaded; actor name only).
- Timelines rendered in recruiter + candidate application pages (`<ol data-application-status-timeline>`, aria-labelledby, translated labels, ISO datetime, translatedFormat dates); EN/FR incl. `status_withdrawn` (Withdrawn/Retirée).
- Live verified: candidate applies → Submitted/Pending; recruiter Shortlisted→Interview; both sides show all events in order; FR locale renders; 390px mobile no overflow.

## Incident: parallel-session deletion
The older candidate-journey session's fix subagent deleted the status_events files twice (12:22, 12:33), mistaking them for scope creep. Files recovered from `/tmp/Application.php.bak` + exact prior reads; full suite re-verified. Lesson: two Hermes orchestrators can touch this repo concurrently; check `~/.hermes/profiles/dev/cache/delegation/live/` manifests before assuming corruption.

## Final state
- Full suite: 228 tests / 1085 assertions green.
- `npm run build` clean, `npm audit --audit-level=high` 0 vulnerabilities, `git diff --check` clean.
- Docker rebuilt (`up -d --build laravel`), health OK, host/container manifests match (`app-O2RbyL4-.css`).
- Migration applied to live MySQL (`php artisan migrate --force` — required because Docker rebuild doesn't auto-migrate).
- Temp live-verify accounts/job/company cleaned up. No commits made.
