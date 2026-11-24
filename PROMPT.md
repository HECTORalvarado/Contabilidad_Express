ROL
Eres el ORQUESTADOR de este proyecto: arquitecto y desarrollador en PHP. Construirás un MVP (producto mínimo viable) de un sistema web contable-financiero. Prioriza que funcione, sea seguro y sea simple. Delegas el trabajo repetitivo a subagentes de opencode según la sección 3. Este archivo (PROMPT.md) es la especificación del proyecto.

# 0. ARRANQUE Y REANUDACIÓN (haz esto antes que nada)
Una sesión anterior con otro modelo pudo cortarse a medias. No asumas nada: comprueba el estado real.
1. Si PROGRESO.md existe, léelo primero. Si no existe, créalo. Formato: "Hecho / A medias o con error / Falta / Decisiones" (máx. 15 líneas).
2. Si el proyecto ya tiene archivos: lista el árbol (sin .git ni storage/uploads), ejecuta `php -l` en todos los .php y corrige PROGRESO.md según lo que realmente existe y funciona. Termina o rehaz SOLO los archivos que estén a medias o con error; no rehagas lo que ya funciona.
3. Si ya existen .opencode/agents/worker-*.md, no los recrees: actualiza SOLO su campo `model` para que coincida con la tabla de 3.1 (el orquestador cambió y los modelos se reasignaron). Repite la prueba 3.3 solo si PROGRESO.md no dice que ya se hizo con el modelo actual de worker-a.
4. Control de versiones: asegúrate de que .gitignore exista (incluye .env y storage/uploads) ANTES de `git init`. Haz un commit corto al terminar cada tarea. Si git no está disponible, sáltalo.
5. Continúa desde la primera fase incompleta.

# 1. CONTEXTO
Contabilidad Express, S.A. de C.V. (El Salvador) presta servicios contables a PYMES. Hoy trabaja con facturas físicas, hojas de cálculo, correos y chats, lo que causa errores de digitación, documentos perdidos, pagos duplicados o tardíos, cobros atrasados y aprobaciones sin evidencia. Necesita un sistema web que centralice facturas con su documento de soporte, controle cuentas por pagar (CxP) y por cobrar (CxC), formalice aprobaciones y deje trazabilidad de todo.

# 2. REGLAS DE TRABAJO (plan gratuito, uso limitado)
- Código compacto, sin sobreingeniería. Sin explicaciones largas ni repetir código ya escrito.
- Sin frameworks ni paquetes de Composer. Sin README extenso. Sin pruebas automatizadas.
- Cuida el contexto y los límites de uso: crea varios archivos relacionados por turno, no releas lo que acabas de escribir, no explores archivos sin necesidad y no pegues archivos completos en tus respuestas.
- Si recibes un error 429, de cuota o de contexto: NO reintentes en bucle. Guarda el estado en PROGRESO.md, dime qué pasó y detente.
- Actualiza PROGRESO.md al terminar CADA tarea (una línea), no solo al final de la fase.
- Trabaja por FASES (sección 10). Al terminar cada fase: la app debe correr sin errores; actualiza PROGRESO.md; indícame en 3-5 líneas cómo probarla; recomiéndame abrir una sesión nueva (/new) para vaciar el contexto, porque PROGRESO.md basta para retomar; y DETENTE hasta que yo escriba "continuar".
- Sin placeholders ni "TODO": todo lo entregado debe funcionar. Archivos completos, nunca a medias.
- Una tarea a la vez. Si algo es ambiguo, elige la opción más simple, anótala en PROGRESO.md y sigue. Pregunta solo si algo te bloquea por completo.

# 3. DELEGACIÓN (opencode)
Tú eres el modelo principal (Big Pickle, opencode/big-pickle). Los workers son modelos gratuitos, de uso limitado, temporales y de calidad desigual: úsalos para trabajo repetitivo y verifica TODO lo que devuelvan.

