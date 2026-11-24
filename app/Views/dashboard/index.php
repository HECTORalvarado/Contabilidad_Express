<?php
/** @var array $resumen */
/** @var array $proximas */
/** @var int $dias */

$h = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$monto = static fn($valor): string => 'US$ ' . $h(number_format((float) $valor, 2, '.', ','));
$conteo = static fn($valor): string => $h((int) $valor);
$fecha = static function ($valor) use ($h): string {
    $valor = (string) $valor;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $partes) === 1) {
        return $h($partes[3] . '/' . $partes[2] . '/' . $partes[1]);
    }
    return $h($valor);
};

$tarjetas = [
    ['Total por pagar (CxP)', $monto($resumen['total_cxp'] ?? 0), 'bi-wallet2', 'primary'],
    ['Total por cobrar (CxC)', $monto($resumen['total_cxc'] ?? 0), 'bi-cash-coin', 'success'],
    ['Vencidas CxP', $conteo($resumen['vencidas_cxp'] ?? 0), 'bi-exclamation-triangle', 'danger'],
    ['Vencidas CxC', $conteo($resumen['vencidas_cxc'] ?? 0), 'bi-exclamation-circle', 'warning text-dark'],
    ['Pendientes de aprobación', $conteo($resumen['pendientes_aprobacion'] ?? 0), 'bi-hourglass-split', 'info'],
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Dashboard</h1>
        <p class="text-secondary small mb-0">Resumen de cuentas por pagar y por cobrar, y vencimientos próximos.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($tarjetas as $tarjeta): ?>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small mb-1">
                        <i class="bi <?= $h($tarjeta[2]) ?> me-1"></i><?= $h($tarjeta[0]) ?>
                    </div>
                    <div class="fs-4 fw-semibold"><?= $tarjeta[1] ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h6 mb-0">Próximas a vencer (<?= $h($dias) ?> días)</h2>
        <a href="/reportes" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-bar-graph me-1"></i>Ver reportes</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tipo</th>
                    <th>Documento</th>
                    <th>Tercero</th>
                    <th>Vencimiento</th>
                    <th>Días</th>
                    <th class="text-end">Saldo</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($proximas === []): ?>
                <tr><td colspan="6" class="text-center text-secondary py-4">No hay documentos próximos a vencer en los próximos <?= $h($dias) ?> días.</td></tr>
            <?php else: ?>
                <?php foreach ($proximas as $proxima): ?>
                    <?php
                    $restantes = (int) $proxima['dias_para_vencer'];
                    $claseTipo = $proxima['tipo'] === 'CXP' ? 'secondary' : 'primary';
                    ?>
                    <tr>
                        <td><span class="badge bg-<?= $h($claseTipo) ?>"><?= $h($proxima['tipo']) ?></span></td>
                        <td class="fw-semibold text-nowrap"><?= $h($proxima['numero_documento']) ?></td>
                        <td><?= $h($proxima['tercero_nombre']) ?></td>
                        <td class="text-nowrap small"><?= $fecha($proxima['fecha_vencimiento']) ?></td>
                        <td>
                            <?php if ($restantes < 0): ?>
                                <span class="badge bg-danger">Vencida</span>
                            <?php elseif ($restantes === 0): ?>
                                <span class="badge bg-warning text-dark">Hoy</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border"><?= $h($restantes) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap"><?= $monto($proxima['saldo']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
