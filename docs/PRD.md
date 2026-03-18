# Product Requirements Document
# KARTA — Project Management Platform

**Version:** 0.1 (Draft)
**Status:** Pre-Alpha
**Last Updated:** March 18, 2026

---

## 1. Executive Summary

KARTA is a high-fidelity project management platform that digitizes the tactile, high-stakes energy of collaborative student and team projects. By reimagining the iconic "Group 1 Manila paper" experience — the collective chaos of markers, sticky notes, and accountability — KARTA delivers a professional workspace where deep technical execution meets nostalgic classroom aesthetics.

Positioned as the middleground between Trello's simplicity and Jira's power, KARTA is built for teams that have outgrown Trello's surface-level tracking but find Jira's complexity alienating. It is the tool for the group that takes their work seriously.

---

## 2. Problem Statement

### 2.1 The Gap in the Market

Current project management tools force a binary choice:

- **Trello** is fast and visual, but shallow. It lacks issue tracking rigor, accountability structures, and documentation integration. Teams hit a ceiling quickly.
- **Jira** is powerful and precise, but dense. Its configuration overhead, steep learning curve, and sterile interface create friction that slows down smaller, agile teams.

No tool exists that is simultaneously **approachable in its interface**, **rigorous in its structure**, and **meaningful in its aesthetic identity**.

### 2.2 The Target Pain

Students, young professionals, and small-to-medium teams routinely manage high-stakes collaborative work — academic projects, startup MVPs, design sprints — using tools that either underserve them or overwhelm them. The result is task drift, accountability gaps, and documentation that lives in no single place.

---

## 3. Goals & Non-Goals

### 3.1 Goals

- Provide a Kanban-first project workspace with physical, tactile visual metaphors.
- Implement a sequential, workspace-scoped ticket indexing system (K-101 style) for professional task tracking.
- Integrate a Wiki module for co-authored, Markdown-rendered documentation that presents as formal group reports.
- Deliver a product experience that feels intentional, not generic — the interface itself communicates that this team means business.
- Be functional for teams of 1–50 with no configuration required out of the box.

### 3.2 Non-Goals (v1.0)

- KARTA is not a time-tracking tool in v1.0.
- KARTA is not a client-facing portal or billing tool.
- KARTA does not replace communication platforms (Slack, Discord). It integrates with them.
- KARTA does not target enterprise-scale deployments (>500 users) in v1.0.
- Native mobile apps are out of scope for v1.0; responsive web is the target.

---

## 4. Target Users

### 4.1 Primary Personas

**The Scrappy Student Team**
Academic project groups (3–6 members) working on capstone projects, thesis, or competition outputs. They are accustomed to Manila paper, sticky notes, and shared Google Docs. They want structure but not bureaucracy. They value aesthetics and take pride in their work.

**The Early-Stage Startup Squad**
Small founding teams (2–10 members) who need to move fast but maintain clarity. They've tried Trello and hit its ceiling. They've looked at Jira and been scared away. They want the discipline of issue tracking without the enterprise overhead.

**The Indie Creative Team**
Freelancers and small agencies managing project deliverables for clients. They need transparency, a clear paper trail, and documentation — without needing a project manager to configure their tools.

### 4.2 Secondary Personas

**The Faculty Advisor / Team Lead**
Someone who needs visibility into a team's progress without being an active contributor. Primarily a consumer of the board and wiki, not a task creator.

**The Solo Power User**
An individual using KARTA as a personal project management system, leveraging its indexing and wiki for structured self-management.

---

## 5. Core Features

### 5.1 The Manila Kanban Board

The primary workspace. A full-width, horizontally scrollable board where tasks exist as physical paper cards with material depth — subtle shadows, paper-grain textures, and border treatments that evoke the feeling of a card pinned to a corkboard.

**Board Structure**
- Boards are organized into **Columns** (swimlanes). Default columns: `Backlog`, `In Progress`, `For Review`, `Done`.
- Columns are fully customizable: rename, reorder, add, delete.
- Each board belongs to a single **Workspace**.

**Card Anatomy**
Each card represents a single task or issue and contains:

| Field | Description |
|---|---|
| **K-Index** | Auto-generated sequential ID (e.g., `K-101`, `K-102`). Scoped per workspace. Never reused. |
| **Title** | Short, imperative task name. |
| **Description** | Rich text / Markdown body for detailed context. |
| **Assignees** | One or more workspace members. |
| **Priority** | `Critical`, `High`, `Medium`, `Low`. Displayed as a color-coded label. |
| **Status** | Mirrors the column the card is in. Can also be manually set. |
| **Due Date** | Optional calendar date with overdue state indicators. |
| **Tags / Labels** | User-defined color labels for categorical filtering. |
| **Attachments** | File and image uploads. |
| **Comments** | Threaded discussion per card. Supports `@mentions`. |
| **Linked Cards** | Dependency references (blocks / blocked by). |
| **Activity Log** | Immutable, timestamped record of all changes to the card. |

