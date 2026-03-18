# Low-Level Design (LLD)

Project: KARTA — Project Management Platform
Version: 0.1 (Draft)
Date: 2026-03-18

Purpose: provide implementable details from the SAD: database schema, API routes, service responsibilities, transaction patterns (K-Index), queue/event contracts, caching, and examples for Laravel v12 + PHP 8.4.

## 1. Overview

This LLD translates the SAD into concrete components and code-level guidance targeted at backend engineers implementing core features: workspace, K-Index, cards, wiki, attachments, notifications, search indexing, and realtime broadcasting.

Assumptions:
- Backend: Laravel v12, PHP 8.4
- DB: Postgres (examples), but compatible with MySQL with minor syntax changes
- Queue: Redis (Laravel queue)
- Search: Meilisearch (HTTP API)
- Storage: S3-compatible

## 2. Database Schema

Below are the core tables with important columns, types, and indexes. Use Laravel migrations to implement.

2.1 workspaces

Columns:
- id: uuid (PK)
- name: string
- slug: string (unique)
- visibility: enum('private','public') default 'private'
- k_prefix: string default 'K-'
- created_at, updated_at

Indexes:
- unique(slug)

2.2 workspace_sequences

Columns:
- workspace_id: uuid (PK, FK -> workspaces.id)
- last_index: bigint default 0
- updated_at

Notes: used for atomic K-Index allocation. Ensure row exists for each workspace on creation.

2.3 users

Columns: id: uuid, name, email (unique), password_hash, avatar_url, created_at, updated_at

2.4 memberships

Columns: id: uuid, workspace_id (FK), user_id (FK), role: enum('owner','admin','member','viewer'), created_at

2.5 cards

Columns:
- id: uuid PK
- workspace_id: uuid FK
- k_index: bigint (nullable until assigned in transaction)
- title: string
- body_markdown: text
- status: string (column name or custom)
- priority: enum('critical','high','medium','low')
- due_date: date nullable
- assignees: jsonb (array of user_ids) OR separate pivot table card_assignees
- labels: jsonb
- deleted_at: timestamp nullable (soft deletes)
- created_at, updated_at

Indexes:
- unique(workspace_id, k_index)
- index(workspace_id)
- fulltext(title, body_markdown) (DB specific)

2.6 card_comments

Columns: id, card_id (FK), user_id (FK), body, parent_id nullable, created_at, updated_at

2.7 attachments

Columns: id, card_id FK, workspace_id FK, storage_key, size_bytes, mime, uploaded_by, created_at

2.8 wiki_pages

Columns: id, workspace_id, parent_id nullable, title, body_markdown, last_edited_by, last_edited_at, created_at, updated_at

2.9 activity_logs

Columns: id, workspace_id, subject_type (card/wiki/...), subject_id, event_type, metadata jsonb, actor_id nullable, created_at

2.10 indexes and migrations

- Add foreign keys, cascade deletes where appropriate (but be conservative: cards soft-delete instead of hard delete).
- Seed `workspace_sequences` when creating a workspace with last_index = 0.

Example migration snippet (Postgres / Laravel):

```php
Schema::create('workspace_sequences', function (Blueprint $table) {
    $table->uuid('workspace_id')->primary();
    $table->unsignedBigInteger('last_index')->default(0);
    $table->timestampsTz();
});
```

## 3. K-Index Service (Implementation)

Goal: allocate the next sequential `k_index` for a workspace atomically and persist it with the card creation.

3.1 Pseudocode (Laravel transaction)

```php
DB::transaction(function () use ($workspaceId, $cardData) {
    $seq = DB::table('workspace_sequences')
        ->where('workspace_id', $workspaceId)
        ->lockForUpdate()
        ->first();

    if (! $seq) {
        DB::table('workspace_sequences')->insert(['workspace_id' => $workspaceId, 'last_index' => 0]);
        $lastIndex = 0;
    } else {
        $lastIndex = $seq->last_index;
    }

    $newIndex = $lastIndex + 1;

    DB::table('workspace_sequences')
        ->where('workspace_id', $workspaceId)
        ->update(['last_index' => $newIndex, 'updated_at' => now()]);

    $cardId = Str::uuid();
    DB::table('cards')->insert(array_merge($cardData, ['id' => $cardId, 'k_index' => $newIndex]));

    // insert activity log, assignees, attachments metadata as needed
});
```

3.2 Notes
- `lockForUpdate()` prevents concurrent transactions from reading stale last_index.
- Keep this transaction short: avoid heavy processing, external calls, or long-running tasks inside the transaction.
- On retries the DB transaction will retry or throw; ensure callers handle transient exceptions.

## 4. RESTful API Design

The API follows resource-oriented, versioned endpoints (e.g., `api/v1/`). Design goals: predictable URIs, proper use of HTTP verbs and status codes, idempotency where appropriate, pagination for collections, and standard error/caching semantics so multiple frontends can integrate reliably.

