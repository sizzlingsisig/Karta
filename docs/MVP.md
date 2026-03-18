# KARTA — MVP Plan (Lab Goal)

Version: 0.1 (Lab)  Date: 2026-03-18

Purpose: define a minimal, testable MVP for the lab that delivers the core KARTA experience: workspace-scoped Kanban with atomic K-Index, a simple integrated Wiki, basic auth, and searchable records.

Scope (MVP):
- Single board per workspace with default columns: Backlog, In Progress, For Review, Done.
- Card CRUD with workspace-scoped, sequential K-Index allocation (no gaps, atomic).
- Basic board UI: list view and drag-and-drop between columns (desktop only).
- Wiki: page tree, Markdown authoring, `[[K-<number>]]` card embeds (live link to card view).
- Authentication: Email/password + Google OAuth (optional if lab time limited).
- In-app notifications for assignments and mentions (no email/webhook required for MVP).
- Search: workspace-scoped search for cards and wiki titles/bodies (simple Meilisearch or DB full-text fallback).
- Attachments: support file uploads via pre-signed URLs with 25MB file limit (optional: defer to v1 if time constrained).

Out-of-scope for MVP:
- CRDT real-time wiki editing (LWW autosave is acceptable).
- Advanced role permissions, billing, native mobile apps, external webhooks, and full enterprise scaling.

Success Criteria / Acceptance Tests:
- AC-1: Create a workspace, create 10 cards — K-Index values are sequential (K-001..K-010) and immutable.
- AC-2: Move cards across columns via drag-and-drop; board state persists and is correct after refresh.
- AC-3: Create, edit, and view wiki pages; embedding `[[K-<number>]]` in a page renders a link/chip to the card.
- AC-4: Search returns matching cards and wiki pages within acceptable latency on local/staging instance.
- AC-5: Basic in-app notifications appear when assigned or @mentioned on a card.

Milestones (4-week lab plan):
- Week 0 — Repo setup & infra: dev environment, database, Redis, Meilisearch (or DB full-text), S3-compatible storage emulator.
- Week 1 — Auth, Workspace model, and `workspace_sequences` migration; seed workspace and test K-Index allocation service with unit tests.
- Week 2 — Card API, `KIndexAllocator` integration, Card listing, basic board UI (create/edit/move), soft-delete behavior.
- Week 3 — Wiki CRUD, Markdown rendering, `[[K-<number>]]` embedding, search indexing for cards & wiki.
- Week 4 — Notifications (in-app), end-to-end tests, polish UI interactions, demo preparation and recorded walkthrough.

Deliverables for the lab demo:
- Running instance (local or staging) with one demo workspace and sample data.
- Short demo script (5–8 minutes) showing: create workspace, create 3 cards, show K-Index, move cards, create wiki with embedded card, search for a card, and show notification.
- Unit test for concurrent card creation demonstrating K-Index uniqueness.
- README with local setup steps and demo commands.

Minimal Tech Checklist:
- Backend: Laravel v12, PHP 8.4
- DB: Postgres (local) or SQLite for simplified local tests
- Queue: Redis (local) for dev
- Search: Meilisearch dev or Postgres full-text fallback
- Storage: Local disk or MinIO for presigned URL flow
- Frontend: Livewire components + lightweight SPA pages (or React for board UI)

Demo Script (5–8 minutes):
1. Start app and navigate to landing page; sign up and create a workspace.
2. Create three cards — show K-Index values and card details.
3. Drag card between columns and refresh to show persistence.
4. Open Wiki, create a page containing `[[K-002]]`, save and click the embedded chip to open the card.
5. Search by card title and by wiki content; show results.
6. Assign user to a card and post an `@mention` in a comment — show in-app notification.

Metrics to report for lab:
- K-Index allocation correctness under concurrency (unit/integration tests). 
- Basic latency numbers for card create and search on the lab environment.

Next steps I can do for you:
- generate the `migrations` and `KIndexAllocator` skeleton, or
- create the README with local-dev run steps and demo commands, or
- scaffold the demo workspace with sample data. 
Tell me which one to generate next.
