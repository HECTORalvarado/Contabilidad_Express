<?php
namespace App\Core;

class Auth {
    public static function user(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool {
        return self::user() !== null;
    }

    public static function require(string|array $roles): void {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }

        $user = self::user();
        $userRol = $user['rol_nombre'] ?? '';

        $rolesArray = is_string($roles) ? [$roles] : $roles;

        if (!in_array($userRol, $rolesArray, true)) {
            http_response_code(403);
            require __DIR__ . '/../Views/errors/403.php';
            exit;
        }
    }
}
