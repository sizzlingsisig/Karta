# Software Requirements Specification (SRS)

Project: KARTA — Project Management Platform
Version: 0.1 (Draft)
Date: 2026-03-18

## 1. Introduction

### 1.1 Purpose
This SRS describes the software requirements for KARTA, a Kanban-first project management platform that combines tactile visual design with disciplined issue tracking and an integrated collaborative Wiki.

### 1.2 Intended audience
- Product managers
- Engineers (backend, frontend, DevOps)
- QA engineers
- Designers
- Stakeholders and project leads

### 1.3 Scope
KARTA v1.0 is a web application providing workspace-scoped Kanban boards, immutable sequential ticket indexing (K-Index), an integrated Markdown Wiki, member and role management, notifications (in-app, email, webhooks), and search. Native mobile apps, advanced enterprise features, and billing are out of scope for v1.0.

## 2. Overall Description

### 2.1 Product perspective
KARTA is a single product composed of Workspaces. Each Workspace contains one Kanban board and one Wiki in v1.0. The application is multi-tenant (workspace scoped) with shared platform services (auth, notifications, search, attachments storage).

### 2.2 User classes and characteristics
- Owner: full admin of a workspace.
- Admin: manage members and workspace-level settings.
- Member: create and edit cards and wiki pages.
- Viewer: read-only access.
- External consumer: receives notifications via configured webhooks (Slack/Discord) or email.

### 2.3 Operating environment
- Web browsers: latest two versions of Chrome, Firefox, Safari, Edge.
- Backend: PHP 8.4 / Laravel v12 (per project conventions).
- Frontend: responsive web targeting desktop (1280px+) and tablet (768px).

### 2.4 Design and implementation constraints
- Use existing project conventions and packages listed in repository documentation.
- Data encryption at rest (AES-256) and TLS 1.3 for transport.
- Attachment limits: 25MB per file, 500MB per workspace for v1.0.

## 3. Functional Requirements

Each requirement has an ID (FR-*) to aid traceability.

### 3.1 Workspace Management
- FR-W1: Create Workspace — Owners can create a workspace with a name, slug, visibility (Private/Public), and optional K-Index prefix.
- FR-W2: Manage Members — Owners/Admins can invite, remove, and change roles of members.

### 3.2 Kanban Board
- FR-B1: Default Columns — A new workspace is initialized with Backlog, In Progress, For Review, Done.
- FR-B2: Column CRUD — Users with Member+ privileges can add, rename, reorder, collapse, and delete columns.
- FR-B3: Card CRUD — Members can create, view, edit, move, and delete cards.
- FR-B4: Drag-and-drop — Cards may be moved between columns via drag-and-drop with latency target <100ms perceived.
- FR-B5: Card Fields — Each card stores K-Index, Title, Description (Markdown/rich text), Assignees, Priority, Status, Due Date, Labels, Attachments, Comments, Linked Cards, Activity Log.

### 3.3 K-Index Sequential Indexing
- FR-K1: Auto-assign Index — When a card is created, the system assigns the next sequential numeric index for that workspace and prepends the workspace prefix (default `K-`).
- FR-K2: Immutable — Once assigned, a K-Index is never reassigned even if a card is deleted (soft-delete recovery window 30 days).
- FR-K3: Workspace-scoped — Each workspace maintains an independent sequence starting at 001 by default.

### 3.4 Wiki
- FR-WK1: Page Tree — Each workspace has a hierarchical Wiki (pages and sub-pages).
- FR-WK2: Markdown Editor — Pages are authored in Markdown with live preview and Table of Contents generation.
- FR-WK3: Card Embeds — `[[K-<number>]]` inline syntax renders a live card preview chip linking to the card.
- FR-WK4: Auto-save — Pages are autosaved; last edited metadata is shown.

### 3.5 Search
- FR-S1: Global Search — Keyboard shortcut (Ctrl/Cmd+K) opens search across cards, wiki pages, and members.
- FR-S2: Filters — Search supports entity-type filtering and card-specific filters (status, assignee, priority, label).

### 3.6 Notifications & Activity
- FR-N1: In-app feed — Users receive a notification feed for triggers: assigned, mentioned, due soon, card moved, comment added, watched wiki edited.
- FR-N2: Email digest — Configurable per user: real-time, daily digest, or off.
- FR-N3: Webhooks — Workspace-level Slack/Discord webhooks can be configured for activity notifications.

