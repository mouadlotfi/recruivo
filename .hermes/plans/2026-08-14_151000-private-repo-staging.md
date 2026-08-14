# Private Staging Repo Before Public Push — Implementation Plan

> **For Hermes:** Execute this plan with direct terminal commands and verify each step against real output. This is a git/GitHub workflow, not a code task — no subagents needed. The working tree must be left clean of secrets and volume data.

**Goal:** Create a private GitHub repo as a staging target for the ~204 uncommitted changes, push there first for review, and push to the public repo (`origin`) only when the user is ready.

**Architecture:** Two-remote workflow. Keep `origin` = public `mouadlotfi/recruivo` untouched until the final step. Add `private` = new private repo `mouadlotfi/recruivo-staging`. Work happens on the existing `main` branch; the same commits get pushed to `private` first, then `origin`. Identical commit lineage on both repos (both start from `43d51d8`), so the public push is a fast-forward with zero divergence.

**Tech Stack:** git, GitHub CLI (`gh` — authed as `mouadlotfi`, scopes include `repo`), SSH protocol.

---

## Current Context (verified 2026-08-14)

- Repo: `/home/crate/Projects/portfolio-projects/recruivo`
- Git history: **one commit** `43d51d8 "first commit"` on `main`, tracking `origin/main` (public: `git@github.com:mouadlotfi/recruivo.git`)
- Working tree: **204 changed/untracked paths** (130 modified, 74 untracked) — all feature work from the prior sessions (job expiry, status timeline, interview mode, note templates, IT preferences + popup + recommendations, UI polish, dashboard banner/tips fix)
- `gh auth status`: logged in as `mouadlotfi`, token scope `repo` — can create private repos
- `.gitignore` already excludes `.env` (confirmed via `git check-ignore`)
- **⚠️ Risk: `data/` is untracked (89MB) and contains the live MySQL/Redis volume data + a DB backup.** It must NEVER be committed. Also untracked: `.hermes/` (agent workspace) and `dogfood-output/` (QA artifacts) — neither belongs in the repo.
- **Pending from prior turn:** the Docker container was mid-rebuild; the live app still serves the pre-edit dashboard (banner/tips change not visible until rebuilt). Verify this BEFORE committing so the pushed state is the verified state.

---

## Proposed Approach

1. Harden `.gitignore` against `data/`, `.hermes/`, `dogfood-output/` (safety net before any `git add .`)
2. Rebuild + live-verify the pending dashboard change (so the commit contains only verified work)
3. Commit the work in **4 logical commits** (grouped by feature) — or a single commit if the user prefers
4. `gh repo create mouadlotfi/recruivo-staging --private --remote private`
5. `git push -u private main` — **private first, nothing touches public**
6. Verify the private tree: visibility = private, HEAD matches, **no `data/`, no `.env`** in the pushed tree
7. When the user says go: `git push origin main` (fast-forward, same commits)
8. Document the ongoing two-remote workflow

**Tradeoffs considered:**
- *Private fork vs separate private repo:* a separate private repo as a second remote is cleaner — no fork relationship noise, `origin` stays the canonical public repo, and the staging repo can later be deleted or made public independently.
- *Private-as-origin vs two-remote:* flipping origin to private would make accidental public pushes impossible but complicates the mental model and any CI. Two-remote keeps `origin`'s meaning intact. Recommended.
- *Single vs logical commits:* history currently has one commit; logical commits make review and future cherry-picking easier. Default: 4 logical groups.

---

## Step-by-Step Plan

### Task 1: Harden `.gitignore` (safety net)

**Objective:** Guarantee the live DB volume and agent artifacts can never be staged.

**Files:**
- Modify: `.gitignore`

**Step 1:** Append to `.gitignore`:
```gitignore
# Local compose volume data (LIVE DB — never commit)
/data/
/dogfood-output/
/.hermes/
```

**Step 2:** Verify:
```bash
git status --porcelain | grep '^??' | grep -E '^(data/|\.hermes/|dogfood-output/)'
# Expected: NO output — all three now ignored
git check-ignore data/ dogfood-output/ .hermes/ 
# Expected: all three paths listed (exit 0)
```

**Step 3:** Commit the ignore change:
```bash
git add .gitignore
git commit -m "chore: ignore local volume data and agent artifacts"
```

---

### Task 2: Finish pending Docker rebuild + verify dashboard change live

**Objective:** The uncommitted work must be verified before it is pushed anywhere.

