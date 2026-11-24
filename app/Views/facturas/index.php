<?php
/** @var array $facturas */
/** @var array $filtros */
/** @var int $pagina */
/** @var int $paginas */
/** @var int $total */
/** @var array $terceros */
/** @var bool $puedeRegistrar */

$h = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$esVencida = static fn($valor): bool => $valor === true || $valor === 1 || $valor === '1' || $valor === 't';

$fecha = static function ($valor) use ($h): string {
    $valor = (string) $valor;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $m) === 1) {
        return $h($m[3] . '/' . $m[2] . '/' . $m[1]);
    }
    return $h($valor);
};
$monto = static fn($valor): string => 'US$ ' . $h(number_format((float) $valor, 2, '.', ','));

$estados = [
    'pendiente_aprobacion' => ['Pendiente de aprobación', 'warning text-dark'],
    'aprobada'             => ['Aprobada', 'primary'],
    'parcial'              => ['Parcial', 'info'],
    'pagada'               => ['Pagada', 'success'],
    'rechazada'            => ['Rechazada', 'danger'],
];

$consultaBase = [
    'numero'       => (string) $filtros['numero'],
    'tipo'         => (string) $filtros['tipo'],
    'estado'       => (string) $filtros['estado'],
    'tercero_id'   => (int) $filtros['tercero_id'],
    'fecha_desde'  => (string) $filtros['fecha_desde'],
    'fecha_hasta'  => (string) $filtros['fecha_hasta'],
];
if (!empty($filtros['solo_vencidas'])) {
    $consultaBase['solo_vencidas'] = '1';
}
$urlPagina = static fn(int $n): string => '/facturas?' . http_build_query(array_merge($consultaBase, ['pagina' => $n]));
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Facturas</h1>
        <p class="text-secondary small mb-0">Cuentas por pagar y por cobrar: busque, filtre y revise el estado de cada factura.</p>
    </div>
    <?php if ($puedeRegistrar): ?>
        <a href="/facturas/crear" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva factura</a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="/facturas" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="numero" class="form-label small mb-1">Número de documento</label>
                <input type="text" class="form-control form-control-sm" id="numero" name="numero" maxlength="60"
                       value="<?= $h($filtros['numero']) ?>" placeholder="Ej. F-001-2026">
            </div>
            <div class="col-md-2">
                <label for="tipo" class="form-label small mb-1">Tipo</label>
                <select class="form-select form-select-sm" id="tipo" name="tipo">
                    <option value="" <?= $filtros['tipo'] === '' ? 'selected' : '' ?>>Todos</option>
                    <option value="CXP" <?= $filtros['tipo'] === 'CXP' ? 'selected' : '' ?>>CXP</option>
                    <option value="CXC" <?= $filtros['tipo'] === 'CXC' ? 'selected' : '' ?>>CXC</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="estado" class="form-label small mb-1">Estado</label>
                <select class="form-select form-select-sm" id="estado" name="estado">
                    <option value="" <?= $filtros['estado'] === '' ? 'selected' : '' ?>>Todos</option>
                    <?php foreach ($estados as $clave => $etiqueta): ?>
                        <option value="<?= $h($clave) ?>" <?= $filtros['estado'] === $clave ? 'selected' : '' ?>>
                            <?= $h($etiqueta[0]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="tercero_id" class="form-label small mb-1">Tercero</label>
                <select class="form-select form-select-sm" id="tercero_id" name="tercero_id">
                    <option value="0" <?= (int) $filtros['tercero_id'] === 0 ? 'selected' : '' ?>>Todos los terceros</option>
                    <?php foreach ($terceros as $t): ?>
                        <option value="<?= $h($t['id']) ?>"
                            <?= (int) $filtros['tercero_id'] === (int) $t['id'] ? 'selected' : '' ?>>
                            <?= $h($t['nombre']) ?> (<?= $h($t['identificador_fiscal']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <label for="fecha_desde" class="form-label small mb-1">Desde</label>
                <input type="date" class="form-control form-control-sm" id="fecha_desde" name="fecha_desde"
                       value="<?= $h($filtros['fecha_desde']) ?>">
            </div>
            <div class="col-md-1">
                <label for="fecha_hasta" class="form-label small mb-1">Hasta</label>
                <input type="date" class="form-control form-control-sm" id="fecha_hasta" name="fecha_hasta"
                       value="<?= $h($filtros['fecha_hasta']) ?>">
            </div>
            <div class="col-md-1">
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" value="1" id="solo_vencidas" name="solo_vencidas"
                        <?= !empty($filtros['solo_vencidas']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="solo_vencidas">Solo vencidas</label>
                </div>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Buscar</button>
                <a href="/facturas" class="btn btn-sm btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tipo</th>
                    <th>Número</th>
                    <th>Tercero</th>
                    <th>Emisión</th>
                    <th>Vencimiento</th>
                    <th>Estado</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Saldo</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($facturas === []): ?>
                <tr><td colspan="9" class="text-center text-secondary py-4">No hay facturas con esos filtros.</td></tr>
            <?php else: ?>
                <?php foreach ($facturas as $f): ?>
                    <?php
                    $id = (int) $f['id'];
                    $claveEstado = (string) $f['estado'];
                    $etiquetaEstado = $estados[$claveEstado] ?? [$claveEstado, 'secondary'];
                    $vencida = $esVencida($f['vencida'] ?? false);
                    ?>
                    <tr>
                        <td><span class="badge bg-<?= $f['tipo'] === 'CXP' ? 'secondary' : 'primary' ?>"><?= $h($f['tipo']) ?></span></td>
                        <td class="fw-semibold text-nowrap"><?= $h($f['numero_documento']) ?></td>
                        <td><?= $h($f['tercero_nombre']) ?></td>
                        <td class="text-nowrap small"><?= $fecha($f['fecha_emision']) ?></td>
                        <td class="text-nowrap small">
                            <?= $fecha($f['fecha_vencimiento']) ?>
                            <?php if ($vencida): ?>
                                <span class="badge bg-danger">Vencida</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?= $h($etiquetaEstado[1]) ?>"><?= $h($etiquetaEstado[0]) ?></span></td>
                        <td class="text-end text-nowrap"><?= $monto($f['total']) ?></td>
                        <td class="text-end text-nowrap"><?= $monto($f['saldo']) ?></td>
                        <td class="text-end">
                            <a href="<?= $h('/facturas/' . $id) ?>" class="btn btn-sm btn-outline-secondary">Detalle</a>
                            <?php if ($puedeRegistrar && in_array((string) $f['estado'], ['pendiente_aprobacion', 'rechazada'], true)): ?>
                                <a href="<?= $h('/facturas/' . $id . '/editar') ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="text-secondary small">
            Página <?= $h($pagina) ?> de <?= $h($paginas) ?> — <?= $h($total) ?> facturas
        </span>
        <nav aria-label="Paginación de facturas">
            <ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">
                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $h($urlPagina(max(1, $pagina - 1))) ?>">Anterior</a>
                </li>
                <li class="page-item <?= $pagina >= $paginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $h($urlPagina(min(max($paginas, 1), $pagina + 1))) ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    </div>
</div>
