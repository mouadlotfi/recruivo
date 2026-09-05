# Recruivo Domain Context

Recruivo is a job platform: candidates find and apply to jobs, recruiters post
jobs and manage applications, admins operate the platform. One codebase serves
two HTTP surfaces (Inertia web app and a JSON API); the web app is the product,
the API is a parallel surface that currently serves only the search autocomplete.

## Core concepts

- **Job** — a published or draft role (`app/Models/Job`). Published jobs are
  visible, not expired, and optionally close on a date (`isPubliclyVisible`,
  `isClosingSoon`). A job belongs to a **Company** and a **Recruiter**.
- **Company** — an employer profile (mission, culture, size, logo). Public
  pages are `/companies/{slug}`.
- **Application** — a candidate's submission for a job, with a resume and cover
  letter. One application per (candidate, job). Status transitions
  (pending → shortlisted → interview → …) are recorded in `ApplicationStatusEvent`.
- **Candidate / Recruiter / Admin** — roles (`spatie/permission`). Candidates
  carry a `CandidateProfile` (resume, skills, preferences). Recruiters belong
  to a company.
- **Demo environment** — a seeded, periodically reset demo (`demo.recruivo.work`)
  with fictional data; production never seeds. Demo accounts are read-only where
  data changes would be destructive.

## Search

- **Search envelope** (`app/Services/SearchEnvelope.php`) — the single result
  type returned by `SmartSearchService::search()`. Carries ranked job and company
  collections plus the optional "did you mean" correction and derived counts.
- **`SmartSearchService`** (`app/Services/SmartSearchService.php`) — the search
  module. One deep interface; every search surface (the `/search` page, the
  autocomplete endpoint) consumes the same ranking answer. Behavior:
  - empty query + no filters → empty envelope
  - empty query + filters → DB-filtered, newest first, unscored
  - text query → weighted PHP scoring (title > company > category > location >
    remote_type > description) with synonym expansion and small-typo tolerance
  - zero results on a text query → suggested correction from the vocabulary
- Tuning lives in `config/search.php` (synonyms, weights, limits, typo distances).
- The jobs **index** (`/jobs`) is a browse surface (DB-paginated filters +
  candidate preference ordering), deliberately separate from ranked search.

## Serialization

- **`app/Support/JobCardSerializer`** — the single web shape for a job card,
  shared by jobs index, search, home, and company show. `is_saved`/`has_applied`
  come from `withExists` subqueries. A loaded `Company` may be passed to avoid a
  relation lookup.
- **`app/Support/CompanyCardSerializer`** — the single web shape for a company
  card (index grid + search grid). `latest_jobs` is passed by the caller (search
  passes none; the count still travels via `jobs_count`).
- The API surface has its own Eloquent resources (`app/Http/Resources/`) with a
  different shape; the two serialization worlds are not yet unified.

## Storage

- Application resumes land on the `private` disk under `config('filesystems.application_resumes')`
  (default `application-resumes`) — one source of truth for both the web and API
  application flows. Candidate profile resumes live under `resumes`.

## Architectural notes (recorded 2026-09-01)

- The web and API controller trees duplicate domain operations (apply to a job,
  job CRUD, profile, admin views) with divergent details. Deepening candidates:
  an application layer behind a seam with both surfaces as thin adapters, and
  shared serializers. Not yet implemented beyond the serialization seam above.
- `LocaleController::switch` rebuilds the previous page's route in the target
  locale (query strings preserved verbatim); a path-segment fallback covers
  non-localized previous pages. The Vue language toggle never hits this route —
  it rebuilds URLs client-side and only the cookie set by `SetLocale` middleware
  is shared.
