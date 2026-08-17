# Recruivo Migration Plan: Laravel 12 → 13 + Inertia + Vue 3 + TypeScript

Status: CHECKPOINTS D–G PASSED — Laravel 13 stable + Inertia/Vue foundation proven live. Page migration in progress. Updated 2026-08-16.

## PROGRESS

- ✅ Phase 0 baseline captured (260 tests / 1071 assertions on Laravel 12.66/PHP 8.2)
- ✅ Environment gate: PHP 8.4.24 (Docker base php:8.4-cli/apache), composer php ^8.3
- ✅ Phase 1 audits: 6 subagents (backend/frontend/tests-deps/loc-routing/auth-security/search-files) — all synthesized
- ✅ Phase 3 package compat: translatable 6.14.1 supports ^13 (stays ^6), collision 8.9.5 supports ^13 (stays ^8), scout/sanctum/permission/boost already L13-tolerant
- ✅ Phase 5: composer update → **Laravel 13.25.0, PHPUnit 12.5.33, tinker 3.0.2**; app image + test image rebuilt on PHP 8.4
- ✅ **CHECKPOINT D: full suite 260/1071 green on Laravel 13** (identical to baseline, zero regressions)
- ✅ Phase 6 foundation: inertiajs/inertia-laravel ^3.0 (v3.3.1), vue 3.5.41, @inertiajs/vue3 ^3.6.1, typescript ^7.0.2, @vitejs/plugin-vue ^6; tsconfig, app.ts bootstrap, env.d.ts, types/index.ts, AppLayout.vue, Pages/Home/Index.vue, inertia.blade.php root, HandleInertiaRequests middleware
- ✅ **CHECKPOINT F: foundation builds** (612 modules; Vue chunk + Inertia entry emitted)
- ✅ **CHECKPOINT G: hybrid Inertia proven live** — probe route returned data-page payload + X-Inertia JSON (200, version handshake works); probe removed after verification
- ✅ T1 (shell): AppLayout + Navigation/MobileNav/UserDropdown/NotificationCenter/ThemeToggle/ScrollToTop/FlashMessages + useTranslation/useDismiss + shell translations middleware — spec PASS, quality APPROVED, theme-persistence + query-string isActive fixes verified
- ✅ T2 (candidate applications): ApplicationController → Inertia::render('Candidate/Applications'), Applications.vue + CandidateApplicationCard/ApplicationStatusBadge/StatusTimeline/CoverLetterDisclosure, labels-prop translations, load-more preserveState — spec PASS, quality APPROVED, load_more_failed wired
- ✅ T3 (recruiter applications): controller migrated, Index.vue + RecruiterApplicationCard/ApplicationReviewPanel/ExpandedTextarea — spec PASS; quality REQUEST_CHANGES → a11y fixes (focus trap/scroll lock/window Escape; filter_applications aria-label) applied, re-review PASS
- ✅ T4 (public jobs index + show): JobController index/show → Inertia, Jobs/Index.vue + Show.vue + Components/Jobs/JobCard.vue, apply form with submission_token (forceFormData multipart), v-html ONLY for JobDescriptionFormatter output, X-Infinite-Scroll branch kept for Blade — spec PASS, quality APPROVED (minors fixed: Head title on index, teal category badge parity)
- ✅ T4b (companies index/show): CompanyController → Inertia, Companies/Index.vue + Show.vue + CompanyCard.vue, JobCard reuse for open positions, candidate-scoped is_saved/has_applied — spec PASS, quality APPROVED (minors fixed: dead applications_count COUNT subquery removed, whole-card focus ring matching Blade)
- ✅ T4c (search page): JobController::search → Inertia, Search/Index.vue, JobCard + CompanyCard reuse with DUAL pagination (jobs_page/companies_page), tabs/filters/correction/popular searches; live autocomplete deliberately deferred to follow-up; quality APPROVED, filter-only saved-state/applied fix re-reviewed PASS
- ✅ T5 (recruiter jobs CRUD): Recruiter/JobController index/create/show/edit → Inertia, Jobs/{Index,Create,Edit,Show}.vue + shared JobForm.vue; store/update/destroy/toggle behavior unchanged; translated published/closing dates — spec PASS after date-label fix, quality APPROVED
- ✅ T6 (profiles): ProfileController edit/preview → Inertia; Profile/Edit.vue includes candidate/recruiter forms, structured profile builder, preferences, uploads, password/email/delete settings; Profile/Preview.vue read-only recruiter view; Candidate/SavedJobs.vue paginated JobCard list with bookmark state; server serialization explicit and mutations/guards unchanged; build/audit/lint/container bake verified; legacy Blade assertions deferred to T9
- ⏭️ NEXT: T7 auth, T8 admin, T9 cleanup (remove legacy Alpine/Blade after all pages migrate; update old markup assertions)

