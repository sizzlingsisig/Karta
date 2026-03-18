# Software Architecture Document (SAD)

Project: KARTA — Project Management Platform
Version: 0.1 (Draft)
Date: 2026-03-18

## 1. Overview

This document defines the high-level architecture for KARTA v1.0. It explains core components, data flows, persistent stores, real-time considerations, deployment topology, and operational concerns necessary to implement the PRD and SRS.

Goals:
- Deliver a resilient, maintainable web platform built on the project's conventions (PHP 8.4, Laravel v12).
- Support workspace-scoped Kanban boards with immutable K-Index sequencing and an integrated Markdown Wiki.
- Provide low-latency UI interactions and reliable persistence for collaboration features.

## 2. Architecture Summary

High-level components:
- Web Client (React / Livewire-based views) — UI served from frontend assets.
- API Layer (Laravel controllers + GraphQL/REST) — business logic endpoints.
- Auth Service (Laravel Auth + OAuth integrations).
- K-Index Service (sequence generator, DB-backed) — atomic per-workspace index assignment.
- Card Service (cards CRUD, activity log, attachments metadata).
- Wiki Service (Markdown storage, rendering, optional real-time sync).
- Search Service (Elasticsearch / Meilisearch) — indexing for cards and wiki.
- Notifications Service (in-app, email, webhook) — queue-backed.
- Storage (S3-compatible object storage) — attachments and static uploads.
- Database (Primary relational DB, e.g., MySQL/Postgres) — transactions and relational data.
- Background Workers (queue consumers) — async tasks: emails, webhooks, search indexing.

ASCII overview:

  [Web Client]
       |
       v
  [API / Web Server (Laravel)] <-> [DB]
       |         \-> [Search Service]
       |         \-> [Object Storage]
       |         \-> [Queue / Worker] -> [Notifications / Webhooks]
       v
  [Realtime Layer (WebSockets / Pusher / Laravel Echo)]

## 3. Component Responsibilities

3.1 Web Client
- Desktop-first responsive SPA pages for board and wiki. Use Livewire for server-driven components where appropriate, otherwise SPA frameworks for complex interactions.

3.2 API Layer
- Expose REST/GraphQL endpoints for listing, creating, and mutating resources.
- Enforce RBAC (workspace roles) and input validation.

3.3 K-Index Service
- Single responsibility: issue the next numeric index for a given workspace and persist the result with the card creation in the same transaction.
- Implementation options:
  - DB sequence/table with atomic increment inside the card creation transaction (recommended for ACID guarantees).
  - Redis INCR combined with durable writeback (risky for durability on its own).
- Edge cases: ensure no gaps/duplicates on retries — use DB transactional insert to a sequences table or SELECT ... FOR UPDATE pattern.

3.4 Card Service
- Store card metadata and Markdown description; create activity log entries for mutations; support soft-delete and restore within retention window.

3.5 Wiki Service
- Store pages as Markdown in DB; render server-side to HTML for preview; support page tree and metadata (last edited, author).
- Real-time editing: two paths — implement CRDT-based live collaboration (longer effort) OR accept last-write-wins with optimistic locking and auto-save for v1.0.

3.6 Search Service
- Index cards and wiki pages; provide quick filters and fuzzy search. Meilisearch is a lightweight option; Elasticsearch for larger scale.

3.7 Notifications
- Emit events into a queue for workers to dispatch in-app notifications, emails, and webhooks. Webhook delivery must be retried with backoff.

3.8 Storage
- Store attachments in S3-compatible object storage. Keep metadata in DB and enforce per-workspace quotas.

## 4. Data Model (summary)

Key entities (high-level):
- Workspace {id, name, slug, visibility, k_prefix, created_at}
- WorkspaceSequence {workspace_id, last_index}
- User {id, email, name, avatar}
- Membership {workspace_id, user_id, role}
- Card {id, workspace_id, k_index, title, body_markdown, status, priority, due_date, assignees}
- Attachment {id, card_id, workspace_id, key, size, mime}
- Comment {id, card_id, user_id, body, parent_id}
- WikiPage {id, workspace_id, parent_id, title, body_markdown, last_edited_by, last_edited_at}
- ActivityLog {id, subject_type, subject_id, event_type, metadata, created_at}

Indexes: primary keys, FK constraints, and indexes on workspace_id, k_index, title (full-text), and updated_at.

## 5. K-Index Assignment (Detailed)

Required properties: atomic, durable, workspace-scoped, immutable.

Recommended implementation (Postgres example):
1. Maintain `workspace_sequences` table: (workspace_id PK, last_index integer).
2. When creating a card, within the same DB transaction:
   - SELECT last_index FROM workspace_sequences WHERE workspace_id = ? FOR UPDATE;
   - new_index = last_index + 1;
   - UPDATE workspace_sequences SET last_index = new_index WHERE workspace_id = ?;
   - INSERT card with `k_index = new_index`.
3. Commit transaction.

This ensures no gaps or duplicates on concurrent card creates and persists the index atomically with the card.

## 6. Real-time & Concurrency

