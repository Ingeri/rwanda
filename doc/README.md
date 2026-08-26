# Rwanda Administrative API

Developer documentation for the PHP API used by the Rwanda Administrative Navigator.

For the browser-readable guide, open [doc/index.html](index.html). This README keeps the technical setup and command-line reference for developers.

## Base URL

Local development:

```text
http://127.0.0.1:8080/api
```

Production example:

```text
https://rwanda.rf.gd/api
```

Do not add `/api` twice. The health URL is `/api/health`, not `/api/api/health`.

## Requirements and setup

- PHP 8.1+ with the `pdo_mysql` extension
- MySQL database named `rwanda`
- Tables `provinces`, `districts`, `sectors`, `cells`, and `villages`
- Hierarchy imported by `save_to_mysql.py`

Create the contact-message table once:

```bash
mysql -u admin -p rwanda < api/schema.sql
```

Configure the database with environment variables:

```bash
export RWANDA_DB_HOST=127.0.0.1
export RWANDA_DB_PORT=3306
export RWANDA_DB_NAME=rwanda
export RWANDA_DB_USER=admin
export RWANDA_DB_PASSWORD='your-password'
```

Environment variables are recommended in deployment so credentials are not stored in source code.

## Run locally

From the project root:

```bash
php -S 127.0.0.1:8080 -t . api/router.php
```

Homepage: `http://127.0.0.1:8080/`  
API base URL: `http://127.0.0.1:8080/api`

## Endpoints

| Method | Endpoint                          | Purpose                                                |
| ------ | --------------------------------- | ------------------------------------------------------ |
| GET    | `/api/health`                     | Check API availability without accessing the database. |
| GET    | `/api/provinces`                  | Return all provinces.                                  |
| GET    | `/api/districts?province_id={id}` | Return districts for a province.                       |
| GET    | `/api/sectors?district_id={id}`   | Return sectors for a district.                         |
| GET    | `/api/cells?sector_id={id}`       | Return cells for a sector.                             |
| GET    | `/api/villages?cell_id={id}`      | Return villages for a cell.                            |
| POST   | `/api/contact.php`                | Validate and save a contact message in MySQL.          |

Use the `id` returned by one request as the parent ID for the next request. Collection responses contain `data` and `count`; child responses also contain the requested parent ID. Each item contains `id` and `name`, and child items contain `parent_id`.

## Get a complete location

```bash
BASE='http://127.0.0.1:8080/api'
curl "$BASE/provinces"
curl "$BASE/districts?province_id=1"
curl "$BASE/sectors?district_id=1"
curl "$BASE/cells?sector_id=1"
curl "$BASE/villages?cell_id=1"
```

Verified local sample IDs are `East` province `1`, `Bugesera` district `1`, `Gashora` sector `1`, and `Biryogo` cell `1`.

Example response from `GET /api/provinces`:

```json
{
  "data": [
    { "id": 1, "name": "East" },
    { "id": 2, "name": "Kigali" }
  ],
  "count": 5
}
```

## Send a contact message

The homepage form uses this same JSON request:

```bash
curl -X POST "$BASE/contact.php" \
  -H 'Content-Type: application/json' \
  -d '{"name":"Jane Doe","email":"jane@example.com","message":"Hello"}'
```

Successful requests return HTTP `201`:

```json
{ "message": "Thank you. Your message has been received." }
```

Name, a valid email address, and message are required. Name is limited to 100 characters, email to 190 characters, and message to 5,000 characters. Run `api/schema.sql` before submitting.

JavaScript example:

```js
const response = await fetch("/api/contact.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    name: "Jane Doe",
    email: "jane@example.com",
    message: "Hello from my application",
  }),
});
const result = await response.json();
```

## Errors and status codes

Errors use this JSON shape:

```json
{ "error": { "message": "A positive integer province_id is required." } }
```

- `200`: successful GET request
- `201`: contact message created
- `204`: CORS preflight request
- `400`: missing or invalid parent ID, JSON, or form data
- `404`: endpoint not found
- `405`: HTTP method is not allowed
- `503`: database or contact-table configuration problem
- `500`: database query failure

## Verified local tests

Tested August 27, 2026 with PHP's built-in server at `127.0.0.1:8080`:

| Request                            | Result                                 |
| ---------------------------------- | -------------------------------------- |
| `GET /api/health`                  | HTTP `200`, status `ok`                |
| `GET /api/provinces`               | HTTP `200`, 5 provinces                |
| `GET /api/districts?province_id=1` | HTTP `200`, 7 districts                |
| `GET /api/sectors?district_id=1`   | HTTP `200`, 15 sectors                 |
| `GET /api/cells?sector_id=1`       | HTTP `200`, 5 cells                    |
| `GET /api/villages?cell_id=1`      | HTTP `200`, 9 villages                 |
| `GET /api/districts`               | HTTP `400`, parent ID validation works |

`POST /api/contact.php` was also tested through the homepage with valid sample data and returned HTTP `201` with the success message. This test created one contact row in the database.
