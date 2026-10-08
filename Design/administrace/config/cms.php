<?php
declare(strict_types=1);

function cms_get_page(PDO $pdo, string $code): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, code, template, admin_label, active, sort_order
         FROM pages
         WHERE code = :code
         LIMIT 1'
    );
    $stmt->execute(array(':code' => $code));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function cms_get_active_languages(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, code, name, native_name, flag_path, is_default, sort_order
         FROM languages
         WHERE active = 1
         ORDER BY is_default DESC, sort_order ASC, id ASC'
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cms_get_page_translations(PDO $pdo, int $pageId): array
{
    $stmt = $pdo->prepare(
        'SELECT page_id, language_id, page_title, slug, meta_title, meta_description
         FROM page_translations
         WHERE page_id = :page_id'
    );
    $stmt->execute(array(':page_id' => $pageId));

    $result = array();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $result[(int)$row['language_id']] = $row;
    }

    return $result;
}

function cms_get_page_sections(PDO $pdo, int $pageId): array
{
    $stmt = $pdo->prepare(
        'SELECT
            s.id AS section_id,
            s.code AS section_code,
            s.admin_label AS section_label,
            s.sort_order AS section_sort,
            f.id AS field_id,
            f.code AS field_code,
            f.admin_label AS field_label,
            f.field_type,
            f.required,
            f.sort_order AS field_sort
         FROM page_sections s
         LEFT JOIN page_section_fields f ON f.section_id = s.id
         WHERE s.page_id = :page_id
           AND s.active = 1
         ORDER BY s.sort_order ASC, s.id ASC, f.sort_order ASC, f.id ASC'
    );
    $stmt->execute(array(':page_id' => $pageId));

    $sections = array();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $sectionId = (int)$row['section_id'];

        if (!isset($sections[$sectionId])) {
            $sections[$sectionId] = array(
                'id' => $sectionId,
                'code' => (string)$row['section_code'],
                'label' => (string)$row['section_label'],
                'fields' => array(),
            );
        }

        if ($row['field_id'] !== null) {
            $sections[$sectionId]['fields'][] = array(
                'id' => (int)$row['field_id'],
                'code' => (string)$row['field_code'],
                'label' => (string)$row['field_label'],
                'type' => (string)$row['field_type'],
                'required' => (int)$row['required'] === 1,
            );
        }
    }

    return array_values($sections);
}

function cms_get_field_translations(PDO $pdo, int $pageId): array
{
    $stmt = $pdo->prepare(
        'SELECT t.field_id, t.language_id, t.value
         FROM page_section_field_translations t
         JOIN page_section_fields f ON f.id = t.field_id
         JOIN page_sections s ON s.id = f.section_id
         WHERE s.page_id = :page_id'
    );
    $stmt->execute(array(':page_id' => $pageId));

    $values = array();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $fieldId = (int)$row['field_id'];
        $languageId = (int)$row['language_id'];

        if (!isset($values[$fieldId])) {
            $values[$fieldId] = array();
        }

        $values[$fieldId][$languageId] = (string)($row['value'] ?? '');
    }

    return $values;
}

function cms_section_complete(array $section, int $languageId, array $values): bool
{
    if (!$section['fields']) {
        return true;
    }

    foreach ($section['fields'] as $field) {
        if (!$field['required']) {
            continue;
        }

        $value = isset($values[$field['id']][$languageId])
            ? trim((string)$values[$field['id']][$languageId])
            : '';

        if ($value === '') {
            return false;
        }
    }

    return true;
}