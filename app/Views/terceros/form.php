<?php
use App\Core\Csrf;

/** @var array|null $registro */
/** @var string $accion */
/** @var bool $esNuevo */

$h = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$tipo = (string) ($registro['tipo'] ?? 'cliente');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1"><?= $esNuevo ? 'Nuevo tercero' : 'Editar tercero' ?></h1>
        <p class="text-secondary small mb-0">
            <?= $esNuevo ? 'Ingrese los datos del nuevo tercero.' : 'Modifique los datos del tercero seleccionado.' ?>
        </p>
    </div>
    <a href="/terceros" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<div class="card p-4" style="max-width: 640px;">
    <form method="post" action="<?= $h($accion) ?>" novalidate>
        <?= Csrf::field() ?>
        <div class="mb-3">
            <label for="<?= $esNuevo ? 'tipo' : 'tipoMostrar' ?>" class="form-label">Tipo</label>
            <?php if ($esNuevo): ?>
                <select class="form-select" id="tipo" name="tipo" required>
                    <option value="">Seleccione un tipo</option>
                    <option value="cliente" <?= $tipo === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                    <option value="proveedor" <?= $tipo === 'proveedor' ? 'selected' : '' ?>>Proveedor</option>
                </select>
            <?php else: ?>
                <input type="hidden" name="tipo" value="<?= $h($tipo) ?>">
                <input type="text" class="form-control" id="tipoMostrar"
                       value="<?= $h($tipo === 'cliente' ? 'Cliente' : 'Proveedor') ?>" disabled>
                <div class="form-text">El tipo no se puede modificar.</div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" maxlength="150"
                   value="<?= $h($registro['nombre'] ?? '') ?>" autocomplete="name" required autofocus>
        </div>

        <div class="mb-3">
            <label for="identificador_fiscal" class="form-label">Identificador fiscal</label>
            <input type="text" class="form-control" id="identificador_fiscal" name="identificador_fiscal" maxlength="40"
                   value="<?= $h($registro['identificador_fiscal'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico (opcional)</label>
            <input type="email" class="form-control" id="email" name="email" maxlength="150"
                   value="<?= $h($registro['email'] ?? '') ?>" autocomplete="email">
        </div>

        <div class="mb-4">
            <label for="telefono" class="form-label">Teléfono (opcional)</label>
            <input type="tel" class="form-control" id="telefono" name="telefono" maxlength="30"
                   value="<?= $h($registro['telefono'] ?? '') ?>" autocomplete="tel">
        </div>

        <button type="submit" class="btn btn-primary"><?= $esNuevo ? 'Crear tercero' : 'Guardar cambios' ?></button>
    </form>
</div>
