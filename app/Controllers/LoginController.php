<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Models\Usuario;

class LoginController {
    public function show(): void {
        if (Auth::check()) {
            $this->redirect('/');
        }
        View::render('auth/login', ['title' => 'Iniciar sesión'], 'auth');
    }

    public function login(): void {
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            Flash::error('La solicitud expiró. Intente de nuevo.');
            $this->redirect('/login');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $usuario = $email !== '' ? Usuario::findByEmail($email) : null;
        $mensaje = 'No se pudo iniciar sesión. Verifique sus datos o intente más tarde.';

        if ($usuario && Usuario::estaBloqueado($usuario)) {
            Audit::log('login_bloqueado', 'usuarios', (int) $usuario['id'], null, null, 'Cuenta bloqueada temporalmente', (int) $usuario['id']);
            Flash::error($mensaje);
            $this->redirect('/login');
        }

        if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
            if ($usuario) {
                Usuario::registrarIntentoFallido((int) $usuario['id']);
            }
            Audit::log('login_fallido', 'usuarios', $usuario ? (int) $usuario['id'] : null, null, null, 'Credenciales incorrectas', $usuario ? (int) $usuario['id'] : null);
            Flash::error($mensaje);
            $this->redirect('/login');
        }

        if (!$usuario['activo']) {
            Audit::log('login_rechazado', 'usuarios', (int) $usuario['id'], null, null, 'Usuario inactivo', (int) $usuario['id']);
            Flash::error($mensaje);
            $this->redirect('/login');
        }

        if (password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
            Usuario::setPassword((int) $usuario['id'], $password);
        }

        Usuario::limpiarIntentos((int) $usuario['id']);
        Auth::login($usuario);
        Audit::log('login', 'usuarios', (int) $usuario['id'], null, ['email' => $usuario['email']], null, (int) $usuario['id']);
        $this->redirect('/');
    }

    public function logout(): void {
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            Flash::error('La solicitud expiró. Intente de nuevo.');
            $this->redirect('/');
        }
        $id = Auth::id();
        if ($id !== null) {
            Audit::log('logout', 'usuarios', $id, null, null, null, $id);
        }
        Auth::logout();
        $this->redirect('/login');
    }

    private function redirect(string $ruta): void {
        header('Location: ' . $ruta);
        exit;
    }
}
