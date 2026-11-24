<?php
/** @var array $registros */
/** @var array $listaUsuarios */
/** @var array $filtros */
/** @var array $resumen */
/** @var array $consultaBase */
/** @var int $total */
/** @var int $pagina */
/** @var int $totalPaginas */
/** @var int $porPagina */

$h = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$urlPagina = static fn(int $n): string => '/bitacora?' . http_build_query(array_merge($consultaBase, ['page' => $n]));
$formatoJson = static function ($valor) use ($h): string {
    if ($valor === null || $valor === '') {
        return '—';
    }
    $datos = is_array($valor) ? $valor : (is_string($valor) ? json_decode($valor, true) : null);
    if (!is_array($datos) || json_last_error() !== JSON_ERROR_NONE) {
        return '—';
    }
    $texto = json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($texto === false) {
        return '—';
    }
    return '<pre class="json-bitacora small mb-0">' . $h($texto) . '</pre>';
};
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Bitácora</h1>
        <p class="text-secondary small mb-0">Registro de las acciones realizadas en el sistema.</p>
    </div>
    <a href="/bitacora" class="btn btn-outline-secondary"><i class="bi bi-eraser me-1"></i>Limpiar</a>
</div>

<form method="get" action="/bitacora" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label for="usuario_id" class="form-label small mb-1">Usuario</label>
            <select class="form-select form-select-sm" id="usuario_id" name="usuario_id">
                <option value="">Todos los usuarios</option>
                <?php foreach ($listaUsuarios as $u): ?>
                    <option value="<?= $h($u['id']) ?>"
                        <?= (string) $filtros['usuario_id'] === (string) $u['id'] ? 'selected' : '' ?>>
                        <?= $h($u['nombre']) ?> (<?= $h($u['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="accion" class="form-label small mb-1">Acción</label>
            <input type="text" class="form-control form-control-sm" id="accion" name="accion" maxlength="60"
                   value="<?= $h($filtros['accion']) ?>" placeholder="Ej. usuario_editado">
        </div>
        <div class="col-md-2">
            <label for="entidad" class="form-label small mb-1">Entidad</label>
            <input type="text" class="form-control form-control-sm" id="entidad" name="entidad" maxlength="60"
                   value="<?= $h($filtros['entidad']) ?>" placeholder="Ej. facturas">
        </div>
        <div class="col-md-2">
            <label for="desde" class="form-label small mb-1">Desde</label>
            <input type="date" class="form-control form-control-sm" id="desde" name="desde" value="<?= $h($filtros['desde']) ?>">
        </div>
        <div class="col-md-2">
            <label for="hasta" class="form-label small mb-1">Hasta</label>
            <input type="date" class="form-control form-control-sm" id="hasta" name="hasta" value="<?= $h($filtros['hasta']) ?>">
        </div>
        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        </div>
    </div>
</form>

<?php if ($resumen !== []): ?>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="text-secondary small">Filtros activos:</span>
        <?php foreach ($resumen as $item): ?>
            <span class="badge bg-light text-dark border"><?= $h($item) ?></span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body py-2 small text-secondary">
        <?php if ($total === 0): ?>
            No hay registros para los filtros seleccionados.
        <?php else: ?>
            Mostrando <?= $h(($pagina - 1) * $porPagina + 1) ?> a
            <?= $h(min($pagina * $porPagina, $total)) ?> de <?= $h($total) ?> registros.
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Entidad</th>
                    <th>Motivo</th>
                    <th>IP</th>
                    <th>Valor anterior</th>
                    <th>Valor nuevo</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($registros === []): ?>
                <tr><td colspan="8" class="text-center text-secondary py-4">No hay movimientos registrados.</td></tr>
            <?php else: ?>
                <?php foreach ($registros as $r): ?>
                    <?php
                    $marca = strtotime((string) $r['fecha']);
                    $fecha = $marca === false ? '—' : date('d/m/Y H:i', $marca);
                    ?>
                    <tr>
                        <td class="text-nowrap small"><?= $h($fecha) ?></td>
                        <td class="small">
                            <?php if ($r['usuario_id'] === null || $r['usuario_nombre'] === null): ?>
                                —
                            <?php else: ?>
                                <?= $h($r['usuario_nombre']) ?><br>
                                <span class="text-secondary"><?= $h($r['usuario_email']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-secondary"><?= $h($r['accion']) ?></span></td>
                        <td class="small">
                            <?= $h($r['entidad']) ?>
                            <?php if ($r['entidad_id'] !== null): ?>
                                <span class="text-secondary">#<?= $h($r['entidad_id']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= $r['motivo'] === null || $r['motivo'] === '' ? '—' : $h($r['motivo']) ?></td>
                        <td class="small text-secondary"><?= $r['ip'] === null || $r['ip'] === '' ? '—' : $h($r['ip']) ?></td>
                        <td><?= $formatoJson($r['valor_anterior']) ?></td>
                        <td><?= $formatoJson($r['valor_nuevo']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPaginas > 1): ?>
    <nav class="mt-3" aria-label="Paginación de la bitácora">
        <ul class="pagination pagination-sm justify-content-center flex-wrap">
            <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $h($urlPagina(max(1, $pagina - 1))) ?>">Anterior</a>
            </li>
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $h($urlPagina($i)) ?>"><?= $h($i) ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $h($urlPagina(min($totalPaginas, $pagina + 1))) ?>">Siguiente</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<style>
    .json-bitacora { white-space: pre-wrap; max-height: 9rem; overflow: auto; font-size: .72rem; }
</style>