### 3.7 Authentication & Onboarding
- FR-A1: Sign-up / Sign-in — Email/password and OAuth (Google) authentication.
- FR-A2: Onboarding flow — After account creation, prompt to create a workspace and optionally invite teammates.

### 3.8 Attachments and Comments
- FR-AT1: File upload — Attachments up to 25MB accepted; workspace storage quotas enforced.
- FR-AT2: Comments — Threaded comments per card with `@mentions` resolving to members.

## 4. Non-Functional Requirements

### 4.1 Performance
- NFR-P1: Initial page load TTI < 3s on standard broadband.
- NFR-P2: Card drag-and-drop perceived latency < 100ms.
- NFR-P3: Search results returned < 500ms from keypress.
- NFR-P4: Real-time wiki sync propagation < 1s (best-effort for v1.0; CRDT optional per architecture decision).

### 4.2 Reliability & Availability
- NFR-R1: Soft-delete retention for recoverability: 30 days for cards.
- NFR-R2: Backups: daily backups for critical workspace data (cards, wiki, users, attachments metadata).

### 4.3 Security
- NFR-S1: TLS 1.3 for all transport.
- NFR-S2: AES-256 encryption for data at rest.
- NFR-S3: Role-based access control for workspace resources.
- NFR-S4: Input validation and sanitization for Markdown, attachments, and user input to prevent XSS and injection.

### 4.4 Scalability
- NFR-SC1: Support workspaces sized 1–50 users efficiently in v1.0. Avoid assumptions for >500 users (enterprise).

### 4.5 Accessibility
- NFR-A11: WCAG 2.1 AA baseline; keyboard navigation for Kanban interactions; screen reader support for card metadata.

## 5. External Interfaces

### 5.1 User Interfaces
- Web UI: Desktop-first responsive web application (1280px+ primary).

### 5.2 APIs
- Internal REST/GraphQL endpoints for frontend—design to follow existing project conventions. Public API and webhooks for third-party integrations are out-of-scope for v1.0, except incoming/outgoing webhooks for Slack/Discord notifications.

### 5.3 Authentication Interfaces
- OAuth 2.0 (Google) for social sign-on and local email/password using secure password storage.

### 5.4 Storage
- Object storage for attachments (S3-compatible) and relational DB for metadata (cards, users, wiki, indexes).

## 6. Data Requirements

- DR-1: K-Index sequence store per workspace must be atomic and durable to avoid duplication or gaps.
- DR-2: Attachments metadata persisted in DB; file content in object storage.
- DR-3: Activity logs immutable and timestamped; kept for auditing per workspace.

## 7. Constraints, Assumptions, and Dependencies

- C-1: v1.0 is single-board-per-workspace (change only if Product decides otherwise).
- C-2: Real-time collaborative editing (CRDT) is an optional architecture decision; WMS may choose LWW autosave if CRDT is deferred.
- A-1: Users will have modern browsers supporting required web APIs.
- D-1: Depends on third-party services for OAuth (Google), email delivery, and optional object storage.

## 8. Quality Attributes and Acceptance Criteria

- AC-1: Create a workspace, create 20 cards, move them across columns, and verify K-Index uniqueness and immutability.
- AC-2: Upload attachments up to 25MB and verify they are retrievable and counted against quota.
- AC-3: Search returns results within 500ms for typical datasets in staging environment.
- AC-4: Verify basic WCAG keyboard interactions for board navigation.

## 9. Traceability Matrix

This SRS maps to PRD features. Key mappings:
- PRD — Manila Kanban Board → FR-B1..FR-B5
- PRD — K-101 Sequential Indexing → FR-K1..FR-K3
- PRD — Integrated Wiki → FR-WK1..FR-WK4
- PRD — Workspaces & roles → FR-W1, FR-W2
- PRD — Notifications & Activity → FR-N1..FR-N3

## 10. Future Considerations (v2+)

- Multiple Kanban boards per workspace.
- Advanced page-level permissions for Wiki.
- Native mobile applications.
- Public API and richer webhooks for automation.
- Usage-based billing and subscription management.

## 11. Appendix

### Glossary
- K-Index: Workspace-scoped, sequential ticket identifier (e.g., K-101).
- Workspace: Top-level container for a project's board, wiki, and members.

### Revision History
- v0.1 — 2026-03-18 — Initial draft derived from PRD.md
