<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Models\Documento;
use App\Models\Factura;
use App\Models\Tercero;
use PDOException;

class FacturasController {
    private const ROLES_EDICION = ['administrador', 'contador', 'auxiliar'];

    public function create(): void {
        Auth::require(self::ROLES_EDICION);
        $this->formulario(null);
    }

    public function store(): void {
        $this->autorizarEdicion();
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            Flash::error('La solicitud expiró. Intente de nuevo.');
            $this->redirect('/facturas/crear');
        }

        [$errores, $datos] = $this->validar($_POST);
        if ($errores !== []) {
            Flash::error(implode(' ', $errores));
            $this->redirect('/facturas/crear');
        }

        try {
            $id = Factura::create($datos, (int) Auth::id());
        } catch (PDOException $e) {
            error_log('Error al crear factura: ' . $e->getMessage());
            Flash::error($e->getCode() === '23505'
                ? 'Ya existe una factura con ese número, fecha y total para el tercero.'
                : 'No se pudo registrar la factura. Intente de nuevo.');
            $this->redirect('/facturas/crear');
            return;
        }

        Audit::log('factura_creada', 'facturas', $id, null, [
            'tipo' => $datos['tipo'],
            'tercero_id' => $datos['tercero_id'],
            'numero_documento' => $datos['numero'],
            'fecha_emision' => $datos['emision'],
            'total' => $datos['total'],
        ], 'Alta de factura', Auth::id());
        Flash::success('La factura se registró. Ahora adjunte el documento de soporte.');
        $this->redirect('/facturas/' . $id . '/editar');
    }

    public function edit(string $id): void {
        $this->autorizarEdicion();
        $factura = Factura::find((int) $id);
        if ($factura === null) {
            Flash::error('No se encontró la factura solicitada.');
            $this->redirect('/facturas');
        }
        if (!$this->puedeEditar($factura)) {
            Flash::error('Esta factura ya no se puede editar.');
            $this->redirect('/facturas');
        }
        $this->formulario($factura);
    }

    public function update(string $id): void {
        $this->autorizarEdicion();
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            Flash::error('La solicitud expiró. Intente de nuevo.');
            $this->redirect('/facturas');
        }

        $factura = Factura::find((int) $id);
        if ($factura === null) {
            Flash::error('No se encontró la factura solicitada.');
            $this->redirect('/facturas');
        }
        if (!$this->puedeEditar($factura)) {
            Flash::error('Esta factura ya no se puede editar.');
            $this->redirect('/facturas');
        }

        [$errores, $datos] = $this->validar($_POST, (int) $id, (string) $factura['tipo']);
        if ($errores !== []) {
            Flash::error(implode(' ', $errores));
            $this->redirect('/facturas/' . (int) $id . '/editar');
        }

        $datos['estado'] = $factura['estado'] === 'rechazada' ? 'pendiente_aprobacion' : $factura['estado'];
        $anterior = $this->resumen($factura);
        try {
            Factura::update((int) $id, $datos);
        } catch (PDOException $e) {
            error_log('Error al editar factura: ' . $e->getMessage());
            Flash::error($e->getCode() === '23505'
                ? 'Ya existe una factura con ese número, fecha y total para el tercero.'
                : 'No se pudo guardar la factura. Intente de nuevo.');
            $this->redirect('/facturas/' . (int) $id . '/editar');
            return;
        }

        $nuevo = [
            'tipo' => $datos['tipo'],
            'tercero_id' => $datos['tercero_id'],
            'numero_documento' => $datos['numero'],
            'fecha_emision' => $datos['emision'],
            'fecha_vencimiento' => $datos['vencimiento'],
            'total' => $datos['total'],
        ];
        Audit::log('factura_editada', 'facturas', (int) $id, $anterior, $nuevo, 'Edición de factura', Auth::id());
        Flash::success('La factura se actualizó correctamente.');
        $this->redirect('/facturas');
    }

    public function subirDocumento(string $id): void {
        $this->autorizarEdicion();
        if (!Csrf::validateToken($_POST['csrf_token'] ?? null)) {
            Flash::error('La solicitud expiró. Intente de nuevo.');
            $this->redirect('/facturas/' . (int) $id . '/editar');
        }
        $factura = Factura::find((int) $id);
        if ($factura === null || !$this->puedeVerFactura($factura)) {
            Flash::error('No se encontró la factura solicitada.');
            $this->redirect('/facturas');
        }

        $resultado = Documento::guardar($_FILES['documento'] ?? [], (int) $id, (int) Auth::id());
        if ($resultado['error'] !== null) {
            Flash::error($resultado['error']);
            $this->redirect('/facturas/' . (int) $id . '/editar');
        }

        $doc = Documento::find($resultado['id']);
        Audit::log('documento_subido', 'documentos', $resultado['id'], null, [
            'factura_id' => (int) $id,
            'nombre_original' => $doc['nombre_original'] ?? '',
            'mime' => $doc['mime'] ?? '',
            'tamano' => (int) ($doc['tamano'] ?? 0),
        ], 'Subida de documento de soporte', Auth::id());
        Flash::success('El documento se subió correctamente.');
        $this->redirect('/facturas/' . (int) $id . '/editar');
    }

    public function descargarDocumento(string $id): void {
        Auth::require();
        $doc = Documento::find((int) $id);
        if ($doc === null) {
            Flash::error('No se encontró el documento solicitado.');
            $this->redirect('/facturas');
        }
        $factura = Factura::find((int) $doc['factura_id']);
        if ($factura === null || !$this->puedeVerFactura($factura)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Acceso denegado']);
            exit;
        }
        $ruta = Documento::ruta((string) $doc['nombre_archivo']);
        if ($ruta === null || !is_file($ruta)) {
            Flash::error('El archivo ya no está disponible.');
            $this->redirect('/facturas/' . (int) $doc['factura_id'] . '/editar');
        }

        Audit::log('documento_descargado', 'documentos', (int) $id, null, [
            'factura_id' => (int) $doc['factura_id'],
            'nombre_original' => $doc['nombre_original'],
        ], 'Descarga de documento de soporte', Auth::id());

        $nombre = str_replace(["\r", "\n", '"', '\\'], '_', (string) $doc['nombre_original']);
        header('Content-Type: ' . (string) $doc['mime']);
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . (string) filesize($ruta));
        header('Cache-Control: no-store');
        readfile($ruta);
        exit;
    }

    private function formulario(?array $factura): void {
        View::render('facturas/form', [
            'title' => $factura === null ? 'Nueva factura' : 'Editar factura',
            'factura' => $factura,
            'terceros' => Tercero::activos(),
            'ivaPorcentaje' => Factura::ivaPorcentaje(),
            'documentos' => $factura === null ? [] : Documento::porFactura((int) $factura['id']),
            'accion' => $factura === null ? '/facturas' : '/facturas/' . (int) $factura['id'],
            'esNuevo' => $factura === null,
        ]);
    }

    private function autorizarEdicion(): void {
        Auth::require(self::ROLES_EDICION);
    }

    private function puedeEditar(array $factura): bool {
        if (!in_array((string) $factura['estado'], ['pendiente_aprobacion', 'rechazada'], true)) {
            return false;
        }
        return $this->puedeVerFactura($factura);
    }

    private function puedeVerFactura(array $factura): bool {
        $rol = Auth::role();
        if ($rol === 'auxiliar') {
            return (int) $factura['registrado_por'] === (int) Auth::id();
        }
        if ($rol === 'encargado_cxp') {
            return $factura['tipo'] === 'CXP';
        }
        if ($rol === 'encargado_cxc') {
            return $factura['tipo'] === 'CXC';
        }
        return in_array($rol, ['administrador', 'gerente', 'contador', 'auditor', 'tesorero'], true);
    }

    private function validar(array $post, int $excluirId = 0, ?string $tipoFijo = null): array {
        $errores = [];
        $tipo = $tipoFijo ?? (string) ($post['tipo'] ?? '');
        $terceroId = (int) ($post['tercero_id'] ?? 0);
        $numero = trim((string) ($post['numero_documento'] ?? ''));
        $emision = (string) ($post['fecha_emision'] ?? '');
        $vencimiento = (string) ($post['fecha_vencimiento'] ?? '');
        $subtotal = trim((string) ($post['subtotal'] ?? ''));

        if (!in_array($tipo, ['CXP', 'CXC'], true)) {
            $errores[] = 'Seleccione un tipo de factura válido.';
        }
        $tercero = $terceroId > 0 ? Tercero::find($terceroId) : null;
        $tipoEsperado = $tipo === 'CXP' ? 'proveedor' : 'cliente';
        if ($tercero === null || !$this->esVerdadero($tercero['activo']) || $tercero['tipo'] !== $tipoEsperado) {
            $errores[] = 'Seleccione un ' . $tipoEsperado . ' activo válido.';
        }
        if ($numero === '' || $this->longitud($numero) > 60) {
            $errores[] = 'El número de documento es obligatorio (máximo 60 caracteres).';
        }
        if (!$this->esFecha($emision) || !$this->esFecha($vencimiento)) {
            $errores[] = 'Las fechas deben tener formato válido.';
        } elseif ($vencimiento < $emision) {
            $errores[] = 'La fecha de vencimiento no puede ser menor a la de emisión.';
        }
        $subtotalNormalizado = $this->monto($subtotal);
        if ($subtotalNormalizado === null) {
            $errores[] = 'El subtotal debe ser un monto válido con hasta 2 decimales.';
        }

        $iva = '0.00';
        $total = '0.00';
        if ($subtotalNormalizado !== null) {
            $iva = Factura::calcularIva($subtotalNormalizado);
            $total = number_format((float) $subtotalNormalizado + (float) $iva, 2, '.', '');
        }
        if ($errores === [] && Factura::existeDuplicado($tipo, $terceroId, $numero, $emision, $total, $excluirId)) {
            $errores[] = 'Ya existe una factura con ese número, fecha y total para el tercero.';
        }

        return [$errores, [
            'tipo' => $tipo,
            'tercero_id' => $terceroId,
            'numero' => $numero,
            'emision' => $emision,
            'vencimiento' => $vencimiento,
            'subtotal' => $subtotalNormalizado ?? '0.00',
            'iva' => $iva,
            'total' => $total,
        ]];
    }

    private function esFecha(string $valor): bool {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $m) !== 1) {
            return false;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    private function monto(string $valor): ?string {
        if (preg_match('/^\d{1,12}(\.\d{1,2})?$/', $valor) !== 1) {
            return null;
        }
        $numero = (float) $valor;
        if ($numero > 999999999999.99) {
            return null;
        }
        return number_format($numero, 2, '.', '');
    }

    private function resumen(array $factura): array {
        return [
            'tipo' => $factura['tipo'],
            'numero_documento' => $factura['numero_documento'],
            'fecha_emision' => $factura['fecha_emision'],
            'total' => $factura['total'],
        ];
    }

    private function esVerdadero(mixed $valor): bool {
        return $valor === true || $valor === 1 || $valor === '1' || $valor === 't' || $valor === 'true';
    }

    private function longitud(string $valor): int {
        return function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);
    }

    private function redirect(string $ruta): void {
        header('Location: ' . $ruta);
        exit;
    }
}
