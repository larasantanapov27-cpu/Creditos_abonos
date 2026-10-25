# Base importada: procedimiento de conservación

## SQL original sin datos

`creditos:adaptar-importacion` comprueba las ocho tablas, ausencia de registros y ausencia del campo nuevo `activo`. Después crea un respaldo con `mysqldump`. Solo si tiene éxito ejecuta un único `RENAME TABLE` con todos los pares, manteniendo los originales y sus relaciones internas bajo el prefijo `legado_FECHA_HORA_`. No ejecuta DROP, DELETE, TRUNCATE ni desactiva claves foráneas. A continuación `migrate --seed` crea el esquema corregido. El adaptador se detiene ante una importación incompleta o con datos. Repetirlo tras instalar no renombra la base nueva.

La migración de negocio comprueba antes de crear nada si ya existe `registro_clientes`; si existe exige el procedimiento seguro. Las migraciones técnicas de Laravel pueden haberse aplicado antes de esa comprobación: no afectan las tablas de negocio originales.

## SQL con registros: conversión supervisada

El SQL no conserva cantidad/periodicidad ni el vínculo municipio-estado; tampoco autor, fecha de cancelación o saldo histórico. No es seguro inventar esos datos. Esta entrega no convierte automáticamente una base poblada. Procedimiento reproducible de preparación:

1. Detén escrituras; ejecuta el respaldo y comprueba el código de salida y tamaño. Conserva además una copia externa. Restaura en una base nueva usando phpMyAdmin. Nunca sobre la base operativa.
2. Cuenta todas las tablas; exporta IDs y consulta relaciones originales en `INFORMATION_SCHEMA.KEY_COLUMN_USAGE`. Detecta huérfanos en clientes-estado/municipio, créditos-cliente/tasa/tipo y abonos-crédito/estado mediante LEFT JOIN con el padre y `WHERE padre.id IS NULL`.
3. Revisa teléfonos/CURP duplicados y `SELECT id_credito,numero_pago,COUNT(*) FROM registro_abonos GROUP BY id_credito,numero_pago HAVING COUNT(*)>1`. Detecta importes <= 0 y saldos negativos. Corrige únicamente con aprobación del encargado y conserva las equivalencias antes/después. El teléfono/código postal guardado como entero puede haber perdido ceros; deben revisarse contra documentos.
4. Prepara archivos de correspondencia: `id_municipio,id_estado`, `id_credito,cantidad_pagos,periodicidad,fecha_pago,fecha_vencimiento`, y equivalencia de `estado_abonos` con Aplicado/Cancelado. Verifica cada condición con el contrato. Si un municipio ambiguo requiere separarse, documenta cómo se reasigna cada cliente.
5. Crea otra base vacía con las migraciones nuevas. Transfiere catálogos con sus IDs, luego clientes, créditos, cuotas generadas con el servicio y abonos. Usa DECIMAL y VARCHAR apropiados. Mantén IDs originales. Autor anterior desconocido puede quedar NULL y la interfaz lo marca como registro anterior; no atribuyas movimientos históricos al importador. Para cancelaciones históricas sin autor/fecha conserva una bitácora explícita y define su tratamiento antes de cargar.
6. Las tres relaciones invertidas se eliminan únicamente en la copia original si se usa ALTER: `estados.vive`, `registro_clientes.registro_clientes_ibfk_1`, `registro_creditos.registro_creditos_ibfk_1`. En el esquema nuevo ya no existen. Los padres correctos son estados → clientes → créditos → abonos; municipio → cliente; tasa y tipo → crédito; estado_abono → abono. `estado_abonos.id_estado_abono` ahora tiene AUTO_INCREMENT.
7. Compara conteos, IDs, sumas de crédito/abonos aplicados/saldos por cliente, estados y calendario. Prueba registros y cancelaciones en una copia. Conserva un informe de excepciones y aprobación del encargado antes de cambiar `DB_DATABASE`.
8. Mantén las bases original y recuperada hasta validar; la reversión consiste en devolver `DB_DATABASE` al origen conservado. Nunca uses `migrate:fresh` sobre datos importados.

Este procedimiento es una guía de conversión con intervención sobre datos incompletos; no constituye evidencia de una migración poblada ejecutada. En el entorno recibido se verificó una importación vacía, que sí fue adaptada y respaldada.