**Board Interactions**
- Drag-and-drop card movement between columns.
- Quick-add card from column header.
- Card filtering by assignee, priority, label, and due date.
- Board-level search.
- Collapsed column mode for focus.

---

### 5.2 K-101 Sequential Indexing

Every card created in a workspace receives a permanent, sequential K-Index. This index is:

- **Workspace-scoped**: `K-001` in Workspace A is independent of `K-001` in Workspace B.
- **Sequential**: Assigned in the order of card creation. No gaps, no duplicates.
- **Immutable**: Once assigned, an index is never reassigned, even if the card is deleted.
- **Universal**: Used across the Kanban board, Wiki links, notifications, and search.

The K-Index serves as the canonical way to reference any task in KARTA. Teams can say "Did you check K-047?" and everyone knows exactly where to look.

**Index Prefix Customization**
Workspaces can define a custom prefix (e.g., `KARTA-`, `PROJ-`, `TS-`) that prepends the numeric index. Default prefix is `K-`.

---

### 5.3 The Integrated Wiki

A documentation hub living alongside the Kanban board. The Wiki is designed to render Markdown as **formal, paper-style group reports** — the digital equivalent of a well-formatted Manila paper submission.

**Wiki Structure**
- The Wiki is organized as a **page tree**: a hierarchy of pages and nested sub-pages.
- Each Workspace has one Wiki.
- Pages are authored collaboratively with real-time co-editing (conflict resolution via operational transforms or CRDT).

**Page Anatomy**

| Element | Description |
|---|---|
| **Title** | The page heading, displayed in the sidebar tree and page header. |
| **Body** | Full Markdown editor with live preview. Supports headers, lists, tables, code blocks, and blockquotes. |
| **Breadcrumb** | Shows the page's position in the hierarchy. |
| **Last Edited** | Author name and timestamp of the last change. |
| **Table of Contents** | Auto-generated from heading structure. |
| **Linked Cards** | Inline `[[K-101]]` syntax to embed a live card preview from the Kanban. |

**K-Index Card Embedding**
Users can reference any card inline using double-bracket syntax: `[[K-101]]`. This renders as a live, non-editable card chip showing the K-Index, title, assignee, and current status. Clicking navigates to the card.

**Wiki Permissions**
- By default, all workspace members can view and edit wiki pages.
- Page-level permissions (view-only for specific roles) are a v2 feature.

---

### 5.4 Workspaces

A Workspace is the top-level container in KARTA. It holds a Kanban board, a Wiki, and a set of members.

| Property | Description |
|---|---|
| **Name** | Display name of the workspace (e.g., "Thesis Group 7"). |
| **Slug / Handle** | URL-safe identifier used in routing. |
| **K-Index Prefix** | Custom ticket prefix (default: `K-`). |
| **Members** | List of users with assigned roles. |
| **Visibility** | `Private` (invite only) or `Public` (view-only for anyone with link). |

**Workspace Roles**

| Role | Permissions |
|---|---|
| **Owner** | Full admin. Can delete workspace, manage billing, and assign roles. |
| **Admin** | Can manage members, settings, and all content. |
| **Member** | Can create, edit, and move cards. Can edit wiki. Cannot manage workspace settings. |
| **Viewer** | Read-only access to board and wiki. Cannot create or edit content. |

---

### 5.5 Notifications & Activity

**Notification Triggers**
- Assigned to a card.
- Mentioned via `@username` in a comment or description.
- Card due date approaching (24 hours prior).
- Card moved to a new column.
- Comment added to a card the user is assigned to or watching.
- Wiki page edited (for pages the user is watching).

**Notification Channels**
- In-app notification bell with a feed of recent activity.
- Email digest (configurable: real-time, daily, or off).
- Slack / Discord webhook integration (workspace-level setting).

---

### 5.6 Search

Global, workspace-scoped search accessible via keyboard shortcut (`Cmd/Ctrl + K`).

**Searchable Entities**
- Cards (by K-Index, title, description, comments)
- Wiki pages (by title and body content)
- Members (by name)

**Search Filters**
- Filter by entity type (cards, wiki, members).
- Filter cards by status, assignee, priority, and label.

---

## 6. User Flows