3.1 Verificación de modelos
Ejecuta `opencode models` y confirma que existen estos IDs. Usa solo los que aparezcan; si falta alguno, sáltalo y anótalo en PROGRESO.md.
- worker-a → opencode/mimo-v2.6-flash-free (principal para módulos completos)
- worker-b → opencode/deepseek-v4.1-flash-free (solo respaldo: puede estar agotado)
- worker-c → opencode/hy4-flash-free (vistas, filtros, reportes)
- worker-d → opencode/beam-free (tareas triviales: seed.php, README)
- Reservas (crea su agente solo si los anteriores fallan): opencode/flint-40b-free, opencode/iceman-free, opencode/nemotron-3.5-lightning-free.
- NO usar para código: opencode/nemotron-3.5-super-vl-free (parece orientado a visión-lenguaje).
- Tu propio ID debe ser opencode/big-pickle; no lo uses en ningún worker.
- Revisor externo, NO subagente (lo usa el usuario a mano): ver 3.10.

3.2 Crear los agentes
Crea .opencode/agents/worker-a.md, worker-b.md, worker-c.md y worker-d.md. Todos con este formato, cambiando solo `model` (usa el ID verificado en 3.1):
---
description: Desarrollador PHP que implementa un módulo según un brief. No decide arquitectura.
mode: subagent
model: opencode/<id-del-modelo>
temperature: 0.2
permission:
  edit: allow
  bash: deny
---
Eres un desarrollador PHP 8.2 + PostgreSQL (PDO). Recibes un brief en docs/briefs/.
- Lee SOLO lo que el brief indique (secciones de PROMPT.md, docs/CONVENCIONES.md, archivo de referencia).
- Crea o modifica SOLO los archivos que el brief lista. Si necesitas otro, dilo en tu respuesta y no lo toques.
- Imita el patrón del archivo de referencia. Sin librerías nuevas ni cambios de arquitectura.
- Obligatorio: consultas preparadas PDO, htmlspecialchars en toda salida, token CSRF en cada POST, permiso verificado en servidor con Auth::require(), Audit::log() en acciones relevantes.
- Nunca leas ni muestres .env, credenciales ni datos reales; usa solo datos ficticios.
- Entrega código completo (sin TODO ni placeholders). Respuesta final: lista de archivos + resumen de máximo 3 líneas.

3.3 Prueba de enrutamiento (una sola vez)
Delega a worker-a con la herramienta de tareas (subagent_type "worker-a") esta tarea: "crea docs/ping.txt con la palabra OK". Si la herramienta no acepta ese subagente o el archivo no aparece, pasa al MODO MANUAL (3.8) y no lo vuelvas a intentar. Si funciona, borra el archivo, anótalo en PROGRESO.md y pídeme que confirme en la sesión hija del subagente que usó el modelo del archivo (mimo-v2.6-flash-free) y no el tuyo.

3.4 Reparto
- Lo haces tú: schema.sql y triggers, autenticación, RBAC, Audit::log, registro de pagos con transacción (FOR UPDATE), reglas de negocio 1-8 de la sección 7, subida y descarga de archivos, integración y corrección de lo delegado.
- Se delega: vistas Bootstrap, CRUDs repetitivos (terceros, usuarios) que sigan un controlador ya hecho, filtros y paginación, dashboard y reportes CSV, seed.php, README.
- Antes de delegar un módulo, escribe tú un ejemplo de referencia completo (p. ej. TercerosController) para que el worker lo imite.
- No delegues tareas triviales (menos de ~30 líneas): hazlas tú. Delega por módulo, no por archivo.

3.5 Brief
Escribe cada brief en docs/briefs/Txx-nombre.md. Debe ser autocontenido: objetivo; archivos exactos a crear o modificar (prohibido tocar otros); qué leer (secciones de PROMPT.md, docs/CONVENCIONES.md, archivo de referencia); reglas aplicables; criterio de aceptación. Luego invoca al worker con: "Lee docs/briefs/Txx-nombre.md y ejecútalo".

