-- RAC CMS
-- Migrace 006: stránka Kontakt
-- Databáze: rac
-- Vyžaduje migraci 004_cms_home.sql.

INSERT INTO pages (code, template, admin_label, active, sort_order)
VALUES ('contact', 'kontakt.php', 'Kontakt', 1, 30)
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
    'Kontakt',
    'kontakt',
    'Štefan Rác | Kontakt',
    'Kontaktní informace a formulář pro spojení s autorem Štefanem Rácem.'
FROM pages p
JOIN languages l ON l.code = 'cs'
WHERE p.code = 'contact'
ON DUPLICATE KEY UPDATE
    page_title = VALUES(page_title),
    slug = VALUES(slug),
    meta_title = VALUES(meta_title),
    meta_description = VALUES(meta_description);

-- Sekce.
INSERT INTO page_sections (page_id, code, admin_label, active, sort_order)
SELECT id, 'header', 'Hlavička stránky', 1, 10
FROM pages
WHERE code = 'contact'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    active = VALUES(active),
    sort_order = VALUES(sort_order);

INSERT INTO page_sections (page_id, code, admin_label, active, sort_order)
SELECT id, 'form_intro', 'Úvod kontaktního formuláře', 1, 20
FROM pages
WHERE code = 'contact'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    active = VALUES(active),
    sort_order = VALUES(sort_order);

INSERT INTO page_sections (page_id, code, admin_label, active, sort_order)
SELECT id, 'contact_info', 'Kontaktní údaje', 1, 30
FROM pages
WHERE code = 'contact'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    active = VALUES(active),
    sort_order = VALUES(sort_order);

-- Pole hlavičky.
INSERT INTO page_section_fields
    (section_id, code, admin_label, field_type, required, sort_order)
SELECT
    s.id, 'title', 'Nadpis stránky', 'text', 1, 10
FROM page_sections s
JOIN pages p ON p.id = s.page_id
WHERE p.code = 'contact' AND s.code = 'header'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    field_type = VALUES(field_type),
    required = VALUES(required),
    sort_order = VALUES(sort_order);

-- Pole úvodu formuláře.
INSERT INTO page_section_fields
    (section_id, code, admin_label, field_type, required, sort_order)
SELECT
    s.id, 'tagline', 'Malý nadpis', 'text', 1, 10
FROM page_sections s
JOIN pages p ON p.id = s.page_id
WHERE p.code = 'contact' AND s.code = 'form_intro'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    field_type = VALUES(field_type),
    required = VALUES(required),
    sort_order = VALUES(sort_order);

INSERT INTO page_section_fields
    (section_id, code, admin_label, field_type, required, sort_order)
SELECT
    s.id, 'title', 'Hlavní nadpis', 'textarea', 1, 20
FROM page_sections s
JOIN pages p ON p.id = s.page_id
WHERE p.code = 'contact' AND s.code = 'form_intro'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    field_type = VALUES(field_type),
    required = VALUES(required),
    sort_order = VALUES(sort_order);

-- Kontaktní údaje.
INSERT INTO page_section_fields
    (section_id, code, admin_label, field_type, required, sort_order)
SELECT
    s.id, 'address', 'Adresa', 'textarea', 0, 10
FROM page_sections s
JOIN pages p ON p.id = s.page_id
WHERE p.code = 'contact' AND s.code = 'contact_info'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    field_type = VALUES(field_type),
    required = VALUES(required),
    sort_order = VALUES(sort_order);

INSERT INTO page_section_fields
    (section_id, code, admin_label, field_type, required, sort_order)
SELECT
    s.id, 'email', 'E-mail', 'text', 0, 20
FROM page_sections s
JOIN pages p ON p.id = s.page_id
WHERE p.code = 'contact' AND s.code = 'contact_info'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    field_type = VALUES(field_type),
    required = VALUES(required),
    sort_order = VALUES(sort_order);

INSERT INTO page_section_fields
    (section_id, code, admin_label, field_type, required, sort_order)
SELECT
    s.id, 'phone', 'Telefon', 'text', 0, 30
FROM page_sections s
JOIN pages p ON p.id = s.page_id
WHERE p.code = 'contact' AND s.code = 'contact_info'
ON DUPLICATE KEY UPDATE
    admin_label = VALUES(admin_label),
    field_type = VALUES(field_type),
    required = VALUES(required),
    sort_order = VALUES(sort_order);

INSERT INTO page_section_fields
    (section_id, code, admin_label, field_type, required, sort_order)
SELECT
    s.id, 'company_info', 'IČ / DIČ / další údaje', 'textarea', 0, 40
FROM page_sections s
JOIN pages p ON p.id = s.page_id
WHERE p.code = 'contact' AND s.code = 'contact_info'
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
        WHEN 'header.title' THEN 'Kontaktní informace'
        WHEN 'form_intro.tagline' THEN 'Kontaktní formulář'
        WHEN 'form_intro.title' THEN 'Pro vydavatelské nabídky, spolupráci a dotazy ke knihám můžeš autora kontaktovat'
        WHEN 'contact_info.address' THEN CONCAT('Ulice číslo popisné', CHAR(10), 'Karvniná, ČR')
        WHEN 'contact_info.email' THEN 'stefan.rac1@gmail.com'
        WHEN 'contact_info.phone' THEN '+42 ??????'
        WHEN 'contact_info.company_info' THEN CONCAT('IČ: ??????', CHAR(10), 'DIČ: ?????')
        ELSE NULL
    END
FROM page_section_fields f
JOIN page_sections s ON s.id = f.section_id
JOIN pages p ON p.id = s.page_id
JOIN languages l ON l.code = 'cs'
WHERE p.code = 'contact'
ON DUPLICATE KEY UPDATE value = VALUES(value);