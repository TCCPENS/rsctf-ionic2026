# Design Specification: Live Attack Heatmap Matrix Screen

- **Date:** 2026-11-01
- **Author:** Fahmi & Codex
- **Target Platform:** `rsctf-ionic2026` (Frontend React + Vite / Backend Axum)
- **Status:** Proposed

---

## 1. Intent & The Why

In Attack-Defense CTF competitions (such as IONIC 2026 at PENS), spectators, organizers, and stage audiences need a clear, high-contrast, real-time visualization of "who is attacking whom" and which services are currently compromised. While the 3D globe arena (`/attack`) provides ambient cinematic lasers, it is complex and difficult to read at a quick glance on a stage projector.

The **Live Attack Heatmap Matrix Screen** provides an intuitive, real-time, 2D tactical grid specifically designed for stage projectors and operational monitoring.

---

## 2. Goals, Non-Goals, & Anti-Goals

### Goals
- **Real-Time Cross-Matrix (Team vs. Team)**: An $N \times N$ interactive grid showing which teams have successfully compromised which opponents.
- **Service SLA & Defense Grid**: A companion matrix view showing each team's per-service health (OK / Mumble / Offline) and defense standing.
- **Stage Projector Fullscreen Mode**: A 1-click toggle to enter a clean, distraction-free view hiding headers and footers with high-contrast typography readable from afar.
- **Live Reactive Animations**: Visual flash/glow on matrix cells whenever a new attack event arrives over WebSocket without full-page re-renders.
- **Light & Dark Mode Support**: Fully aligned with IONIC 2026 Royal Blue styling (`#2a42de` / `#0c1445`).

### Non-Goals
- Replacing the official A&D scoreboard (official epoch rankings and mathematical formulas remain on `/scoreboard`).
- Modifying backend scoring algorithms or database schemas.

### Anti-Goals
- Heavy 3D WebGL requirements that cause projector laptops or low-power display machines to lag or drop frames.
- Cluttered interface that is unreadable from 5–10 meters away on an auditorium projector.

---

## 3. Constraints
- **Routing**: Must follow the standard `pages/games/[id]/` file-based routing convention (`/games/:id/matrix`).
- **Data Transport**: Must reuse existing endpoints (`/api/game/{id}/ad/scoreboard`) and plain WebSocket mirror (`/hub/attack/ws?game={id}`).
- **Permissions**: Must be accessible to both participants and monitors/admins (`Role.User`).
- **Performance**: Cap live attack history at 100 recent events to prevent unbounded DOM memory growth during long competitions.

---

## 4. Approaches Considered & Trade-Offs

### Approach 1: Team-vs-Team Cross-Matrix Only
- *Pros*: Simple, classic CTF visual.
- *Cons*: Misses per-challenge service breakdown in multi-challenge competitions.

### Approach 2: Team-vs-Service Status Table Only
- *Pros*: Informative for service health.
- *Cons*: Lacks the excitement of competitive rivalry ("who attacked whom").

### Approach 3: Hybrid Matrix with Projector Mode (Selected)
- *Pros*:
  - Tabbed interface allowing users to switch between **Team vs. Team Cross-Matrix** and **Service SLA Matrix**.
  - Includes a dedicated **Projector Mode** button for full-screen auditorium displays.
  - Live attack ticker at the footer displaying real-time breaches with First Blood badges.
- *Cons*: Slightly larger component surface, but cleanly modularized.

---

## 5. Technical Implementation Details

### A. Route & Navigation Integration
- Add `matrix.tsx` in `web/src/pages/games/[id]/matrix.tsx`.
- Register the tab in `web/src/components/GameWorkspaceHeader.tsx`:
  - Path: `matrix`, Title: `Matrix`, Icon: `mdiGrid` / `mdiTableLarge`.
  - Filter: `Role.User` and active when the competition is ongoing or accessible.

### B. State & Real-Time Ingestion
- Ingest live events via WebSocket `ws://${host}/hub/attack/ws?game=${id}`:
  - When an attack event arrives (`kind: "attack"`):
    - Increment cell hits counter `matrix[attacker][victim] += 1`.
    - Mark the cell as `flashing = true` for 2 seconds (CSS keyframe transition).
    - Prepend event to the recent live attack ticker.
- Fallback / Hydration:
  - SWR poll of `/api/game/{id}/ad/scoreboard` every 10 seconds for baseline official evidence.

### C. UI & Visual Hierarchy
1. **Header Bar**:
   - Current Round & Epoch badge.
   - Sisa detik round timer with countdown alert.
   - Projector mode toggle (enter/exit browser fullscreen).
2. **Tab 1: Team-vs-Team Cross Grid**:
   - Rows: Attacking Teams.
   - Columns: Victim Teams.
   - Cells: Interactive badge with hit count, highlight on hover/click, and gold glow for First Blood.
3. **Tab 2: Service Health Grid**:
   - Rows: Teams.
   - Columns: Challenge Services.
   - Cells: Status badge (`OK` green, `Mumble` yellow, `Down` red) + defense integrity.
4. **Bottom Ticker**:
   - Live stream of recent attacks scrolling horizontally or stacked cards with timestamps.

---

## 6. What's Left for the Builder
- Implement `web/src/pages/games/[id]/matrix.tsx` and accompanying CSS module.
- Add the navigation tab in `GameWorkspaceHeader.tsx`.
- Verify TypeScript types (`pnpm check`) and run existing test suites (`node scripts/run-tests.mjs`).
- Build production assets (`pnpm build`) and deploy to the server.
