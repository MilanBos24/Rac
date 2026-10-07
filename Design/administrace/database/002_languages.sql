-- RAC ADMINISTRACE
-- Migrace 002: správa jazyků
-- Databáze: rac
-- Bezpečné spuštění nad již existující databází.

CREATE TABLE IF NOT EXISTS languages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(100) NOT NULL,
    native_name VARCHAR(100) NOT NULL,
    locale VARCHAR(20) DEFAULT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_languages_code (code),
    KEY idx_languages_active_sort (active, sort_order),
    KEY idx_languages_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO languages
    (code, name, native_name, locale, is_default, active, sort_order)
VALUES
    ('cs', 'Čeština',     'Čeština',    'cs_CZ', 1, 1, 10),
    ('sk', 'Slovenština', 'Slovenčina', 'sk_SK', 0, 1, 20),
    ('pl', 'Polština',    'Polski',      'pl_PL', 0, 1, 30),
    ('en', 'Angličtina',  'English',     'en_GB', 0, 1, 40),
    ('de', 'Němčina',     'Deutsch',     'de_DE', 0, 1, 50)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    native_name = VALUES(native_name),
    locale = VALUES(locale);
