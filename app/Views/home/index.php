<?php /** @var bool $dbOk */ /** @var string|null $dbError */ ?>
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

            <p class="text-secondary small mb-0"><i class="bi bi-info-circle me-1"></i>El inicio de sesión se habilita en la Fase 1.</p>
        </div>
    </div>
</div>
