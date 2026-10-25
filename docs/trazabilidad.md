# Matriz de trazabilidad

Fuente: requisitos RF01–RF25 y RNF01–RNF20 del PDF entregado, páginas 7–8. Estado de implementación de esta entrega; “Cumplido” no equivale a aceptación formal ni a garantía futura. Las evidencias están en pruebas.md y en los archivos locales de QA.

## Requisitos funcionales

| ID | Requisito | Implementación | Verificación | Estado |
|---|---|---|---|---|
| RF01 | Registro de clientes | ClienteController + ClienteRequest | Alta HTTP y navegador | Cumplido |
| RF02 | Modificar clientes | update + formulario reutilizable | Validación del formulario; vista de edición renderizada | Cumplido |
| RF03 | Consultar clientes | show + ficha y estado de cuenta | Prueba de vistas y revisión visual | Cumplido |
| RF04 | Eliminar/desactivar/reactivar | destroy/toggle; bloqueo del cliente | Prueba de desactivación, reactivación y restricción de eliminación | Cumplido |
| RF05 | Buscar por nombre/teléfono/ID | scopeBuscar; filtros y paginación | Búsqueda por nombre completo y pruebas de listado | Cumplido |
| RF06 | Crear crédito para cliente | CreditoService::guardar | Alta HTTP/navegador; bloqueo de cliente inactivo | Cumplido |
| RF07 | Monto total | DECIMAL(14,2), Form Request, centavos | Pruebas de montos y redondeo | Cumplido |
| RF08 | Inicio y vencimiento | Fechas persistentes, calendario validado | Rechazo de vencimiento inconsistente | Cumplido |
| RF09 | Cantidad y periodicidad | cantidad_pagos, periodicidad y cuotas | Semanal, 15 días, mensual y finales de mes | Cumplido |
| RF10 | Registrar abonos | CreditoService::abonar | Alta HTTP y navegador sobre MariaDB | Cumplido |
| RF11 | Fecha/hora de abono | now() de servidor; zona Ciudad de México | Comprobante y registro persistido | Cumplido |
| RF12 | Importe de abono | DECIMAL y centavos | Abonos parciales/exactos e inválidos | Cumplido |
| RF13 | Saldo automático | Credito::saldo y servicio | Saldo 1000 → 666.67 → 0 | Cumplido |
| RF14 | Actualizar saldo al abonar | Cálculo desde movimientos aplicados | Regresión de liquidación/cancelación | Cumplido |
| RF15 | Total abonado por cliente | Detalle cliente suma créditos | Ficha de cliente, pruebas de vistas | Cumplido |
| RF16 | Créditos pendientes | Filtro derivado de saldo/fecha | Filtro en listado y home | Cumplido |
| RF17 | Créditos liquidados | Filtro saldo cero | Prueba de filtro y liquidación | Cumplido |
| RF18 | Historial de abonos | Detalle de crédito + comprobantes | Prueba de vistas; navegador aplica/cancela | Cumplido |
| RF19 | Historial de créditos | Ficha de cliente | Tabla con créditos y enlace al detalle | Cumplido |
| RF20 | Buscar créditos por cliente | whereHas(cliente.buscar), filtro id_cliente | Prueba con nombre completo | Cumplido |
| RF21 | Identificar liquidación | Estado derivado de saldo cero | Pago exacto y rechazo de abonos posteriores | Cumplido |
| RF22 | Identificar vencimiento | Fecha anterior a hoy con saldo positivo | Prueba con reloj controlado y distinción de cuotas | Cumplido |
| RF23 | Modificar crédito | Servicio protege condiciones con abonos | Edición sin movimientos y restricción financiera | Cumplido |
| RF24 | Detalle completo | Saldo, total, abonos, cuotas y estado | Vistas HTTP, escritorio/móvil | Cumplido |
| RF25 | Comprobante | abonos/show, impresión local y cancelación visible | Captura de impresión y comprobante navegador | Cumplido |

