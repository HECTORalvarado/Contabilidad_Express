<?php
/** @var string $title */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Reportes</h1>
        <p class="text-secondary small mb-0">Consulte la cartera por antigüedad y las cuentas por pagar pendientes o vencidas.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-2"><i class="bi bi-clock-history me-2"></i>CxC por antigüedad</h2>
                <p class="text-secondary small">
                    Saldos por cliente: corriente, 1-30, 31-60, 61-90 y más de 90 días vencidos.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="/reportes/cxc-antiguedad" class="btn btn-primary"><i class="bi bi-eye me-1"></i>Ver reporte</a>
                    <a href="/reportes/cxc-antiguedad/csv" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Descargar CSV</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-2"><i class="bi bi-cash-stack me-2"></i>CxP pendientes y vencidas</h2>
                <p class="text-secondary small">
                    Documentos por proveedor ordenados por vencimiento, con saldo, situación y días de atraso.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="/reportes/cxp-pendientes" class="btn btn-primary"><i class="bi bi-eye me-1"></i>Ver reporte</a>
                    <a href="/reportes/cxp-pendientes/csv" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Descargar CSV</a>
                </div>
            </div>
        </div>
    </div>
</div>
