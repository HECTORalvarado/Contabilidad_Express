<?php
/** @var array $filas */

$h = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$monto = static fn($valor): string => 'US$ ' . $h(number_format((float) $valor, 2, '.', ','));
$fecha = static function ($valor) use ($h): string {
    $valor = (string) $valor;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $partes) === 1) {
        return $h($partes[3] . '/' . $partes[2] . '/' . $partes[1]);
    }
    return $h($valor);
};
$situaciones = [
    'vencida' => ['Vencida', 'danger'],
    'pendiente' => ['Pendiente', 'warning text-dark'],
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">CxP pendientes y vencidas</h1>
        <p class="text-secondary small mb-0">Documentos por proveedor, ordenados por fecha de vencimiento.</p>
    </div>
    <a href="/reportes/cxp-pendientes/csv" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Descargar CSV</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Proveedor</th>
                    <th>Documento</th>
                    <th>Emisión</th>
                    <th>Vencimiento</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Saldo</th>
                    <th>Situación</th>
                    <th class="text-end">Días atraso</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($filas === []): ?>
                <tr><td colspan="8" class="text-center text-secondary py-4">No hay cuentas por pagar pendientes ni vencidas.</td></tr>
            <?php else: ?>
                <?php foreach ($filas as $fila): ?>
                    <?php
                    $claveSituacion = (string) $fila['situacion'];
                    $situacion = $situaciones[$claveSituacion] ?? [$claveSituacion, 'secondary'];
                    ?>
                    <tr>
                        <td>
                            <?= $h($fila['proveedor']) ?>
                            <?php if ($fila['identificador_fiscal'] !== null && $fila['identificador_fiscal'] !== ''): ?>
                                <br><span class="text-secondary small"><?= $h($fila['identificador_fiscal']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold text-nowrap"><?= $h($fila['numero_documento']) ?></td>
                        <td class="text-nowrap small"><?= $fecha($fila['fecha_emision']) ?></td>
                        <td class="text-nowrap small"><?= $fecha($fila['fecha_vencimiento']) ?></td>
                        <td class="text-end text-nowrap"><?= $monto($fila['total']) ?></td>
                        <td class="text-end text-nowrap"><?= $monto($fila['saldo']) ?></td>
                        <td><span class="badge bg-<?= $h($situacion[1]) ?>"><?= $h($situacion[0]) ?></span></td>
                        <td class="text-end"><?= $h((int) $fila['dias_atraso']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <a href="/reportes" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver a reportes</a>
</div>
