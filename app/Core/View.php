<?php
namespace App\Core;

class View {
    public static function render(string $view, array $data = [], ?string $layout = 'main'): void {
        $file = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($file)) {
            http_response_code(500);
            error_log("Vista no encontrada: $view");
            exit('Error interno del servidor.');
        }

        extract($data, EXTR_SKIP);
        $content = (function () use ($file, $data): string {
            extract($data, EXTR_SKIP);
            ob_start();
            require $file;
            return (string) ob_get_clean();
        })();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = BASE_PATH . '/app/Views/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            http_response_code(500);
            exit('Error interno del servidor.');
        }

        $usuario = Auth::user();
        $appName = (require BASE_PATH . '/config/config.php')['app']['name'];
        require $layoutFile;
    }

    public static function e(mixed $value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
