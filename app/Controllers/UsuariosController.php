<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Models\Usuario;
use PDOException;

class UsuariosController {
    public function index(): void {
        Auth::require('administrador');
        View::render('usuarios/index', [
            'title' => 'Usuarios',
            'usuarios' => Usuario::all(),
            'yo' => (int) Auth::id(),
        ]);
    }

    public function create(): void {
        Auth::require('administrador');
        View::render('usuarios/form', [
            'title' => 'Nuevo usuario',
            'registro' => null,
            'roles' => Usuario::roles(),
            'accion' => '/usuarios',
            'esNuevo' => true,
            'esPropio' => false,
        ]);
    }

    public function store(): void {
        Auth::require('administrador');
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            Flash::error('La solicitud expiró. Intente de nuevo.');
            $this->redirect('/usuarios');
        }

        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $rolId = (int) ($_POST['rol_id'] ?? 0);
        $password = (string) ($_POST['password'] ?? '');

        $errores = $this->validar($nombre, $email, $rolId, true, $password, 0);
        if ($errores !== []) {
            Flash::error(implode(' ', $errores));
            $this->redirect('/usuarios/crear');
        }

        try {
            $id = Usuario::create($nombre, $email, $password, $rolId);
        } catch (PDOException $e) {
            error_log('Error al crear usuario: ' . $e->getMessage());
            Flash::error($e->getCode() === '23505'
                ? 'Ya existe un usuario con ese correo electrónico.'
                : 'No se pudo crear el usuario. Intente de nuevo.');
            $this->redirect('/usuarios/crear');
            return;
        }

