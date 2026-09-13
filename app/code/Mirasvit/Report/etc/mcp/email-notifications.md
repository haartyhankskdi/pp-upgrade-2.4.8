# Email Notifications

Scheduled and on-demand email notifications with report and dashboard data. Notifications combine multiple report/dashboard blocks into a single email with optional CSV attachments.

## Block Identifiers

Each notification contains one or more blocks. Two types of blocks are available:

- **Report blocks** — use the report identifier directly (e.g., `catalog_product`, `order_overview`). Discover available identifiers via `GET /V1/reports/reports`.
- **Dashboard blocks** — use `boardIdentifier:blockIdentifier` format (e.g., `c68023a03c33d614:90bff1067d8d3b2f`). Get board identifiers from `GET /V1/dashboard/boards` — each board has an `identifier` field, and each block within it has its own `identifier`. Only `single` and `table` renderer blocks produce email content; `chart` blocks are skipped.

## Time Ranges

The `timeRange` field on each block accepts these values:

| Value | Label |
|-------|-------|
| `today` | Today |
| `yesterday` | Yesterday |
| `last7Days` | Last 7 days |
| `last30Days` | Last 30 days |
| `last90Days` | Last 90 days |
| `last365days` | Last 365 days |
| `week` | Week to date |
| `month` | Month to date |
| `quarter` | Quarter to date |
| `year` | Year to date |
| `prev_week` | Last week |
| `prev_month` | Last month |
| `prev_year` | Last year |
| `lifetime` | Lifetime (all data) |

## Creating and Sending a Notification

### Workflow

1. Create the notification:

```json
{
  "endpoint": "/V1/report/email",
  "method": "POST",
  "body": {
    "email": {
      "title": "Weekly Sales Report",
      "subject": "Weekly Sales Summary",
      "recipient": "admin@example.com,manager@example.com",
      "schedule": "0 9 * * 1",
      "is_active": 1,
      "is_attach_enabled": 1,
      "blocks": [
        {"identifier": "order_overview", "timeRange": "prev_week", "limit": 100},
        {"identifier": "c68023a03c33d614:90bff1067d8d3b2f", "timeRange": "prev_week", "limit": 0}
      ]
    }
  }
}
```

2. Send immediately (optional):

```json
{
  "endpoint": "/V1/report/email/{id}/send",
  "method": "POST"
}
```

3. Send to a specific recipient without changing saved config:

```json
{
  "endpoint": "/V1/report/email/{id}/send",
  "method": "POST",
  "body": {
    "recipientEmail": "someone@example.com"
  }
}
```

### Fields

- `title` — internal name for the notification
- `subject` — email subject line (timestamp is appended automatically)
- `recipient` — comma-separated email addresses
- `schedule` — cron expression (e.g., `0 9 * * 1` = Mondays at 9 AM)
- `is_active` — `1` to enable scheduled sending, `0` to disable
- `is_attach_enabled` — `1` to attach CSV exports of report blocks
- `blocks[].limit` — max rows; use `0` for default

## Updating a Notification

Use `PUT /V1/report/email/{id}` with only the fields you want to change. Blocks are replaced entirely when provided.

## Pitfalls

- CSV attachments only work for report blocks (not dashboard blocks) and only when the block produces a table
- Dashboard `chart` renderer blocks are silently skipped — they don't produce email content
- If a block identifier doesn't match any registered report or dashboard block, it is skipped without error
- The `schedule` field is a standard cron expression but sending depends on Magento cron being configured and running
