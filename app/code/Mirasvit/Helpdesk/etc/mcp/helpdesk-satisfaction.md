# Customer Satisfaction

How to retrieve customer satisfaction ratings for helpdesk tickets. Satisfaction records are created when customers rate their support experience.

## Listing Satisfaction Records

`GET /V1/helpdesk/satisfactions` with `searchCriteria` to list and filter records.

```json
{
  "query": {
    "searchCriteria[pageSize]": "50",
    "searchCriteria[sortOrders][0][field]": "created_at",
    "searchCriteria[sortOrders][0][direction]": "DESC"
  }
}
```

## Satisfaction Fields

| Field | Type | Description |
|-------|------|-------------|
| `satisfaction_id` | int | Record identifier |
| `ticket_id` | int | Associated ticket (numeric ID) |
| `message_id` | int | The message that triggered the satisfaction request |
| `user_id` | int | Admin user who handled the ticket |
| `customer_id` | int | Customer who provided the rating |
| `store_id` | int | Store view ID |
| `rate` | int | Satisfaction rating value |
| `comment` | string | Optional customer comment |
| `created_at` | string | When the rating was submitted |
| `updated_at` | string | Last update timestamp |

## Getting a Specific Record

`GET /V1/helpdesk/satisfaction/:satisfactionId` — returns a single satisfaction record by ID.

## Common Filters

**Satisfaction for a specific ticket:**
```json
{
  "query": {
    "searchCriteria[filterGroups][0][filters][0][field]": "ticket_id",
    "searchCriteria[filterGroups][0][filters][0][value]": "157",
    "searchCriteria[filterGroups][0][filters][0][conditionType]": "eq"
  }
}
```

**Ratings by a specific agent:**
```json
{
  "query": {
    "searchCriteria[filterGroups][0][filters][0][field]": "user_id",
    "searchCriteria[filterGroups][0][filters][0][value]": "5",
    "searchCriteria[filterGroups][0][filters][0][conditionType]": "eq",
    "searchCriteria[sortOrders][0][field]": "created_at",
    "searchCriteria[sortOrders][0][direction]": "DESC"
  }
}
```

### Pitfalls

- Not all tickets have satisfaction records — only tickets where the customer submitted a rating.
- Uses numeric `ticket_id`, not ticket `code`.
- The `user_id` refers to the admin agent who was assigned to the ticket, not the customer.
