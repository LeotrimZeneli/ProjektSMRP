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

Backend-i është i organizuar në shtresa: Controllers, Services, Repositories dhe Middleware. Kodi i aplikacionit dhe dokumentacioni teknik gjenden në dosjen [`ProjektSMRP/`](./ProjektSMRP/).

## Nisja lokale me XAMPP

1. Vendose dosjen `ProjektSMRP` brenda `C:\xampp\htdocs\`.
2. Nise **Apache** dhe **MySQL** nga XAMPP Control Panel.
3. Në phpMyAdmin importo [`schema.sql`](./ProjektSMRP/backend/database/schema.sql) për të krijuar databazën `smrp_db` dhe tabelat e nevojshme.
4. Në terminal, ndërto frontend-in:

   ```powershell
   cd C:\xampp\htdocs\ProjektSMRP\frontend
   npm install
   npm run build
   ```

5. Hape aplikacionin në [http://localhost/ProjektSMRP/frontend/public/app-react/](http://localhost/ProjektSMRP/frontend/public/app-react/).
6. Kontrollo lidhjen e API-së në [http://localhost/ProjektSMRP/backend/api/v1/health](http://localhost/ProjektSMRP/backend/api/v1/health).

Konfigurimi fillestar pret PHP 8.1+ me `pdo_mysql`, `fileinfo` dhe `mbstring`, si dhe MySQL në portin `3306`. Për kredenciale ose konfigurim tjetër, shiko udhëzuesin e plotë.

## Dokumentacioni

- [Konfigurimi dhe nisja me XAMPP](./ProjektSMRP/docs/xampp-setup.md)
- [Dokumentacioni i API-së REST](./ProjektSMRP/docs/api.md)
- [Skema e databazës](./ProjektSMRP/backend/database/schema.sql)

## Siguria

Çelësi lokal JWT krijohet automatikisht dhe ruhet jashtë Git-it. CV-të ruhen në backend dhe nuk shërbehen drejtpërdrejt nga Apache; shkarkimi bëhet përmes API-së me autorizim. Përpara publikimit jashtë ambientit lokal, vendos një `JWT_SECRET` privat dhe kredenciale të sigurta për databazën.
