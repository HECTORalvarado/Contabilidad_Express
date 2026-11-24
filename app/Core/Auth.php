<?php
namespace App\Core;

class Auth {
    private const IDLE_LIMIT = 1800; // 30 minutos

    public static function user(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user'])) {
            return null;
        }
        $ultimo = (int) ($_SESSION['last_activity'] ?? 0);
        if ($ultimo > 0 && (time() - $ultimo) > self::IDLE_LIMIT) {
            unset($_SESSION['user'], $_SESSION['last_activity']);
            Flash::error('La sesión expiró por inactividad. Vuelva a iniciar sesión.');
            return null;
        }
        $_SESSION['last_activity'] = time();
        return $_SESSION['user'];
    }

    public static function check(): bool {
        return self::user() !== null;
    }

    public static function id(): ?int {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function role(): ?string {
        $user = self::user();
        return $user['rol_nombre'] ?? null;
    }

    public static function hasRole(string|array $roles): bool {
        $user = self::user();
        if (!$user) {
            return false;
        }
        $roles = is_string($roles) ? [$roles] : $roles;
        return in_array($user['rol_nombre'] ?? '', $roles, true);
    }

    public static function login(array $usuario): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email'],
            'rol_id' => (int) $usuario['rol_id'],
            'rol_nombre' => $usuario['rol_nombre'],
        ];
        $_SESSION['last_activity'] = time();
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function require(string|array $roles = []): void {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }

        if ($roles !== [] && !self::hasRole($roles)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Acceso denegado']);
            exit;
        }
    }
}
