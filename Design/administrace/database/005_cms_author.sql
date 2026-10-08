-- RAC CMS
-- Migrace 005: stránka O autorovi
-- Databáze: rac
-- Vyžaduje migraci 004_cms_home.sql.

INSERT INTO pages (code, template, admin_label, active, sort_order)
VALUES ('author', 'o-autorovi.php', 'O autorovi', 1, 20)
ON DUPLICATE KEY UPDATE
    template = VALUES(template),
    admin_label = VALUES(admin_label),
    active = VALUES(active),
    sort_order = VALUES(sort_order);

-- Česká metadata.
INSERT INTO page_translations
    (page_id, language_id, page_title, slug, meta_title, meta_description)
SELECT
    p.id,
    l.id,
    'O autorovi',
    'o-autorovi',
    'Štefan Rác | O autorovi',
    'Informace o autorovi Štefanu Rácovi a jeho tvorbě.'
FROM pages p
JOIN languages l ON l.code = 'cs'
WHERE p.code = 'author'
ON DUPLICATE KEY UPDATE
    page_title = VALUES(page_title),
    slug = VALUES(slug),
    meta_title = VALUES(meta_title),
    meta_description = VALUES(meta_description);

-- Sekce hlavičky.
INSERT INTO page_sections (page_id, code, admin_label, active, sort_order)
SELECT id, 'header', 'Hlavička stránky', 1, 10
FROM pages
WHERE code = 'author'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    active = VALUES(active),
    sort_order = VALUES(sort_order);

-- Hlavní obsah.
INSERT INTO page_sections (page_id, code, admin_label, active, sort_order)
SELECT id, 'content', 'Obsah stránky', 1, 20
FROM pages
WHERE code = 'author'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    active = VALUES(active),
    sort_order = VALUES(sort_order);

-- Pole hlavičky.
INSERT INTO page_section_fields
    (section_id, code, admin_label, field_type, required, sort_order)
SELECT
    s.id,
    'title',
    'Nadpis stránky',
    'text',
    1,
    10
FROM page_sections s
JOIN pages p ON p.id = s.page_id
WHERE p.code = 'author' AND s.code = 'header'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    field_type = VALUES(field_type),
    required = VALUES(required),
    sort_order = VALUES(sort_order);

-- Pole hlavního obsahu.
INSERT INTO page_section_fields
    (section_id, code, admin_label, field_type, required, sort_order)
SELECT
    s.id,
    'heading',
    'Hlavní nadpis',
    'text',
    1,
    10
FROM page_sections s
JOIN pages p ON p.id = s.page_id
WHERE p.code = 'author' AND s.code = 'content'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    field_type = VALUES(field_type),
    required = VALUES(required),
    sort_order = VALUES(sort_order);

INSERT INTO page_section_fields
    (section_id, code, admin_label, field_type, required, sort_order)
SELECT
    s.id,
    'text',
    'Text O autorovi',
    'textarea',
    0,
    20
FROM page_sections s
JOIN pages p ON p.id = s.page_id
WHERE p.code = 'author' AND s.code = 'content'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    field_type = VALUES(field_type),
    required = VALUES(required),
    sort_order = VALUES(sort_order);

-- Současný český obsah z webu.
INSERT INTO page_section_field_translations (field_id, language_id, value)
SELECT
    f.id,
    l.id,
    CASE CONCAT(s.code, '.', f.code)
        WHEN 'header.title' THEN 'Štefan Rác'
        WHEN 'content.heading' THEN 'Připravujeme...'
        WHEN 'content.text' THEN NULL
        ELSE NULL
    END
FROM page_section_fields f
JOIN page_sections s ON s.id = f.section_id
JOIN pages p ON p.id = s.page_id
JOIN languages l ON l.code = 'cs'
WHERE p.code = 'author'
ON DUPLICATE KEY UPDATE value = VALUES(value);