        Audit::log('usuario_creado', 'usuarios', $id, null, [
            'nombre' => $nombre,
            'email' => $email,
            'rol' => $this->nombreRol($rolId),
            'activo' => true,
        ], 'Alta de usuario', Auth::id());
        Flash::success('El usuario se creó correctamente.');
        $this->redirect('/usuarios');
    }

    public function edit(string $id): void {
        Auth::require('administrador');
        $id = (int) $id;
        $registro = Usuario::find($id);
        if ($registro === null) {
            Flash::error('No se encontró el usuario solicitado.');
            $this->redirect('/usuarios');
        }

        View::render('usuarios/form', [
            'title' => 'Editar usuario',
            'registro' => $registro,
            'roles' => Usuario::roles(),
            'accion' => '/usuarios/' . $id,
            'esNuevo' => false,
            'esPropio' => (int) Auth::id() === $id,
        ]);
    }

    public function update(string $id): void {
        Auth::require('administrador');
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            Flash::error('La solicitud expiró. Intente de nuevo.');
            $this->redirect('/usuarios');
        }

        $id = (int) $id;
        $registro = Usuario::find($id);
        if ($registro === null) {
            Flash::error('No se encontró el usuario solicitado.');
            $this->redirect('/usuarios');
        }

        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $rolId = (int) ($_POST['rol_id'] ?? 0);

        $errores = $this->validar($nombre, $email, $rolId, false, '', $id);
        if ((int) Auth::id() === $id && $rolId !== (int) $registro['rol_id']) {
            $errores[] = 'No puede cambiar su propio rol.';
        }
        if ($errores !== []) {
            Flash::error(implode(' ', $errores));
            $this->redirect('/usuarios/' . $id . '/editar');
        }

        $anterior = [
            'nombre' => $registro['nombre'],
            'email' => $registro['email'],
            'rol' => $registro['rol_nombre'],
        ];
        $nuevo = [
            'nombre' => $nombre,
            'email' => $email,
            'rol' => $this->nombreRol($rolId),
        ];

        try {
            Usuario::update($id, $nombre, $email, $rolId);
        } catch (PDOException $e) {
            error_log('Error al editar usuario: ' . $e->getMessage());
            Flash::error($e->getCode() === '23505'
                ? 'Ya existe un usuario con ese correo electrónico.'
                : 'No se pudo guardar el usuario. Intente de nuevo.');
            $this->redirect('/usuarios/' . $id . '/editar');
            return;
        }

        Audit::log('usuario_editado', 'usuarios', $id, $anterior, $nuevo, 'Edición de usuario', Auth::id());
        Flash::success('Los datos del usuario se actualizaron.');
        $this->redirect('/usuarios');
    }

    public function toggle(string $id): void {
        Auth::require('administrador');
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            Flash::error('La solicitud expiró. Intente de nuevo.');
            $this->redirect('/usuarios');
        }

        $id = (int) $id;
        $registro = Usuario::find($id);
        if ($registro === null) {
            Flash::error('No se encontró el usuario solicitado.');
            $this->redirect('/usuarios');
        }

        $estabaActivo = $this->esActivo($registro['activo']);
        $activar = !$estabaActivo;
        if (!$activar && (int) Auth::id() === $id) {
            Flash::error('No puede desactivar su propia cuenta.');
            $this->redirect('/usuarios');
        }

        Usuario::setActivo($id, $activar);
        Audit::log(
            $activar ? 'usuario_activado' : 'usuario_desactivado',
            'usuarios',
            $id,
            ['activo' => $estabaActivo],
            ['activo' => $activar],
            $activar ? 'Activación de usuario' : 'Desactivación de usuario',
            Auth::id()
        );
        Flash::success($activar ? 'El usuario se activó correctamente.' : 'El usuario se desactivó correctamente.');
        $this->redirect('/usuarios');
    }

    public function unlock(string $id): void {
        Auth::require('administrador');
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            Flash::error('La solicitud expiró. Intente de nuevo.');
            $this->redirect('/usuarios');
        }

        $id = (int) $id;
        $registro = Usuario::find($id);
        if ($registro === null) {
            Flash::error('No se encontró el usuario solicitado.');
            $this->redirect('/usuarios');
        }

        Usuario::desbloquear($id);
        Audit::log('usuario_desbloqueado', 'usuarios', $id, [
            'intentos_fallidos' => (int) $registro['intentos_fallidos'],
            'bloqueado_hasta' => $registro['bloqueado_hasta'],
        ], [
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ], 'Desbloqueo de usuario', Auth::id());
        Flash::success('El usuario se desbloqueó correctamente.');
        $this->redirect('/usuarios');
    }

    public function resetPassword(string $id): void {
        Auth::require('administrador');
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            Flash::error('La solicitud expiró. Intente de nuevo.');
            $this->redirect('/usuarios');
        }

        $id = (int) $id;
        if (Usuario::find($id) === null) {
            Flash::error('No se encontró el usuario solicitado.');
            $this->redirect('/usuarios');
        }

        $password = (string) ($_POST['password'] ?? '');
        if ($this->longitud($password) < 8) {
            Flash::error('La contraseña debe tener al menos 8 caracteres.');
            $this->redirect('/usuarios');
        }

        Usuario::setPassword($id, $password);
        Audit::log('usuario_password_reset', 'usuarios', $id, null, [
            'cambio' => 'contraseña actualizada',
        ], 'Restablecimiento de contraseña', Auth::id());
        Flash::success('La contraseña se restableció correctamente.');
        $this->redirect('/usuarios');
    }

    private function validar(string $nombre, string $email, int $rolId, bool $crear, string $password, int $id): array {
        $errores = [];

        if ($nombre === '') {
            $errores[] = 'El nombre es obligatorio.';
        } elseif ($this->longitud($nombre) > 120) {
            $errores[] = 'El nombre no puede superar los 120 caracteres.';
        }

        if ($email === '') {
            $errores[] = 'El correo electrónico es obligatorio.';
        } elseif ($this->longitud($email) > 150) {
            $errores[] = 'El correo electrónico no puede superar los 150 caracteres.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errores[] = 'El correo electrónico no es válido.';
        } else {
            $existente = Usuario::findByEmail($email);
            if ($existente !== null && (int) $existente['id'] !== $id) {
                $errores[] = 'Ya existe un usuario con ese correo electrónico.';
            }
        }

        $roles = array_map('intval', array_column(Usuario::roles(), 'id'));
        if (!in_array($rolId, $roles, true)) {
            $errores[] = 'Seleccione un rol válido.';
        }

        if ($crear && $this->longitud($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        return $errores;
    }

    private function nombreRol(int $rolId): string {
        foreach (Usuario::roles() as $rol) {
            if ((int) $rol['id'] === $rolId) {
                return (string) $rol['nombre'];
            }
        }
        return 'Desconocido';
    }

    private function longitud(string $valor): int {
        return function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);
    }

    private function esActivo(mixed $valor): bool {
        return $valor === true || $valor === 1 || $valor === '1' || $valor === 't' || $valor === 'true';
    }

    private function redirect(string $ruta): void {
        header('Location: ' . $ruta);
        exit;
    }
}
