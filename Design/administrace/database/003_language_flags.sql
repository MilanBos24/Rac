-- RAC ADMINISTRACE
-- Migrace 003: vlastní vlajka jazykové mutace
-- Databáze: rac
--
-- flag_path je cesta relativní k /administrace/
-- např. uploads/languages/lang_a1b2c3.webp

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'languages'
      AND COLUMN_NAME = 'flag_path'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE languages ADD COLUMN flag_path VARCHAR(255) DEFAULT NULL AFTER locale',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;