-- RAC CMS
-- Migrace 004: obecná struktura stránek + Úvodní stránka
-- Databáze: rac
-- Vyžaduje již existující tabulku languages.

CREATE TABLE IF NOT EXISTS pages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(100) NOT NULL,
    template VARCHAR(191) DEFAULT NULL,
    admin_label VARCHAR(191) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pages_code (code),
    KEY idx_pages_active_sort (active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_translations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    page_id INT UNSIGNED NOT NULL,
    language_id INT UNSIGNED NOT NULL,
    page_title VARCHAR(255) DEFAULT NULL,
    slug VARCHAR(191) DEFAULT NULL,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description TEXT NULL,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_page_translation (page_id, language_id),
    KEY idx_page_translation_language (language_id),
    CONSTRAINT fk_page_translations_page
        FOREIGN KEY (page_id) REFERENCES pages(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_page_translations_language
        FOREIGN KEY (language_id) REFERENCES languages(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_sections (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    page_id INT UNSIGNED NOT NULL,
    code VARCHAR(100) NOT NULL,
    admin_label VARCHAR(191) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_page_section (page_id, code),
    KEY idx_page_sections_sort (page_id, active, sort_order),
    CONSTRAINT fk_page_sections_page
        FOREIGN KEY (page_id) REFERENCES pages(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_section_fields (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    section_id INT UNSIGNED NOT NULL,
    code VARCHAR(100) NOT NULL,
    admin_label VARCHAR(191) NOT NULL,
    field_type VARCHAR(30) NOT NULL DEFAULT 'text',
    required TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_section_field (section_id, code),
    KEY idx_section_fields_sort (section_id, sort_order),
    CONSTRAINT fk_section_fields_section
        FOREIGN KEY (section_id) REFERENCES page_sections(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_section_field_translations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    field_id INT UNSIGNED NOT NULL,
    language_id INT UNSIGNED NOT NULL,
    value MEDIUMTEXT NULL,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_field_translation (field_id, language_id),
    KEY idx_field_translation_language (language_id),
    CONSTRAINT fk_field_translations_field
        FOREIGN KEY (field_id) REFERENCES page_section_fields(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_field_translations_language
        FOREIGN KEY (language_id) REFERENCES languages(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Úvodní stránka
-- ------------------------------------------------------------

INSERT INTO pages (code, template, admin_label, active, sort_order)
VALUES ('home', 'index.php', 'Úvodní stránka', 1, 10)
ON DUPLICATE KEY UPDATE
    template = VALUES(template),
    admin_label = VALUES(admin_label),
    active = VALUES(active),
    sort_order = VALUES(sort_order);

-- Základní SEO metadata v češtině.
INSERT INTO page_translations
    (page_id, language_id, page_title, slug, meta_title, meta_description)
SELECT
    p.id,
    l.id,
    'Úvodní stránka',
    NULL,
    'RAC',
    'Knihy a audioknihy'
FROM pages p
JOIN languages l ON l.code = 'cs'
WHERE p.code = 'home'
ON DUPLICATE KEY UPDATE
    page_title = VALUES(page_title),
    meta_title = VALUES(meta_title),
    meta_description = VALUES(meta_description);

-- Sekce.
INSERT INTO page_sections (page_id, code, admin_label, active, sort_order)
SELECT id, 'intro', 'Myšlenka, která všechno spojuje', 1, 10 FROM pages WHERE code = 'home'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), active = VALUES(active), sort_order = VALUES(sort_order);

INSERT INTO page_sections (page_id, code, admin_label, active, sort_order)
SELECT id, 'books_intro', 'Knihy & projekty', 1, 20 FROM pages WHERE code = 'home'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), active = VALUES(active), sort_order = VALUES(sort_order);

INSERT INTO page_sections (page_id, code, admin_label, active, sort_order)
SELECT id, 'author', 'O autorovi', 1, 30 FROM pages WHERE code = 'home'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), active = VALUES(active), sort_order = VALUES(sort_order);

INSERT INTO page_sections (page_id, code, admin_label, active, sort_order)
SELECT id, 'upcoming_intro', 'Připravujeme', 1, 40 FROM pages WHERE code = 'home'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), active = VALUES(active), sort_order = VALUES(sort_order);

INSERT INTO page_sections (page_id, code, admin_label, active, sort_order)
SELECT id, 'contact', 'Kontakt', 1, 50 FROM pages WHERE code = 'home'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), active = VALUES(active), sort_order = VALUES(sort_order);

INSERT INTO page_sections (page_id, code, admin_label, active, sort_order)
SELECT id, 'audio', 'Audioukázka', 1, 60 FROM pages WHERE code = 'home'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), active = VALUES(active), sort_order = VALUES(sort_order);

-- Pole sekcí.
INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'tagline', 'Malý nadpis', 'text', 1, 10
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'intro'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'title', 'Hlavní nadpis', 'textarea', 1, 20
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'intro'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'quote', 'Text / citát', 'textarea', 1, 30
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'intro'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'tagline', 'Malý nadpis', 'text', 1, 10
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'books_intro'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'title', 'Hlavní nadpis', 'text', 1, 20
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'books_intro'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'formats', 'Dostupné formáty', 'textarea', 1, 30
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'books_intro'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'tagline', 'Malý nadpis', 'text', 1, 10
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'author'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'title', 'Hlavní nadpis', 'text', 1, 20
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'author'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'text_one', 'První odstavec', 'textarea', 1, 30
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'author'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'text_two', 'Druhý odstavec', 'textarea', 1, 40
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'author'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'tagline', 'Malý nadpis', 'text', 1, 10
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'upcoming_intro'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'title', 'Hlavní nadpis', 'text', 1, 20
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'upcoming_intro'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'quote', 'Text / citát', 'textarea', 1, 30
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'upcoming_intro'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'tagline', 'Malý nadpis', 'text', 1, 10
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'contact'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'title', 'Hlavní nadpis', 'textarea', 1, 20
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'contact'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

INSERT INTO page_section_fields (section_id, code, admin_label, field_type, required, sort_order)
SELECT s.id, 'image_alt', 'Alternativní text obrázku', 'text', 0, 10
FROM page_sections s JOIN pages p ON p.id = s.page_id
WHERE p.code = 'home' AND s.code = 'audio'
ON DUPLICATE KEY UPDATE admin_label = VALUES(admin_label), field_type = VALUES(field_type), required = VALUES(required), sort_order = VALUES(sort_order);

-- Český obsah z dnešní homepage.
INSERT INTO page_section_field_translations (field_id, language_id, value)
SELECT
    f.id,
    l.id,
    CASE CONCAT(s.code, '.', f.code)
        WHEN 'intro.tagline' THEN 'Myšlenka, která všechno spojuje'
        WHEN 'intro.title' THEN CONCAT('Nejdelší cesta nevede přes svět.', CHAR(10), 'Vede do člověka.')
        WHEN 'intro.quote' THEN 'Každá kniha otevírá jinou kapitolu lidského života. Sebepoznání, láska, rodina, společnost i odvaha podívat se pravdě do očí. Jednotlivé příběhy. Jedna společná cesta.'
        WHEN 'books_intro.tagline' THEN 'Autorský svět'
        WHEN 'books_intro.title' THEN 'Knihy & projekty'
        WHEN 'books_intro.formats' THEN CONCAT('Knihy jsou dostupné v těchto podobách:', CHAR(10), 'Elektronická kniha / Tištěná kniha / Audiokniha')
        WHEN 'author.tagline' THEN 'O autorovi'
        WHEN 'author.title' THEN 'Slova mají smysl, když v nich poznáš život.'
        WHEN 'author.text_one' THEN 'Štefan Rác je český autor, který ve své tvorbě zkoumá člověka, lidské vztahy a otázky, na které neexistují pohodlné odpovědi.'
        WHEN 'author.text_two' THEN 'Jeho autorský svět propojuje sebepoznání, lásku, rodinu i kritický pohled na společnost. Každý rukopis stojí na přesvědčení, že silný příběh nezačíná efektem, ale opravdovostí.'
        WHEN 'upcoming_intro.tagline' THEN 'Další kapitoly'
        WHEN 'upcoming_intro.title' THEN 'Na čem se pracuje'
        WHEN 'upcoming_intro.quote' THEN 'Bez vymyšlených termínů. S důrazem na kvalitu.'
        WHEN 'contact.tagline' THEN 'Některé věci začínají jednou zprávou.'
        WHEN 'contact.title' THEN 'Pro vydavatelské nabídky, spolupráci a dotazy ke knihám můžeš autora kontaktovat přímo e-mailem.'
        WHEN 'audio.image_alt' THEN 'Audioukázka'
        ELSE NULL
    END
FROM page_section_fields f
JOIN page_sections s ON s.id = f.section_id
JOIN pages p ON p.id = s.page_id
JOIN languages l ON l.code = 'cs'
WHERE p.code = 'home'
ON DUPLICATE KEY UPDATE value = VALUES(value);