- Board interactions (drag/drop) should be optimistic UI updates with server reconciliation.
- Use WebSockets (Laravel Echo + Pusher or self-hosted socket server) to broadcast card changes and wiki updates.
- For wiki collaborative editing, v1 fallback: auto-save + change highlights + manual merge (LWW). CRDT recommended for v2.

## 7. Scaling & Performance

- Vertical scaling for initial rollout: scale app servers and workers behind load balancer.
- Use separate read replicas for heavy read workloads (search, board reads) as needed.
- Offload full-text search to Meilisearch/Elasticsearch to meet <500ms search SLA.
- Cache common reads (workspace metadata, board layout) via Redis.

Performance targets (from SRS): TTI < 3s, drag perceived latency <100ms, search <500ms.

## 8. Security

- TLS for all traffic; HSTS enabled.
- Store secrets in environment variables or secret manager; never commit keys.
- Encrypt attachments at rest via S3 server-side encryption; application-level AES-256 for sensitive fields if required.
- Strict RBAC checks on API; rate limit public endpoints.
- Sanitize Markdown and user content before rendering to prevent XSS.

## 9. Reliability & Backup

- Daily DB backups with point-in-time recovery where supported.
- Object storage lifecycle rules to enforce quotas.
- Implement soft-delete for cards with 30-day retention and a garbage collection job to purge expired records.

## 10. Deployment & Infrastructure

Suggested minimal deployment topology:
- Load balancer (Cloud LB / NGINX)
- Multiple App servers running PHP-FPM + Laravel
- Queue workers (supervised processes)
- Postgres primary + read replicas
- Meilisearch / Elasticsearch cluster
- Redis for caching and ephemeral state
- S3-compatible object storage
- WebSocket provider (Pusher or self-hosted)

CI/CD:
- Build assets, run linters, run targeted tests, and deploy to staging then production. Use environment-specific configs and `APP_ENV` flags.

Environment variables (examples):
- APP_ENV, APP_KEY, DB_URL, REDIS_URL, QUEUE_CONNECTION, S3_BUCKET, S3_REGION, MAIL_* , OAUTH_GOOGLE_* , SEARCH_URL

## 11. Observability & Runbook

- Instrument key metrics: request latency, error rates, queue backlog, search latency, K-Index allocation latency.
- Centralized logs (ELK / similar) and alerts for high error rates and failed webhook deliveries.
- Runbook snippets:
  - If K-Index allocation stalls: inspect DB locks on `workspace_sequences` and queue backlog.
  - If search is degraded: verify search cluster health and reconnect indexing worker.

## 12. Testing Strategy

- Unit tests for sequence allocator and critical business logic.
- Integration tests for API endpoints that create cards and validate K-Index uniqueness.
- End-to-end tests for board flows (create card, move, comment, attach file).

## 13. Operational Considerations

- Migration path: create `workspace_sequences` for pre-existing workspaces using max(card.k_index) as seed.
- Quotas: implement enforcement in write path and surface UI warnings when quotas approach limits.

## 14. Trade-offs & Open Decisions

- Real-time wiki editing model: CRDT (complex, future) vs LWW (simpler, acceptable for v1). Decision: start LWW; revisit for v2.
- Search engine: Meilisearch for simplicity and speed vs Elasticsearch for advanced features. Start with Meilisearch.

## 15. Multi-Frontend Support

This section captures architecture and operational recommendations to support multiple frontends (web SPA, Livewire/SSR pages, mobile apps, and embedded clients) consuming the same backend services.

- API-First: Provide a stable, versioned API (REST or GraphQL) as the canonical contract between backend and all frontends. Keep UI concerns out of business logic so frontends can evolve independently.
- Stateless Auth: Use token-based authentication (short-lived JWTs or opaque tokens with refresh + optional Redis-backed session revocation) and clear CORS/Cookie policies to support multiple origins and clients.
- API Gateway: Introduce an API gateway or reverse-proxy to centralize TLS termination, routing, authentication enforcement, per-client rate limiting, quota enforcement, and request logging.
- CDN & Asset Delivery: Serve frontend bundles and static assets from a CDN with appropriate cache-control headers; use cache-busting for releases.
- Realtime Scaling: Insert a message broker (Redis Pub/Sub, NATS, or Kafka) between application servers and realtime workers. Use a managed realtime provider (Pusher/Ably) or horizontally-scalable WebSocket server with sticky sessions or a shared session store.
- Contract Stability & Testing: Version APIs, publish an OpenAPI spec, and add automated contract tests (consumer-driven tests) to prevent breaking changes across frontends.
- Search & Read Scaling: Offload heavy read workloads to read replicas and a dedicated search cluster (Meilisearch/Elasticsearch). Use Redis caching for frequently-read board and workspace metadata.
- CI/CD and Feature Flags: Build and deploy frontends independently. Use feature flags and progressive rollouts to coordinate cross-front-end changes.
- Monitoring & Alerts per Frontend: Track API usage, error rates, and latency per client (frontend type/SDK version) to detect client-specific regressions.

These measures ensure the platform can safely support multiple, independently-evolving frontends while preserving K-Index guarantees and realtime semantics.

## 16. Revision History

- v0.1 — 2026-03-18 — Initial draft
