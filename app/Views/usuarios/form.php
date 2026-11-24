<?php
use App\Core\Csrf;

/** @var array|null $registro */
/** @var array $roles */
/** @var string $accion */
/** @var bool $esNuevo */
/** @var bool $esPropio */

$h = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1"><?= $esNuevo ? 'Nuevo usuario' : 'Editar usuario' ?></h1>
        <p class="text-secondary small mb-0">
            <?= $esNuevo ? 'Ingrese los datos de la nueva cuenta.' : 'Modifique los datos de la cuenta seleccionada.' ?>
        </p>
    </div>
    <a href="/usuarios" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<div class="card p-4" style="max-width: 640px;">
    <form method="post" action="<?= $h($accion) ?>" novalidate>
        <?= Csrf::field() ?>
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" maxlength="120"
                   value="<?= $h($registro['nombre'] ?? '') ?>" autocomplete="name" required autofocus>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="email" name="email" maxlength="150"
                   value="<?= $h($registro['email'] ?? '') ?>" autocomplete="email" required>
        </div>

        <div class="mb-3">
            <label for="rol_id" class="form-label">Rol</label>
            <?php if ($esPropio): ?>
                <input type="hidden" name="rol_id" value="<?= $h($registro['rol_id']) ?>">
                <select class="form-select" id="rol_id" disabled>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= $h($rol['id']) ?>"
                            <?= (int) ($registro['rol_id'] ?? 0) === (int) $rol['id'] ? 'selected' : '' ?>>
                            <?= $h(str_replace('_', ' ', (string) $rol['nombre'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">No puede cambiar su propio rol.</div>
            <?php else: ?>
                <select class="form-select" id="rol_id" name="rol_id" required>
                    <option value="">Seleccione un rol</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= $h($rol['id']) ?>"
                            <?= (int) ($registro['rol_id'] ?? 0) === (int) $rol['id'] ? 'selected' : '' ?>>
                            <?= $h(str_replace('_', ' ', (string) $rol['nombre'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <?php if ($esNuevo): ?>
            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" minlength="8"
                       autocomplete="new-password" required>
                <div class="form-text">Mínimo 8 caracteres.</div>
            </div>
        <?php else: ?>
            <div class="alert alert-info small">
                La contraseña no se modifica en este formulario. Use la opción «Contraseña» en el listado de usuarios.
            </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary"><?= $esNuevo ? 'Crear usuario' : 'Guardar cambios' ?></button>
    </form>
</div>
