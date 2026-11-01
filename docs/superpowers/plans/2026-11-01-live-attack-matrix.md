# Live Attack Heatmap Matrix Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a real-time, projector-ready Attack-Defense Heatmap Matrix screen with Cross-Matrix (Attacker vs Victim), Service SLA Matrix, and live attack streaming.

**Architecture:** Create a new page `web/src/pages/games/[id]/matrix.tsx` supported by modular presentation components (`AttackCrossMatrix.tsx`, `ServiceSlaMatrix.tsx`, `MatrixTicker.tsx`, and `Matrix.module.css`). Real-time attack events stream via WebSocket (`/hub/attack/ws?game={id}`) with SWR baseline polling of `/api/game/{id}/ad/scoreboard`. Register the navigation tab in `GameWorkspaceHeader.tsx`.

**Tech Stack:** React 19, TypeScript, Mantine UI v7, `@mdi/js`, WebSockets, Day.js, Vite.

**Spec:** `docs/superpowers/specs/2026-11-01-live-attack-matrix-design.md`

## Global Constraints
- **Routing**: Follow file-based routing at `web/src/pages/games/[id]/matrix.tsx`.
- **Styling**: Use Mantine components and scoped CSS modules with contrast-safe tokens and PENS IONIC 2026 Royal Blue palette (`#2a42de` / `#0c1445`).
- **Data Transport**: Consume `api.game.useGameAdScoreboard(numId)` and plain WebSocket `/hub/attack/ws?game={id}`.
- **Buffer Safety**: Cap live attack history at 100 recent events to prevent unbounded DOM growth.
- **Internationalization**: Use `useTranslation` with English and Indonesian fallback keys.

## Review Focus
1. An event with zero teams or zero challenges renders a clean empty state instead of division-by-zero or empty table headers.
2. A WebSocket attack payload with missing or unknown victim/attacker team names is gracefully ignored without throwing errors or breaking the matrix grid.
3. Rapid succession of attack events on the same cell keeps the hits counter accurate and resets the flash animation cleanly.
4. Switching between Light and Dark mode maintains legible text contrast on colored badges and matrix cells.
5. Projector mode correctly enters/exits fullscreen across different browser engines without leaving the header/footer trapped.

---

### Task 1: Matrix Data Utilities & Unit Tests

**Files:**
- Create: `web/src/utils/matrixModel.ts`
- Create: `web/src/components/competition/AttackMatrix.test.ts`

**Interfaces:**
- Produces:
  - `buildTeamMatrix(teams: AdTeamScoreModel[], liveEvents: LiveMatrixAttackEvent[]): TeamMatrixData`
  - `calculateServiceHealth(service: AdServiceScoreModel): { status: string; color: string; label: string }`
  - `interface LiveMatrixAttackEvent { id: string; timestamp: number; attackerTeam: string; victimTeam: string; challengeTitle: string; blood: string }`

- [ ] **Step 1: Write the failing unit test in `web/src/components/competition/AttackMatrix.test.ts`**
  Assert matrix generation initializes $N \times N$ teams, correctly marks self-cells, aggregates attacks, and handles empty rosters gracefully.

- [ ] **Step 2: Run test to verify it fails**
  Run: `cd rsctf-ionic2026/web && node scripts/run-tests.mjs`
  Expected: FAIL with module not found or function undefined.

- [ ] **Step 3: Implement `web/src/utils/matrixModel.ts`**
  Implement matrix calculation, hits aggregation, and service status mappings.

- [ ] **Step 4: Run test to verify it passes**
  Run: `cd rsctf-ionic2026/web && node scripts/run-tests.mjs`
  Expected: PASS.

- [ ] **Step 5: Commit**
  ```bash
  git add web/src/utils/matrixModel.ts web/src/components/competition/AttackMatrix.test.ts
  git commit -m "feat(matrix): add matrix calculation model and unit tests"
  ```

---

### Task 2: Cross-Matrix, Service SLA Matrix, and Ticker Components

**Files:**
- Create: `web/src/components/competition/AttackCrossMatrix.tsx`
- Create: `web/src/components/competition/ServiceSlaMatrix.tsx`
- Create: `web/src/components/competition/MatrixTicker.tsx`
- Create: `web/src/styles/pages/Matrix.module.css`

**Interfaces:**
- Consumes: `TeamMatrixData`, `LiveMatrixAttackEvent` from `web/src/utils/matrixModel.ts`
- Produces:
  - `AttackCrossMatrix`: FC rendering the Attacker vs Victim $N \times N$ grid with cell flash keyframe.
  - `ServiceSlaMatrix`: FC rendering Teams vs Challenges SLA health and defense status.
  - `MatrixTicker`: FC rendering live recent attack cards with First Blood badges.

