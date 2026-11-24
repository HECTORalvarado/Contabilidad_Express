<?php
/** @var array $factura */
/** @var array $documentos */
/** @var array $pagos */
/** @var array $historial */
/** @var bool $puedeEditar */
/** @var bool $puedeAprobar */
/** @var bool $puedePagar */
/** @var string $tipoPago */
/** @var array $metodos */

use App\Core\Csrf;

$h = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');

$fecha = static function ($valor) use ($h): string {
    $valor = (string) $valor;
    if ($valor === '') {
        return '—';
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $m) === 1) {
        return $h($m[3] . '/' . $m[2] . '/' . $m[1]);
    }
    return $h($valor);
};
$fechaHora = static function ($valor) use ($h, $fecha): string {
    $valor = (string) $valor;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/', $valor, $m) === 1) {
        return $h($m[3] . '/' . $m[2] . '/' . $m[1] . ' ' . $m[4] . ':' . $m[5]);
    }
    return $fecha($valor);
};
$monto = static fn($valor): string => 'US$ ' . $h(number_format((float) $valor, 2, '.', ','));

$jsonLegible = static function ($valor) use ($h): string {
    $valor = (string) $valor;
    if ($valor === '') {
        return '—';
    }
    $decodificado = json_decode($valor, true);
    if (is_array($decodificado)) {
        $partes = [];
        foreach ($decodificado as $clave => $item) {
            if (is_array($item) || is_object($item)) {
                $item = json_encode($item, JSON_UNESCAPED_UNICODE);
            }
            $partes[] = $h((string) $clave) . ': ' . $h(is_bool($item) ? ($item ? 'sí' : 'no') : (string) $item);
        }
        return $partes === [] ? '—' : implode('; ', $partes);
    }
    return $h($valor);
};
$tamanoLegible = static function ($valor) use ($h): string {
    $bytes = (int) $valor;
    if ($bytes >= 1048576) {
        return $h(number_format($bytes / 1048576, 2, '.', ',')) . ' MB';
    }
    if ($bytes >= 1024) {
        return $h(number_format($bytes / 1024, 2, '.', ',')) . ' KB';
    }
    return $h($bytes) . ' B';
};

$estados = [
    'pendiente_aprobacion' => ['Pendiente de aprobación', 'warning text-dark'],
    'aprobada'             => ['Aprobada', 'primary'],
    'parcial'              => ['Parcial', 'info'],
    'pagada'               => ['Pagada', 'success'],
    'rechazada'            => ['Rechazada', 'danger'],
];

$id = (int) $factura['id'];
$claveEstado = (string) $factura['estado'];
$etiquetaEstado = $estados[$claveEstado] ?? [$claveEstado, 'secondary'];
$saldo = (float) $factura['saldo'];
$vencimiento = (string) $factura['fecha_vencimiento'];
$vencida = $saldo > 0
    && preg_match('/^(\d{4}-\d{2}-\d{2})/', $vencimiento, $mv) === 1
    && $mv[1] < date('Y-m-d');
$esRechazada = $claveEstado === 'rechazada';
$maxMonto = number_format($saldo, 2, '.', '');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Detalle de factura <?= $h($factura['numero_documento']) ?></h1>
        <p class="text-secondary small mb-0">Datos, documento de soporte, movimientos e historial de la factura.</p>
    </div>
    <a href="/facturas" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver a facturas</a>
</div>

