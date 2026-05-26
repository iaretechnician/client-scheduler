<?php

declare(strict_types=1);

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']);
}

function admin_login(string $username, string $password, array $config): bool
{
    $expectedUser = (string) ($config['admin']['username'] ?? '');
    $expectedPass = (string) ($config['admin']['password'] ?? '');

    if (!hash_equals($expectedUser, $username)) {
        return false;
    }

    // Development mode supports plain-text config password.
    // Production should use password_hash() and password_verify().
    $isValid = str_starts_with($expectedPass, '$2y$')
        ? password_verify($password, $expectedPass)
        : hash_equals($expectedPass, $password);

    if ($isValid) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
    }

    return $isValid;
}

function admin_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        redirect_to('/admin/login.php');
    }
}
