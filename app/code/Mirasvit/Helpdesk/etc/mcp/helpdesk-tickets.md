# Helpdesk Tickets

How to manage helpdesk tickets, messages, and ticket workflow via the REST API. Covers ticket lifecycle, message exchange, folder management, and filtering.

## Listing and Filtering Tickets

Use `GET /V1/helpdesk/tickets` with `searchCriteria` query parameters to list and filter tickets.

### Filterable Fields

| Field | Type | Description |
|-------|------|-------------|
| `status_id` | int | 1=Open, 2=In Progress, 3=Closed |
| `priority_id` | int | Priority level |
| `department_id` | int | Department |
| `user_id` | int | Assigned admin user |
| `customer_email` | string | Customer email |
| `customer_id` | int | Customer ID |
| `order_id` | int | Related order |
| `folder` | int | 1=Inbox, 2=Archive, 3=Spam |
| `created_at` | datetime | Creation date |
| `updated_at` | datetime | Last update date |
| `last_reply_at` | datetime | Last reply date |
| `subject` | string | Ticket subject |
| `code` | string | Ticket code identifier |

### Common Filters

**Open inbox tickets assigned to a specific agent:**
```json
{
  "query": {
    "searchCriteria[filterGroups][0][filters][0][field]": "status_id",
    "searchCriteria[filterGroups][0][filters][0][value]": "1",
    "searchCriteria[filterGroups][0][filters][0][conditionType]": "eq",
    "searchCriteria[filterGroups][1][filters][0][field]": "user_id",
    "searchCriteria[filterGroups][1][filters][0][value]": "5",
    "searchCriteria[filterGroups][1][filters][0][conditionType]": "eq",
    "searchCriteria[filterGroups][2][filters][0][field]": "folder",
    "searchCriteria[filterGroups][2][filters][0][value]": "1",
    "searchCriteria[filterGroups][2][filters][0][conditionType]": "eq",
    "searchCriteria[pageSize]": "20"
  }
}
```

**Tickets created in the last 7 days:**
```json
{
  "query": {
    "searchCriteria[filterGroups][0][filters][0][field]": "created_at",
    "searchCriteria[filterGroups][0][filters][0][value]": "2026-03-05 00:00:00",
    "searchCriteria[filterGroups][0][filters][0][conditionType]": "gteq",
    "searchCriteria[sortOrders][0][field]": "created_at",
    "searchCriteria[sortOrders][0][direction]": "DESC"
  }
}
```

**Find tickets by customer email domain:**
```json
{
  "query": {
    "searchCriteria[filterGroups][0][filters][0][field]": "customer_email",
    "searchCriteria[filterGroups][0][filters][0][value]": "%@example.com",
    "searchCriteria[filterGroups][0][filters][0][conditionType]": "like"
  }
}
```

### Pitfalls

- Multiple filters in the **same** `filterGroups` index are OR-ed. Filters in **different** `filterGroups` indices are AND-ed.
- Always include `searchCriteria[pageSize]` to avoid unbounded result sets.
- The `code` field is the ticket's business identifier (e.g., `HDK-00042`), not the numeric ID.

## Viewing a Ticket

`GET /V1/helpdesk/ticket/:code` returns the ticket with all its messages embedded in chronological order.

For tickets with many messages, use the paginated endpoint:
```
GET /V1/helpdesk/ticket/:code/message
  query: {
    "searchCriteria[pageSize]": "10",
    "searchCriteria[currentPage]": "1",
    "searchCriteria[sortOrders][0][field]": "created_at",
    "searchCriteria[sortOrders][0][direction]": "DESC"
  }
```

Messages can be filtered by `type` (`public`, `internal`), `created_at`, `user_id`, `customer_id`, etc.

## Creating a Ticket

`POST /V1/helpdesk/ticket` with a `ticketData` body object.

**Required fields:** `customer_email`, `subject`, `message`.

**Optional fields:** `store_id`, `priority_id`, `department_id`, `status_id`, `order_id`, `customer_id`, `customer_name`, `owner`, `cc`, `bcc`, `tags`.

