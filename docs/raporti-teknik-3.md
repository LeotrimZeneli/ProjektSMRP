# Raporti Teknik 3 – Frontend-i, integrimi dhe progresi funksional

**Projekti:** Sistemi për Menaxhimin e Rekrutimit (SMRP)
**Statusi i dokumentit:** Përmbledhje e implementimit dhe verifikimit lokal

## 1. Qëllimi dhe gjendja e projektit

SMRP mbështet procesin e rekrutimit nga publikimi i vendeve të punës deri te aplikimi i kandidatëve, shqyrtimi i aplikimeve dhe planifikimi i intervistave. Në këtë fazë, fokusi është te përdorimi i funksioneve nga ndërfaqja dhe komunikimi i tyre me API-në dhe databazën.

Stack-u i implementuar përbëhet nga React 19 dhe Vite në frontend, API REST me PHP në backend dhe MySQL për ruajtjen e të dhënave. Skema ruan përdoruesit, shpalljet, aplikimet dhe intervistat në tabela të lidhura me çelësa të huaj.

## 2. Frontend-i dhe organizimi i kodit

Pika aktive e nisjes është `src/main.jsx`, e cila renderon `App.jsx` dhe ngarkon stilet nga `src/assets/styles.css`. `App.jsx` mban state-in kryesor të përdoruesit, shpalljeve, aplikimeve, intervistave dhe gabimeve. Format dhe kartat kryesore janë komponentë të përcaktuar në të njëjtin skedar.

Komunikimi HTTP është centralizuar në `src/api.js`. Ky modul shton token-in JWT në kërkesat e autentikuara, përdor JSON për payload-et e zakonshme, lë `FormData` pa `Content-Type` të vendosur manualisht dhe kthen gabime të qarta për përgjigjet jo të suksesshme ose jo-JSON.

Ndërfaqja ka forma për kyçje/regjistrim, publikim shpalljeje, aplikim me CV, ndryshim statusi dhe planifikim interviste. Stilet përfshijnë përshtatje për ekrane të vogla. Për versionin aktual, shumica e rrjedhës funksionale është e përqendruar në `App.jsx`; kjo e bën demonstrimin të drejtpërdrejtë, por ndarja e mëtejshme në faqe dhe shërbime mund të jetë përmirësim pas kësaj faze.

## 3. Arkitektura e integrimit

```text
Përdoruesi
   ↓ veprim në UI
React / Vite (App.jsx)
   ↓ kërkesë HTTP, JSON ose multipart/form-data
API REST në PHP (/api/v1)
   ↓ autentikim, autorizim dhe logjikë shërbimi
Repositories / PDO
   ↓
MySQL (users, jobs, applications, interviews)
   ↑
Përgjigjja e API-së → përditësimi i state-it dhe UI-së
```

Në zhvillim, Vite përcjell kërkesat `/api/v1` te backend-i lokal përmes proxy-t. Në build-in e prodhimit, adresa e API-së llogaritet relativisht ndaj aplikacionit të publikuar. Kërkesat private përdorin JWT në header-in `Authorization`; token-i mbahet në `sessionStorage`.

## 4. Rrjedhat funksionale të implementuara

| Rrjedha | Veprimi në frontend | Integrimi dhe rezultati |
|---|---|---|
| Kyçja dhe regjistrimi | Përdoruesi plotëson formën; në sukses, UI ruan token-in dhe të dhënat e përdoruesit. | `POST /auth/login` ose `POST /auth/register`; pas rifreskimit, `GET /auth/me` verifikon sesionin. |
| Shfaqja dhe publikimi i vendeve | Vizitori shfleton shpalljet; HR/admin mund të krijojë shpallje. | `GET /jobs` lexon shpalljet; `POST /jobs` ruan një të re pas validimit në backend. |
| Aplikimi me CV | Aplikuesi zgjedh CV-në PDF/DOC/DOCX dhe mund të shtojë letër motivimi. | `POST /applications` dërgon `multipart/form-data`; backend-i kontrollon shpalljen dhe skedarin, e ruan CV-në dhe regjistron aplikimin. |
| Shqyrtimi i aplikimit | HR/admin ndryshon statusin dhe mund të shkarkojë CV-në. | `PATCH /applications/{id}/status` ruan statusin; `GET /applications/{id}/cv` e shërben CV-në me autorizim. |
| Planifikimi i intervistës | HR/admin cakton datën, orën, lokacionin/lidhjen dhe shënimet. | `POST /interviews` ruan intervistën; `GET /interviews` e kthen për shfaqje në UI. |

