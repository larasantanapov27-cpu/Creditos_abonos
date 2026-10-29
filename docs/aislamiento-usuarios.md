# Aislamiento por usuario

Clientes y créditos aplican un alcance Eloquent global por usuario autenticado. Créditos también exigen un cliente propio. Abonos y cuotas heredan la propiedad del crédito. Sin sesión estas consultas no devuelven registros, incluso en consola. Los identificadores de rutas usan esos mismos alcances y rechazan expedientes ajenos con 404. Las consultas SQL financieras están correlacionadas con el crédito ya autorizado y conservan todos sus movimientos históricos.

Los formularios rechazan `user_id`; los modelos lo asignan desde la sesión y los servicios comprueban al actor y vuelven a consultar dentro de la transacción. Teléfono y CURP son únicos dentro de cada cuenta. Los catálogos geográficos, tasas y tipos de pago siguen siendo compartidos; sus restricciones de uso comprueban todas las cuentas para preservar referencias. No existen endpoints de exportación ni API en este proyecto; consultas y comprobantes utilizan los modelos protegidos. Los respaldos son administrativos y contienen la base completa: no deben publicarse.

## Despliegue y datos anteriores

1. Respalda la base y prueba la restauración antes del despliegue.
2. Ejecuta `php artisan migrate`. Se reutiliza la migración de clientes existente `2026_10_28_101009` y se añade `2026_10_28_110000` para unicidad por cuenta. No se ejecutan migraciones automáticamente contra la base real.
3. Ejecuta `php artisan creditos:asignar-propietario` para listar clientes sin propietario. Este comando es administrativo local, nunca una ruta web.
4. Verifica documentalmente el propietario de cada expediente y revisa los `user_id` existentes en sus créditos y abonos. No se infieren propietarios ni se atribuyen al primer usuario. Los expedientes sin cliente propietario permanecen ocultos, pero intactos.
5. Ejecuta `php artisan creditos:asignar-propietario ID_CLIENTE ID_USUARIO --confirmar` para cada expediente revisado. Se asigna únicamente el cliente y los propietarios nulos de sus créditos/abonos, dentro de una transacción auditada. Cualquier propietario incompatible o identidad duplicada cancela todo. Los conflictos requieren conciliación supervisada; no se sobrescriben automáticamente.
6. No reviertas la unicidad sin revisar duplicados entre cuentas. Su migración rechaza deliberadamente una reversión automática insegura.

Las auditorías antiguas conservan sus actores originales. El `user_id` de abonos históricos puede representar al capturista original; la autorización y los saldos dependen siempre del crédito propietario para no perder movimientos ni alterar balances. Las asignaciones CLI quedan identificadas como tales en auditoría (el usuario indicado es el destinatario, no una sesión de operador).

## Pruebas

`php artisan test` usa SQLite en memoria según `phpunit.xml`, nunca la base de operación. Las pruebas de aislamiento verifican dos cuentas, URL manipuladas, validación de referencias, formularios, saldos, estadísticas y asignación de expedientes antiguos.
