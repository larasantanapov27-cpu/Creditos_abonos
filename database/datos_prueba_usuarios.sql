-- Ejecutar en una copia de pruebas de creditos_abonos, desde una misma conexion.
-- Solo agrega registros nuevos. Cada ejecucion crea un lote diferente.
-- Si ocurre un error, detener la ejecucion y ejecutar ROLLBACK; no continuar al COMMIT.
-- Requiere las columnas user_id y los catalogos del archivo SQL proporcionado.

SET NAMES utf8mb4;
SET @lote = REPLACE(UUID(), '-', '');
SET @ahora = NOW();
SET @hoy = CURDATE();
SET @mensual_inicio = DATE_SUB(DATE_SUB(@hoy, INTERVAL (DAYOFMONTH(@hoy) - 1) DAY), INTERVAL 5 MONTH);
SET @tasa = (SELECT id_tasa_interes FROM tasa_intereses WHERE tasa = 0 ORDER BY id_tasa_interes LIMIT 1);
SET @tipo = (SELECT id_tipo_pago FROM tipo_pagos WHERE tipo = 'Efectivo' ORDER BY id_tipo_pago LIMIT 1);
SET @aplicado = (SELECT id_estado_abono FROM estado_abonos WHERE estado_abono = 'Aplicado' LIMIT 1);
SET @cancelado = (SELECT id_estado_abono FROM estado_abonos WHERE estado_abono = 'Cancelado' LIMIT 1);
SET @municipio = (SELECT m.id_municipio FROM municipios m JOIN estados e ON e.id_estado = m.id_estado ORDER BY m.id_municipio LIMIT 1);
SET @estado = (SELECT id_estado FROM municipios WHERE id_municipio = @municipio);
SET @hash = '$2y$10$NIAyFo4rd9N2Hg4Ly6MmieAKn7FYuptFlF2o1.KUMA/50qXzKFJ2W';

CREATE TEMPORARY TABLE prueba_numeros (
    numero INT PRIMARY KEY,
    nombre VARCHAR(25),
    paterno VARCHAR(25),
    materno VARCHAR(25)
);
INSERT INTO prueba_numeros VALUES
(1, 'Ana', 'Lopez', 'Perez'), (2, 'Luis', 'Lopez', 'Perez'),
(3, 'Maria', 'Lopez', 'Perez'), (4, 'Carlos', 'Lopez', 'Perez'),
(5, 'Elena', 'Lopez', 'Perez'), (6, 'Jorge', 'Lopez', 'Perez'),
(7, 'Sofia', 'Lopez', 'Perez'), (8, 'Pedro', 'Lopez', 'Perez'),
(9, 'Laura', 'Lopez', 'Perez'), (10, 'Diego', 'Lopez', 'Perez');

START TRANSACTION;

INSERT INTO users (name, username, email, password, created_at, updated_at)
VALUES ('Encargado de prueba A', CONCAT('prueba_a_', @lote), CONCAT('a.', @lote, '@example.test'), @hash, @ahora, @ahora);
SET @usuario_a = LAST_INSERT_ID();

INSERT INTO users (name, username, email, password, created_at, updated_at)
VALUES ('Encargado de prueba B', CONCAT('prueba_b_', @lote), CONCAT('b.', @lote, '@example.test'), @hash, @ahora, @ahora);
SET @usuario_b = LAST_INSERT_ID();

INSERT INTO registro_clientes
(fecha_registro_cliente, nombre, apellido_paterno, apellido_materno, telefono,
 correo_electronico, id_estado, id_municipio, codigo_postal, direccion, curp, activo, user_id)
SELECT DATE_SUB(@hoy, INTERVAL 200 DAY), n.nombre, n.paterno, n.materno,
       CONCAT('55', LPAD(u.user_id * 10 + n.numero, 8, '0')),
       CONCAT(n.numero, '.', u.cuenta, '.', LEFT(@lote, 12), '@example.test'),
       @estado, @municipio, '51200', CONCAT('Calle Prueba ', n.numero),
       CONCAT(IF(n.numero IN (1, 3, 5, 7, 9), 'LOPA', 'LOPL'),
              DATE_FORMAT(DATE_ADD('1950-01-01', INTERVAL (u.user_id * 10 + n.numero) DAY), '%y%m%d'),
              IF(n.numero IN (1, 3, 5, 7, 9), 'M', 'H'), 'MCPRN01'),
       n.numero <= 8, u.user_id
