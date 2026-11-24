# PROGRESO
## Hecho
- Fases 0-2: MVC, BD con trigger inmutable, seed, login/CSRF/bloqueo/RBAC/bitácora y usuarios.
- T03 modelos Tercero/Factura/Documento: IVA por parámetro, duplicados, saldo, vencidas, MIME real 5 MB; T04 alta/edición y documentos; T05 CRUD terceros (worker-c, 1 corrección); T06 listado, filtros y paginación (worker-c).
- Orquestador Big Pickle; workers a=flint-40b-free, b=iceman-free, c=hy4, d=beam; reservas nemotron→mimo→deepseek (mimo/deepseek 429: solo reintento); 429 marca agotado y se rota.
- Verificación: php -l 33 archivos OK; HTTP 200 en /, /terceros, /facturas, /facturas/crear, /bitacora; auditor 403 en altas.
- Ping 3.3: respondieron worker-a (flint-40b-free) y worker-b (iceman-free); crearon docs/ping-a.txt y docs/ping-b.txt con OK, borrados; cada uno confirmó su modelo en sesión hija.
- php -l en app/ y config/ tras el cambio: 33 archivos, 0 errores.
## A medias o con error
- Ninguno.
## Falta
- Fase 3: aprobación/rechazo, pagos/cobros con FOR UPDATE, detalle de factura y revisión crítica (3.10).
## Decisiones
- BD local `Contabilidad_express`; credenciales solo en `.env`; bitácora se reinicia con `database/seed.php`.
- Terceros: administrador y contador editan; cualquier usuario autenticado lista.
