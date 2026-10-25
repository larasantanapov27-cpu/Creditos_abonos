# Verificaciones de la entrega

Fecha: 18 de octubre de 2026, zona de operación America/Mexico_City.

## Entorno y separación de datos

Windows, XAMPP PHP 8.2.12, Laravel 12, MariaDB 10.4.32 y Edge 155 instalado en el equipo. Bootstrap 5.3.8 de public/bootstrap, sin cambios. No se descargaron recursos de diseño ni navegadores. Los módulos se prueban también con SQLite en memoria.

- Base de trabajo: `creditos_abonos`; las ocho tablas importadas estaban vacías. Respaldo previo `creditos_20261018_120801_822546.sql`; tablas conservadas como `legado_20261018_120802_*`. Esquema nuevo migrado y catálogos mínimos sembrados. Sin usuarios ni datos de negocio de demostración en esa base.
- Base de navegador: `creditos_abonos_verificacion_20261018`, con datos opcionales de DemoSeeder y registros del flujo de QA. Nunca sustituye la base de trabajo.
- La evidencia de rendimiento se tomó con cinco clientes, cinco créditos y cinco abonos antes de las tres operaciones medidas de alta/pago/cancelación. Después quedaron seis créditos y seis abonos, con movimientos cancelados conservados. El volumen es pequeño y no prueba la carga real esperada.

## Pruebas automáticas

`php artisan test --compact`: **22 pruebas aprobadas, 124 aserciones, 13.34 s** en la ejecución final de lógica y búsqueda. Se ejecutó fuera de la restricción del sandbox que impedía la comprobación `is_readable` de PHPUnit; no se alteró vendor para evitarla. La base de pruebas fue SQLite en memoria.

Cubren: registro y hash, login/logout, límite de intentos, usuario/correo únicos, rutas protegidas, alta y validación geográfica de cliente, desactivación/reactivación, prohibición de nuevo crédito inactivo y conservación de deuda cobrable; calendarios semanal/quincenal/mensual, finales de mes, suma exacta y vencimiento coherente; edición de crédito sin abonos y bloqueo financiero con movimientos; pagos parciales/exactos, rechazo de cero/negativos/sobrepago/precisión extra, liquidación, cancelación, idempotencia y consecutivo; vencimiento con reloj controlado, fichas, filtros/historiales, búsqueda por nombre completo, catálogos protegidos y recursos locales. Una prueba adicional del home comprueba que los próximos pagos no incluyan cuotas ya cubiertas.

Los casos de duplicados verifican lógica e índice; no se presentó como prueba de estrés concurrente de MariaDB.

## Navegador sin acceso a orígenes externos

`scripts/qa-browser.cjs` ejecutó registro real con CSRF, mostrar/ocultar contraseña, login, cerrar alerta, menú de cuenta, modal de catálogo, alta de cliente, vista previa de tres mensualidades, alta de crédito confirmada, abono de $40 sobre $100, comprobante con saldo $60, cancelación y saldo reabierto, logout y bloqueo de home. Probó menú móvil y pantallas a 1440×1000 y 390×844. Se corrigió una etiqueta accesible de tabla que inicialmente aumentaba el ancho de página en móvil; la repetición no tuvo desbordamientos.

Resultado de la repetición completa: **cero solicitudes externas y cero errores JavaScript**. Contexto de navegador nuevo y todas las solicitudes fuera de http://127.0.0.1:8001 bloqueadas; servidor y MariaDB continuaron disponibles. Esta es una prueba de aislamiento de red por origen, no una afirmación de que se desconectó físicamente la computadora.

`scripts/qa-final.cjs` revisó nuevamente login, registro, home, clientes, créditos, abonos y sus formularios en ambas resoluciones, ausencia de ancho excedente y estilo de impresión del comprobante. Se inspeccionaron capturas y se corrigieron espaciado del título de login móvil y contraste de marca.

Evidencia local fuera de public:

- `storage/app/private/qa/informe-navegador.json`: recorrido completo y tiempos con recursos/networkidle.
- `storage/app/private/qa/mediciones-http.json`: muestras HTTP sin captura ni espera artificial.
- `storage/app/private/qa/*-final-1440.png` y `*-final-390.png`: capturas finales.
- `storage/app/private/qa/comprobante-impresion.png`: revisión del medio de impresión.

No faltan bootstrap.min.css ni bootstrap.bundle.min.js. Las dos cabeceras indican 5.3.8. SHA-256 de los archivos usados:

