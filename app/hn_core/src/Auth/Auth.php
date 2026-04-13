<?php
declare(strict_types=1);

namespace HnCore\Auth;

final class Auth
{
    private const SESSION_KEY = 'admin';
    private const LAST_ACTIVITY = 'last_activity';
    private const TIMEOUT = 1800; // 30 min

    public static function isAdmin(): bool
    {
        return !empty($_SESSION[self::SESSION_KEY]);
    }

    public static function login(): void
    {
        $_SESSION[self::SESSION_KEY] = true;
        $_SESSION[self::LAST_ACTIVITY] = time();
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY], $_SESSION[self::LAST_ACTIVITY]);
    }

    public static function checkTimeout(): void
    {
        if (!self::isAdmin()) {
            return;
        }

        $last = $_SESSION[self::LAST_ACTIVITY] ?? 0;

        if (time() - $last > self::TIMEOUT) {
            self::logout();
            self::redirectToLogin();
        }

        $_SESSION[self::LAST_ACTIVITY] = time();
    }

    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            self::storeRedirect();
            self::redirectToLogin();
        }

        self::checkTimeout();
    }

    private static function storeRedirect(): void
    {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/hn_admin/index.php';
    }

    public static function redirectAfterLogin(): void
    {
        $redirect = $_SESSION['redirect_after_login'] ?? '/hn_admin/index.php';

        if (!is_string($redirect) || !str_starts_with($redirect, '/')) {
            $redirect = '/hn_admin/index.php';
        }

        unset($_SESSION['redirect_after_login']);

        header('Location: ' . $redirect);
        exit;
    }

    public static function redirectToLogin(): void
    {
        header('Location: /hn_admin/login.php');
        exit;
    }
}