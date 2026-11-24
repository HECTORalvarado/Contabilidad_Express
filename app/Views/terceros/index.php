<?php
use App\Core\Csrf;

/** @var array $terceros */
/** @var string $tipo */
/** @var string $q */
/** @var bool $puedeEditar */

$h = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$esActivo = static fn($valor): bool => $valor === true || $valor === 't' || $valor === 'true' || $valor === 1 || $valor === '1';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Clientes y proveedores</h1>
        <p class="text-secondary small mb-0">Administre el catálogo de terceros de la organización.</p>
    </div>
    <?php if ($puedeEditar): ?>
        <a href="/terceros/crear" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Nuevo tercero</a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="/terceros" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label for="q" class="form-label">Buscar</label>
                <input type="search" class="form-control" id="q" name="q" maxlength="100"
                       value="<?= $h($q) ?>" placeholder="Nombre o identificador fiscal">
            </div>
            <div class="col-md-3">
                <label for="tipo" class="form-label">Tipo</label>
                <select class="form-select" id="tipo" name="tipo">
                    <option value="">Todos</option>
                    <option value="cliente" <?= $tipo === 'cliente' ? 'selected' : '' ?>>Clientes</option>
                    <option value="proveedor" <?= $tipo === 'proveedor' ? 'selected' : '' ?>>Proveedores</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Buscar</button>
                <a href="/terceros" class="btn btn-outline-secondary">Limpiar</a>
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
                    <th>Nombre</th>
                    <th>Identificador fiscal</th>
                    <th>Correo electrónico</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <?php if ($puedeEditar): ?>
                        <th class="text-end">Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if ($terceros === []): ?>
                <tr><td colspan="<?= $puedeEditar ? 7 : 6 ?>" class="text-center text-secondary py-4">No hay terceros registrados.</td></tr>
            <?php else: ?>
                <?php foreach ($terceros as $t): ?>
                    <?php
                    $id = (int) $t['id'];
                    $activo = $esActivo($t['activo']);
                    ?>
                    <tr>
                        <td><span class="badge bg-<?= $t['tipo'] === 'cliente' ? 'primary' : 'secondary' ?>"><?= $h(ucfirst((string) $t['tipo'])) ?></span></td>
                        <td class="fw-semibold"><?= $h($t['nombre']) ?></td>
                        <td><?= $h($t['identificador_fiscal']) ?></td>
                        <td><?= ($t['email'] ?? '') !== '' ? $h($t['email']) : '—' ?></td>
                        <td><?= ($t['telefono'] ?? '') !== '' ? $h($t['telefono']) : '—' ?></td>
                        <td>
                            <?php if ($activo): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($puedeEditar): ?>
                            <td>
                                <div class="d-flex justify-content-end gap-1 flex-wrap">
                                    <a href="/terceros/<?= $id ?>/editar" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <form method="post" action="/terceros/<?= $id ?>/estado" class="m-0">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-<?= $activo ? 'warning' : 'success' ?>">
                                            <?= $activo ? 'Desactivar' : 'Activar' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
