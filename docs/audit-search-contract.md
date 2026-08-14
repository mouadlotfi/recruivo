# Recruivo Search Backend — Implementation Contract

> Audit date: 2026-08-12 · Scope: `JobController::search`, `Api/SearchController`, `Api/JobController::index`, `Job`/`Company` models · Read-only audit

---

## 1. Current State Summary

### Active search endpoints (all identical pattern)

| Endpoint | File | Scope |
|---|---|---|
| `GET /search` (web) | `app/Http/Controllers/JobController.php:82` | Jobs + Companies |
| `GET /api/jobs` | `app/Http/Controllers/Api/JobController.php:15` | Jobs only |
| `GET /api/search/suggestions` | `app/Http/Controllers/Api/SearchController.php:12` | Jobs + Companies |
| `GET /jobs` (web index) | `app/Http/Controllers/JobController.php:11` | Jobs only |

### Core pattern (every endpoint)

```php
->where('column', 'like', "%{$query}%")
```

- No ranking — flat `WHERE` filter, results ordered only by `published_at`
- No typo tolerance — `like` is exact substring match
- No normalization — hyphen/word boundary issues (`front-end` ≠ `frontend`)
- No synonyms — "remote" ≠ "work from home", "eng" ≠ "engineer"
- No multiword scoring — single `like` per column, no term-frequency weighting

### Scout status

- `laravel/scout` + `meilisearch/meilisearch-php` installed
- `SCOUT_DRIVER=null` in both `.env` and `.env.example`
- `Job` model has `Searchable` trait + `toSearchableArray()` but **no call to `Job::search()` exists anywhere**
- `Company` model has **no** `Searchable` trait
- PHPUnit uses `SCOUT_DRIVER=collection` (in-memory fallback)

### Schema columns searched

**Jobs:** `title`, `description`, `location`, `category`, `remote_type`, `company.name` (via `orWhereHas`)
**Companies:** `name`, `location`, `tagline`, `mission`, `culture`

---

## 2. Findings & Severity

| # | Finding | Severity | Impact |
|---|---|---|---|
| F1 | Scout configured but unused — dead code path | Medium | `Job::toSearchableArray()` returns data never indexed or queried |
| F2 | Company model missing `Searchable` trait | Medium | Cannot search companies via Scout even if enabled |
| F3 | Four controllers duplicate LIKE search logic | Medium | Fix in one place ≠ fix everywhere; drift risk |
| F4 | No typo tolerance at all | High | "enginer" returns zero results for "Engineer" |
| F5 | No multiword ranking | High | "senior react developer" matches any single word equally |
| F6 | No normalization (hyphens, casing) | Medium | "front-end" ≠ "frontend", "Full-Stack" may not match "full stack" |
| F7 | `description` searched with LIKE on TEXT column | Low | Full-table scan on MySQL; slow on large datasets |
| F8 | `orWhereHas('company')` runs subquery per job | Low | N+1-adjacent performance on MySQL |

---

## 3. Implementation Contract

### 3.1 Guiding Principles

1. **Single search service** — one class, all endpoints delegate
2. **Portable fallback** — works on SQLite (dev/test) and MySQL (prod) without external services
3. **Progressive enhancement** — Scout/Meilisearch optional upgrade; LIKE fallback always works
4. **Bounded cost** — cap at 500 results, paginate properly

### 3.2 Architecture

```
app/Services/Search/
├── SearchService.php          ← facade: routes query → driver
├── Drivers/
│   ├── SearchDriver.php       ← interface
│   ├── SqliteMySqlDriver.php  ← LIKE-based with normalization + multiword scoring
│   └── MeilisearchDriver.php  ← Scout-backed (when enabled)
├── Normalizer.php             ← text normalization
├── QueryParser.php            ← tokenize, expand synonyms, build term list
├── RankingBuilder.php         ← weighted score for multiword queries
└── SynonymMap.php             ← synonym dictionary
config/search.php              ← synonyms, typo map, settings
```

### 3.3 Driver Interface

```php
interface SearchDriver
{
    public function searchJobs(string $query, array $filters, int $perPage, int $page): array;
    public function searchCompanies(string $query, int $perPage, int $page): array;
    public function suggestions(string $query, int $limit = 10): Collection;
}
```

`SearchService` selects driver at runtime based on `config('scout.driver')`.

### 3.4 SqliteMySqlDriver — Spec

#### Normalization

| Input | Output | Rule |
|---|---|---|
| `"Front-End"` | `"frontend"` | Lowercase, strip hyphens, collapse spaces |
| `"Work From Home"` | `"workfromhome"` | Same |
| `"Sr. Engineer"` | `"sr engineer"` | Strip trailing punctuation |

Apply `Str::ascii()` for Unicode, then normalize on both stored values and query.

#### Multiword Query Parsing

```
"senior react developer" → ["senior", "react", "developer"]
```

Build weighted scoring query:

```sql
SELECT *, 
  (CASE WHEN title LIKE '%senior%' THEN 10 ELSE 0 END
 + CASE WHEN title LIKE '%react%' THEN 10 ELSE 0 END
 + CASE WHEN description LIKE '%react%' THEN 3 ELSE 0 END
 + ...) AS relevance_score
FROM jobs
WHERE (title LIKE '%senior%' OR title LIKE '%react%' OR ...)
ORDER BY relevance_score DESC, published_at DESC
```

