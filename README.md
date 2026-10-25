# Créditos y Abonos

Aplicación académica local en español para el encargado de un negocio. Conserva Laravel 12, PHP 8.2 y **Bootstrap 5.3.8 proporcionado por el usuario**, sin descargarlo ni modificarlo. Los clientes son registros; el registro público crea cuentas de encargado y no hay portal ni roles de cliente.

## Ejecutar en este equipo

1. Enciende **Apache y MySQL en XAMPP**.
2. Abre **http://localhost/creditos_abonos/public/**. El servidor web debe publicar exclusivamente `public`, nunca exponer `.env`, `storage` o el resto del proyecto. Para uso continuo configura un VirtualHost de Apache con `DocumentRoot C:/xampp/htdocs/creditos_abonos/public` y `AllowOverride All`. La URL de subcarpeta es útil para desarrollo local; no publiques la carpeta raíz del proyecto en una red.
3. Pulsa **Crear una cuenta**, registra al encargado e inicia sesión con el nombre de usuario. No se instala una contraseña por defecto en la base de trabajo.
4. En **Catálogos**, agrega estados y municipios vinculados; los tipos de pago, la tasa 0 y los estados internos se incluyen al instalar.
5. Registra un cliente; luego un crédito, calcula su calendario y confirma; registra los abonos desde su detalle.

También puedes ejecutar `C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000` y abrir http://127.0.0.1:8000. No se necesita Node, npm ni un servidor Vite para usar la aplicación. Si cambias la URL permanente actualiza `APP_URL` en `.env` y ejecuta `php artisan optimize:clear`.

## Instalación vacía

Las dependencias PHP están presentes en esta entrega. En otra instalación, `composer install` puede requerir internet **solo para la instalación inicial**; conserva `vendor` para ejecución sin conexión. Requiere PDO MySQL, mbstring, openssl, XML, fileinfo y bcmath de PHP/XAMPP. Para pruebas se requiere PDO SQLite.

```powershell
Copy-Item .env.example .env
php artisan key:generate
# Crea una base vacía creditos_abonos en MariaDB y configura DB_* en .env.
php artisan migrate --seed
php artisan serve
```

`.env.example` no contiene claves ni contraseñas. Configura la contraseña de tu usuario MariaDB cuando exista. `APP_DEBUG=false` evita mostrar errores SQL. No regeneres `APP_KEY` en una instalación ya utilizada porque invalida sesiones cifradas.

## Base previamente importada

No ejecutes `migrate:fresh`. Antes de migrar lee [docs/importacion.md](docs/importacion.md).

El comando `php artisan creditos:adaptar-importacion` hace respaldo y renombra conjuntamente las ocho tablas originales **solo si todas están vacías**. Conserva las originales como `legado_FECHA_HORA_*`; después ejecuta `php artisan migrate --seed`. Si hay registros se detiene sin renombrar: el documento explica una conversión supervisada sobre una copia. No adivina condiciones de créditos ni relaciones geográficas ausentes.

En este equipo se comprobó que las ocho tablas estaban vacías. Las originales se conservaron y el esquema nuevo se instaló con migraciones. Los datos de prueba están en una base separada.

## Bootstrap y funcionamiento sin internet

Archivos detectados y utilizados sin cambios:

- `public/bootstrap/css/bootstrap.min.css` — Bootstrap 5.3.8.
- `public/bootstrap/js/bootstrap.bundle.min.js` — Bootstrap 5.3.8 con Popper.
- `public/css/app.css` — estilos propios cargados después de Bootstrap.
- `public/js/app.js` — confirmaciones, calendario, filtros geográficos y vistas previas.
- `public/favicon.svg` — icono local. Los demás iconos son SVG incluidos en Blade.

El layout `resources/views/layouts/app.blade.php` los carga con `asset()` en login, registro y todas las vistas del sistema. Se usan fuentes del equipo (`Segoe UI`, Arial). No hay CDN, fuentes remotas ni imágenes externas. Los archivos `.map` ya provistos siguen en su ubicación. No faltan archivos de Bootstrap. Los archivos de Vite del esqueleto no se cargan en ninguna vista.

El diseño adapta la referencia con fondo gris, menú oscuro etiquetado, tarjetas redondeadas y azul suave. La tarjeta decorativa de acceso no representa estadísticas del negocio. El home usa consultas reales y muestra ceros al no haber registros.

Prueba manual sin conexión: desconecta el acceso a internet manteniendo Apache y MariaDB encendidos, abre una ventana privada, registra/inicia sesión, prueba menú móvil, desplegable de cuenta, cerrar alertas, modales, clientes, vista previa de cuotas y abonos. No selecciones el modo Offline de DevTools: también bloquea localhost. La verificación automatizada bloquea todo origen ajeno al servidor local y usa un contexto sin caché; sus resultados están en [docs/pruebas.md](docs/pruebas.md).

## Reglas del negocio

