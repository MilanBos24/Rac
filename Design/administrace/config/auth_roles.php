<?php
declare(strict_types=1);

const ROLE_ORDER = array(
    'admin' => 10,
    'superadmin' => 20,
);

function role_label(string $role): string
{
    $role = strtolower($role);

    if ($role === 'superadmin') {
        return 'Superadmin';
    }

    if ($role === 'admin') {
        return 'Administrátor';
    }

    return $role;
}

function role_rank(?string $role): int
{
    $key = strtolower((string)$role);
    return isset(ROLE_ORDER[$key]) ? (int)ROLE_ORDER[$key] : 0;
}

function has_role(string $role): bool
{
    $user = current_user_row();

    return $user !== null &&
        strtolower((string)$user['role']) === strtolower($role);
}

function can_access_min(string $role): bool
{
    $user = current_user_row();

    return $user !== null &&
        role_rank(isset($user['role']) ? (string)$user['role'] : null) >= role_rank($role);
}

function require_min_role(string $role): void
{
    require_login();

    if (!can_access_min($role)) {
        header('Location: access-denied.php');
        exit;
    }
}

function can_manage_user(array $target): bool
{
    $current = current_user_row();

    if (!$current) {
        return false;
    }

    if (strtolower((string)$current['role']) === 'superadmin') {
        return true;
    }

    return strtolower((string)$target['role']) === 'admin';
}
