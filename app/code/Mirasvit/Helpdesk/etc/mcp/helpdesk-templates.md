# Quick Response Templates

Pre-written reply templates that agents can use when responding to tickets. Templates save time on common responses.

## Listing Templates

Use `GET /V1/helpdesk/templates` with `searchCriteria` to list available templates.

```json
{
  "query": {
    "searchCriteria[pageSize]": "100",
    "fields": "items[template_id,name,template,is_active],total_count"
  }
}
```

## Template Fields

| Field | Type | Description |
|-------|------|-------------|
| `template_id` | int | Unique template identifier |
| `name` | string | Template name/title |
| `template` | string | Template message body |
| `is_active` | int | 1 = active, 0 = inactive |
| `store_ids` | int[] | Store view IDs this template is available for |
| `created_at` | string | Creation timestamp |
| `updated_at` | string | Last update timestamp |

## Getting a Specific Template

`GET /V1/helpdesk/template/:templateId` — returns the full template including the message body.

## Using Templates to Reply

1. List templates to find a suitable one — filter by `is_active` = 1.
2. Get the full template by ID if needed.
3. Use the template's `template` field as the message body in `POST /V1/helpdesk/ticket/:code/message`.
4. The user may want to customize the message before sending — present the template content and ask for confirmation.

### Pitfalls

- Filter by `is_active` = 1 to exclude disabled templates.
- The message body field is called `template`, not `message`.
- Template content may contain placeholder text that needs to be replaced before sending.
- Templates are scoped to store views via `store_ids` — check that the template applies to the ticket's store.