```text
CSS d85327d99c7a3ee1f9b5d0500d1370acea3ad2db39c163c2f51f232baedbdede
JS  e4fd49181388c48ec5040bd3fe66f57c29c8e67fcd8502b3354b96ec7ab47cc7
```

Se buscaron `http(s)://`, `@vite`, fuentes remotas y CDN en vistas y recursos propios sin encontrar dependencias. Los SVG están incluidos localmente y Bootstrap usa sus imágenes embebidas. La cabecera CSP permite únicamente recursos propios y datos de imagen embebidos.

## Mediciones

Tiempos HTTP secuenciales en el servidor de desarrollo local con datos anteriores; incluyen procesamiento y respuesta. No son una prueba de carga. Cada GET tiene tres muestras; los POST corresponden a operaciones efectivamente guardadas en la base de verificación. HTTP 302 indica redirección al detalle/comprobante tras guardar; el script comprobó el destino.

| Operación | Tiempos (ms) | Resultado |
|---|---|---|
| GET /home | 591, 895, 650 | 200 |
| GET /clientes | 1821, 463, 427 | 200 |
| GET /creditos | 551, 1442, 604 | 200 |
| GET /abonos | 520, 464, 552 | 200 |
| GET /consultas | 645, 514, 583 | 200 |
| POST /creditos | 643 | 302 |
| POST /abonos | 620 | 302 |
| POST /abonos/6/cancelar | 513 | 302 |

En el recorrido completo de navegador, que incluía carga de CSS/JS y espera de `networkidle`, los rangos fueron **1,245–3,575 ms**. Dos cargas móviles superaron tres segundos: formulario de cliente **3,063 ms** y formulario de crédito **3,575 ms**. Por eso RNF03 permanece parcial: no se ocultan esos resultados ni se garantiza respuesta <3 s con otro volumen/dispositivo. Las mediciones HTTP posteriores fueron de **427–1,821 ms** para consultas y de **513–643 ms** para las tres escrituras. Se requiere medir con el volumen objetivo, Apache en configuración definitiva y el equipo de operación.

## Respaldo y restauración realmente ejecutados

1. `creditos:respaldar` generó respaldo previo antes de adaptar la base original vacía.
2. Respaldo del esquema instalado: `creditos_20261018_121611_051345.sql`. Se restauró en `creditos_restauracion_20261018_1216`: **coincidieron conteos y SHA-256 de todas las tablas**, incluidas las tablas de legado.
3. Respaldo poblado de verificación: `creditos_20261018_121814_932297.sql`. Restaurado en `creditos_restauracion_datos_20261018`: **coincidieron todas las huellas**, con cinco clientes, cinco créditos y cinco abonos, incluidos cancelados, cuotas, usuarios y auditoría.
4. Informes: `storage/app/private/respaldos/verificacion_creditos_restauracion_20261018_1216.json` y `verificacion_creditos_restauracion_datos_20261018.json`. Bases destino conservadas para inspección; la base de trabajo no se sobrescribió.
5. `php artisan schedule:list` mostró respaldo diario a las **23:00**. La tarea de Windows que dispara `schedule:run` no fue registrada; README contiene los parámetros exactos. El horario programado en Laravel por sí solo no ejecuta tareas si Windows no dispara el scheduler.

## Apache y verificaciones adicionales

- `http://localhost/creditos_abonos/public/login`: HTTP **200**.
- `http://localhost/creditos_abonos/.env`: HTTP **403**.
- `http://localhost/creditos_abonos/storage/logs/laravel.log`: HTTP **403**.
- `.htaccess` en raíz protege los directorios del proyecto; se recomienda VirtualHost public como DocumentRoot.
- `php artisan migrate:status`: cinco migraciones aplicadas; `view:cache` compiló las vistas; `node --check public/js/app.js` pasó.

## Pendientes reales

- Registrar y comprobar la tarea de respaldos de Windows y definir retención/copia a otro dispositivo.
- Validación con encargado, aceptación de usabilidad y evaluación en teléfono físico.
- Medición con el volumen esperado, carga simultánea y operación prolongada; RNF03 y disponibilidad no se garantizan por la elección de framework.
- Para una base importada **con datos**, completar los mapeos y la conversión supervisada descrita en importacion.md. El adaptador automático solo actúa sobre las ocho tablas vacías recibidas.
- Las actas y aprobaciones de fases académicas del Modelo en Cascada corresponden al equipo/curso; no se inventaron evidencias de despliegue definitivo o mantenimiento futuro.
