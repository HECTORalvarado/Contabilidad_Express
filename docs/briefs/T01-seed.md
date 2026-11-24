# T01 — Seed de datos de demostración

## Objetivo
Crear `database/seed.php`: script CLI idempotente que llena la base con datos ficticios de demostración para Contabilidad Express.

## Archivos
- CREAR: `database/seed.php` (único archivo permitido).
- Puedes LEER: `database/schema.sql`, `docs/CONVENCIONES.md`, sección 6 de `PROMPT.md`.
- PROHIBIDO: tocar cualquier otro archivo (incluido `.env`), leer credenciales o usar datos reales.

## Implementación
- PHP 8.2 puro, sin Composer. Usa `require __DIR__ . '/../app/Core/Database.php';` y `App\Core\Database::getConnection()` para obtener PDO (ya lee `config/config.php`). Nada de credenciales en el código.
- Idempotente: al inicio `TRUNCATE bitacora, pagos, documentos, facturas, terceros, usuarios, roles RESTART IDENTITY CASCADE;` y `INSERT ... ON CONFLICT` para `parametros`.
- Roles (8): administrador, gerente, contador, auxiliar, encargado_cxp, encargado_cxc, tesorero, auditor.
- Usuarios: 1 por rol, nombre ficticio, emails `admin@demo.local`, `gerente@demo.local`, `contador@demo.local`, `auxiliar@demo.local`, `cxp@demo.local`, `cxc@demo.local`, `tesorero@demo.local`, `auditor@demo.local`; todos activos, contraseña `Demo1234!` con `password_hash(..., PASSWORD_DEFAULT)`.
- Terceros: 3 clientes y 3 proveedores ficticios con NIT `0614-xxxxxx-00x-x`; emails de ejemplo en dominio `example.com`.
- Facturas (8, montos NUMERIC con IVA 13 %, siempre `total = subtotal + iva`):
  - CXP pendiente_aprobacion, vence en +15 días.
  - CXP aprobada, vence en +3 días (aprobada por gerente, registrada por auxiliar).
  - CXP parcial con un pago parcial, vencida (−10 días).
  - CXP pagada con pago por el total (vencida).
  - CXC pendiente_aprobacion, vence en +20 días.
  - CXC aprobada, vence en +2 días.
  - CXC parcial con un pago parcial, vencida (−20 días).
  - CXC pagada con pago por el total.
  - `registrado_por` = auxiliar; `aprobado_por` = gerente o contador (nunca el mismo); `fecha_aprobacion` coherente; la rechazada no es necesaria.
  - Fechas de emisión calculadas con `DateTime` respecto a hoy; usa `dd/mm` solo en pantalla, en BD `Y-m-d`.
- Pagos: 1 por cada factura parcial y 1 por cada pagada (4 en total), montos > 0 y ≤ saldo, método `transferencia` o `efectivo`.
- Documentos: 1 por factura (8), `mime` `application/pdf`, `tamano` real del archivo, `nombre_archivo` aleatorio único (`bin2hex(random_bytes(8)) . '.pdf'`); escribe un PDF mínimo válido (`%PDF-1.4` … `%%EOF`) en `storage/uploads/` con `file_put_contents` (crea la carpeta si no existe).
- `parametros`: `iva_porcentaje=13`, `dias_alerta_vencimiento=5`.
- Salida por consola: conteos insertados. Sin `TODO` ni placeholders. UTF-8.

## Criterio de aceptación
- `php -l database/seed.php` sin errores.
- `php database/seed.php` corre sin errores dos veces seguidas.
- Al final: 8 roles, 8 usuarios, 6 terceros, 8 facturas, 8 documentos, 4 pagos, 2 parámetros.
- Cada factura aprobada/parcial/pagada tiene `aprobado_por` distinto de `registrado_por` y al menos un documento.
