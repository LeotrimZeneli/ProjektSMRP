# Running SMRP with XAMPP

## One-time setup

1. Clone the repository into `C:\xampp\htdocs\ProjektSMRP`. The application source is in the `ProjektSMRP` subfolder of the clone.
2. Open XAMPP Control Panel and start **Apache** and **MySQL**. No other XAMPP configuration changes are required.
3. Open `http://localhost/phpmyadmin`, select the server, and use **Import** to import `backend/database/schema.sql`. The script creates and selects `smrp_db`, then creates its four tables. Re-importing is safe for tables that already exist.
4. For development, open PowerShell in `C:\xampp\htdocs\ProjektSMRP\ProjektSMRP\frontend` and run `npm install` followed by `npm run dev`. Open `http://127.0.0.1:5173/`; the Vite proxy forwards API requests to Apache.
5. For a static build, run `npm run build` in the same directory and open `http://localhost:8080/ProjektSMRP/ProjektSMRP/frontend/public/app-react/`.

The current XAMPP installation uses Apache on port `8080` because IIS occupies port `80`. If Apache uses port `80` on your computer, omit `:8080` from the URLs. The corresponding API health check is `http://localhost:8080/ProjektSMRP/ProjektSMRP/backend/api/v1/health`.

The database connection defaults to MySQL at `127.0.0.1:3306` with user `root` and an empty password, which is the usual local XAMPP setup. If your MySQL credentials or port differ, set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` in Apache's environment before starting it. The defaults use `smrp_db`. If you already created the earlier database named `projektsmrp`, this app does not use or delete it; the schema creates `smrp_db`.

The backend needs PHP 8.1 or later and PHP's `pdo_mysql`, `fileinfo`, and `mbstring` extensions. They are normally enabled in current XAMPP releases. CV uploads allow up to 5 MB; only if uploads fail because of PHP limits, open **Config > PHP (php.ini)** in XAMPP, set `upload_max_filesize` to at least `5M` and `post_max_size` to at least `6M`, then restart Apache.

On first use, the API creates a random local JWT secret in `backend/storage/jwt_secret`; that folder is blocked from direct web access and excluded from Git. Keep this key when using existing login tokens. Before exposing the app outside your computer, set a private `JWT_SECRET` of at least 32 characters in Apache's environment and protect it as a production secret.

Protected API requests require a signed JWT access token in the HTTP `Authorization` header. Routes are under `/api/v1`; the backend requires Apache's `mod_rewrite` and `.htaccess` overrides, which are enabled by default in XAMPP.

## Create the first HR account

Register through the app first. New accounts are applicants. To let a trusted account create job posts and manage applications, open `smrp_db` in phpMyAdmin, select **SQL**, and run the following with that account's email:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
```

Sign out and sign in again to receive a token reflecting the updated role.

To populate the public page with three fictional openings, import `backend/database/seed.sql` after creating the first HR/admin account. The seed is repeatable and does not create users or passwords. Skip it if you prefer to publish your own job listings.

`../.env.example` documents the supported database and CORS variables. PHP does not load `.env` files by default; configure those values in Apache's environment or XAMPP configuration.

CV files are saved under `backend/storage/uploads/cvs`; Apache is blocked from serving that folder directly. The API provides access-controlled CV downloads to the applicant and HR/admin accounts.
