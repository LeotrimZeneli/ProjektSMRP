# Dokumentacioni i API-se REST

Ky dokument pershkruan API-ne aktuale te SMRP. Te gjitha rruget perdorin prefiksin `/api/v1` dhe kthejne JSON, pervec shkarkimit te CV-se.

## Bazat

- **Base URL, Vite:** `http://127.0.0.1:5173/api/v1`
- **Base URL, XAMPP ne kete instalim:** `http://localhost:8080/ProjektSMRP/ProjektSMRP/backend/api/v1`
- **Autentikimi:** `Authorization: Bearer <token>` per rruget private
- **Datetime:** ISO 8601, per shembull `2026-11-25T14:30`
- **Upload CV:** `multipart/form-data`; llojet e lejuara jane PDF, DOC dhe DOCX

## Rruget publike

| Metoda | Rruga | Pershkrimi | Suksesi |
|---|---|---|---|
| `GET` | `/health` | Kontrollon qe API-ja eshte e arritshme pa hapur lidhje databaze. | `200` |
| `POST` | `/auth/register` | Krijon llogari me rolin `applicant`. | `201` |
| `POST` | `/auth/login` | Verifikon kredencialet dhe kthen token JWT. | `200` |
| `GET` | `/jobs` | Kthen shpalljet e hapura per vizitoret. | `200` |

### Regjistrimi dhe kyçja

`POST /auth/register` pranon JSON:

```json
{
  "full_name": "Arta Kandidatja",
  "email": "arta@example.com",
  "password": "nje-fjalekalim-i-forte",
  "phone_number": "+383 44 000 000"
}
```

`phone_number` eshte opsional. Password-i duhet te kete se paku 8 karaktere. Pergjigjja perfshin token JWT dhe fushat publike te perdoruesit, jo hash-in e password-it.

`POST /auth/login` pranon `email` dhe `password`, dhe kthen te njejten forme te pergjigjes. Dergo token-in me `Authorization: Bearer <token>` ne rruget private.

## Rruget e autentikuara

| Metoda | Rruga | Rolet | Pershkrimi |
|---|---|---|---|
| `GET` | `/auth/me` | Te gjithe | Kthen llogarine e perdoruesit te kyçur. |
| `GET` | `/jobs` | Te gjithe | Reviewer/HR/manager/admin mund te filtroje me `?status=open`, `closed` ose `draft`; kandidati sheh vetem shpalljet publike. |
| `POST` | `/jobs` | HR, manager, admin | Krijon shpallje. |
| `GET` | `/applications` | Te gjithe | Kandidati sheh aplikimet e veta; reviewer/HR/manager/admin sheh aplikimet per shqyrtim. |
| `POST` | `/applications` | applicant | Aplikon ne nje shpallje me CV. |
| `PATCH` | `/applications/{id}/status` | Reviewer, HR, manager, admin | Ndryshon statusin e aplikimit. |
| `GET` | `/applications/{id}/cv` | Pronari, reviewer, HR, manager, admin | Shkarkon CV-ne pas autorizimit. |
| `GET` | `/interviews` | Te gjithe | Kandidati sheh intervistat e veta; HR/manager/admin sheh intervistat e rekrutimit. Reviewer nuk ka qasje ne kete liste. |
| `POST` | `/interviews` | HR, manager, admin | Planifikon interviste per nje aplikim. |
| `GET` | `/admin/users` | admin | Liston llogarite pa te dhena te fjalekalimit. |
| `PATCH` | `/admin/users/{id}/role` | admin | Cakton `applicant`, `reviewer`, `hr`, `manager` ose `admin`; ndalon vetë-demotimin dhe demotimin e administratorit te fundit. |

## Krijimi i shpalljes

`POST /jobs` pranon JSON:

```json
{
  "title": "Zhvillues/e PHP",
  "description": "Ndertimi dhe mirembajtja e sherbimeve web.",
  "department": "Inxhinieri",
  "location": "Prizren / Hybrid",
  "deadline": "2026-12-31",
  "status": "open"
}
```

`status` mund te jete `open`, `closed` ose `draft`; nese mungon, perdoret `open`. Data duhet te jete ne formatin `YYYY-MM-DD`.

## Aplikimi me CV

`POST /applications` pranon `multipart/form-data`:

| Fusha | Lloji | Kerkesa |
|---|---|---|
| `job_id` | integer | ID e nje shpalljeje te hapur |
| `cv` | file | E detyrueshme; PDF, DOC ose DOCX; maksimumi 5 MB |
| `cover_letter` | tekst | Opsionale; maksimumi 10,000 karaktere |

Nje kandidat mund te aplikoje vetem nje here ne te njejten shpallje. Backend-i validon llojin/madhesine, ruan CV-ne jashte rrugeve publike dhe regjistron shtegun relativ. Shkarkimi behet vetem permes `GET /applications/{id}/cv`.

## Statusi i aplikimit

`PATCH /applications/{id}/status` pranon JSON:

```json
{
  "status": "reviewing"
}
```

Vlerat e lejuara: `pending`, `reviewing`, `interview`, `accepted`, `rejected`.

## Planifikimi i intervistes

`POST /interviews` pranon JSON:

```json
{
  "application_id": 12,
  "scheduled_at": "2026-12-02T14:30",
  "location_link": "Prizren, zyra 2",
  "notes": "Interviste teknike"
}
```

`notes` eshte opsionale. Aplikimi duhet te ekzistoje dhe mund te kete vetem nje interviste.

## Gabimet

Gabimet kthehen si JSON me fushen `error`:

```json
{
  "error": "A field in the request is invalid."
}
```

Statuset e zakonshme jane `400` (kerkese e keqformuar), `401` (mungon ose eshte i pavlefshem token-i), `403` (rol/qasje e ndaluar), `404` (resursi ose rruga nuk ekziston), `405` (metode HTTP e pambeshtetur), `409` (konflikt, si aplikimi i dyfishte) dhe `422` (validimi deshton). Gabimet e databazes nuk ekspozojne detaje te brendshme te lidhjes.

## Kontrollet automatike

Ne PowerShell, nga rrënja e projektit:

```powershell
& 'C:\xampp\php\php.exe' -l backend\index.php
& 'C:\xampp\php\php.exe' backend\tests\Unit\AuthServiceTest.php
& 'C:\xampp\php\php.exe' backend\tests\Unit\UserManagementTest.php
& 'C:\xampp\php\php.exe' backend\tests\Feature\JobFlowTest.php
```