**Weight tiers:** Title 10× · Company name 8× · Category 5× · Description 3× · Location 2× · Remote type 1×

#### Synonym Expansion

Config file `config/search.php`:

```php
'synonyms' => [
    'frontend'  => ['front-end', 'front end', 'ui'],
    'backend'   => ['back-end', 'back end', 'server-side'],
    'fullstack' => ['full-stack', 'full stack', 'fullstack'],
    'javascript'=> ['js', 'ecmascript'],
    'remote'    => ['work from home', 'wfh', 'telecommute'],
    'engineer'  => ['developer', 'eng'],
    'devops'    => ['dev ops', 'site reliability', 'sre'],
],
```

**Boundary:** max 3 expansion terms per synonym group to prevent combinatorial explosion.

#### Typo Tolerance (portable)

1. **Zero-result retry** — if no results, retry with prefix-only `LIKE '{$term}%'`
2. **Known typo map** — pre-computed common typos in config
3. **Meilisearch path** — native typo tolerance when Scout driver = `meilisearch`

#### Company Search

Same pipeline, scoped to: `name` (10×), `tagline` (5×), `mission` (3×), `culture` (2×), `location` (1×).

### 3.5 Meilisearch Driver

When `SCOUT_DRIVER=meilisearch`:

1. Add `Searchable` trait to `Company`
2. Configure index settings:
   ```php
   $client->index('jobs')->updateSettings([
       'searchableAttributes' => ['title', 'company', 'category', 'description', 'location'],
       'rankingRules' => ['words', 'typo', 'proximity', 'attribute', 'sort', 'exactness'],
       'synonyms' => [/* same map */],
       'sortableAttributes' => ['published_at'],
   ]);
   ```
3. Calls `Job::search($query)` / `Company::search($query)` — Scout handles everything.

### 3.6 Controller Refactor Pattern

```php
public function search(Request $request)
{
    $results = app(SearchService::class)->searchJobs(
        query: $request->input('search', ''),
        filters: ['remote_type' => $request->input('remote_type'), 'location' => $request->input('location')],
        perPage: 12,
        page: (int) $request->input('jobs_page', 1),
    );
    // ... render
}
```

### 3.7 Performance Bounds

| Measure | Limit |
|---|---|
| Max total hits | 500 |
| Description search | Only when query has 3+ words |
| Synonym expansion | 3 terms per group max |
| `orWhereHas` | Replace with JOIN |
| LIKE wildcards | Use normalized prefix match where possible for index usage |

### 3.8 MySQL Indexes

```sql
ALTER TABLE jobs ADD INDEX idx_title_prefix (title(191));
ALTER TABLE jobs ADD INDEX idx_category (category);
ALTER TABLE jobs ADD INDEX idx_status_published (status, published_at);
ALTER TABLE companies ADD INDEX idx_name_prefix (name(191));
```

---

## 4. Edge Cases

| Case | Risk | Mitigation |
|---|---|---|
| Empty query + filters only | Returns all jobs | Skip scoring, apply filter WHERE only |
| Single-char query | Massive LIKE scan | Min 2 chars for LIKE; suggestions allow 1 char |
| SQL injection via `%`/`_` | LIKE pattern metacharacters | Escape `%` and `_` in user input |
| Unicode normalization | "résumé" ≠ "resume" | `Str::ascii()` before normalization; document limitation |
| SQLite vs MySQL case-sensitivity | `LIKE` differs by driver | `LOWER(column) LIKE LOWER(?)` explicitly |
| Meilisearch down | Scout throws exception | try/catch → fallback to SqliteMySqlDriver |

---

## 5. Migration Path (phased)

| Phase | Deliverable | Risk |
|---|---|---|
| P1 | Extract `SearchService` + `SqliteMySqlDriver` (normalization + multiword) | Low |
| P2 | Add `SynonymMap` + `QueryParser` | Low |
| P3 | Add typo fallback (zero-result retry) | Low |
| P4 | Add `Searchable` to `Company`, enable Meilisearch driver | Medium |
| P5 | MySQL FULLTEXT index on `description` | Medium |

---

## 6. Files Plan

### New
- `app/Services/Search/SearchService.php`
- `app/Services/Search/Drivers/SearchDriver.php`
- `app/Services/Search/Drivers/SqliteMySqlDriver.php`
- `app/Services/Search/Drivers/MeilisearchDriver.php`
- `app/Services/Search/Normalizer.php`
- `app/Services/Search/QueryParser.php`
- `app/Services/Search/RankingBuilder.php`
- `app/Services/Search/SynonymMap.php`
- `config/search.php`
- `tests/Unit/Services/Search/*Test.php`

### Modified
- `app/Http/Controllers/JobController.php`
- `app/Http/Controllers/Api/JobController.php`
- `app/Http/Controllers/Api/SearchController.php`
- `app/Http/Controllers/Api/CompanyController.php`
- `app/Models/Company.php` — add `Searchable` + `toSearchableArray()`
- `config/scout.php` — add fallback driver config
- `database/migrations/*_add_search_indexes.php` — MySQL prefix indexes