FROM prueba_numeros n
CROSS JOIN (SELECT @usuario_a AS user_id, 'a' AS cuenta UNION ALL SELECT @usuario_b, 'b') u;

CREATE TEMPORARY TABLE prueba_clientes AS
SELECT c.id_cliente, c.user_id, n.numero
FROM registro_clientes c
JOIN prueba_numeros n ON c.direccion = CONCAT('Calle Prueba ', n.numero)
WHERE c.user_id IN (@usuario_a, @usuario_b);

INSERT INTO registro_creditos
(id_cliente, motivo_credito, monto_credito, fecha_aprobacion, fecha_vencimiento,
 id_tasa_interes, id_tipo_pago, fecha_pago, cantidad_pagos, periodicidad, user_id)
SELECT c.id_cliente, CONCAT('Prueba ', LEFT(@lote, 8), ' semanal'),
       1000.00 + c.numero * 200.00, DATE_SUB(@hoy, INTERVAL 3 DAY),
       DATE_ADD(@hoy, INTERVAL 28 DAY), @tasa, @tipo,
       DATE_ADD(@hoy, INTERVAL 7 DAY), 4, 'semanal', c.user_id
FROM prueba_clientes c WHERE c.numero <= 8;

INSERT INTO registro_creditos
(id_cliente, motivo_credito, monto_credito, fecha_aprobacion, fecha_vencimiento,
 id_tasa_interes, id_tipo_pago, fecha_pago, cantidad_pagos, periodicidad, user_id)
SELECT c.id_cliente, CONCAT('Prueba ', LEFT(@lote, 8), ' historico'),
       2000.00 + c.numero * 400.00,
       IF(MOD(c.numero, 2) = 0, DATE_SUB(@hoy, INTERVAL 110 DAY), DATE_SUB(@mensual_inicio, INTERVAL 7 DAY)),
       IF(MOD(c.numero, 2) = 0, DATE_SUB(@hoy, INTERVAL 55 DAY), DATE_ADD(@mensual_inicio, INTERVAL 3 MONTH)),
       @tasa, @tipo,
       IF(MOD(c.numero, 2) = 0, DATE_SUB(@hoy, INTERVAL 100 DAY), @mensual_inicio),
       4, IF(MOD(c.numero, 2) = 0, 'quincenal', 'mensual'), c.user_id
FROM prueba_clientes c WHERE c.numero <= 8;

CREATE TEMPORARY TABLE prueba_creditos AS
SELECT cr.id_credito, cr.user_id, cr.monto_credito, cr.fecha_pago,
       cr.periodicidad, c.numero
FROM registro_creditos cr
JOIN prueba_clientes c ON c.id_cliente = cr.id_cliente
WHERE cr.user_id IN (@usuario_a, @usuario_b);

INSERT INTO cuotas (id_credito, numero, fecha, importe)
SELECT cr.id_credito, n.numero,
       CASE cr.periodicidad
           WHEN 'semanal' THEN DATE_ADD(cr.fecha_pago, INTERVAL ((n.numero - 1) * 7) DAY)
           WHEN 'quincenal' THEN DATE_ADD(cr.fecha_pago, INTERVAL ((n.numero - 1) * 15) DAY)
           ELSE DATE_ADD(cr.fecha_pago, INTERVAL (n.numero - 1) MONTH)
       END,
       cr.monto_credito / 4
FROM prueba_creditos cr CROSS JOIN prueba_numeros n WHERE n.numero <= 4;

INSERT INTO registro_abonos
(id_credito, fecha_registro_abono, monto_abono, numero_pago, id_estado_abono,
 user_id, token, saldo_resultante)