<?php if ($esRechazada && (string) $factura['motivo_rechazo'] !== ''): ?>
    <div class="alert alert-danger" role="alert">
        <strong><i class="bi bi-exclamation-triangle me-1"></i>Factura rechazada:</strong>
        <?= $h($factura['motivo_rechazo']) ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Datos generales</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <span class="text-secondary small d-block">Tipo</span>
                        <span class="badge bg-<?= $h($factura['tipo'] === 'CXP' ? 'secondary' : 'primary') ?>"><?= $h($factura['tipo']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-secondary small d-block">Estado</span>
                        <span class="badge bg-<?= $h($etiquetaEstado[1]) ?>"><?= $h($etiquetaEstado[0]) ?></span>
                        <?php if ($vencida): ?>
                            <span class="badge bg-danger">Vencida</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <span class="text-secondary small d-block">Número de documento</span>
                        <span class="fw-semibold"><?= $h($factura['numero_documento']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-secondary small d-block">Tercero (<?= $h($factura['tercero_tipo']) ?>)</span>
                        <span><?= $h($factura['tercero_nombre']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-secondary small d-block">Identificador fiscal</span>
                        <span><?= $h($factura['identificador_fiscal']) ?></span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-secondary small d-block">Fecha de emisión</span>
                        <span><?= $fecha($factura['fecha_emision']) ?></span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-secondary small d-block">Fecha de vencimiento</span>
                        <span><?= $fecha($factura['fecha_vencimiento']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-secondary small d-block">Subtotal</span>
                        <span><?= $monto($factura['subtotal']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-secondary small d-block">IVA</span>
                        <span><?= $monto($factura['iva']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-secondary small d-block">Total</span>
                        <span class="fw-semibold"><?= $monto($factura['total']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-secondary small d-block">Registrado por</span>
                        <span><?= $h($factura['registrado_por_nombre'] ?? $factura['registrado_por']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-secondary small d-block">Aprobado por</span>
                        <span><?= $h($factura['aprobado_por_nombre'] ?? $factura['aprobado_por']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-secondary small d-block">Fecha de aprobación</span>
                        <span><?= $fechaHora($factura['fecha_aprobacion']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-semibold">Documentos de soporte</span>
                <?php if ($puedeEditar): ?>
                    <a href="<?= $h('/facturas/' . $id . '/editar') ?>" class="btn btn-sm btn-outline-primary">Editar y subir documentos</a>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Archivo</th>
                            <th>Tipo MIME</th>
                            <th>Tamaño</th>
                            <th>Subido por</th>
                            <th>Fecha</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($documentos === []): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-4">No hay documentos de soporte registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($documentos as $doc): ?>
                            <tr>
                                <td><i class="bi bi-paperclip me-1"></i><?= $h($doc['nombre_original']) ?></td>
                                <td class="small text-secondary"><?= $h($doc['mime']) ?></td>
                                <td class="text-nowrap small"><?= $tamanoLegible($doc['tamano']) ?></td>
                                <td><?= $h($doc['subido_por_nombre']) ?></td>
                                <td class="text-nowrap small"><?= $fechaHora($doc['creado_en']) ?></td>
                                <td class="text-end">
                                    <a href="<?= $h('/documentos/' . (int) $doc['id'] . '/descargar') ?>" class="btn btn-sm btn-outline-secondary">Descargar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold"><?= $h($tipoPago === 'cobro' ? 'Cobros registrados' : 'Pagos registrados') ?></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th class="text-end">Monto</th>
                            <th>Método</th>
                            <th>Referencia</th>
                            <th>Registrado por</th>
                            <th>Registrado en</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($pagos === []): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-4">No hay movimientos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pagos as $pago): ?>
                            <tr>
                                <td class="text-nowrap small"><?= $fecha($pago['fecha']) ?></td>
                                <td class="text-end text-nowrap"><?= $monto($pago['monto']) ?></td>
                                <td><?= $h($pago['metodo']) ?></td>
                                <td><?= $h($pago['referencia']) ?></td>
                                <td><?= $h($pago['registrado_por_nombre']) ?></td>
                                <td class="text-nowrap small"><?= $fechaHora($pago['creado_en']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Historial de bitácora</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Acción</th>
                            <th>Entidad</th>
                            <th>Usuario</th>
                            <th>Motivo</th>
                            <th>Valor anterior</th>
                            <th>Valor nuevo</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($historial === []): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-4">No hay registros en la bitácora.</td></tr>
                    <?php else: ?>
                        <?php foreach ($historial as $entrada): ?>
                            <tr>
                                <td class="text-nowrap small"><?= $fechaHora($entrada['fecha']) ?></td>
                                <td><?= $h($entrada['accion']) ?></td>
                                <td class="small"><?= $h($entrada['entidad']) ?> #<?= $h($entrada['entidad_id']) ?></td>
                                <td><?= $h($entrada['usuario_nombre']) ?></td>
                                <td class="small"><?= $h($entrada['motivo']) ?></td>
                                <td class="small"><?= $jsonLegible($entrada['valor_anterior']) ?></td>
                                <td class="small"><?= $jsonLegible($entrada['valor_nuevo']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Resumen</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-7">Total</dt>
                    <dd class="col-5 text-end mb-2"><?= $monto($factura['total']) ?></dd>
                    <dt class="col-7">Pagado / cobrado</dt>
                    <dd class="col-5 text-end mb-2"><?= $monto($factura['pagado']) ?></dd>
                    <dt class="col-7">Saldo</dt>
                    <dd class="col-5 text-end mb-2 fw-bold"><?= $monto($factura['saldo']) ?></dd>
                    <dt class="col-7">Estado</dt>
                    <dd class="col-5 text-end mb-0"><span class="badge bg-<?= $h($etiquetaEstado[1]) ?>"><?= $h($etiquetaEstado[0]) ?></span></dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Acciones</div>
            <div class="card-body">
                <?php if (!$puedeEditar && !$puedeAprobar && !$puedePagar): ?>
                    <p class="text-secondary small mb-0">No tiene acciones disponibles para esta factura.</p>
                <?php endif; ?>

                <?php if ($puedeAprobar): ?>
                    <form method="post" action="<?= $h('/facturas/' . $id . '/aprobar') ?>" class="mb-3">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-lg me-1"></i>Aprobar factura</button>
                    </form>
                    <form method="post" action="<?= $h('/facturas/' . $id . '/rechazar') ?>" class="mb-3">
                        <?= Csrf::field() ?>
                        <div class="mb-2">
                            <label for="motivo" class="form-label small mb-1">Motivo del rechazo <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm" id="motivo" name="motivo" rows="3" required maxlength="500"></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100"><i class="bi bi-x-lg me-1"></i>Rechazar factura</button>
                    </form>
                <?php endif; ?>

                <?php if ($puedePagar): ?>
                    <form method="post" action="<?= $h('/facturas/' . $id . '/pagar') ?>">
                        <?= Csrf::field() ?>
                        <div class="mb-2">
                            <label for="fecha" class="form-label small mb-1">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="fecha" name="fecha" required>
                        </div>
                        <div class="mb-2">
                            <label for="monto" class="form-label small mb-1">Monto <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="monto" name="monto"
                                   step="0.01" min="0.01" max="<?= $h($maxMonto) ?>" required>
                        </div>
                        <div class="mb-2">
                            <label for="metodo" class="form-label small mb-1">Método</label>
                            <select class="form-select form-select-sm" id="metodo" name="metodo">
                                <?php foreach ($metodos as $claveMetodo => $etiquetaMetodo): ?>
                                    <?php $valorMetodo = is_int($claveMetodo) ? $etiquetaMetodo : $claveMetodo; ?>
                                    <option value="<?= $h($valorMetodo) ?>"><?= $h($etiquetaMetodo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="referencia" class="form-label small mb-1">Referencia</label>
                            <input type="text" class="form-control form-control-sm" id="referencia" name="referencia" maxlength="100">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-cash me-1"></i><?= $h($tipoPago === 'cobro' ? 'Registrar cobro' : 'Registrar pago') ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
