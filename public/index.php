<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

$config = require BASE_PATH . '/config/config.php';
$isDev = $config['app']['env'] === 'development';

error_reporting(E_ALL);
ini_set('display_errors', $isDev ? '1' : '0');
ini_set('log_errors', '1');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', '1800');
session_name('CONTASID');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

set_exception_handler(function (Throwable $e) use ($isDev): void {
    error_log('Excepción no controlada: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    try {
        App\Core\View::render('errors/500', ['title' => 'Error interno']);
    } catch (Throwable) {
        echo 'Error interno del servidor.';
    }
    if ($isDev) {
        error_log($e->getTraceAsString());
    }
});

$router = new App\Core\Router();
$router->get('/', [App\Controllers\HomeController::class, 'index']);
$router->get('/login', [App\Controllers\LoginController::class, 'show']);
$router->post('/login', [App\Controllers\LoginController::class, 'login']);
$router->post('/logout', [App\Controllers\LoginController::class, 'logout']);

$router->get('/usuarios', [App\Controllers\UsuariosController::class, 'index']);
$router->get('/usuarios/crear', [App\Controllers\UsuariosController::class, 'create']);
$router->post('/usuarios', [App\Controllers\UsuariosController::class, 'store']);
$router->get('/usuarios/{id}/editar', [App\Controllers\UsuariosController::class, 'edit']);
$router->post('/usuarios/{id}', [App\Controllers\UsuariosController::class, 'update']);
$router->post('/usuarios/{id}/estado', [App\Controllers\UsuariosController::class, 'toggle']);
$router->post('/usuarios/{id}/desbloquear', [App\Controllers\UsuariosController::class, 'unlock']);
$router->post('/usuarios/{id}/password', [App\Controllers\UsuariosController::class, 'resetPassword']);

$router->get('/bitacora', [App\Controllers\BitacoraController::class, 'index']);

$router->get('/terceros', [App\Controllers\TercerosController::class, 'index']);
$router->get('/terceros/crear', [App\Controllers\TercerosController::class, 'create']);
$router->post('/terceros', [App\Controllers\TercerosController::class, 'store']);
$router->get('/terceros/{id}/editar', [App\Controllers\TercerosController::class, 'edit']);
$router->post('/terceros/{id}', [App\Controllers\TercerosController::class, 'update']);
$router->post('/terceros/{id}/estado', [App\Controllers\TercerosController::class, 'toggle']);

$router->get('/facturas', [App\Controllers\FacturasController::class, 'index']);
$router->get('/facturas/crear', [App\Controllers\FacturasController::class, 'create']);
$router->post('/facturas', [App\Controllers\FacturasController::class, 'store']);
$router->get('/facturas/{id}/editar', [App\Controllers\FacturasController::class, 'edit']);
$router->post('/facturas/{id}', [App\Controllers\FacturasController::class, 'update']);
$router->post('/facturas/{id}/documentos', [App\Controllers\FacturasController::class, 'subirDocumento']);
$router->get('/documentos/{id}/descargar', [App\Controllers\FacturasController::class, 'descargarDocumento']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
