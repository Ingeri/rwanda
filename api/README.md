# Rwanda Administrative API

A small PHP/PDO REST API for the Rwanda administrative hierarchy stored in the `rwanda` MySQL database.

## Requirements

- PHP 8.1+ with `pdo_mysql` enabled
- MySQL database `rwanda`
- Tables populated by `../save_to_mysql.py`

Create the contact table once:

```bash
mysql -u admin -p rwanda < api/schema.sql
```

The API reads these environment variables. Defaults match the local development setup, but setting the password through the environment is recommended:

```bash
export RWANDA_DB_HOST=127.0.0.1
export RWANDA_DB_PORT=3306
export RWANDA_DB_NAME=rwanda
export RWANDA_DB_USER=admin
export RWANDA_DB_PASSWORD='your-password'
```

## Start locally

From the project root:

```bash
php -S localhost:8080 -t . api/router.php
```

The API base URL is `http://localhost:8080/api`.

## Endpoints

| Method | Endpoint                       | Description                            |
| ------ | ------------------------------ | -------------------------------------- |
| GET    | `/api/health`                  | Check that the API is available.       |
| GET    | `/api/provinces`               | Get every province.                    |
| GET    | `/api/districts?province_id=1` | Get districts belonging to a province. |
| GET    | `/api/sectors?district_id=1`   | Get sectors belonging to a district.   |
| GET    | `/api/cells?sector_id=1`       | Get cells belonging to a sector.       |
| GET    | `/api/villages?cell_id=1`      | Get villages belonging to a cell.      |
| POST   | `/api/contact`                 | Save a contact message.                |

Child endpoints require a positive integer parent ID. IDs come from the response of the previous endpoint.

## Response examples

```json
{
  "data": [{ "id": 1, "name": "East" }],
  "count": 1
}
```

```bash
curl 'http://localhost:8080/api/districts?province_id=1'
```

Submit a contact message as JSON:

```bash
curl -X POST 'http://localhost:8080/api/contact' \
  -H 'Content-Type: application/json' \
  -d '{"name":"Jane Doe","email":"jane@example.com","message":"Hello"}'
```

Errors use this shape and an appropriate HTTP status:

```json
{ "error": { "message": "A positive integer province_id is required." } }
```
