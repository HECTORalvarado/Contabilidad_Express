# REVISIÓN CRÍTICA — FASE 3 (manual, no subagente)

## Instrucción para el revisor
Revisa el código crítico de la Fase 3. **Solo REPORTA problemas con archivo y línea; no reescribas nada; lee pocos archivos por turno.** Si no encuentras problemas en un archivo, dilo brevemente y sigue con el siguiente. No ejecutes cambios ni propongas código completo: indica archivo, línea y el problema concreto.

## Archivos a revisar
- `database/schema.sql` (tablas facturas, documentos, pagos, bitácora y trigger de inmutabilidad).
- `app/Core/Auth.php`, `app/Core/Audit.php`, `app/Core/Csrf.php`.
- `app/Models/Factura.php` (find, update, aprobar, rechazar), `app/Models/Documento.php` (subida/descarga), `app/Models/Pago.php` (transacción y FOR UPDATE).
- `app/Controllers/FacturasController.php` (show, aprobar, rechazar, pagar, subirDocumento, descargarDocumento) y `public/index.php` (rutas nuevas).
- `app/Views/facturas/show.php` (detalle, formularios y permisos en la vista).

## Checklist
Reglas de la sección 7 del PROMPT.md (todas):
1. Aprobar o pagar sin documento de soporte debe ser imposible.
2. Quien registra la factura no puede aprobarla ni rechazarla (segregación).
3. Rechazar exige motivo; rechazada se corrige y vuelve a pendiente_aprobacion.
4. Factura aprobada/parcial/pagada no es editable (ni admite más documentos).
5. Pagos/cobros solo sobre aprobada/parcial; monto ≤ saldo; saldada → pagada, abono → parcial.
6. Factura pagada no se paga de nuevo; transacción con `SELECT ... FOR UPDATE`.
7. Duplicados bloqueados (error 23505 con mensaje amigable).
8. CXP solo proveedores, CXC solo clientes; IVA calculado con parámetro y validado en servidor.
9. Eliminación lógica (`activo = false`), nunca DELETE de negocio.
10. Bitácora de aprobación, rechazo, pagos/cobros, subida y descarga de documentos.

Seguridad (sección 9): solo consultas preparadas PDO; toda salida escapada; sesión/regeneración y expiración por inactividad; CSRF en cada POST; subida PDF/JPG/PNG con MIME real y máx. 5 MB fuera de `public/`; descarga con permiso en servidor; credenciales solo en `.env`; errores genéricos al usuario y detalle en `error_log`.

Permisos (sección 5): gerente/contador/administrador aprueban (contador y admin solo facturas que no registraron); encargado_cxp registra pagos CXP, encargado_cxc registra cobros CXC; auditor/tesorero y demás roles sin acciones de escritura; acceso directo por URL denegado con 403.

## Cómo ejecutarlo
Abre una sesión nueva (`/new`) con `google/gemini-4-hydrogen` (permite pocas solicitudes por minuto: hazlas con pausas) o, si no hay cuota, con `opencode/flint-40b-free`, y pásale este archivo. Trae el reporte a esta sesión para corregir lo que proceda.