## Requisitos no funcionales

| ID | Requisito | Implementación | Verificación / límite | Estado |
|---|---|---|---|---|
| RNF01 | Interfaz sencilla | Layout, navegación etiquetada, componentes Bootstrap | Revisión visual; falta aceptación con usuario final | Parcial |
| RNF02 | Aprendizaje básico | Estados vacíos, etiquetas y ayudas de formularios | Flujo probado; falta sesión de usabilidad con encargado | Parcial |
| RNF03 | Máximo 3 segundos | Índices, paginación y consultas resumidas | Mediciones registradas; dos cargas móviles del recorrido superaron 3 s | Parcial |
| RNF04 | Protección de información | auth, CSRF, asignación validada, public exclusivo | Pruebas de rutas; cabeceras locales y protección Apache | Cumplido |
| RNF05 | Autenticación | Username/password, sesión y límite de intentos | Prueba registro/login/logout y rutas protegidas | Cumplido |
| RNF06 | Contraseñas seguras | Cast hashed y validación | Hash::check sobre contraseña persistida | Cumplido |
| RNF07 | Integridad | FK RESTRICT, transacciones, bloqueo y claves únicas | Migraciones MariaDB, pruebas de saldo/tokens; falta estrés concurrente | Parcial |
| RNF08 | Validación antes de guardar | Form Requests y reglas del servicio | Rechazo de relación geográfica y condiciones inválidas | Cumplido |
| RNF09 | No negativos/inválidos | DECIMAL validado, centavos y límite del saldo | Casos 0, -10, sobrepago y precisión excesiva | Cumplido |
| RNF10 | Disponibilidad en horario de negocio | XAMPP local y manual de operación | Requiere equipo/servicios encendidos; no hay monitoreo operacional probado | Pendiente |
| RNF11 | Respaldos periódicos | Comando, log, horario diario configurable | Respaldo ejecutado; falta registrar tarea de Windows | Parcial |
| RNF12 | Recuperación | Verificador en base nueva con SHA-256 por tabla | Restauración de base vacía y de base con datos coincidente | Cumplido |
| RNF13 | Funcionamiento estable | Transacciones, idempotencia y regresión | Suite y recorrido completos; no prueba prolongada/carga | Parcial |
| RNF14 | Compatibilidad | PHP 8.2, Laravel 12, MariaDB 10.4, Bootstrap 5.3.8 | Windows/Edge y resoluciones 1440 y 390; falta dispositivo móvil físico | Parcial |
| RNF15 | Mantenibilidad | MVC, servicio, Form Requests y componentes | Código dividido y documentación de diseño | Cumplido |
| RNF16 | Extensibilidad | Reglas centrales y pruebas de regresión | Puntos de extensión documentados; intereses sin fórmula inventada | Cumplido |
| RNF17 | Claridad de información | Estados, MXN, ayudas e historiales | Revisión visual de pantallas principales | Cumplido |
| RNF18 | Formatos consistentes | MXN 2 decimales, fechas d/m/Y y zona única | Comprobante y calendario verificados | Cumplido |
| RNF19 | Errores claros | Validación en español y páginas de error locales | Errores de autenticación, montos y condiciones probados | Cumplido |
| RNF20 | Confirmaciones | Modal compartido antes de crédito, abono, cancelación/eliminación | Navegador confirma crédito/abono/cancelación; formularios destructivos reutilizan modal | Cumplido |

## Límites adicionales de la entrega

La base importada recibida estaba vacía y fue conservada antes de instalar el esquema nuevo. Para importaciones con datos incompletos, la conversión supervisada se documenta en importacion.md; no hay evidencia de una conversión poblada ejecutada. Las pruebas de navegador bloquean internet y mantienen disponible el origen local, sin desconectar físicamente el equipo. El cronograma académico del PDF se conserva como referencia, sin inventar actas de aceptación o fases futuras completadas.