### Shembull i rrjedhës së aplikimit deri te databaza

1. Aplikuesi zgjedh një shpallje të hapur dhe plotëson formularin me CV.
2. Frontend-i ndërton `FormData` me `job_id`, skedarin dhe letrën motivuese opsionale.
3. `api.js` shton JWT-në dhe dërgon kërkesën te `POST /api/v1/applications`.
4. Backend-i verifikon përdoruesin, ID-në dhe gjendjen e shpalljes; validon skedarin dhe ruan CV-në.
5. Repository e regjistron aplikimin në MySQL. Kufizimi unik pengon aplikimin e dyfishtë për të njëjtën shpallje.
6. Frontend-i rifreskon listat; gabimet e API-së paraqiten në ndërfaqe.

Nëse ruajtja në databazë dështon pas ngarkimit të skedarit, shërbimi i aplikimeve e fshin skedarin e përkohshëm të ruajtur, që të mos mbeten skedarë pa aplikim përkatës.

## 5. Gjendjet e ndërfaqes dhe gabimet

- **Loading:** gjatë ngarkimit fillestar shfaqet mesazhi “Duke ngarkuar…”.
- **Empty state:** kur nuk ka shpallje, aplikime ose intervista, UI paraqet mesazhe përkatëse.
- **Error state:** gabimet nga API-ja shfaqen në një njoftim të dallueshëm; formularët shfaqin gjendje pritjeje dhe çaktivizojnë butonin gjatë dërgimit.
- **Validimi:** kontrollet bazë të formave bëhen në HTML/frontend; backend-i validon përsëri të dhënat dhe kthen gabime me status HTTP për input të pavlefshëm, autorizim ose konflikte.

## 6. Pjesë e code review-it: rifreskimi i të dhënave

Funksioni `refresh()` në `App.jsx` kërkon shpalljet dhe, kur ka përdorues të kyçur, aplikimet dhe intervistat. Kërkesat e pavarura kryhen paralelisht me `Promise.all`; për vizitorët, endpoint-et private nuk thirren. Pas veprimeve si aplikimi, ndryshimi i statusit ose krijimi i intervistës, rifreskimi e sinkronizon UI-në me të dhënat e kthyera nga backend-i.

Në anën e serverit, `ApplicationService::create()` validon `job_id`, kontrollon që shpallja pranon aplikime, ruan CV-në dhe trajton konfliktin e aplikimit të dyfishtë. Kjo e mban integritetin e të dhënave në backend, edhe nëse një kërkesë anashkalon kontrollet e ndërfaqes.

## 7. Skenari i rekomanduar për live demo

1. Hapni aplikacionin dhe tregoni listën publike të vendeve të punës.
2. Kyçuni si aplikues dhe demonstroni aplikimin në një shpallje me CV testuese.
3. Tregoni aplikimin e regjistruar në listën e aplikuesit.
4. Kyçuni si HR/admin, hapni të njëjtin aplikim dhe ndryshoni statusin.
5. Shkarkoni CV-në dhe krijoni një intervistë për aplikimin.
6. Kthehuni te lista e intervistave dhe demonstroni gjendjen e gabimit ose boshllëkut me një rast të përshtatshëm.

Për demonstrimin e ruajtjes në databazë, pas një veprimi mund të kontrollohet rreshti përkatës në MySQL. Duhet përdorur të dhëna testuese dhe jo CV personale.

## 8. Verifikimi dhe kufizimet

Verifikimi lokal përfshiu:

- Build-in e frontend-it me `npm run build`.
- PHP lint për 30 skedarë.
- Testet `AuthServiceTest.php` dhe `JobFlowTest.php`.
- Rrjedhën end-to-end për regjistrim/kyçje, krijim shpalljeje, aplikim me CV, parandalim të aplikimit të dyfishtë, shkarkim të autorizuar të CV-së, ndryshim statusi dhe planifikim interviste. Të dhënat e përdorura për provë u pastruan pas testimit.
- Kontrollin e API health check dhe të versionit statik në Apache; ndërfaqja u kontrollua edhe në pamje mobile.