4.1 Resource URIs (examples)
- Workspaces: `/api/v1/workspaces` (collection), `/api/v1/workspaces/{workspace_id}` (resource)
- Cards: `/api/v1/workspaces/{workspace_id}/cards` (collection), `/api/v1/workspaces/{workspace_id}/cards/{k_index}` (resource by K-Index)
- Comments: `/api/v1/workspaces/{workspace_id}/cards/{k_index}/comments`
- Wiki pages: `/api/v1/workspaces/{workspace_id}/wiki/pages` and `/api/v1/workspaces/{workspace_id}/wiki/pages/{page_id}`
- Attachments: `/api/v1/workspaces/{workspace_id}/attachments` and `/api/v1/workspaces/{workspace_id}/attachments/{id}`

4.2 HTTP verbs and behavior
- `GET` — read a resource or collection. Support query params for filtering, sorting, and pagination (`page`, `per_page`, `q`, `assignee`, `status`).
- `POST` — create a new resource. On success return `201 Created` with `Location` header pointing to the new resource and the resource body in JSON.
- `PUT` — full replace (idempotent). Use for resources where clients perform full updates.
- `PATCH` — partial update for fields (e.g., move column, change priority). Return `200 OK` with updated resource.
- `DELETE` — soft-delete a resource; return `204 No Content` on success.

