# T07 — Vista de detalle de factura (worker-c)

## Objetivo
Crear únicamente `app/Views/facturas/show.php`: pantalla de detalle de una factura con datos, saldo, documentos, pagos/cobros, historial de bitácora y botones de acción según permiso y estado.

## Archivos
- Crear: `app/Views/facturas/show.php`.
- Prohibido tocar cualquier otro archivo (controlador, modelos, rutas, otras vistas).

## Qué leer
- `docs/CONVENCIONES.md`.
- PROMPT.md: secciones 5, 7 y 9.
- Archivo de referencia de estilo: `app/Views/facturas/index.php` (badges, fechas dd/mm/aaaa, montos US$, Bootstrap).

## Datos disponibles (ya preparados por el controlador)
- `$factura`: array con `id`, `tipo` (CXP/CXC), `numero_documento`, `tercero_nombre`, `identificador_fiscal`, `tercero_tipo`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `estado`, `motivo_rechazo`, `registrado_por`, `aprobado_por`, `fecha_aprobacion`, `pagado`, `saldo`.
- `$documentos`: array con `id`, `nombre_original`, `mime`, `tamano`, `subido_por_nombre`, `creado_en`.
- `$pagos`: array con `id`, `fecha`, `monto`, `metodo`, `referencia`, `registrado_por_nombre`, `creado_en`.
- `$historial`: array con `id`, `fecha`, `accion`, `entidad`, `entidad_id`, `usuario_nombre`, `motivo`, `valor_anterior`, `valor_nuevo` (los dos últimos são JSON en texto; muéstralos legibles y escapados).
- `$puedeEditar`, `$puedeAprobar`, `$puedePagar`: booleanos. `$tipoPago`: `'pago'` o `'cobro'`. `$metodos`: array con métodos válidos.
- `$title` ya lo pasa el controlador al layout.

## Reglas
- Badges de estado: pendiente_aprobacion=warning, aprobada=primary, parcial=info, pagada=success, rechazada=danger. Mostrar "Vencida" cuando `fecha_vencimiento < hoy` y `saldo > 0`.
- Mostrar `motivo_rechazo` de forma destacada si el estado es rechazada.
- Toda salida escapada con `View::e()` o `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. Montos con `US$` y 2 decimales; fechas `dd/mm/aaaa`.
- Enlaces de descarga: `/documentos/{id}/descargar` para cada documento.
- Enlace a `/facturas/{id}/editar` solo si `$puedeEditar` (edición y subida de documentos).
- Formularios POST (todos con `Csrf::field()`), renderizados solo cuando corresponda:
  - `POST /facturas/{id}/aprobar`: solo si `$puedeAprobar`; botón "Aprobar factura".
  - `POST /facturas/{id}/rechazar`: solo si `$puedeAprobar`; `textarea name="motivo" required maxlength="500"`; botón "Rechazar factura".
  - `POST /facturas/{id}/pagar`: solo si `$puedePagar`; campos `fecha` (date, required), `monto` (number, step 0.01, min 0.01, max = saldo), `metodo` (select con `$metodos`), `referencia` (text, maxlength 100). Botón "Registrar pago" o "Registrar cobro" según `$tipoPago`.
- Sin lógica de negocio nueva: el controlador ya valida roles, estado, saldo, documentos y bitácora. No añadir JavaScript ni librerías.

## Criterio de aceptación
- `php -l app/Views/facturas/show.php` sin errores.
- Solo se crea ese archivo; sin TODO ni placeholders; tablas/vacíos con mensajes claros.
- Respuesta final: lista de archivos + resumen máximo de 3 líneas.