- Dinero en `DECIMAL(14,2)`; cálculos del servicio en centavos enteros. La tasa es una referencia: el total capturado es el total pactado y no se añaden intereses ni recargos. Para otra fórmula, primero definir el contrato y extender `CreditoService` y sus pruebas.
- Periodicidad semanal = 7 días; quincenal = 15 días. Mensual = mismo día del mes original o último día disponible (31/01, 28/02, 31/03). La última cuota recibe los centavos del redondeo. Vencimiento = última cuota. Ninguna cuota puede ser menor a $0.01; máximo 600 cuotas.
- Saldo = total pactado menos abonos no cancelados. Liquidado = cero; vencido = saldo positivo y vencimiento anterior a hoy; pendiente = saldo positivo y plazo vigente. Zona horaria `America/Mexico_City`. La cobertura de cuotas sigue orden de antigüedad y puede haber cuotas atrasadas sin que venza el plazo completo.
- Clientes inactivos no reciben nuevos créditos; sí pueden abonar a deudas existentes. Solo se eliminan clientes sin créditos. Con movimientos, un crédito solo permite editar el motivo.
- Los abonos se guardan con transacción y bloqueo `FOR UPDATE` del crédito, token UUID idempotente, consecutivo único por crédito, autor, fecha y saldo al aplicarse. Una cancelación conserva el registro, autor y motivo y reabre el saldo. El comprobante conserva el saldo histórico; no equivale al saldo actual.
- Los catálogos usados no se editan ni se eliminan; crea otro elemento. Aplicado/Cancelado están protegidos. CSRF, hash de contraseñas, regeneración de sesiones, límite de intentos y rutas internas autenticadas.

## Respaldo y recuperación

```powershell
php artisan creditos:respaldar
# Usa la ruta exacta impresa por el comando:
php artisan creditos:verificar-respaldo "C:\ruta\creditos_FECHA.sql" --database=creditos_restauracion_prueba_nueva
```

Los archivos fechados quedan en `storage/app/private/respaldos`, fuera de `public`. Los resultados se registran en `storage/logs/laravel.log`. El verificador **rechaza bases existentes**, restaura en una base nueva separada y compara conteos y SHA-256 de los registros contra la base origen; genera un informe JSON y conserva la base de prueba. Para comparar sin diferencias, úsalo inmediatamente tras el respaldo y sin escrituras simultáneas. Un respaldo antiguo puede restaurar correctamente y diferir del origen actual; el informe permite distinguirlo.

Programación propuesta: diaria a las 23:00 de Ciudad de México, configurable con `BACKUP_TIME`. En Programador de tareas de Windows crea una tarea al iniciar el equipo con disparador repetido cada minuto: programa `C:\xampp\php\php.exe`, argumentos `artisan schedule:run`, iniciar en `C:\xampp\htdocs\creditos_abonos`. La tarea debe usar una cuenta con permisos para ejecutar `mysqldump`, escribir en storage y acceder a MariaDB. No se registró una tarea del sistema automáticamente. Comprueba periódicamente los registros y copia los respaldos a otro dispositivo para protegerte de una falla del disco.

Para restaurar tras una falla, conserva la base dañada y restaura el SQL en **otra base vacía** usando Importar de phpMyAdmin o el cliente mysql. Verifica conteos, saldos e inicio de sesión; luego cambia `DB_DATABASE` en `.env` a la base recuperada y ejecuta `php artisan optimize:clear`. No importes el SQL sobre la base de trabajo para hacer pruebas. Lee [docs/pruebas.md](docs/pruebas.md) para la restauración realmente ejecutada.

## Datos opcionales y pruebas

`php artisan db:seed --class=DemoSeeder` agrega ejemplos identificables sin repetirlos. Úsalo solo en una base de demostración: crea la cuenta `demo` / `DemoLocal2026!` y tres clientes con créditos y abonos. El seeder normal no crea usuarios ni datos del negocio.

```powershell
php artisan test
```

Las pruebas usan SQLite **en memoria**, sin tocar MariaDB de trabajo. La comprobación de concurrencia real de varios usuarios requiere MariaDB; las pruebas de tokens y consecutivos no equivalen a una prueba de carga simultánea. `scripts/qa-browser.cjs` es una verificación opcional del entorno de desarrollo con Playwright y Edge ya instalados; apunta por defecto al servidor de prueba 8001 y escribe datos de prueba. No es una dependencia de ejecución y no debe apuntarse a la base de trabajo.

## Documentación y alcance

- [Fases del Modelo en Cascada y diseño](docs/cascada.md).
- [Matriz RF01–RF25 / RNF01–RNF20](docs/trazabilidad.md).
- [Verificaciones, mediciones y pendientes](docs/pruebas.md).
- [Conversión segura de una base importada](docs/importacion.md).

La disponibilidad en horario de negocio depende de Apache, MariaDB, el equipo y su operación. Las mediciones con pocos registros no garantizan rendimiento con otro volumen. La validación de facilidad de uso con usuarios reales y la programación efectiva de respaldos quedan explícitamente pendientes.
#   C r e d i t o s _ a b o n o s  
 