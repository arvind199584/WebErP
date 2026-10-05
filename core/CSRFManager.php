<?php

declare(strict_types=1);

namespace App\Core;

class CSRFManager
{
    /**
     * Generates a CSRF token and stores it in the session.
     * @return string
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Validates a CSRF token against the one stored in the session.
     * @param string|null $token
     * @return bool
     */
    public static function validateToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Returns a hidden input field with the CSRF token.
     * @return string
     */
    public static function getTokenInput(): string
    {
        $token = self::generateToken();
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
    }
}
