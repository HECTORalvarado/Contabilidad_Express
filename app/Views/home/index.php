<?php
use App\Core\Auth;
/** @var bool $dbOk */
/** @var string|null $dbError */
$usuario = Auth::user();
$rol = $usuario['rol_nombre'] ?? null;
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card p-4">
            <h1 class="h4 mb-1">Sistema contable-financiero</h1>
            <p class="text-secondary mb-4">Facturas, cuentas por pagar y por cobrar, aprobaciones y trazabilidad.</p>

            <?php if ($dbOk): ?>
                <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Conexión a PostgreSQL correcta.</span>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mb-4">
                    <div class="fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i>Sin conexión a la base de datos.</div>
                    <div class="small mt-1">Revisa <code>.env</code> y ejecuta <code>database/schema.sql</code>.</div>
                </div>
            <?php endif; ?>

            <?php if ($usuario): ?>
                <p class="mb-3">
                    Sesión iniciada como <strong><?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="badge text-bg-secondary"><?= htmlspecialchars(str_replace('_', ' ', (string) $rol), ENT_QUOTES, 'UTF-8') ?></span>
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($rol === 'administrador'): ?>
                        <a class="btn btn-primary btn-sm" href="/usuarios"><i class="bi bi-person-gear me-1"></i>Usuarios</a>
                    <?php endif; ?>
                    <?php if (in_array($rol, ['administrador', 'auditor'], true)): ?>
                        <a class="btn btn-outline-secondary btn-sm" href="/bitacora"><i class="bi bi-journal-text me-1"></i>Bitácora</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <a class="btn btn-primary" href="/login"><i class="bi bi-box-arrow-in-right me-1"></i>Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </div>
</div>
