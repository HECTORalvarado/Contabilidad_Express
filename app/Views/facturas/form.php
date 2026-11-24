<?php
use App\Core\Csrf;
use App\Core\View;

/** @var array|null $factura */
/** @var array $terceros */
/** @var float $ivaPorcentaje */
/** @var array $documentos */
/** @var string $accion */
/** @var bool $esNuevo */

$h = static fn($valor): string => View::e($valor);
$tipo = (string) ($factura['tipo'] ?? 'CXP');
$tercerosJson = json_encode($terceros, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1"><?= $esNuevo ? 'Nueva factura' : 'Editar factura ' . $h($factura['numero_documento']) ?></h1>
        <p class="text-secondary small mb-0">
            <?= $esNuevo ? 'Registre la factura y luego adjunte su documento de soporte.' : 'Modifique los datos y administre los documentos de soporte.' ?>
        </p>
    </div>
    <a href="/facturas" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver al listado</a>
</div>

<div class="card p-4" style="max-width: 820px;">
    <form method="post" action="<?= $h($accion) ?>" novalidate>
        <?= Csrf::field() ?>
        <?php if (!$esNuevo): ?>
            <input type="hidden" name="tipo" value="<?= $h($tipo) ?>">
        <?php endif; ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="tipo" class="form-label">Tipo</label>
                <?php if ($esNuevo): ?>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <option value="CXP">Cuentas por pagar (CXP)</option>
                        <option value="CXC">Cuentas por cobrar (CXC)</option>
                    </select>
                <?php else: ?>
                    <input class="form-control" type="text" value="<?= $h($tipo === 'CXP' ? 'Cuentas por pagar (CXP)' : 'Cuentas por cobrar (CXC)') ?>" disabled>
                    <div class="form-text">El tipo no se puede cambiar después de registrar la factura.</div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="tercero_id" class="form-label">Tercero</label>
                <select class="form-select" id="tercero_id" name="tercero_id" required>
                    <option value="">Seleccione un tercero</option>
                    <?php foreach ($terceros as $tercero): ?>
                        <option value="<?= $h($tercero['id']) ?>" data-tipo="<?= $h($tercero['tipo'] === 'proveedor' ? 'CXP' : 'CXC') ?>"
                            <?= (int) ($factura['tercero_id'] ?? 0) === (int) $tercero['id'] ? 'selected' : '' ?>>
                            <?= $h($tercero['nombre']) ?> · <?= $h($tercero['identificador_fiscal']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="numero_documento" class="form-label">Número de documento</label>
                <input type="text" class="form-control" id="numero_documento" name="numero_documento" maxlength="60"
                       value="<?= $h($factura['numero_documento'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label for="subtotal" class="form-label">Subtotal (USD)</label>
                <input type="number" class="form-control" id="subtotal" name="subtotal" min="0" max="999999999999.99"
                       step="0.01" value="<?= $h($factura['subtotal'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="fecha_emision" class="form-label">Fecha de emisión</label>
                <input type="date" class="form-control" id="fecha_emision" name="fecha_emision"
                       value="<?= $h($factura === null ? date('Y-m-d') : substr((string) $factura['fecha_emision'], 0, 10)) ?>" required>
            </div>
            <div class="col-md-4">
                <label for="fecha_vencimiento" class="form-label">Fecha de vencimiento</label>
                <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento"
                       value="<?= $h($factura === null ? date('Y-m-d', strtotime('+30 days')) : substr((string) $factura['fecha_vencimiento'], 0, 10)) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">IVA (<?= $h($ivaPorcentaje) ?>%)</label>
                <input class="form-control" type="text" id="iva" value="<?= $h($factura['iva'] ?? '0.00') ?>" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Total (USD)</label>
                <input class="form-control fw-semibold" type="text" id="total" value="<?= $h($factura['total'] ?? '0.00') ?>" readonly>
                <div class="form-text">Calculado con el IVA configurado (<?= $h($ivaPorcentaje) ?>%).</div>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><?= $esNuevo ? 'Registrar factura' : 'Guardar cambios' ?></button>
            <a href="/facturas" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php if (!$esNuevo): ?>
    <?php $facturaId = (int) $factura['id']; ?>
    <div class="card p-4 mt-4" style="max-width: 820px;">
        <h2 class="h5 mb-3">Documentos de soporte</h2>
        <?php if ($documentos === []): ?>
            <div class="alert alert-warning small">Aún no hay documentos. Adjunte al menos uno.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-3">
                    <thead class="table-light"><tr><th>Archivo</th><th>Tipo</th><th>Tamaño</th><th>Subido por</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($documentos as $doc): ?>
                        <tr>
                            <td><?= $h($doc['nombre_original']) ?></td>
                            <td><?= $h(strtoupper((string) $doc['mime'])) ?></td>
                            <td><?= $h(number_format((int) $doc['tamano'] / 1024, 1)) ?> KB</td>
                            <td><?= $h($doc['subido_por_nombre']) ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/documentos/<?= (int) $doc['id'] ?>/descargar">
                                    <i class="bi bi-download me-1"></i>Descargar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <form method="post" action="/facturas/<?= $facturaId ?>/documentos" enctype="multipart/form-data" class="row g-3 align-items-end">
            <?= Csrf::field() ?>
            <div class="col-md-8">
                <label for="documento" class="form-label">Adjuntar PDF, JPG o PNG (máximo 5 MB)</label>
                <input class="form-control" type="file" id="documento" name="documento"
                       accept="application/pdf,image/jpeg,image/png" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-upload me-1"></i>Subir documento</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<script>
(function () {
    const porcentaje = <?= $h($ivaPorcentaje) ?>;
    const tipo = document.getElementById('tipo');
    const tercero = document.getElementById('tercero_id');
    const subtotal = document.getElementById('subtotal');
    const iva = document.getElementById('iva');
    const total = document.getElementById('total');
    const opciones = <?= $tercerosJson ?: '[]' ?>;

    function calcular() {
        const base = Number(subtotal.value || 0);
        const impuesto = Math.round(base * porcentaje) / 100;
        iva.value = impuesto.toFixed(2);
        total.value = (base + impuesto).toFixed(2);
    }

    function filtrar() {
        const actual = tipo ? tipo.value : (tercero.querySelector('option:checked')?.dataset.tipo || 'CXP');
        Array.from(tercero.options).forEach(function (op) {
            if (op.value === '') { return; }
            op.hidden = op.dataset.tipo !== actual;
        });
        const seleccionada = tercero.selectedOptions[0];
        if (seleccionada && seleccionada.value !== '' && seleccionada.dataset.tipo !== actual) {
            tercero.value = '';
        }
    }

    if (tipo) { tipo.addEventListener('change', filtrar); }
    subtotal.addEventListener('input', calcular);
    filtrar();
    calcular();
})();
</script>
