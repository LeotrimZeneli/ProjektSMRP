-- Optional, repeatable demo data. First create an account in the app and
-- promote it to hr/admin; this script never creates credentials or users.
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
        'Remote',
        30
) AS sample
JOIN (
    SELECT id
    FROM users
    WHERE role IN ('admin', 'hr')
    ORDER BY id
    LIMIT 1
) AS staff
WHERE NOT EXISTS (
    SELECT 1
    FROM jobs AS existing
    WHERE existing.title = sample.title
      AND existing.department = sample.department
);