### Pitfalls

- The `customer_email` must be a valid email format.
- `cc` and `bcc` are comma-separated strings of email addresses, not arrays.
- `tags` is a string array: `["tag1", "tag2"]`.
- The endpoint requires admin authentication — it creates tickets on behalf of the logged-in admin user.

## Updating a Ticket

`PUT /V1/helpdesk/ticket/:code` — only include fields you want to change.

**Updatable fields:** `statusId`, `priorityId`, `userId`, `subject`, `tags`, `folder`.

### Folder Management

Move tickets between folders by setting the `folder` field:
- `1` — Inbox (active tickets)
- `2` — Archive (resolved)
- `3` — Spam

Example — archive a ticket:
```json
{
  "statusId": 3,
  "folder": 2
}
```

### Pitfalls

- Field names in the update body use camelCase (`statusId`, `priorityId`, `userId`), not snake_case.
- Only the listed fields are updatable via this endpoint. Other ticket fields (like `customer_email`) cannot be changed after creation.
- `folder` only accepts values 1, 2, or 3.

## Adding Messages

`POST /V1/helpdesk/ticket/:code/message` adds a message to an existing ticket.

**Parameters:**
- `message` (required) — The message body text.
- `type` (optional) — `"public"` (default) or `"internal"`.

### Public vs Internal Messages

- **Public** messages are sent to the customer via email notification.
- **Internal** messages are staff-only notes, invisible to the customer.

### Pitfalls

- If `type` is omitted, the message defaults to `public` — it will be visible to the customer.
- Empty message bodies are rejected with a validation error.
- The message is created on behalf of the authenticated admin user.

## Deleting a Ticket

`DELETE /V1/helpdesk/ticket/:code` permanently removes the ticket and logs the deletion.

Returns `true` on success. This action is **irreversible**.

## Reference Data: Statuses

`GET /V1/helpdesk/statuses` — list all statuses with SearchCriteria.

`GET /V1/helpdesk/status/:statusId` — get a single status by ID.

Each status has: `status_id`, `name`, `code`, `color`, `sort_order`.

Predefined: 1 = Open, 2 = In Progress, 3 = Closed. Custom statuses can be added.

## Reference Data: Priorities

`GET /V1/helpdesk/priorities` — list all priorities with SearchCriteria.

`GET /V1/helpdesk/priority/:priorityId` — get a single priority by ID.

Each priority has: `priority_id`, `name`, `color`, `sort_order`.

## Reference Data: Departments

`GET /V1/helpdesk/departments` — list all departments with SearchCriteria.

`GET /V1/helpdesk/department/:departmentId` — get a single department by ID.

Each department has: `department_id`, `name`, `notification_email`, `sort_order`, `is_active`.

### Pitfalls

- Filter by `is_active` = 1 to get only active departments.
- Departments are read-only via REST API — managed through admin UI.

## Reference Data: Tags

`GET /V1/helpdesk/tags` — list all tags with SearchCriteria.

Each tag has: `tag_id`, `name`.

Tags are assigned to tickets via the ticket update endpoint (`tags` field as a string array). This endpoint lets you discover available tags.

## Reference Data: Custom Fields

`GET /V1/helpdesk/fields` — list all custom field definitions with SearchCriteria.

`GET /V1/helpdesk/field/:fieldId` — get a single field definition by ID.

Each field has: `field_id`, `name`, `code`, `type`, `description`, `values`, `is_active`, `sort_order`.

### Field Types

- `select` — Dropdown with predefined values
- `checkbox` — Boolean checkbox
- `text` — Single-line text input
- `textarea` — Multi-line text input
- `date` — Date picker

### Pitfalls

- Field codes are auto-prefixed with `f_` (e.g., field with code `issue_type` becomes `f_issue_type`).
- The `values` field contains the allowed options for select-type fields.
- Custom fields are read-only definitions via REST API — field values on tickets are managed through the ticket entity.