- [ ] **Step 1: Create `web/src/styles/pages/Matrix.module.css`**
  Define `.matrixGrid`, `.table`, `.cell`, `.cellSelf`, `.cellFlash`, `.projectorMode`, and high-contrast typography for stage displays.

- [ ] **Step 2: Implement `AttackCrossMatrix.tsx`**
  Implement sticky team headers, hit counter cells, first blood indicators, and cell flash animation when a live attack matches `(attacker, victim)`.

- [ ] **Step 3: Implement `ServiceSlaMatrix.tsx`**
  Implement the per-challenge status table showing SLA badges (OK/Mumble/Offline), Defense %, and per-service capture counts.

- [ ] **Step 4: Implement `MatrixTicker.tsx`**
  Implement the horizontal scrolling or responsive card stream for real-time attack events.

- [ ] **Step 5: Verify types with `pnpm check`**
  Run: `cd rsctf-ionic2026/web && pnpm check`
  Expected: Exit code 0.

- [ ] **Step 6: Commit**
  ```bash
  git add web/src/components/competition/AttackCrossMatrix.tsx web/src/components/competition/ServiceSlaMatrix.tsx web/src/components/competition/MatrixTicker.tsx web/src/styles/pages/Matrix.module.css
  git commit -m "feat(matrix): create Cross-Matrix, Service SLA grid, and live ticker components"
  ```

---

### Task 3: Assemble Matrix Page with Projector Mode & WebSocket Ingestion

**Files:**
- Create: `web/src/pages/games/[id]/matrix.tsx`

**Interfaces:**
- Consumes: `AttackCrossMatrix`, `ServiceSlaMatrix`, `MatrixTicker`, `useGameAdScoreboard`, `useGame`
- Produces: Route component for `/games/:id/matrix` wrapped in `WithGameTab`.

- [ ] **Step 1: Implement `web/src/pages/games/[id]/matrix.tsx`**
  - Integrate SWR `useGameAdScoreboard` for baseline statistics and team/challenge data.
  - Establish plain WebSocket connection to `/hub/attack/ws?game=${numId}` with auto-reconnect.
  - State management for active tab (`cross` vs `services`).
  - Fullscreen Projector Mode toggle using the HTML5 Fullscreen API with fallback container expansion.
  - Render top status bar (Round number, Tick countdown, Epoch progress, and Projector button).

- [ ] **Step 2: Type check and test execution**
  Run: `cd rsctf-ionic2026/web && pnpm check && node scripts/run-tests.mjs`
  Expected: All checks and tests PASS.

- [ ] **Step 3: Commit**
  ```bash
  git add web/src/pages/games/[id]/matrix.tsx
  git commit -m "feat(matrix): assemble live matrix page with projector mode"
  ```

---

### Task 4: Navigation Tab & i18n Integration

**Files:**
- Modify: `web/src/components/GameWorkspaceHeader.tsx`
- Modify: `web/src/locales/en-US/game.json`
- Modify: `web/src/locales/id-ID/game.json`

**Interfaces:**
- Updates `pages` array in `GameWorkspaceHeader.tsx` to include `matrix`.

- [ ] **Step 1: Update `GameWorkspaceHeader.tsx`**
  Add `{ icon: mdiTableLarge, title: t('game.tab.matrix', 'Matrix'), path: 'matrix', link: 'matrix', requireRole: Role.User }`.

- [ ] **Step 2: Update locale files**
  Add `'game.tab.matrix'` translations for `en-US` ("Attack Matrix") and `id-ID` ("Matriks Serangan").

- [ ] **Step 3: Run full tests**
  Run: `cd rsctf-ionic2026/web && node scripts/run-tests.mjs`
  Expected: PASS.

- [ ] **Step 4: Commit**
  ```bash
  git add web/src/components/GameWorkspaceHeader.tsx web/src/locales/en-US/game.json web/src/locales/id-ID/game.json
  git commit -m "feat(nav): add Matrix tab to game workspace header and localized strings"
  ```

---

### Task 5: Production Build & Deployment to Server

**Files:**
- Deploy to: `/opt/rsctf/deploy/web-dist` on `103.148.19.64`

- [ ] **Step 1: Run production build**
  Run: `cd rsctf-ionic2026/web && pnpm build`
  Expected: Clean Vite build with zero errors.

- [ ] **Step 2: Push git commit to remote**
  Run: `git push origin main`

- [ ] **Step 3: Deploy tarball to server and reload container**
  Package `web/build`, transfer to server `103.148.19.64:/opt/rsctf/deploy/web-dist/`, and restart `rsctf-rsctf-1`.

- [ ] **Step 4: Verify live endpoint**
  Curl `https://taniahacker.tccpens.id/games/4/matrix` and verify HTTP 200.
