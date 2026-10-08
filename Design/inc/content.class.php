<?php
declare(strict_types=1);

/**
 * Načte obsah jedné CMS stránky.
 * Při nedostupné DB nebo neexistující CMS struktuře vrátí prázdná data,
 * takže veřejná šablona může použít svůj bezpečný fallback.
 */
function racCmsLoadPage(string $pageCode, string $languageCode): array
{
    if (!function_exists('racFrontendPdo')) {
        return array('meta' => array(), 'sections' => array());
    }

    $pdo = racFrontendPdo();

    if (!$pdo instanceof PDO) {
        return array('meta' => array(), 'sections' => array());
    }

    try {
        $stmtPage = $pdo->prepare(
            'SELECT id
             FROM pages
             WHERE code = :code AND active = 1
             LIMIT 1'
        );
        $stmtPage->execute(array(':code' => $pageCode));
        $pageId = (int)$stmtPage->fetchColumn();

        if ($pageId <= 0) {
            return array('meta' => array(), 'sections' => array());
        }

        $requestedCode = strtolower(trim($languageCode));
        $defaultCode = defined('DEFAULT_LANGUAGE')
            ? strtolower((string)DEFAULT_LANGUAGE)
            : 'cs';

        $stmtLanguages = $pdo->prepare(
            'SELECT id, code
             FROM languages
             WHERE code = :requested
                OR code = :default_code
                OR code = "cs"'
        );
        $stmtLanguages->execute(array(
            ':requested' => $requestedCode,
            ':default_code' => $defaultCode,
        ));

        $languageIds = array();

        foreach ($stmtLanguages->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $languageIds[strtolower((string)$row['code'])] = (int)$row['id'];
        }

        $requestedId = isset($languageIds[$requestedCode])
            ? $languageIds[$requestedCode]
            : 0;

        $defaultId = isset($languageIds[$defaultCode])
            ? $languageIds[$defaultCode]
            : 0;

        $czechId = isset($languageIds['cs'])
            ? $languageIds['cs']
            : 0;

        $stmtMeta = $pdo->prepare(
            'SELECT
                req.page_title AS req_page_title,
                req.meta_title AS req_meta_title,
                req.meta_description AS req_meta_description,
                def.page_title AS def_page_title,
                def.meta_title AS def_meta_title,
                def.meta_description AS def_meta_description,
                cs.page_title AS cs_page_title,
                cs.meta_title AS cs_meta_title,
                cs.meta_description AS cs_meta_description
             FROM pages p
             LEFT JOIN page_translations req
                ON req.page_id = p.id AND req.language_id = :requested_id
             LEFT JOIN page_translations def
                ON def.page_id = p.id AND def.language_id = :default_id
             LEFT JOIN page_translations cs
                ON cs.page_id = p.id AND cs.language_id = :czech_id
             WHERE p.id = :page_id
             LIMIT 1'
        );
        $stmtMeta->execute(array(
            ':requested_id' => $requestedId,
            ':default_id' => $defaultId,
            ':czech_id' => $czechId,
            ':page_id' => $pageId,
        ));
        $metaRow = $stmtMeta->fetch(PDO::FETCH_ASSOC) ?: array();

        $pick = static function (array $row, string $base): string {
            foreach (array('req_', 'def_', 'cs_') as $prefix) {
                $value = isset($row[$prefix . $base])
                    ? trim((string)$row[$prefix . $base])
                    : '';

                if ($value !== '') {
                    return $value;
                }
            }

            return '';
        };

        $meta = array(
            'page_title' => $pick($metaRow, 'page_title'),
            'meta_title' => $pick($metaRow, 'meta_title'),
            'meta_description' => $pick($metaRow, 'meta_description'),
        );

        $stmtFields = $pdo->prepare(
            'SELECT
                s.code AS section_code,
                f.code AS field_code,
                req.value AS req_value,
                def.value AS def_value,
                cs.value AS cs_value
             FROM page_sections s
             JOIN page_section_fields f ON f.section_id = s.id
             LEFT JOIN page_section_field_translations req
                ON req.field_id = f.id AND req.language_id = :requested_id
             LEFT JOIN page_section_field_translations def
                ON def.field_id = f.id AND def.language_id = :default_id
             LEFT JOIN page_section_field_translations cs
                ON cs.field_id = f.id AND cs.language_id = :czech_id
             WHERE s.page_id = :page_id
               AND s.active = 1
             ORDER BY s.sort_order ASC, f.sort_order ASC'
        );
        $stmtFields->execute(array(
            ':requested_id' => $requestedId,
            ':default_id' => $defaultId,
            ':czech_id' => $czechId,
            ':page_id' => $pageId,
        ));

        $sections = array();

        foreach ($stmtFields->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $sectionCode = (string)$row['section_code'];
            $fieldCode = (string)$row['field_code'];

            $value = '';

            foreach (array('req_value', 'def_value', 'cs_value') as $column) {
                $candidate = isset($row[$column])
                    ? trim((string)$row[$column])
                    : '';

                if ($candidate !== '') {
                    $value = $candidate;
                    break;
                }
            }

            if (!isset($sections[$sectionCode])) {
                $sections[$sectionCode] = array();
            }

            $sections[$sectionCode][$fieldCode] = $value;
        }

        return array(
            'meta' => $meta,
            'sections' => $sections,
        );
    } catch (Throwable $e) {
        return array('meta' => array(), 'sections' => array());
    }
}

function racCmsValue(string $section, string $field, string $fallback = ''): string
{
    global $racCmsPageContent;

    if (
        isset($racCmsPageContent['sections'][$section][$field]) &&
        trim((string)$racCmsPageContent['sections'][$section][$field]) !== ''
    ) {
        return (string)$racCmsPageContent['sections'][$section][$field];
    }

    return $fallback;
}

function racCmsMeta(string $field, string $fallback = ''): string
{
    global $racCmsPageContent;

    if (
        isset($racCmsPageContent['meta'][$field]) &&
        trim((string)$racCmsPageContent['meta'][$field]) !== ''
    ) {
        return (string)$racCmsPageContent['meta'][$field];
    }

    return $fallback;
}