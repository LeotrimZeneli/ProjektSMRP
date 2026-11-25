# SMRP — Sistemi për Menaxhimin e Rekrutimit

SMRP është një aplikacion web për menaxhimin e procesit të rekrutimit: nga publikimi i vendeve të punës dhe aplikimi i kandidatëve, deri te shqyrtimi i aplikimeve dhe planifikimi i intervistave.

## Funksionalitetet

- Regjistrim dhe kyçje me autentikim JWT.
- Shfaqje publike e vendeve të hapura të punës.
- Krijim dhe menaxhim i shpalljeve nga stafi HR dhe administratori.
- Aplikim për punë me CV në format PDF, DOC ose DOCX.
- Shqyrtim i aplikimeve dhe përditësim i statusit nga HR/administratori.
- Planifikim dhe listim i intervistave.
- Qasje e autorizuar në CV-të e ngarkuara.

## Teknologjitë

- **Frontend:** React 19 dhe Vite
- **Backend:** PHP 8.1+ me API REST dhe PDO
- **Databaza:** MySQL
- **Autentikimi:** JSON Web Token (JWT)

Backend-i është i organizuar në shtresa: Controllers, Services, Repositories dhe Middleware. Kodi i aplikacionit gjendet te [`backend/`](./backend/), ndërsa dokumentacioni teknik te [`docs/`](./docs/).

## Nisja lokale me XAMPP

1. Nise **Apache** dhe **MySQL** nga XAMPP Control Panel.
2. Në phpMyAdmin importo [`schema.sql`](./backend/database/schema.sql) për të krijuar databazën `smrp_db` dhe tabelat e nevojshme.
3. Regjistro llogarinë tënde në aplikacion, pastaj cakto rolin `admin` për email-in tënd nga phpMyAdmin. Udhëzimet gjenden më poshtë.
4. (Opsionale) Importo [`seed.sql`](./backend/database/seed.sql) për tri shpallje shembull.
5. Në PowerShell, nis frontend-in:

   ```powershell
   cd C:\xampp\htdocs\ProjektSMRP\ProjektSMRP\frontend
   npm install
   npm run dev
   ```

   Hape [http://127.0.0.1:5173/](http://127.0.0.1:5173/). Vite dërgon automatikisht kërkesat `/api/v1` te Apache/PHP.

6. Për versionin statik, ndalo Vite me `Ctrl+C` dhe ekzekuto:

   ```powershell
   npm run build
   ```

5. Hape build-in në [http://localhost:8080/ProjektSMRP/ProjektSMRP/frontend/public/app-react/](http://localhost:8080/ProjektSMRP/ProjektSMRP/frontend/public/app-react/).
6. API health check: [http://localhost:8080/ProjektSMRP/ProjektSMRP/backend/api/v1/health](http://localhost:8080/ProjektSMRP/ProjektSMRP/backend/api/v1/health).

Ky mjedis përdor portin `8080` për Apache sepse porti `80` është i zënë. Në instalime ku Apache përdor portin `80`, hiqe `:8080` nga URL-të. Konfigurimi pret PHP 8.1+ me `pdo_mysql`, `fileinfo` dhe `mbstring`, si dhe MySQL në portin `3306`.

## Dokumentacioni

- [Konfigurimi dhe nisja me XAMPP](./docs/xampp-setup.md)
- [Dokumentacioni i API-së REST](./docs/api.md)
- [Arkitektura](./docs/architecture.md)
- [Modeli relacional (ERD)](./docs/erd.md)
- [Skema e databazës](./backend/database/schema.sql)
- [Të dhëna demo opsionale](./backend/database/seed.sql)

## Raportet teknike

- [Raporti Teknik 1](./docs/raporti-teknik-1.docx)
- [Raporti Teknik 2](./docs/raporti-teknik-2.docx)
- [Raporti Teknik 3 — Word](./docs/raporti-teknik-3.docx)
- [Raporti Teknik 3](./docs/raporti-teknik-3.md)

Raporti Teknik 4 (testim, siguri dhe deployment) mbetet fazë e ardhshme sipas udhëzuesit të lëndës.

## Siguria

Çelësi lokal JWT krijohet automatikisht dhe ruhet jashtë Git-it. CV-të ruhen në backend dhe nuk shërbehen drejtpërdrejt nga Apache; shkarkimi bëhet përmes API-së me autorizim. Përpara publikimit jashtë ambientit lokal, vendos një `JWT_SECRET` privat dhe kredenciale të sigurta për databazën.