4.3 Status codes & error format
- Success: `200 OK`, `201 Created`, `204 No Content`.
- Client errors: `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `409 Conflict` (for concurrency), `422 Unprocessable Entity` (validation errors).
- Server errors: `500`, `502`, `503`.
- Errors use a consistent JSON structure, e.g.:

```json
{
    "error": {
        "code": "validation_failed",
        "message": "Validation failed for 'title'",
        "details": { "title": ["The title field is required."] }
    }
}
```

4.4 Pagination, filtering, and sorting
- Collections return a `data` array and a `meta` object with paging info, plus `links` for `self`, `next`, `prev`.
- Support `page`/`per_page` or cursor-based paging for large boards.

4.5 Idempotency and safe retries
- Use idempotency keys for non-idempotent POSTs from clients that may retry (e.g., attachment-finishing callbacks). Implement via an `Idempotency-Key` header persisted server-side for a short window.

4.6 Concurrency control
- Support conditional requests via `ETag` / `If-Match` headers for updates to avoid lost updates. On mismatch return `412 Precondition Failed` or `409 Conflict` with server state for manual merge.

4.7 Caching & headers
- Set `Cache-Control`, `ETag`, and `Last-Modified` for `GET` responses. Use `Vary` header for auth-sensitive content.

4.8 Authentication & Authorization
- Bearer token (OAuth2 / JWT) recommended for multi-frontend support. Protect endpoints with RBAC checks per workspace membership.

4.9 HATEOAS & links (optional)
- Include relevant links in resource representations (e.g., card includes `links.wiki_page`, `links.comments`, `links.self`) to make APIs easier to navigate for clients.

4.10 Example: Create Card (RESTful)

Request:

POST /api/v1/workspaces/3f8a/cards

```json
{
    "title": "Implement login",
    "body_markdown": "Details...",
    "priority": "high",
    "assignees": ["user-uuid-1"]
}
```

Successful response:

Status: 201 Created
Headers: `Location: /api/v1/workspaces/3f8a/cards/K-101`

Body:

```json
{
    "id": "uuid",
    "k_index": 101,
    "title": "Implement login",
    "status": "Backlog",
    "links": { "self": "/api/v1/workspaces/3f8a/cards/K-101" }
}
```

4.11 Controller responsibilities (RESTful)
- Validate request, enforce RBAC, and delegate to domain services (`KIndexAllocator`, `CardService`).
- Keep controllers thin: orchestrate, map requests to services, and translate service results to HTTP responses with appropriate status codes and headers.
- Emit domain events for async side effects (indexing, notifications, broadcasts).

4.12 Versioning & contract stability
- Version APIs under `/api/v1/`. Use semantic versioning for breaking changes and maintain compatibility via deprecation headers and migration guides.


## 5. Services & Domain Layer

5.1 CardService
- Responsibilities: create/update/delete cards, handle assignees, create activity logs, emit events.
- Methods: create(workspaceId, data, actor), update(workspaceId, kIndex, data, actor), move(), softDelete(), restore()

5.2 KIndexAllocator
- Encapsulates the transaction described in Section 3. Use dependency injection; mock in tests.

5.3 WikiService
- CRUD pages, render Markdown (sanitize output), embed card-chips by resolving `[[K-<number>]]`.

5.4 AttachmentService
- Handle multipart upload to S3, enforce size and workspace quotas, store metadata.

5.5 NotificationService
- Produce notification records and enqueue delivery jobs. Provide methods: notifyAssigned(), notifyMention(), notifyDueSoon().

## 6. Events, Queues, and Workers

Event bus: use Laravel events -> listeners, or dispatch jobs directly.

6.1 Important events
- CardCreated (payload: workspace_id, card_id, k_index, actor_id)
- CardUpdated
- CommentAdded
- WikiPageEdited

6.2 Workers
- SearchIndexJob — index card/wiki page with Meilisearch.
- SendEmailJob — send email notifications.
- WebhookDeliveryJob — deliver to Slack/Discord with retries and backoff.
- BroadcastRealtimeJob — push via WebSocket provider or publish to Redis channel for WebSocket servers.

6.3 Retry & Dead-letter
- Configure exponential backoff and dead-letter queues for failed webhook/email deliveries. Log failures to Sentry/monitoring.

## 7. Realtime Design

7.1 Channels & Events
- Channel: `workspace.{workspace_id}` — broadcasts card and wiki events.
- Event payload: minimal change set (card id, k_index, changed_fields, timestamp, actor_id).

7.2 Client responsibilities
- Apply optimistic UI updates; reconcile with server state on event receipt.

7.3 Scaling
- Use Redis/Message broker pubsub between app servers and socket servers; for managed, use Pusher/Ably.

## 8. Search Indexing Flow

- On create/update/delete of cards and wiki pages, dispatch `SearchIndexJob`.
- Job transforms DB model to search document and sends to Meilisearch.
- Maintain incremental indexing: include `updated_at` to reindex only changed entities.

## 9. Caching Strategy

- Cache workspace metadata and board layout for 30s–5m via Redis.
- Cache rendered wiki HTML for pages; invalidate on edit.
- Use ETag/Last-Modified headers for frontend to short-circuit full responses.

## 10. Attachments & Quota Enforcement

- Upload flow: client requests pre-signed URL (POST /attachments/request-upload) -> server validates quota & returns URL -> client uploads directly to S3 -> server verifies upload and records metadata.
- Enforce per-workspace total storage limit in AttachmentService when generating upload URLs.

## 11. Security Considerations

- Sanitize Markdown output with a whitelist sanitizer (e.g., HTMLPurifier) and strip dangerous attributes.
- Validate MIME and file extension for attachments on upload completion (check S3 metadata if possible).
- RBAC checks in every write path.
- Rate limit endpoints and protect against CSRF for cookie-based auth flows.

## 12. Testing

- Unit tests: KIndexAllocator, CardService, AttachmentService.
- Integration tests: API endpoints for creating card (concurrent test to validate K-Index uniqueness), upload flow, and search indexing.
- E2E tests: Playwright/Cypress to simulate board interactions and realtime events.

Example PHPUnit concurrency test sketch:

```php
// Spawn multiple parallel requests to create card and ensure k_index sequence is contiguous
// Use Laravel's parallel testing or a small script firing concurrent HTTP requests.
```

## 13. Observability

- Emit metrics for: card_create_latency, k_index_lock_wait_time, queue_job_duration, search_index_latency.
- Log structured events for important domain actions.

## 14. Migration Notes

- For existing projects, seed `workspace_sequences` with `max(k_index)` per workspace.

SQL:

```sql
INSERT INTO workspace_sequences (workspace_id, last_index, updated_at)
SELECT workspace_id, COALESCE(MAX(k_index),0)::bigint, now()
FROM cards GROUP BY workspace_id;
```

## 15. Example Files to Add (implementation checklist)

- migrations/2026_03_18_create_workspaces_and_sequences.php
- app/Services/KIndexAllocator.php
- app/Services/CardService.php
- app/Http/Controllers/Api/V1/CardController.php
- jobs/SearchIndexJob.php
- listeners/BroadcastCardCreated.php
- tests/Feature/CreateCardConcurrencyTest.php

## 16. Appendix: Sample Laravel KIndexAllocator

```php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KIndexAllocator
{
    public function allocateAndCreateCard(string $workspaceId, array $cardData): array
    {
        return DB::transaction(function () use ($workspaceId, $cardData) {
            $seq = DB::table('workspace_sequences')
                ->where('workspace_id', $workspaceId)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                DB::table('workspace_sequences')->insert(['workspace_id' => $workspaceId, 'last_index' => 0, 'updated_at' => now()]);
                $lastIndex = 0;
            } else {
                $lastIndex = (int) $seq->last_index;
            }

            $newIndex = $lastIndex + 1;

            DB::table('workspace_sequences')
                ->where('workspace_id', $workspaceId)
                ->update(['last_index' => $newIndex, 'updated_at' => now()]);

            $cardId = Str::uuid()->toString();
            $card = array_merge($cardData, [
                'id' => $cardId,
                'workspace_id' => $workspaceId,
                'k_index' => $newIndex,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('cards')->insert($card);

            return $card;
        }, 5); // retry up to 5 times on deadlock
    }
}
```

---

If you'd like, I can:
- generate the Laravel migration files and service skeletons next, or
- produce an OpenAPI spec for the endpoints listed above.
