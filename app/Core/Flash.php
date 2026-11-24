<?php
namespace App\Core;

class Flash {
    public static function set(string $type, string $message): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function success(string $message): void {
        self::set('success', $message);
    }

    public static function error(string $message): void {
        self::set('danger', $message);
    }

    public static function render(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $html = '';
        foreach ($_SESSION['flash'] ?? [] as $flash) {
            $type = htmlspecialchars((string) $flash['type'], ENT_QUOTES, 'UTF-8');
            $message = htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8');
            $html .= '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
                . $message
                . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>'
                . '</div>';
        }
        unset($_SESSION['flash']);
        return $html;
    }
}
