# Ticket History, Activities & Attachments

How to retrieve ticket change history, activity logs, and attachment metadata. Use these endpoints to understand what happened to a ticket over time.

## History Records

`GET /V1/helpdesk/history` — lists change and event records for tickets.

### History Fields

| Field | Type | Description |
|-------|------|-------------|
| `history_id` | int | Record identifier |
| `ticket_id` | int | Associated ticket (numeric ID) |
| `triggered_by` | string | Who or what triggered the change (e.g., admin user, system rule) |
| `name` | string | What changed or happened (e.g., field name, event type) |
| `message` | string | Human-readable description of the change |
| `created_at` | string | When the change occurred |

### Common Filters

**All history for a specific ticket:**
```json
{
  "query": {
    "searchCriteria[filterGroups][0][filters][0][field]": "ticket_id",
    "searchCriteria[filterGroups][0][filters][0][value]": "157",
    "searchCriteria[filterGroups][0][filters][0][conditionType]": "eq",
    "searchCriteria[sortOrders][0][field]": "created_at",
    "searchCriteria[sortOrders][0][direction]": "DESC",
    "searchCriteria[pageSize]": "50"
  }
}
```

### Pitfalls

- History uses numeric `ticket_id`, not the ticket `code`. Fetch the ticket first if you only have the code.
- The `message` field contains a pre-formatted description — use it for display rather than trying to parse `name` values.

## Activity Records

`GET /V1/helpdesk/activities` — lists activity events across tickets.

Activity records provide a broader activity feed. Filter by `ticket_id` to scope to a specific ticket. Sort by `created_at` DESC for reverse-chronological view.

```json
{
  "query": {
    "searchCriteria[pageSize]": "50",
    "searchCriteria[sortOrders][0][field]": "created_at",
    "searchCriteria[sortOrders][0][direction]": "DESC"
  }
}
```

## Attachments

`GET /V1/helpdesk/attachments` — lists file attachment metadata.

### Attachment Fields

| Field | Type | Description |
|-------|------|-------------|
| `attachment_id` | int | Attachment identifier |
| `message_id` | int | Associated message ID |
| `email_id` | int | Associated email ID (for email-originated attachments) |
| `name` | string | Original filename |
| `type` | string | MIME type |
| `size` | int | File size in bytes |
| `external_id` | string | External storage identifier |
| `storage` | string | Storage backend type |

### Common Filters

**Attachments for a specific message:**
```json
{
  "query": {
    "searchCriteria[filterGroups][0][filters][0][field]": "message_id",
    "searchCriteria[filterGroups][0][filters][0][value]": "42",
    "searchCriteria[filterGroups][0][filters][0][conditionType]": "eq"
  }
}
```

### Pitfalls

- Attachments are linked to messages via `message_id`, not directly to tickets. To list all attachments for a ticket, first get the ticket's message IDs, then filter attachments by those IDs.
- This endpoint returns metadata only — it does not provide file download URLs or content.
- Without a filter, all attachments across all tickets are returned — always scope your query.