## Verified external facts (lead agent, not from memory)

- Laravel 13.x requires PHP ^8.3 (release notes: 13.x = PHP 8.3–8.5, released 2026-03-17).
- laravel/framework latest: v13.25.0 (2026-08-11), requires php ^8.3.
- Official upgrade guide (https://laravel.com/docs/13.x/upgrade):
  - Dependencies: laravel/framework ^13.0, laravel/boost ^2.0, laravel/tinker ^3.0, phpunit/phpunit ^12.0, pestphp/pest ^4.0.
  - HIGH impact: CSRF middleware renamed VerifyCsrfToken → PreventRequestForgery + Sec-Fetch-Site origin verification (deprecated aliases kept). Check withoutMiddleware references in tests.
  - MEDIUM: cache config gains serializable_classes=false; upsert validates uniqueBy non-empty (MySQL/MariaDB).
  - LOW: cache/redis/session prefix hyphenation (env defaults may change cookie name); collection model serialization restores eager relations; Container::call nullable defaults; domain route precedence; JobAttempted event $exception payload; QueueBusy $connectionName; pagination view names; polymorphic pivot pluralization; session serialization json default (invalidates sessions — decide: keep php or accept re-auth); Str factories reset between tests; Js::from now JSON_UNESCAPED_UNICODE (app uses Js::from for Alpine note templates); Symfony polyfill-php85 global function conflicts (array_first/array_last); password reset subject change ("Reset your password").
- PHP 8.4.24 docker image verified runnable (php:8.4-cli).

## Environment decision (Checkpoint B)

Runtime container is PHP 8.2.33 (php:8.2-apache + php:8.2-cli Dockerfile stages); composer constraint ^8.2.
Decision (recommended default): upgrade Docker base images to php:8.4-* and composer php constraint to ^8.3.
Rationale: 8.4 is current stable, satisfies Laravel 13 (8.3–8.5) and Boost 2.x; 8.3 is the floor. One-way door? No — revertible by editing Dockerfile + constraint. No data change.

## Baseline (Phase 0) — captured before any change

- Tests: 260 test methods; full suite green 260 tests / 1071 assertions (harness /tmp/recruivo-test.sh, SQLite file DB).
- Build: npm run build green (Vite, app-*.css/js).
- Laravel 12 (`^12.0`), sanctum ^4, scout ^10, meilisearch-php ^1.6, spatie/permission ^6, spatie/translatable ^6.
- Dev: laravel/boost ^2.5 (ALREADY INSTALLED), pint ^1.13, phpunit ^11, collision ^8, mockery ^1.6.
- Dockerfile: node:20-alpine build stage, php:8.2-cli composer stage, php:8.2-apache production.
- Repo: single initial commit, tree intentionally dirty; no commits made by agents.

## Phase sequencing

0. Baseline snapshot — DONE (above).
1. Architecture audit — RUNNING (6 subagents, 2 batches).
2. Laravel 13 compat audit — merged into upgrade guide above + subagent reports.
3. Package compatibility — subagent C.
4. PHP runtime upgrade (8.4) + Dockerfile + composer constraint.
5. Composer update to Laravel 13 (framework ^13, boost ^2, tinker ^3, phpunit ^12), stabilize Blade/Alpine on 13.
6. Checkpoint D: full suite green on Laravel 13 before Inertia.
7. Install Inertia + Vue 3 + TS foundation.
8. Migrate shared shell, then pages progressively (candidate → recruiter → public → admin).
9. Remove Alpine only after all Blade pages migrated.
10. Final report.

## Decision log

- PHP 8.4 runtime: proceed (recommended default; user dialog unanswered, treating as consent to recommended path).
- No Vue Router, no Pinia unless a real need is documented.
- Keep Laravel-owned localization ({locale}/...), auth, roles, search (Scout/Meilisearch), validation.
- Do NOT remove Alpine while any Blade page depends on it.
- No destructive DB changes; no credentials needed so far.