3.6 Verificación al recibir
Ejecuta `php -l` en cada archivo entregado y revisa: consultas preparadas, htmlspecialchars, CSRF, permiso en servidor, bitácora, y que no tocó archivos fuera del brief. Da UNA ronda de corrección al worker; si sigue mal, cambia al siguiente worker de la cadena; si también falla, lo haces tú. Registra en PROGRESO.md: tarea → modelo → ok/corregido.

3.7 Fallos y límites
Si un worker da error, alcanza su límite, responde vacío o deja de ser gratuito, pasa al siguiente: worker-a → worker-b → worker-c → worker-d → reservas → tú. Nunca dejes un archivo a medias: termina la unidad de trabajo o márcala como pendiente en PROGRESO.md.

3.8 Modo manual (si los subagentes personalizados no funcionan)
No simules que delegas. Escribe igualmente los briefs en docs/briefs/ y dame, para cada uno, el comando exacto para ejecutarlo en una segunda terminal: opencode run -m opencode/<modelo> "Lee docs/briefs/Txx-nombre.md y ejecútalo". Si esa versión no acepta -m, indícame cambiar de modelo con /models en una sesión nueva. Cuando yo escriba "listo", verifícalo con 3.6.

3.9 Privacidad
Los modelos gratuitos pueden registrar tus prompts o usarlos para mejorar el modelo. Nunca incluyas en un brief ni dejes leer a un worker el .env, contraseñas o datos reales de clientes. Todo dato de prueba es ficticio.

3.10 Revisión crítica con otro modelo (manual, al final de las Fases 3 y 5)
El código crítico lo escribes tú, así que lo revisa un modelo distinto. Escribe docs/briefs/REVISION-faseN.md con: la lista de archivos críticos (schema.sql, Auth, RBAC, aprobación, pagos, subida y descarga de archivos), la checklist (reglas 1-8 de la sección 7 y la sección 9) y la instrucción "solo REPORTA problemas con archivo y línea; no reescribas nada; lee pocos archivos por turno". Dime que abra una sesión nueva (/new) con google/gemini-4-hydrogen (permite pocas solicitudes por minuto: que las haga con pausas) o, si no hay cuota, con opencode/mimo-v2.6-flash-free, y le pase ese archivo. Cuando yo te traiga el reporte, corrige lo que proceda. Si ninguno está disponible, anótalo en PROGRESO.md y continúa sin este paso.

# 4. STACK (ya decidido)
- PHP 8.2+ puro, MVC simple: front controller en public/index.php, router mínimo, controladores, vistas PHP con layout común.
- Base de datos: PostgreSQL 15+ con PDO (pdo_pgsql). Motivo: sistema contable que exige montos exactos (NUMERIC), integridad (FK, CHECK, índices únicos), transacciones con bloqueo de filas, JSONB para la bitácora y triggers para volverla inmutable.
- Frontend: HTML renderizado en servidor + Bootstrap 5 por CDN + JS mínimo. Interfaz en español, moneda USD, fechas dd/mm/aaaa. Estados con badges de color y mensajes claros.
- Debe ejecutarse con: php -S localhost:8000 -t public
- Estructura sugerida:
  public/ (index.php, assets)
  app/ (Core, Controllers, Models, Views)
  config/ (config.php lee .env)
  database/ (schema.sql, seed.php)
  storage/uploads/ (fuera de public)
  docs/ (CONVENCIONES.md, briefs/)
  .opencode/agents/ (worker-*.md)
  .env.example, .gitignore, PROGRESO.md

# 5. ROLES Y PERMISOS
Verifica el permiso en el servidor en CADA acción, no solo ocultando botones.
- administrador: gestiona usuarios y parámetros; ve todo.
- gerente: ve todo, dashboard, reportes, aprueba/rechaza.
- contador: registra y edita facturas, aprueba/rechaza las que NO registró, reportes.
- auxiliar: registra y edita sus facturas (mientras estén pendientes o rechazadas), sube documentos.
- encargado_cxp: ve facturas CxP y registra pagos.
- encargado_cxc: ve facturas CxC y registra cobros.
- tesorero: solo lectura de dashboard y reportes.
- auditor: solo lectura de todo + bitácora. No puede modificar nada.

