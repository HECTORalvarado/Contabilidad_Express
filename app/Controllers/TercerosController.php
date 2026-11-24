<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Models\Tercero;
use PDOException;

class TercerosController {
    public function index(): void {
        Auth::require();
        $tipo = (string) ($_GET['tipo'] ?? '');
        if ($tipo !== 'cliente' && $tipo !== 'proveedor') {
            $tipo = '';
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        $terceros = Tercero::all($tipo);
        if ($q !== '') {
            $terceros = array_values(array_filter($terceros, static function (array $t) use ($q): bool {
                return stripos((string) $t['nombre'], $q) !== false
                    || stripos((string) $t['identificador_fiscal'], $q) !== false;
            }));
        }
        View::render('terceros/index', [
            'title' => 'Clientes y proveedores',
            'terceros' => $terceros,
            'tipo' => $tipo,
            'q' => $q,
            'puedeEditar' => Auth::hasRole(['administrador', 'contador']),
        ]);
    }

    public function create(): void {
        Auth::require(['administrador', 'contador']);
        View::render('terceros/form', [
            'title' => 'Nuevo tercero',
            'registro' => null,
            'accion' => '/terceros',
            'esNuevo' => true,
        ]);
    }

    public function store(): void {
        Auth::require(['administrador', 'contador']);
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            Flash::error('La solicitud expiró. Intente de nuevo.');
            $this->redirect('/terceros');
        }

        $tipo = (string) ($_POST['tipo'] ?? '');
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $identificador = trim((string) ($_POST['identificador_fiscal'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $telefono = trim((string) ($_POST['telefono'] ?? ''));

        $errores = $this->validar($tipo, $nombre, $identificador, $email, $telefono, 0);
        if ($errores !== []) {
            Flash::error(implode(' ', $errores));
            $this->redirect('/terceros/crear');
        }

        $email = $email === '' ? null : $email;
        $telefono = $telefono === '' ? null : $telefono;

        try {
            $id = Tercero::create($tipo, $nombre, $identificador, $email, $telefono);
        } catch (PDOException $e) {
            error_log('Error al crear tercero: ' . $e->getMessage());
            Flash::error($e->getCode() === '23505'
                ? 'Ya existe un tercero con ese tipo e identificador fiscal.'
                : 'No se pudo crear el tercero. Intente de nuevo.');
            $this->redirect('/terceros/crear');
            return;
        }

        Audit::log('tercero_creado', 'terceros', $id, null, [
            'tipo' => $tipo,
            'nombre' => $nombre,
            'identificador_fiscal' => $identificador,
            'email' => $email,
            'telefono' => $telefono,
            'activo' => true,
        ], 'Alta de tercero', Auth::id());
        Flash::success('El tercero se creó correctamente.');
        $this->redirect('/terceros');
    }

    public function edit(string $id): void {
        Auth::require(['administrador', 'contador']);
        $id = (int) $id;
        $registro = Tercero::find($id);
        if ($registro === null) {
            Flash::error('No se encontró el tercero solicitado.');
            $this->redirect('/terceros');
        }

        View::render('terceros/form', [
            'title' => 'Editar tercero',
            'registro' => $registro,
            'accion' => '/terceros/' . $id,
            'esNuevo' => false,
        ]);
    }

    public function update(string $id): void {
        Auth::require(['administrador', 'contador']);
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            Flash::error('La solicitud expiró. Intente de nuevo.');
            $this->redirect('/terceros');
        }

        $id = (int) $id;
        $registro = Tercero::find($id);
        if ($registro === null) {
            Flash::error('No se encontró el tercero solicitado.');
            $this->redirect('/terceros');
        }

        $tipo = (string) $registro['tipo'];
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $identificador = trim((string) ($_POST['identificador_fiscal'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $telefono = trim((string) ($_POST['telefono'] ?? ''));

        $errores = $this->validar($tipo, $nombre, $identificador, $email, $telefono, $id);
        if ($errores !== []) {
            Flash::error(implode(' ', $errores));
            $this->redirect('/terceros/' . $id . '/editar');
        }

        $email = $email === '' ? null : $email;
        $telefono = $telefono === '' ? null : $telefono;

        $anterior = [
            'nombre' => $registro['nombre'],
            'identificador_fiscal' => $registro['identificador_fiscal'],
            'email' => $registro['email'],
            'telefono' => $registro['telefono'],
        ];
        $nuevo = [
            'nombre' => $nombre,
            'identificador_fiscal' => $identificador,
            'email' => $email,
            'telefono' => $telefono,
        ];

        try {
            Tercero::update($id, $nombre, $identificador, $email, $telefono);
        } catch (PDOException $e) {
            error_log('Error al editar tercero: ' . $e->getMessage());
            Flash::error($e->getCode() === '23505'
                ? 'Ya existe un tercero con ese tipo e identificador fiscal.'
                : 'No se pudo guardar el tercero. Intente de nuevo.');
            $this->redirect('/terceros/' . $id . '/editar');
            return;
        }

        Audit::log('tercero_editado', 'terceros', $id, $anterior, $nuevo, 'Edición de tercero', Auth::id());
        Flash::success('Los datos del tercero se actualizaron.');
        $this->redirect('/terceros');
    }

    public function toggle(string $id): void {
        Auth::require(['administrador', 'contador']);
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            Flash::error('La solicitud expiró. Intente de nuevo.');
            $this->redirect('/terceros');
        }

        $id = (int) $id;
        $registro = Tercero::find($id);
        if ($registro === null) {
            Flash::error('No se encontró el tercero solicitado.');
            $this->redirect('/terceros');
        }

        $estabaActivo = $this->esActivo($registro['activo']);
        $activar = !$estabaActivo;

        try {
            Tercero::setActivo($id, $activar);
        } catch (PDOException $e) {
            error_log('Error al cambiar el estado del tercero: ' . $e->getMessage());
            Flash::error('No se pudo cambiar el estado del tercero. Intente de nuevo.');
            $this->redirect('/terceros');
            return;
        }

        Audit::log(
            $activar ? 'tercero_activado' : 'tercero_desactivado',
            'terceros',
            $id,
            ['activo' => $estabaActivo],
            ['activo' => $activar],
            $activar ? 'Activación de tercero' : 'Desactivación de tercero',
            Auth::id()
        );
        Flash::success($activar ? 'El tercero se activó correctamente.' : 'El tercero se desactivó correctamente.');
        $this->redirect('/terceros');
    }

    private function validar(string $tipo, string $nombre, string $identificador, string $email, string $telefono, int $id): array {
        $errores = [];

        if ($tipo !== 'cliente' && $tipo !== 'proveedor') {
            $errores[] = 'Seleccione un tipo válido.';
        }

        if ($nombre === '') {
            $errores[] = 'El nombre es obligatorio.';
        } elseif ($this->longitud($nombre) > 150) {
            $errores[] = 'El nombre no puede superar los 150 caracteres.';
        }

        if ($identificador === '') {
            $errores[] = 'El identificador fiscal es obligatorio.';
        } elseif ($this->longitud($identificador) > 40) {
            $errores[] = 'El identificador fiscal no puede superar los 40 caracteres.';
        } elseif (Tercero::existeIdentificador($tipo, $identificador, $id)) {
            $errores[] = 'Ya existe un tercero con ese tipo e identificador fiscal.';
        }

        if ($email !== '') {
            if ($this->longitud($email) > 150) {
                $errores[] = 'El correo electrónico no puede superar los 150 caracteres.';
            } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errores[] = 'El correo electrónico no es válido.';
            }
        }

        if ($this->longitud($telefono) > 30) {
            $errores[] = 'El teléfono no puede superar los 30 caracteres.';
        }

        return $errores;
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
