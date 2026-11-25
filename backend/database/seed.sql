-- Optional, repeatable demo vacancies. Create an admin, HR, or manager account first.
-- This script never creates users or passwords; the listings are fictional samples.
INSERT INTO jobs (title, description, department, location, status, created_by, deadline)
SELECT
    sample.title,
    sample.description,
    sample.department,
    sample.location,
    'open',
    staff.id,
    DATE_ADD(CURDATE(), INTERVAL sample.deadline_days DAY)
FROM (
    SELECT
        'Zhvillues/e PHP' AS title,
        'Zhvillo dhe mirembaj API REST, shkruaj teste dhe bashkepuno me ekipin e produktit.' AS description,
        'Inxhinieri' AS department,
        'Prizren / Hybrid' AS location,
        45 AS deadline_days
    UNION ALL
    SELECT
        'Specialist/e i/e burimeve njerezore',
        'Koordino procesin e rekrutimit, komuniko me kandidatet dhe mbeshtet ekipet e brendshme.',
        'Burime njerezore',
        'Prishtine / Ne zyre',
        35
    UNION ALL
    SELECT
        'Dizajner/e UX/UI',
        'Kthe nevoja te perdoruesve ne rrjedha dhe nderfaqe te qarta per produkte web.',
        'Produkt',
        'Remote / Kosove',
        40
    UNION ALL
    SELECT
        'Inxhinier/e Frontend (React)',
        'Nderto pervoja web te shpejta dhe te qasshme me React. Bashkepuno ngushte me dizajnin dhe backend-in, shkruaj teste dhe kujdesu per cilesine e kodit.',
        'Teknologji',
        'Prishtine / Hybrid',
        35
    UNION ALL
    SELECT
        'Analist/e i/e te Dhenave',
        'Kthe te dhenat ne vendime te kuptueshme: krijo raporte, puno me SQL dhe vizualizime, dhe ndihmo ekipet te matin rezultatet.',
        'Analitika',
        'Remote / Kosove',
        32
    UNION ALL
    SELECT
        'Menaxher/e i/e Projekteve',
        'Koordino ekipet dhe afatet, komuniko qarte me palet e interesuara dhe siguro qe projektet digjitale dorezohen me cilesi.',
        'Menaxhim',
        'Prizren / Hybrid',
        38
    UNION ALL
    SELECT
        'Specialist/e i/e Marketingut Digjital',
        'Planifiko fushata online, krijo permbajtje qe angazhon audiencen dhe analizo performancen per te permiresuar rezultatet.',
        'Marketing',
        'Prishtine / Remote',
        28
    UNION ALL
    SELECT
        'Koordinator/e i/e Mbeshtejes ndaj Klienteve',
        'Ndihmo klientet permes email-it dhe bisedes online, zgjidh kerkesat me empati dhe bashkepuno me ekipet e produktit.',
        'Sherbim ndaj klienteve',
        'Prizren / Ne zyre',
        30
    UNION ALL
    SELECT
        'Specialist/e i/e Sigurise se Informacionit',
        'Mbeshtet praktikat e sigurise, dokumento kontrollet dhe bashkepuno me ekipin teknik per te mbrojtur sherbimet dhe te dhenat.',
        'Teknologji',
        'Remote / Hybrid',
        42
) AS sample
JOIN (
    SELECT id
    FROM users
    WHERE role IN ('admin', 'hr', 'manager')
    ORDER BY id
    LIMIT 1
) AS staff
WHERE NOT EXISTS (
    SELECT 1
    FROM jobs AS existing
    WHERE existing.title = sample.title
      AND existing.department = sample.department
);