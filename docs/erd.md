# Modeli relacional i databazes

Skema e ekzekutueshme eshte [`../backend/database/schema.sql`](../backend/database/schema.sql). Ky diagram pasqyron marredheniet dhe kufizimet kryesore.

```mermaid
erDiagram
    USERS {
        int id PK
        string full_name
        string email UK
        string password
        enum role
        string phone_number
        datetime created_at
    }

    JOBS {
        int id PK
        string title
        text description
        string department
        string location
        enum status
        int created_by FK
        date deadline
        datetime created_at
    }

    APPLICATIONS {
        int id PK
        int user_id FK
        int job_id FK
        string cv_file_path
        text cover_letter
        enum status
        datetime applied_at
    }

    INTERVIEWS {
        int id PK
        int application_id FK_UK
        int interviewer_id FK
        datetime scheduled_at
        string location_link
        text notes
        datetime created_at
    }

    USERS ||--o{ JOBS : publishes
    USERS ||--o{ APPLICATIONS : submits
    JOBS ||--o{ APPLICATIONS : receives
    APPLICATIONS ||--o| INTERVIEWS : may_schedule
    USERS ||--o{ INTERVIEWS : conducts
```

## Relacionet dhe rregullat

| Relacioni | Kardinaliteti | Rregulla |
|---|---|---|
| Perdoruesi → shpalljet | 1:N | `jobs.created_by` lidhet me krijuesin; krijuesi nuk fshihet nese ka shpallje. |
| Perdoruesi → aplikimet | 1:N | `applications.user_id` lidhet me kandidatin; fshirja e perdoruesit fshin aplikimet e tij. |
| Shpallja → aplikimet | 1:N | `applications.job_id` lidhet me shpalljen; fshirja e shpalljes fshin aplikimet. |
| Aplikimi → intervista | 1:0..1 | `interviews.application_id` eshte unik; nje aplikim mund te kete maksimumi nje interviste. |
| Perdoruesi → intervistat | 1:N | `interviews.interviewer_id` lidhet me HR/admin qe e krijon; perdoruesi referues nuk fshihet. |

Indeksi unik `unique_applicant_per_job` mbi (`user_id`, `job_id`) garanton qe i njejti kandidat nuk mund te aplikoje dy here per te njejten shpallje. Statuset ruhen si `ENUM` ne MySQL dhe validohen gjithashtu ne shtresen e aplikacionit.