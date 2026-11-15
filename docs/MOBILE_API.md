# Carverse KE Mobile API

Base URL for local development:

```text
http://127.0.0.1:8000/api/v1
```

Content endpoints are public and read-only. Profile and car-management endpoints require a Sanctum Bearer token from `/auth/login`. Send `Accept: application/json`. Requests are limited to 120 per minute per client, with a stricter limit on login attempts.

## Endpoints

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/leaderboard` | Verified racing results and tied ranks |
| GET | `/events` | Upcoming approved events |
| GET | `/events/{id}` | Approved event details |
| GET | `/articles` | Latest published non-feature articles |
| GET | `/articles/{slug}` | Published article details and body |
| GET | `/features` | Latest published car features |
| GET | `/features/{slug}` | Car feature details and vehicle data |
| GET | `/garages` | Approved garages |
| GET | `/garages/{id}` | Approved garage details and public reviews |
| GET | `/mechanics` | Approved skilled mechanics |
| GET | `/mechanics/{id}` | Approved mechanic details and public reviews |
| POST | `/auth/login` | Create a 30-day device token |
| POST | `/auth/logout` | Revoke the current device token |
| GET, PATCH | `/profile` | Read or update the signed-in member |
| GET, POST, DELETE | `/profile/photo` | Read, replace or remove the profile image |
| GET, POST | `/cars` | List or create the member's cars |
| GET, PATCH, DELETE | `/cars/{id}` | Read, update or safely delete an owned car |
| GET, POST, DELETE | `/cars/{id}/photo` | Read, replace or remove a private car photo |

## Authentication

Only phone-verified accounts can obtain mobile tokens. A new login for the same `device_name` revokes the previous token for that device. Tokens expire after 30 days.

```bash
curl -X POST "http://127.0.0.1:8000/api/v1/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"member@example.com","password":"your-password","device_name":"Baraka Android"}'
```

Store the returned token in the device's secure credential storage and send it on protected requests:

```text
Authorization: Bearer YOUR_TOKEN
```

Never place the token in a URL, application log or source-control file.

## Filters

- Leaderboard: `distance=201|402|805`, `event=Event name`
- Events: `q`, `category=meetup|car_show|track_day|motorsport|workshop|other`, `page`, `per_page`
- Articles and features: `q`, `category`, `page`, `per_page`
- Garages and mechanics: `q`, `location`, `page`, `per_page`
- `per_page` defaults to 15 and is capped at 50.

## List response

```json
{
  "data": [],
  "links": {
    "first": "http://127.0.0.1:8000/api/v1/events?page=1",
    "last": "http://127.0.0.1:8000/api/v1/events?page=1",
    "previous": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 0
  }
}
```

Detail endpoints return `{ "data": { ... } }`. Validation errors use HTTP 422, missing or non-public records use 404, and throttled requests use 429.

## Examples

```bash
curl -H "Accept: application/json" \
  "http://127.0.0.1:8000/api/v1/leaderboard?distance=402"

curl -H "Accept: application/json" \
  "http://127.0.0.1:8000/api/v1/events?category=car_show&per_page=10"

curl -H "Accept: application/json" \
  "http://127.0.0.1:8000/api/v1/garages?location=Nairobi"
```

Dates and times are ISO 8601. Event responses identify `Africa/Nairobi` as their display timezone. Image, website and directory links are absolute URLs suitable for native mobile clients.

Car responses expose `has_identifier` but never return the encrypted chassis identifier itself. Mileage cannot be reduced. Cars with service requests, records, reminders, fitments or history shares return HTTP 409 instead of being deleted.

The API deliberately excludes user contact details, application evidence, internal reviewer notes, unpublished articles, pending providers, stale event versions, and racing evidence files.
