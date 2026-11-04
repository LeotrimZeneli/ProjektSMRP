# SMRP REST API

The PHP API is served by `backend/index.php` and uses MySQL from `backend/config/database.php`.
All routes are prefixed with `/api/v1` and return JSON unless the route downloads a CV.

## Authentication

`POST /api/v1/auth/register`

```json
{
  "full_name": "Example Applicant",
  "email": "applicant@example.com",
  "password": "at-least-8-characters",
  "phone_number": "optional"
}
```

Creates an `applicant` account and returns `201` with a user object and a one-hour JWT:

```json
{
  "user": {
    "id": 1,
    "full_name": "Example Applicant",
    "email": "applicant@example.com",
    "role": "applicant",
    "phone_number": "optional"
  },
  "token": "header.payload.signature"
}
```

`POST /api/v1/auth/login` accepts `email` and `password`, returning the same user/token shape.
Protected endpoints require a signed JWT access token in the HTTP `Authorization` header. `GET /api/v1/auth/me` returns the authenticated user.

## Jobs

| Method | Route | Access | Purpose |
|---|---|---|---|
| GET | `/api/v1/jobs` | Public | Lists open, non-expired jobs. HR/admin may pass `?status=open`, `closed`, or `draft` to filter all jobs. |
| POST | `/api/v1/jobs` | HR/admin | Creates a job. |

Job creation accepts `title`, `description`, `department`, `location`, `deadline` (`YYYY-MM-DD`), and optional `status` (`open`, `closed`, `draft`).

## Applications

| Method | Route | Access | Purpose |
|---|---|---|---|
| GET | `/api/v1/applications` | Applicant, HR/admin | Lists the caller's applications or all applications for staff. |
| POST | `/api/v1/applications` | Applicant | Creates an application using `multipart/form-data`: `job_id`, `cv` (PDF/DOC/DOCX, max 5 MB), and optional `cover_letter`. |
| PATCH | `/api/v1/applications/{id}/status` | HR/admin | Changes status to `pending`, `reviewing`, `interview`, `accepted`, or `rejected`. JSON body: `{"status":"reviewing"}`. |
| GET | `/api/v1/applications/{id}/cv` | Applicant who owns it, HR/admin | Downloads the stored CV. |

An applicant can submit only one application for each job; duplicates return `409 Conflict`.

## Interviews

| Method | Route | Access | Purpose |
|---|---|---|---|
| GET | `/api/v1/interviews` | Applicant, HR/admin | Lists the caller's interviews or all interviews for staff. |
| POST | `/api/v1/interviews` | HR/admin | Schedules one interview per application. |

Interview creation accepts `application_id`, `scheduled_at` (`YYYY-MM-DDTHH:MM`), `location_link`, and optional `notes`.

`GET /api/v1/health` checks that the API can connect to the configured database.
