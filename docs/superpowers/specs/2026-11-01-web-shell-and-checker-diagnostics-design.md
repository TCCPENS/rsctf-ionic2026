# Design Specification: In-Browser Web SSH Terminal & SLA Checker Diagnostics

**Feature 1:** In-Browser Web SSH Terminal (XTerm.js) for Challenge Containers  
**Feature 7:** SLA Checker Error Diagnostics Snippet in `AdChallengePanel`  
**Date:** 2026-11-01  
**Target:** `FahmiYoshikage/rsctf-ionic2026`  

---

## 1. Context & Motivation

During Attack-Defense (A&D) competitions:
1. **Terminal Obstacles:** Participants frequently struggle with local SSH key configuration (permissions `chmod 600`, OpenSSH path on Windows, or restricted outbound port 2222 on campus Wi-Fi). Having an in-browser web terminal (`xterm.js`) allows 100% of participants to immediately open a shell inside their team's container to view code and apply defense patches with zero local setup.
2. **SLA Opacity:** When participants patch a service, the checker may report `Mumble` or `Offline`. Without error diagnostics, participants cannot tell whether they broke a specific route (`/health`), altered database credentials, or introduced a syntax error. Capturing checker stderr and displaying it in `AdChallengePanel` gives participants instant feedback to debug their defense without leaking exploit secrets.

---

## 2. Architecture & Security Model

### 2.1 In-Browser Web Shell (`/hub/containerExec/games/{id}`)
- **SignalR WebSocket Hub:** RSCTF contains `ContainerExecHub` which attaches to Docker exec (`bollard`) with bidirectional stream pumping, PTY sizing, and base64 chunking.
- **Access Authorization Policy:**
  - `scoped::game_access`:
    - Admin or Game Manager $\rightarrow$ Allowed.
    - Regular User $\rightarrow$ Allowed IF user is a member of a team with `ParticipationStatus::Accepted` in the game.
  - `scoped::authorize_target`:
    - Admin or Game Manager $\rightarrow$ Can exec into any container in the game.
    - Participant (Role::User) $\rightarrow$ Can ONLY exec into `AdTeamServices.container_id` belonging to their own team (`participation.team_id`). Attempting to target another team's container is rejected with 403 Forbidden.
- **Frontend Exposure:**
  - In `Ad/State` API (`AdTeamServiceStateModel`), expose `container_id` for the team's own services.
  - In `AdChallengePanel.tsx`, add a **"Web Shell"** button next to the SSH hint.
  - Clicking "Web Shell" opens `ContainerExecModal` with `scopedGameId={gameId}` and `containerGuid={service.containerId}`.

### 2.2 SLA Checker Diagnostics
- **Checker Sandboxing (`src/services/ad/engine/sandbox.rs`):**
  - Pipe stderr from the child checker process.
  - Read up to 512 bytes of stderr if the process exits with a non-zero code.
  - Sanitize and format into `last_check_message`.
- **Database & State Tail (`AdCheckResults` & `Ad/State`):**
  - `AdCheckResults.message` already exists in PostgreSQL schema.
  - `state_tail.rs` query selects `result.message AS last_check_message` in the lateral join.
  - `AdTeamServiceStateModel` serializes `last_check_message`.
- **Frontend UI (`AdChallengePanel.tsx`):**
  - If `service.lastCheckStatus != 'Ok'` and `service.lastCheckMessage` is present, render a light-variant Alert box displaying the checker output in monospace font.

---

## 3. Scope & Interfaces

### Backend DTO (`AdTeamServiceStateModel`)
```rust
pub struct AdTeamServiceStateModel {
    // ... existing fields ...
    pub container_id: Option<String>,
    pub last_check_message: Option<String>,
}
```

### Frontend Type (`AdTeamServiceStateModel`)
```typescript
export interface AdTeamServiceStateModel {
    // ... existing fields ...
    containerId?: string | null
    lastCheckMessage?: string | null
}
```

---

## 4. Acceptance Criteria
1. Backend compiles cleanly with `cargo check` and passing tests.
2. Participants can connect to `/hub/containerExec/games/{gameId}` and open a shell into their own container.
3. Participants cannot open a shell into other teams' containers (denied by target authorization).
4. `AdChallengePanel` renders the "Web Shell" button which opens `ContainerExecModal`.
5. Non-Ok SLA statuses display the sanitized `lastCheckMessage` in `AdChallengePanel`.
6. Frontend passes `pnpm check`, `node scripts/run-tests.mjs`, and `pnpm build`.