### 6.1 Onboarding Flow

1. User signs up with email or OAuth (Google).
2. User is prompted to create their first Workspace (name + optional prefix).
3. Default Kanban columns are pre-populated: `Backlog`, `In Progress`, `For Review`, `Done`.
4. User is offered an optional invite step to add teammates by email.
5. User lands on the empty Kanban board with a "Create your first card" prompt.

### 6.2 Card Lifecycle

1. User creates a card from the `Backlog` column.
2. K-Index is auto-assigned (e.g., `K-001`).
3. User fills in title, description, assignees, priority, and due date.
4. Team works the card through columns: `Backlog` → `In Progress` → `For Review` → `Done`.
5. Card remains in `Done` as a permanent, searchable record.

### 6.3 Wiki Documentation Flow

1. User navigates to the Wiki tab within their Workspace.
2. User creates a new page (e.g., "Project Proposal").
3. User writes content in Markdown, referencing relevant cards with `[[K-003]]` syntax.
4. Page is saved automatically and visible to all workspace members.
5. Page can be organized under a parent page in the hierarchy.

---

## 7. Technical Requirements

### 7.1 Platform

- **Web application** (primary). Responsive design targeting 1280px+ desktop and 768px tablet.
- **Browser Support:** Latest two versions of Chrome, Firefox, Safari, and Edge.
- **Mobile Web:** Functional but not the primary experience in v1.0.

### 7.2 Performance Targets

| Metric | Target |
|---|---|
| Initial page load (TTI) | < 3 seconds on standard broadband |
| Card drag-and-drop latency | < 100ms perceived |
| Search results | < 500ms from keypress |
| Real-time wiki sync | < 1 second propagation delay |

### 7.3 Data & Storage

- Card attachments: max 25MB per file, 500MB per workspace (v1.0 free tier).
- All user data encrypted at rest (AES-256) and in transit (TLS 1.3).
- Soft-delete for cards: deleted cards are recoverable within 30 days.

### 7.4 Integrations (v1.0)

| Integration | Type | Scope |
|---|---|---|
| Google OAuth | Auth | Sign in / sign up |
| Slack | Webhook | Workspace-level notifications |
| Discord | Webhook | Workspace-level notifications |
| GitHub | Link preview | Paste a GitHub issue/PR URL → renders as a rich link card |

### 7.5 Accessibility

- WCAG 2.1 AA compliance as a baseline target.
- Full keyboard navigation for Kanban board interactions.
- Screen reader support for card metadata.

---

## 8. Metrics & Success Criteria

### 8.1 Acquisition

- 1,000 registered workspaces within 90 days of public launch.
- 40% of signups invited at least one teammate within their first session.

### 8.2 Engagement

- 7-day retention: ≥ 45% of new workspaces active after 7 days.
- Average cards created per active workspace in the first 30 days: ≥ 15.
- Wiki pages created per active workspace in the first 30 days: ≥ 3.

### 8.3 Product Quality

- Bug report rate < 1 critical bug per 1,000 active sessions.
- Net Promoter Score (NPS) ≥ 40 by the end of Month 3.

---

## 9. Out of Scope for v1.0

The following features are explicitly deferred to future versions:

- **Time tracking** and logged hours per card.
- **Gantt / Timeline view** for deadline planning.
- **Roadmap view** for epics and milestone tracking.
- **Recurring tasks** and card templates.
- **Advanced role permissions** at the page or column level.
- **Native iOS / Android apps.**
- **SSO / SAML** for enterprise authentication.
- **API / Webhooks** for third-party automation (Zapier, n8n, Make).
- **Billing and subscription management UI** (v1.0 is free during beta).

---

## 10. Open Questions

| # | Question | Owner | Target Resolution |
|---|---|---|---|
| 1 | Should K-Index reset across board archives, or remain globally unique per workspace forever? | Product | Before backend spec |
| 2 | Is real-time collaborative wiki editing (CRDT) in scope for v1.0, or is last-write-wins acceptable? | Engineering | Architecture phase |
| 3 | What is the free tier storage limit? Is there a paid tier at launch? | Product / Business | Before launch |
| 4 | Should viewers be able to comment on cards, or only full members? | Product | UX review |
| 5 | Do we support multiple Kanban boards per workspace, or one board per workspace in v1.0? | Product | Before backend spec |

---

## 11. Revision History

| Version | Date | Author | Notes |
|---|---|---|---|
| 0.1 | 2026-03-18 | — | Initial draft. Core feature set defined. Design guidelines deferred. |

---

*This document is a living specification. All sections are subject to revision as product discovery progresses.*
