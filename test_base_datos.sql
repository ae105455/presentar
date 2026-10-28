-- ================================================================
-- SCRIPT DE PRUEBA Y VERIFICACIÓN - BASE DE DATOS AUREAMED
-- ================================================================
-- Ejecuta este script en phpMyAdmin después de importar aureamed_database.sql
-- para verificar que todo está configurado correctamente
-- ================================================================

USE aureamed;

-- ================================================================
-- 1. VERIFICAR TABLAS CREADAS
-- ================================================================
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    DATA_LENGTH,
    INDEX_LENGTH
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'aureamed'
ORDER BY TABLE_NAME;

-- El script crea 9 tablas, incluidas audiometrias y audiometria_imagenes.

-- ================================================================
-- 2. VERIFICAR USUARIO ADMIN
-- ================================================================
SELECT 
    id_usuario,
    usuario,
    nombre,
    email,
    rol,
    estado,
    fecha_registro
FROM usuarios
WHERE usuario = 'admin';

-- Resultado esperado: 1 registro con usuario='admin', rol='admin'

-- ================================================================
-- 3. VERIFICAR EMPRESA
-- ================================================================
SELECT 
    id_empresa,
    nombre,
    email,
    telefono,
    direccion,
    horario_atencion
FROM empresa
LIMIT 1;

-- Resultado esperado: Datos de Centro Auditivo AureaMed

-- ================================================================
-- 4. VERIFICAR SERVICIOS
-- ================================================================
SELECT 
    id_servicio,
    nombre_servicio,
    descripcion,
    duracion_minutos,
    precio,
    estado
FROM servicios
ORDER BY id_servicio;

-- Resultado esperado: 5 servicios disponibles

-- ================================================================
-- 5. VERIFICAR ESTRUCTURA DE USUARIOS
-- ================================================================
DESCRIBE usuarios;

-- Columnas esperadas:
-- id_usuario | INT | AUTO_INCREMENT PRIMARY KEY
-- usuario | VARCHAR(50) | UNIQUE NOT NULL
-- nombre | VARCHAR(100) | NOT NULL
-- email | VARCHAR(100) | 
-- password | VARCHAR(255) | NOT NULL
-- rol | ENUM('admin','recepcion','audiologo') |
-- estado | ENUM('activo','inactivo') |
-- fecha_registro | TIMESTAMP |
-- ultimo_acceso | DATETIME |

-- ================================================================
-- 6. VERIFICAR ESTRUCTURA DE PACIENTES
-- ================================================================
DESCRIBE pacientes;

-- Columnas esperadas:
-- id_paciente | INT | AUTO_INCREMENT PRIMARY KEY
-- cedula | VARCHAR(20) | UNIQUE NOT NULL
-- nombre | VARCHAR(100) | NOT NULL
-- apellido | VARCHAR(100) | NOT NULL
-- telefono | VARCHAR(20) |
-- email | VARCHAR(100) |
-- direccion | TEXT |
-- fecha_nac | DATE |
-- genero | ENUM |
-- estado | ENUM |
-- fecha_registro | TIMESTAMP |

-- ================================================================
-- 7. VERIFICAR ESTRUCTURA DE SERVICIOS
-- ================================================================
DESCRIBE servicios;

-- Columnas esperadas:
-- id_servicio | INT | AUTO_INCREMENT PRIMARY KEY
-- nombre_servicio | VARCHAR(100) | NOT NULL
-- descripcion | TEXT |
-- duracion_minutos | INT |
-- precio | DECIMAL(10,2) | NOT NULL
-- estado | ENUM |
-- fecha_registro | TIMESTAMP |

-- ================================================================
-- 8. VERIFICAR ESTRUCTURA DE RESERVAS
-- ================================================================
DESCRIBE reservas;

-- Columnas esperadas:
-- id_reserva | INT | AUTO_INCREMENT PRIMARY KEY
-- id_paciente | INT | FOREIGN KEY
-- id_servicio | INT | FOREIGN KEY
-- id_usuario | INT | FOREIGN KEY
-- fecha_reserva | DATE | NOT NULL
-- hora_reserva | VARCHAR(10) | NOT NULL
-- estado | ENUM |
-- observaciones | TEXT |
-- fecha_registro | TIMESTAMP |

