# PROGRESO
## Hecho
- Fases 0-2: MVC, BD con trigger inmutable, seed, login/CSRF/bloqueo/RBAC/bitácora, usuarios; Tercero/Factura/Documento (IVA, duplicados, saldo, vencidas, MIME 5 MB); alta/edición, documentos y listado con filtros/paginación.
- Orquestador Big Pickle; workers a=flint-40b-free, b=iceman-free, c=hy4, d=beam; reservas nemotron→mimo→deepseek (429: solo reintento); 429 marca agotado y se rota. Ping: a y b crearon ping-a/b (borrados) y confirmaron su modelo en sesión hija.
- Verificación: php -l 33 archivos OK; HTTP 200 en /, /terceros, /facturas, /facturas/crear, /bitacora; auditor 403 en altas; php -l app/+config/ tras cambio workers: 33/0.
- Fase 3 (yo): Factura::aprobar/rechazar y Pago::registrar con FOR UPDATE, reglas 1-10; controlador show/aprobar/rechazar/pagar, rutas y enlace Detalle; php -l OK.
## A medias o con error
- Ninguno.
## Falta
- Fase 3: vista de detalle (worker-c), verificación y revisión crítica (3.10).
## Decisiones
- BD local `Contabilidad_express`; credenciales solo en `.env`; bitácora se reinicia con `database/seed.php`.
- Terceros: administrador y contador editan; cualquier usuario autenticado lista. Aprueban gerente/contador/admin (nunca el registrador); pagos/cobros solo encargado_cxp/cxc.
