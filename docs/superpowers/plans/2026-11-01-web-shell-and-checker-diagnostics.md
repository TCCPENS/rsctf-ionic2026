# Implementation Plan: In-Browser Web SSH Terminal & SLA Checker Diagnostics

- **Spec:** `docs/superpowers/specs/2026-11-01-web-shell-and-checker-diagnostics-design.md`
- **Branch:** `main`
- **Workspace:** `.superpowers/sdd/2026-11-01-web-shell-and-checker-diagnostics`

---

## Global Constraints & Principles
1. Follow the inline `executing-plans` process: RED-GREEN TDD for every change, ledger progress, no unverified steps.
2. Security boundary: A participant must NEVER be able to open an exec session into a container belonging to another team.
3. Diagnostic sanity: Checker messages must be bounded (max 512 bytes), single-line, and sanitized to prevent memory exhaustion or XSS/injection.

---

## Task 1: Backend Scoped Exec & Target Authorization for Participants

**Files:**
- `src/hubs/container/scoped.rs`
- `src/hubs/container.rs`

- [ ] **Step 1: Write unit tests for participant scoped access and target isolation**
  Add unit tests in `src/hubs/container/scoped.rs` verifying:
  - An accepted participant in the game passes `game_access`.
  - An unaccepted or non-participating user is rejected by `game_access`.
  - A participant can only authorize targets belonging to their own team service.
  - Attempting to authorize another team's container returns `None`.

- [ ] **Step 2: Update `GAME_ACCESS_SQL` to admit accepted team members**
  In `src/hubs/container/scoped.rs`:
  Add `OR EXISTS (SELECT 1 FROM "Participations" p JOIN "TeamMembers" m ON m.team_id = p.team_id WHERE p.game_id = $1 AND m.user_id = $2 AND p.status = 1)` to `GAME_ACCESS_SQL`.

- [ ] **Step 3: Update `authorize_target` with user ownership enforcement**
  In `src/hubs/container/scoped.rs`:
  Update `authorize_target` to take `user_id` and `is_privileged` (admin or game manager).
  If not privileged, query `AdTeamServices` matching `service.container_id = $1 AND service.game_id = $2 AND p.team_id = (SELECT team_id FROM "TeamMembers" WHERE user_id = $3)`.
  Update callsites in `src/hubs/container.rs`.

- [ ] **Step 4: Verify with cargo test**
  Run: `cargo test --lib hubs::container`
  Expected: All tests pass.

- [ ] **Step 5: Commit backend authorization changes**
  Run: `git commit -am "feat(container-hub): allow participants scoped exec into their own team containers"`

---

## Task 2: Backend Checker Stderr Diagnostics & Ad/State DTO Extension

**Files:**
- `src/services/ad/engine/sandbox.rs`
- `src/services/ad/engine/checker.rs`
- `src/controllers/game/ad/state_tail.rs`
- `src/controllers/game/ad/scoreboard.rs`

- [ ] **Step 1: Write unit tests for checker stderr capture and diagnostic formatting**
  In `src/services/ad/engine/sandbox.rs` or `checker.rs`:
  Verify non-zero exit codes with stderr output capture the sanitized message.

- [ ] **Step 2: Pipe and capture stderr in `sandbox::run`**
  In `src/services/ad/engine/sandbox.rs`:
  Configure `cmd.stderr(Stdio::piped())`.
  Read up to 512 bytes from stderr on child exit.
  Return `SandboxOutcome::Exit(code, Option<String>)`.

- [ ] **Step 3: Format `last_check_message` in `checker.rs`**
  In `src/services/ad/engine/checker.rs`:
  Use the captured stderr if present, or fallback to `"checker exit {code}"`.

- [ ] **Step 4: Update `state_tail.rs` to select `result.message` and `service.container_id`**
  In `src/controllers/game/ad/state_tail.rs`:
  Select `result.message AS last_check_message` in the lateral join.
  Add `last_check_message: Option<String>` to `AdStateTailServiceRow` and `AdStateTailService`.

- [ ] **Step 5: Expose `container_id` and `last_check_message` in `AdTeamServiceStateModel`**
  In `src/controllers/game/ad/scoreboard.rs`:
  Add `pub container_id: Option<String>` and `pub last_check_message: Option<String>` to `AdTeamServiceStateModel`.
  Populate them in `scoreboard::state` handler.

- [ ] **Step 6: Verify backend with cargo check and tests**
  Run: `cargo check` and `cargo test --lib controllers::game::ad`
  Expected: Clean compilation and tests pass.

- [ ] **Step 7: Commit backend diagnostics and DTO changes**
  Run: `git commit -am "feat(ad-state): capture checker stderr diagnostics and expose container_id for web shell"`

---

## Task 3: Frontend Web Shell Modal & SLA Checker Diagnostics Alert

**Files:**
- `web/src/api/` (type definitions)
- `web/src/components/AdChallengePanel.tsx`
- `web/src/components/AdChallengePanel.test.ts`
- `web/src/locales/en-US/game.json`
- `web/src/locales/id-ID/game.json`

- [ ] **Step 1: Update TypeScript types for `AdTeamServiceStateModel`**
  Add `containerId?: string | null` and `lastCheckMessage?: string | null` to the model interface.

- [ ] **Step 2: Add TDD unit test in `AdChallengePanel.test.ts`**
  Write tests in `web/src/components/AdChallengePanel.test.ts` asserting:
  - When `lastCheckStatus` is `Mumble` or `Offline` and `lastCheckMessage` exists, the diagnostic alert is rendered.
  - When `containerId` is present, the "Web Shell" button is rendered.

- [ ] **Step 3: Update `AdChallengePanel.tsx`**
  Import `ContainerExecModal` from `@Components/admin/ContainerExecModal`.
  Add `terminalOpen` state.
  Add "Web Shell" button next to SSH command hint.
  Add diagnostic alert for non-Ok statuses displaying `service.lastCheckMessage`.

- [ ] **Step 4: Add localization strings**
  In `en-US/game.json` and `id-ID/game.json`:
  Add `'game.button.ad.web_shell'`: "Web Shell" / "Terminal Web"
  Add `'game.content.ad.checker_diagnostic'`: "Checker Diagnostic" / "Diagnostik Pemeriksa SLA"

- [ ] **Step 5: Run frontend test suite and type check**
  Run: `cd rsctf-ionic2026/web && pnpm check && node scripts/run-tests.mjs`
  Expected: All 781+ tests pass with zero type errors.

- [ ] **Step 6: Commit frontend changes**
  Run: `git commit -am "feat(ui): add Web Shell terminal button and SLA checker diagnostics in AdChallengePanel"`

---

## Task 4: Production Build, Server Deployment & Live Verification

**Files:**
- Deploy to: `103.148.19.64` (`/opt/rsctf/deploy/web-dist` and backend)

- [ ] **Step 1: Build frontend**
  Run: `cd rsctf-ionic2026/web && pnpm build`
  Expected: Vite build succeeds with zero errors.

- [ ] **Step 2: Push changes to remote repository**
  Run: `git push origin main`

- [ ] **Step 3: Deploy frontend to production server**
  Package `web/build`, transfer to server `103.148.19.64:/opt/rsctf/deploy/web-dist/`, and restart `rsctf-rsctf-1`.

- [ ] **Step 4: Live verification**
  - Verify `Ad/State` API endpoint returns `containerId` and `lastCheckMessage`.
  - Verify Web Shell button and Checker Diagnostic alert appear in challenge view.
