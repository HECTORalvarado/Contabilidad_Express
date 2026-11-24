<?php
/** @var array $filas */

$h = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$monto = static fn($valor): string => 'US$ ' . $h(number_format((float) $valor, 2, '.', ','));
$tieneVencido = static function (array $fila): bool {
    return (float) $fila['a1_30'] + (float) $fila['a31_60'] + (float) $fila['a61_90'] + (float) $fila['mas90'] > 0;
};
$hayVencidos = false;
foreach ($filas as $fila) {
    if ($tieneVencido($fila)) {
        $hayVencidos = true;
        break;
    }
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">CxC por antigüedad</h1>
        <p class="text-secondary small mb-0">Cartera por cliente, clasificada según días vencidos.</p>
    </div>
    <a href="/reportes/cxc-antiguedad/csv" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Descargar CSV</a>
</div>

<?php if ($hayVencidos): ?>
    <div class="alert alert-warning py-2 small">
        <i class="bi bi-exclamation-triangle me-1"></i>Existen clientes con saldos vencidos; se marcan en la columna Total.
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cliente</th>
                    <th class="text-end">Corriente</th>
                    <th class="text-end">1-30</th>
                    <th class="text-end">31-60</th>
                    <th class="text-end">61-90</th>
                    <th class="text-end">+90</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($filas === []): ?>
                <tr><td colspan="7" class="text-center text-secondary py-4">No hay cuentas por cobrar pendientes.</td></tr>
            <?php else: ?>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <td>
                            <?= $h($fila['cliente']) ?>
                            <?php if ($fila['identificador_fiscal'] !== null && $fila['identificador_fiscal'] !== ''): ?>
                                <br><span class="text-secondary small"><?= $h($fila['identificador_fiscal']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap"><?= $monto($fila['corriente']) ?></td>
                        <td class="text-end text-nowrap"><?= $monto($fila['a1_30']) ?></td>
                        <td class="text-end text-nowrap"><?= $monto($fila['a31_60']) ?></td>
                        <td class="text-end text-nowrap"><?= $monto($fila['a61_90']) ?></td>
                        <td class="text-end text-nowrap"><?= $monto($fila['mas90']) ?></td>
                        <td class="text-end text-nowrap fw-semibold">
                            <?= $monto($fila['total']) ?>
                            <?php if ($tieneVencido($fila)): ?>
                                <span class="badge bg-danger">Vencido</span>
                            <?php endif; ?>
                        </td>
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