-- ================================================================
-- 9. VERIFICAR FOREIGN KEYS
-- ================================================================
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'aureamed' 
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME;

-- Resultado esperado: Foreign Keys en tabla 'reservas'

-- ================================================================
-- 10. VERIFICAR ÍNDICES
-- ================================================================
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'aureamed'
  AND INDEX_NAME != 'PRIMARY'
ORDER BY TABLE_NAME, INDEX_NAME;

-- Resultado esperado: Índices en columnas importantes

-- ================================================================
-- 11. PRUEBA DE INTEGRIDAD - INSERTAR DATOS DE PRUEBA
-- ================================================================
-- DESCOMENTA LAS LÍNEAS SIGUIENTES PARA HACER PRUEBAS

/*
-- Insertar paciente de prueba
INSERT INTO pacientes 
(cedula, nombre, apellido, telefono, email, direccion, fecha_nac, genero, estado)
VALUES 
('12345678', 'Juan', 'Pérez', '555-1234', 'juan@example.com', 'Calle Principal 123', '1985-05-15', 'M', 'activo');

-- Insertar usuario de prueba (recepción)
INSERT INTO usuarios 
(usuario, nombre, email, password, rol, estado)
VALUES 
('recepcion1', 'María García', 'maria@aureamed.com', MD5('pass123'), 'recepcion', 'activo');

-- Crear reserva de prueba (descomentar después de insertar paciente)
-- INSERT INTO reservas 
-- (id_paciente, id_servicio, id_usuario, fecha_reserva, hora_reserva, estado)
-- VALUES 
-- (1, 1, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00', 'pendiente');

-- Ver los datos insertados
-- SELECT * FROM pacientes;
-- SELECT * FROM usuarios WHERE usuario = 'recepcion1';
-- SELECT * FROM reservas;

-- ELIMINAR DATOS DE PRUEBA (descomentar después de verificar)
-- DELETE FROM reservas WHERE id_reserva > 0;
-- DELETE FROM pacientes WHERE cedula = '12345678';
-- DELETE FROM usuarios WHERE usuario = 'recepcion1';

*/

-- ================================================================
-- 12. CONTAR REGISTROS TOTALES
-- ================================================================
SELECT 'usuarios' as tabla, COUNT(*) as total FROM usuarios
UNION ALL
SELECT 'pacientes', COUNT(*) FROM pacientes
UNION ALL
SELECT 'servicios', COUNT(*) FROM servicios
UNION ALL
SELECT 'reservas', COUNT(*) FROM reservas
UNION ALL
SELECT 'empresa', COUNT(*) FROM empresa
UNION ALL
SELECT 'mensajes_contacto', COUNT(*) FROM mensajes_contacto
UNION ALL
SELECT 'reservas_rapidas', COUNT(*) FROM reservas_rapidas;

-- Resultado esperado al importar:
-- usuarios: 1 (admin)
-- pacientes: 0
-- servicios: 5
-- reservas: 0
-- empresa: 1
-- mensajes_contacto: 0
-- reservas_rapidas: 0

-- ================================================================
-- 13. VERIFICAR CODIFICACIÓN
-- ================================================================
SELECT 
    SCHEMA_NAME,
    DEFAULT_CHARACTER_SET_NAME,
    DEFAULT_COLLATION_NAME
FROM INFORMATION_SCHEMA.SCHEMATA
WHERE SCHEMA_NAME = 'aureamed';

-- Resultado esperado: utf8mb4 | utf8mb4_unicode_ci

-- ================================================================
-- 14. LISTAR TODAS LAS RESTRICCIONES
-- ================================================================
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    CONSTRAINT_TYPE
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = 'aureamed'
ORDER BY TABLE_NAME;

-- ================================================================
-- FIN DE PRUEBAS
-- ================================================================
-- Si todos los queries anteriores ejecutan sin errores,
-- tu base de datos está correctamente instalada y lista para usar.
-- ================================================================