**Step 1:** Rebuild the container (resources/ is baked into the image):
```bash
docker compose build
docker compose up -d
docker compose ps
# Expected: app healthy
```

**Step 2:** Verify the dashboard change is live:
- Open `/en/dashboard` as a candidate with a complete profile → no completion banner
- Open with an incomplete profile → banner shown
- Tips for Success block absent
- (Manual browser check or curl the rendered HTML and grep for `tips_for_success` — expected absent)

**Step 3:** Run the full test suite (confirms the working tree is green before commit):
```bash
/tmp/recruivo-test.sh 2>&1 | tail -4
# Expected: OK (265 tests, 1167 assertions) or later count if more tests added
```

---

### Task 3: Commit the feature work (4 logical commits)

**Objective:** Capture the 204 paths in reviewable groups. Exact commands below; each commit must include ONLY its listed paths (no `git add .`).

**Step 1 — Foundation & data model** (job expiry, status timeline, migrations, services):
```bash
git add app/Enums app/Models app/Services app/Support app/Policies app/Providers app/Console app/Notifications
git add app/Http/Requests app/Http/Resources
git add app/Http/Controllers/Api app/Http/Controllers/Auth
git add database/migrations database/seeders
git add resources/lang
git commit -m "feat: job expiry, application status timeline, demo safeguards"
```
> **Note:** if any of these paths contain files from OTHER feature groups (e.g. a controller touched by both timeline and preferences work), prefer finer-grained adds: `git add app/Models/Job.php app/Models/Application.php ...` — list files explicitly rather than whole directories when grouping is ambiguous. Verify with `git status` after each commit that the expected files are staged.

**Step 2 — Recruiter tools** (interview mode, optional notes, note templates):
```bash
git add app/Http/Controllers/Recruiter app/Http/Controllers/Candidate
git add app/Models/RecruiterNoteTemplate.php
git add resources/views/recruiter
git add tests/Feature/InterviewModeTest.php tests/Feature/RecruiterNoteTemplateTest.php tests/Feature/RecruiterInterviewDetailsTest.php
git commit -m "feat: interview mode (online/onsite), optional notes, note templates"
```

**Step 3 — Candidate experience** (IT preferences, popup, recommendations, saved jobs, profile):
```bash
git add app/Enums/ItCategory.php app/Http/Controllers/HomeController.php app/Http/Controllers/JobController.php app/Http/Controllers/ProfileController.php
git add app/Http/Controllers/Candidate resources/views/candidate resources/views/jobs resources/views/profile resources/views/components
git add tests/Feature/CandidatePreferencesTest.php tests/Feature/SavedJobsTest.php
git commit -m "feat: IT job preferences, first-login popup, recommended jobs"
```

**Step 4 — UI polish & dashboard fix** (collapsed cover letters, autosize, clickable locations, banner/tips):
```bash
git add resources/views app/Http/Controllers/Candidate/DashboardController.php
git add resources/js tests/Feature/ApplicationUiPolishTest.php tests/Feature/CandidateProfileCompletionTest.php
git commit -m "feat: UI polish — collapsed cover letters, autosize textareas, clickable company locations, conditional completion banner"
```

**Step 5 — remaining infra/docs/loose files**:
```bash
git add Dockerfile .dockerignore compose.yaml compose.dev.yaml README.md docs
git add --all
git status
# Review that NOTHING from data/ .hermes/ dogfood-output/ appears
git commit -m "chore: container config and documentation"
```

**Step 6:** Verify the tree is clean and history is as expected:
```bash
git status            # Expected: nothing (clean)
git log --oneline     # Expected: first commit + 5 new commits
```

---

### Task 4: Create the private staging repo

**Objective:** New private repo, added as remote `private`, nothing pushed yet.

**Step 1:**
```bash
cd /home/crate/Projects/portfolio-projects/recruivo
gh repo create mouadlotfi/recruivo-staging --private --remote private
# Expected: "✓ Created repository mouadlotfi/recruivo-staging on GitHub"
```

**Step 2:** Verify remote:
```bash
git remote -v
# Expected: origin -> public repo; private -> git@github.com:mouadlotfi/recruivo-staging.git
```

**Step 3:** Confirm the repo is private via API:
```bash
gh api repos/mouadlotfi/recruivo-staging --jq '{visibility, private, default_branch}'
# Expected: {"visibility":"private","private":true,"default_branch":"main"}
```