Këto rezultate konfirmojnë funksionimin në mjedisin lokal të provës, por nuk përbëjnë certifikim për çdo mjedis prodhimi. Deployment publik, HTTPS dhe konfigurimi i sekreteve prodhuese nuk u testuan. Analizimi automatik i CV-ve me AI dhe dërgimi i email-eve nuk janë implementuar në versionin aktual.

## 9. Përmbledhje

Frontend-i i SMRP-së është i lidhur me API-në REST dhe mbulon rrjedhat kryesore të rekrutimit: autentikimin, shpalljet, aplikimet me CV, shqyrtimin e aplikimeve dhe intervistat. Gjendjet e ngarkimit, boshllëkut dhe gabimit janë të pranishme. Build-i, testet e përzgjedhura dhe rrjedha lokale end-to-end kaluan; puna e ardhshme mund të fokusohet në testim më të gjerë dhe përgatitjen e deployment-it prodhues.

## 10. Lista e Kontrollit për Dorëzimin e Raportit 3

| Kërkesa | Statusi | Dëshmia / veprimi |
|---|---|---|
| Prezantimi me live demo të funksionaliteteve kryesore | Skenari është përgatitur; demonstrimi live duhet të realizohet gjatë prezantimit. | Ndiqni skenarin në seksionin 7. Përdorni llogari dhe të dhëna testuese. |
| Versioni aktual i kodit në repository | Po | Repository: https://github.com/LeotrimZeneli/ProjektSMRP — dega `main`. Kodi i aplikacionit dhe dokumentacioni janë të publikuara; versioni i implementimit për këtë raport është commit-i `dabd6d5`. |
| Diagram ose shpjegim i flow-it të një funksionaliteti kryesor | Po | Flow-i i aplikimit me CV paraqitet më poshtë dhe përshkruhet te seksioni 4. |
| Përmbledhje e asaj që është përfunduar dhe çfarë mbetet deri në beta | Po | Përmbledhja dhe detyrat e mbetura paraqiten në tabelën e progresit më poshtë. |

### Flow i funksionalitetit: aplikimi për pozitë me CV

```text
Kandidati zgjedh pozitën dhe CV-në
                |
                v
React ndërton FormData dhe dërgon JWT-në
                |
                v
POST /api/v1/applications
                |
                v
PHP verifikon rolin, pozitën dhe skedarin
                |
                v
Ruhet CV-ja -> Repository regjistron aplikimin në MySQL
                |
                v
HTTP 201 -> React rifreskon listën e aplikimeve

Gabim validimi -> përgjigje HTTP me gabim
Aplikim i përsëritur -> HTTP 409 Conflict
```

### Përmbledhja e progresit deri në versionin beta

| E përfunduar | Mbetet për versionin beta |
|---|---|
| Frontend React/Vite i integruar me API-në PHP dhe MySQL. | Të realizohet prezantimi live dhe të kontrollohen të gjitha rrjedhat me llogari demo në ditën e prezantimit. |
| Autentikimi, rolet, shpalljet, aplikimi me CV, ndryshimi i statusit dhe planifikimi i intervistave. | Testim më i gjerë i regresionit dhe përdorshmërisë me të dhëna të ndryshme. |
| Validimi i të dhënave dhe skedarëve, kontrolli i aplikimeve të dyfishta dhe shkarkimi i autorizuar i CV-së. | Përgatitja dhe verifikimi i konfigurimit të deployment-it prodhues, HTTPS-it dhe sekreteve. |
| Build-i, PHP lint-i, testet `AuthServiceTest`/`JobFlowTest` dhe verifikimi lokal end-to-end. | Analiza automatike e CV-ve me AI dhe njoftimet me email nuk janë pjesë e versionit aktual; të planifikohen vetëm nëse kërkohen për beta. |

**Shënim për demonstrimin:** Raporti dhe repository përmbajnë skenarin e live demo-s, por vetë prezantimi para auditorit nuk mund të kryhet nga dokumenti; ai duhet të realizohet nga studenti gjatë dorëzimit.