SELECT cr.id_credito,
       IF(cr.periodicidad = 'semanal', @ahora, TIMESTAMP(cr.fecha_pago, '12:00:00')),
       cr.monto_credito / 4, 1, @aplicado, cr.user_id, UUID(), cr.monto_credito * 3 / 4
FROM prueba_creditos cr;

-- Liquidar los historicos quincenales; los mensuales quedan vencidos.
INSERT INTO registro_abonos
(id_credito, fecha_registro_abono, monto_abono, numero_pago, id_estado_abono,
 user_id, token, saldo_resultante)
SELECT cr.id_credito, TIMESTAMP(DATE_ADD(cr.fecha_pago, INTERVAL 45 DAY), '12:00:00'),
       cr.monto_credito * 3 / 4, 2, @aplicado, cr.user_id, UUID(), 0.00
FROM prueba_creditos cr WHERE cr.periodicidad = 'quincenal';

-- El comprobante conserva el saldo al aplicar el pago; la cancelacion lo reabre.
INSERT INTO registro_abonos
(id_credito, fecha_registro_abono, monto_abono, numero_pago, id_estado_abono,
 user_id, token, saldo_resultante, motivo_cancelacion, cancelado_at, cancelado_por)
SELECT cr.id_credito, @ahora, cr.monto_credito / 4, 2, @cancelado,
       cr.user_id, UUID(), cr.monto_credito / 2,
       'Cancelacion ficticia para verificar reapertura de saldo', @ahora, cr.user_id
FROM prueba_creditos cr WHERE cr.periodicidad = 'semanal' AND cr.numero <= 2;

INSERT INTO auditoria (user_id, accion, entidad, registro_id, datos, created_at)
SELECT c.user_id, 'registrar', 'cliente', c.id_cliente,
       JSON_OBJECT('origen', 'sql_prueba', 'lote', @lote), @ahora FROM prueba_clientes c;

INSERT INTO auditoria (user_id, accion, entidad, registro_id, datos, created_at)
SELECT cr.user_id, 'guardar', 'credito', cr.id_credito,
       JSON_OBJECT('origen', 'sql_prueba', 'lote', @lote), @ahora FROM prueba_creditos cr;

INSERT INTO auditoria (user_id, accion, entidad, registro_id, datos, created_at)
SELECT a.user_id, 'registrar', 'abono', a.id_abono,
       JSON_OBJECT('origen', 'sql_prueba', 'lote', @lote, 'monto', a.monto_abono), a.fecha_registro_abono
FROM registro_abonos a JOIN prueba_creditos cr ON cr.id_credito = a.id_credito;

INSERT INTO auditoria (user_id, accion, entidad, registro_id, datos, created_at)
SELECT a.cancelado_por, 'cancelar', 'abono', a.id_abono,
       JSON_OBJECT('origen', 'sql_prueba', 'lote', @lote, 'motivo', a.motivo_cancelacion), a.cancelado_at
FROM registro_abonos a JOIN prueba_creditos cr ON cr.id_credito = a.id_credito
WHERE a.cancelado_at IS NOT NULL;

COMMIT;

SELECT id AS usuario_id, name, email, 'Prueba2026!' AS clave_solo_pruebas
FROM users WHERE id IN (@usuario_a, @usuario_b);

SELECT cr.user_id, COUNT(*) AS creditos, SUM(cr.monto_credito) AS prestado,
       SUM(COALESCE(a.aplicado, 0)) AS abonado,
       SUM(cr.monto_credito - COALESCE(a.aplicado, 0)) AS saldo
FROM prueba_creditos cr
LEFT JOIN (SELECT id_credito, SUM(monto_abono) AS aplicado FROM registro_abonos
           WHERE cancelado_at IS NULL GROUP BY id_credito) a ON a.id_credito = cr.id_credito
GROUP BY cr.user_id;

DROP TEMPORARY TABLE prueba_creditos;
DROP TEMPORARY TABLE prueba_clientes;
DROP TEMPORARY TABLE prueba_numeros;