# 6. MODELO DE DATOS (database/schema.sql)
- roles(id, nombre único)
- usuarios(id, nombre, email único, password_hash, rol_id, activo, intentos_fallidos, bloqueado_hasta, creado_en)
- terceros(id, tipo 'cliente'|'proveedor', nombre, identificador_fiscal, email, telefono, activo, creado_en; único (tipo, identificador_fiscal))
- facturas(id, tipo 'CXP'|'CXC', tercero_id, numero_documento, fecha_emision, fecha_vencimiento, subtotal, iva, total NUMERIC(14,2), estado ('pendiente_aprobacion','aprobada','rechazada','parcial','pagada'), motivo_rechazo, registrado_por, aprobado_por, fecha_aprobacion, activo, creado_en)
  Restricciones: total = subtotal + iva; vencimiento >= emisión; índice ÚNICO (tipo, tercero_id, numero_documento, fecha_emision, total) para evitar duplicados.
- documentos(id, factura_id, nombre_original, nombre_archivo, mime, tamano, subido_por, creado_en)
- pagos(id, factura_id, fecha, monto > 0, metodo, referencia, registrado_por, creado_en) -- sirve para pagos (CXP) y cobros (CXC)
- bitacora(id, usuario_id, fecha timestamptz, accion, entidad, entidad_id, valor_anterior JSONB, valor_nuevo JSONB, motivo, ip). TRIGGER que lance excepción en cualquier UPDATE o DELETE (inmutable).
- parametros(clave, valor): iva_porcentaje=13, dias_alerta_vencimiento=5.
Saldo = total - suma(pagos). "Vencida" se calcula (vencimiento < hoy y saldo > 0), no se guarda.
database/seed.php: crea roles, 1 usuario demo por rol (contraseña de desarrollo "Demo1234!" con password_hash), 3 clientes, 3 proveedores y ~8 facturas ficticias en distintos estados.

# 7. REGLAS DE NEGOCIO (en servidor y, cuando aplique, en la BD)
1. No se puede aprobar ni pagar una factura sin al menos un documento de soporte.
2. Quien registra una factura NO puede aprobarla (segregación de funciones).
3. Rechazar exige motivo. Una factura rechazada puede corregirse y reenviarse (vuelve a pendiente_aprobacion).
4. Una factura aprobada ya no es editable.
5. Solo se registran pagos/cobros sobre facturas aprobadas o parciales; el monto no puede exceder el saldo. Al saldar pasa a 'pagada'; con abono parcial, 'parcial'.
6. Una factura pagada no se puede pagar de nuevo. Usa transacción con SELECT ... FOR UPDATE sobre la factura al registrar pagos.
7. No se permiten facturas duplicadas (combinación de la sección 6). Captura el error 23505 y muestra un mensaje amigable.
8. CXP solo con proveedores; CXC solo con clientes. El IVA se calcula con el parámetro iva_porcentaje y se valida en servidor.
9. Eliminación siempre lógica (activo=false). Nunca DELETE de datos de negocio.
10. Cada acción relevante se registra en la bitácora: login exitoso/fallido, creación y edición de facturas, terceros y usuarios, aprobación, rechazo, pagos, subida y descarga de documentos, cambios de rol.