---

### Task 5: Push to private and verify the pushed tree

**Objective:** The staging repo holds exactly the local history, with no volume data or secrets.

**Step 1:**
```bash
git push -u private main
# Expected: 5 new commits pushed
```

**Step 2:** Verify remote HEAD matches local:
```bash
git rev-parse main                       # local HEAD
git ls-remote private refs/heads/main    # private HEAD — must match exactly
```

**Step 3:** Verify no forbidden paths in the pushed tree:
```bash
git ls-tree -r --name-only private/main | grep -E '(^|/)data/|\.env|dogfood-output|\.hermes/' 
# Expected: NO output
```

**Step 4:** Confirm visibility remains private:
```bash
gh api repos/mouadlotfi/recruivo-staging --jq .visibility
# Expected: private
```

---

### Task 6: Push to public (only when the user approves)

**Objective:** The public repo receives the identical commits (fast-forward).

**Step 1:**
```bash
git push origin main
```

**Step 2:** Verify:
```bash
git ls-remote origin refs/heads/main    # must equal local main
git ls-remote private refs/heads/main   # must equal local main
# Both repos now point at the same commit — divergence is impossible
```

**Step 3:** Spot-check the public repo renders (optional):
```bash
gh api repos/mouadlotfi/recruivo --jq .default_branch
```

---

### Task 7: Document the ongoing workflow

**Objective:** The two-remote pattern is repeatable.

**Step 1:** Append to `README.md` (or a `docs/git-workflow.md`) a short section:
```markdown
## Staging workflow
- Work on `main` locally; commit logically.
- `git push private main` — push to the private staging repo (`recruivo-staging`).
- Review / demo / test against staging.
- `git push origin main` — release to the public repo.
```
**Step 2:** Commit + push the doc to private:
```bash
git add README.md docs
git commit -m "docs: document two-remote staging workflow"
git push private main
```

---

## Tests / Validation Summary

| Check | Command | Expected |
|---|---|---|
| No volume data staged | `git status --porcelain \| grep '^??'` | no `data/`, `.hermes/`, `dogfood-output/` |
| No secrets in tree | `git ls-tree -r --name-only private/main \| grep -E '\.env\|data/'` | empty |
| Suites green | `/tmp/recruivo-test.sh 2>&1 \| tail -4` | OK (265+, 1167+ assertions) |
| Private visibility | `gh api repos/mouadlotfi/recruivo-staging --jq .visibility` | `private` |
| Repos in sync | `git ls-remote private/main origin/main` vs `git rev-parse main` | all equal |
| Public untouched until Task 6 | `git ls-remote origin refs/heads/main` | still `43d51d8` before Task 6 |

---

## Risks, Tradeoffs & Open Questions

- **Critical:** `data/` (live DB + backups) and any `.env` must never be committed. Task 1's gitignore entries are the primary guard; Task 5 Step 3 is the verification. The live MySQL data also contains real accounts created during verification — keep it out of both repos, not just the public one.
- **Force-push discipline:** after Task 6 (public push), never `git rebase`/`--amend` and force-push to only one remote — the repos would diverge. Rewrites must be pushed to BOTH remotes together, or avoided entirely once public.
- **Commit grouping ambiguity:** some files (controllers, lang files) span multiple feature groups. The plan prefers explicit file lists over directory adds when ambiguous — the executor should check `git status` output between commits.
- **Private repo name:** `recruivo-staging` is the default; the user may prefer another name (e.g. `recruivo-private`). Trivial to change at creation time only — decide before Task 4.
- **Single vs logical commits:** if the user prefers one commit for simplicity (repo history is currently a single "first commit"), Task 3 collapses to one `git add --all` + commit — but ONLY after Task 1's gitignore safety net.
- **What about `public/build`?** Already gitignored (build artifacts) — the Docker image builds assets at build time; no action needed.
- **Later lifecycle:** the staging repo can be made public later (`gh repo edit --visibility public`), renamed, or deleted after the work is merged — deleting it does not affect local remotes beyond a stale `git remote remove private`.

---

## Execution Handoff

Plan complete and saved. Ready to execute — I'll run the tasks directly (they are git/GitHub commands with verification at each step, no code changes), starting with Task 1's gitignore safety net and the pending Docker rebuild, then the commit grouping, then private repo creation + push, and stopping before Task 6 (public push) for your explicit go-ahead. Shall I proceed?
