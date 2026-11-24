<?php
use App\Core\Csrf;

/** @var array $usuarios */
/** @var int|null $yo */

$h = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$esActivo = static fn($valor): bool => $valor === true || $valor === 't' || $valor === 'true' || $valor === 1 || $valor === '1';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Usuarios</h1>
        <p class="text-secondary small mb-0">Administre las cuentas, los roles y el estado de acceso.</p>
    </div>
    <a href="/usuarios/crear" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Nuevo usuario</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Correo electrónico</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Bloqueo</th>
                    <th>Intentos fallidos</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($usuarios === []): ?>
                <tr><td colspan="7" class="text-center text-secondary py-4">No hay usuarios registrados.</td></tr>
            <?php else: ?>
                <?php foreach ($usuarios as $u): ?>
                    <?php
                    $id = (int) $u['id'];
                    $activo = $esActivo($u['activo']);
                    $bloqueado = !empty($u['bloqueado_hasta']) && strtotime((string) $u['bloqueado_hasta']) > time();
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= $h($u['nombre']) ?></td>
                        <td><?= $h($u['email']) ?></td>
                        <td><span class="badge bg-secondary"><?= $h(str_replace('_', ' ', (string) $u['rol_nombre'])) ?></span></td>
                        <td>
                            <?php if ($activo): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($bloqueado): ?>
                                <span class="badge bg-danger">Bloqueado</span>
                            <?php else: ?>
                                <span class="text-secondary">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $h($u['intentos_fallidos']) ?></td>
                        <td>
                            <div class="d-flex justify-content-end gap-1 flex-wrap">
                                <a href="/usuarios/<?= $id ?>/editar" class="btn btn-sm btn-outline-primary">Editar</a>
                                <?php if ($yo !== $id): ?>
                                    <form method="post" action="/usuarios/<?= $id ?>/estado" class="m-0">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-<?= $activo ? 'warning' : 'success' ?>">
                                            <?= $activo ? 'Desactivar' : 'Activar' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark align-self-center">Su cuenta</span>
                                <?php endif; ?>
                                <?php if ($bloqueado): ?>
                                    <form method="post" action="/usuarios/<?= $id ?>/desbloquear" class="m-0">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Desbloquear</button>
                                    </form>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#clave<?= $id ?>">Contraseña</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($usuarios as $u): ?>
    <?php $id = (int) $u['id']; ?>
    <div class="modal fade" id="clave<?= $id ?>" tabindex="-1" aria-labelledby="claveTitulo<?= $id ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="/usuarios/<?= $id ?>/password">
                    <?= Csrf::field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title" id="claveTitulo<?= $id ?>">Restablecer contraseña</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-secondary">Nueva contraseña para <?= $h($u['nombre']) ?>. Mínimo 8 caracteres.</p>
                        <label for="nuevaClave<?= $id ?>" class="form-label">Nueva contraseña</label>
                        <input type="password" class="form-control" id="nuevaClave<?= $id ?>" name="password"
                               minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Restablecer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