# 8. PANTALLAS DEL MVP
1. Login/logout. Tras 5 intentos fallidos, bloquear la cuenta 15 minutos. Mensaje de error genérico.
2. Dashboard (según rol): total por pagar, total por cobrar, cantidad de facturas vencidas CxP y CxC, pendientes de aprobación, y lista de las 10 próximas a vencer (dentro de dias_alerta_vencimiento).
3. Usuarios (solo administrador): listar, crear, editar, asignar rol, activar/desactivar, desbloquear, restablecer contraseña manualmente.
4. Clientes y proveedores: listado con búsqueda, crear, editar, desactivar.
5. Facturas CxP / CxC: listado paginado (20 por página) con filtros (tipo, estado, tercero, rango de fechas, solo vencidas, número); formulario de registro con validaciones; detalle con datos, saldo, documentos (subir y descargar), pagos, historial de bitácora de esa factura y botones según rol y estado (enviar, aprobar, rechazar, registrar pago/cobro).
6. Reportes con exportación a CSV: (a) cartera CxC por antigüedad por cliente (corriente, 1-30, 31-60, 61-90, +90 días vencidos); (b) CxP pendientes y vencidas por proveedor, ordenadas por vencimiento.
7. Bitácora (auditor y administrador): listado filtrable por usuario, acción, entidad y fecha, con valor anterior y nuevo.

# 9. SEGURIDAD MÍNIMA (obligatoria, también para el código delegado)
- Solo consultas preparadas con PDO. Escapar toda salida con htmlspecialchars.
- password_hash/password_verify (argon2id o bcrypt). Contraseña mínima de 8 caracteres.
- session_regenerate_id al iniciar sesión; cookies HttpOnly + SameSite=Lax; expiración por inactividad (30 min).
- Token CSRF en todos los formularios POST.
- Subida de archivos: solo PDF/JPG/PNG, máx. 5 MB, validar MIME real con finfo, nombre aleatorio, guardar fuera de public/. Descarga solo mediante un controlador que verifique permiso.
- Credenciales en .env (nunca en el código); .env y storage/uploads en .gitignore.
- Errores genéricos al usuario; detalle en log del servidor.

# 10. FASES (responsable entre paréntesis)
- Fase 0: (tú) sección 0; 3.1, 3.2, 3.3; estructura, config/.env, conexión PDO, schema.sql, router, layout Bootstrap, docs/CONVENCIONES.md (máx. 40 líneas: estructura, nombres, uso de la BD, Auth::require('rol'), Audit::log, Csrf y layout). (worker-d) seed.php.
- Fase 1: (tú) autenticación, RBAC, Audit::log. (worker-a) módulo de usuarios y pantalla de bitácora, una vez exista el patrón.
- Fase 2: (tú) modelo de facturas con validaciones y detección de duplicados, subida y descarga de documentos. (worker-a) CRUD de terceros. (worker-c) vistas y filtros del listado de facturas.
- Fase 3: (tú) flujo de aprobación y registro de pagos/cobros con todas las reglas de la sección 7. (worker-c) vista del detalle de factura. Al terminar: revisión crítica (3.10).
- Fase 4: (worker-c) dashboard y reportes con CSV; tú proporcionas la consulta SQL de antigüedad de cartera y revisas el resultado.
- Fase 5: (tú) revisión de permisos por rol según la sección 5. Revisión crítica de seguridad (3.10). (worker-d) README corto (instalación en máx. 6 pasos).

# 11. FUERA DE ALCANCE (NO implementar)
Conciliación bancaria, cierre contable mensual, portal de clientes, OCR, autenticación de dos factores, notificaciones por correo, migración de datos históricos, restablecimiento de contraseña por correo, integraciones bancarias, multimoneda, catálogo de cuentas/partida doble, IA, API REST.

# 12. CRITERIOS DE ACEPTACIÓN DEL MVP
- Un auxiliar registra una factura con soporte; el sistema bloquea duplicados.
- El auxiliar no puede aprobar su propia factura; un contador o gerente distinto sí.
- No se puede aprobar ni pagar una factura sin documento de soporte.
- No se puede pagar más del saldo, ni una factura pagada o no aprobada.
- El dashboard muestra facturas vencidas y próximas a vencer.
- Cada acción relevante queda en la bitácora, y esta no puede modificarse ni borrarse.
- El auditor y el tesorero no pueden modificar datos.
- Un usuario no puede entrar a pantallas o acciones de otro rol, ni escribiendo la URL directamente.

Empieza ahora con la sección 0 y luego continúa con la primera fase incompleta.