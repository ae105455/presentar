-- ================================================================
-- SCRIPT SQL PARA BASE DE DATOS AUREAMED - XAMPP
-- ================================================================
-- Base de datos para Centro Auditivo AureaMed
-- Creado: 2026-06-18
-- ================================================================

-- ================================================================
-- 1. CREAR BASE DE DATOS
-- ================================================================
CREATE DATABASE IF NOT EXISTS aureamed;
USE aureamed;

-- ================================================================
-- 2. TABLA: USUARIOS
-- ================================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'recepcion', 'audiologo') DEFAULT 'recepcion',
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME,
    INDEX idx_usuario (usuario),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 3. TABLA: PACIENTES
-- ================================================================
CREATE TABLE IF NOT EXISTS pacientes (
    id_paciente INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion TEXT,
    fecha_nac DATE,
    genero ENUM('M', 'F', 'Otro') DEFAULT 'M',
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cedula (cedula),
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLAS: HISTORIAL DE AUDIOMETRÍAS Y SUS IMÁGENES
-- ================================================================
CREATE TABLE IF NOT EXISTS audiometrias (
    id_audiometria INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    fecha_audiometria DATE NOT NULL,
    edad SMALLINT UNSIGNED NOT NULL,
    audifono_actual VARCHAR(180) NOT NULL,
    audifono_recomendado VARCHAR(180) NOT NULL,
    responsable VARCHAR(150) NOT NULL,
    observaciones TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audiometria_paciente FOREIGN KEY (id_paciente)
        REFERENCES pacientes(id_paciente) ON DELETE CASCADE,
    INDEX idx_audiometria_paciente (id_paciente),
    INDEX idx_audiometria_fecha (fecha_audiometria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audiometria_imagenes (
    id_imagen INT AUTO_INCREMENT PRIMARY KEY,
    id_audiometria INT NOT NULL,
    archivo VARCHAR(40) NOT NULL,
    tipo_mime VARCHAR(30) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_imagen_audiometria FOREIGN KEY (id_audiometria)
        REFERENCES audiometrias(id_audiometria) ON DELETE CASCADE,
    INDEX idx_imagen_audiometria (id_audiometria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 4. TABLA: SERVICIOS
-- ================================================================
CREATE TABLE IF NOT EXISTS servicios (
    id_servicio INT AUTO_INCREMENT PRIMARY KEY,
    nombre_servicio VARCHAR(100) NOT NULL,
    descripcion TEXT,
    duracion_minutos INT DEFAULT 30,
    precio DECIMAL(10, 2) NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre_servicio),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 5. TABLA: RESERVAS
-- ================================================================
CREATE TABLE IF NOT EXISTS reservas (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_servicio INT NOT NULL,
    id_usuario INT NOT NULL,
    fecha_reserva DATE NOT NULL,
    hora_reserva VARCHAR(10) NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'cancelada', 'completada') DEFAULT 'pendiente',
    estado_atencion ENUM('pendiente', 'atendida', 'no_asistio') NOT NULL DEFAULT 'pendiente',
    observaciones TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reserva_paciente FOREIGN KEY (id_paciente) REFERENCES pacientes(id_paciente) ON DELETE CASCADE,
    CONSTRAINT fk_reserva_servicio FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio),
    CONSTRAINT fk_reserva_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    INDEX idx_fecha (fecha_reserva),
    INDEX idx_estado (estado),
    UNIQUE KEY unique_reserva (id_paciente, id_servicio, fecha_reserva, hora_reserva)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 6. TABLA: EMPRESA
-- ================================================================
CREATE TABLE IF NOT EXISTS empresa (
    id_empresa INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL DEFAULT 'Centro Auditivo AureaMed',
    email VARCHAR(100),
    telefono VARCHAR(20),
    direccion TEXT,
    logo VARCHAR(255),
    horario_atencion VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 7. TABLA: PAGOS
-- ================================================================
CREATE TABLE IF NOT EXISTS pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    id_reserva INT NULL,
    id_usuario INT NOT NULL,
    monto DECIMAL(10, 2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo VARCHAR(100) NOT NULL DEFAULT 'Efectivo',
    nota TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    numero_factura VARCHAR(30),
    token_comprobante CHAR(64) UNIQUE,
    CONSTRAINT fk_pagos_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresa(id_empresa) ON DELETE RESTRICT,
    CONSTRAINT fk_pagos_reserva FOREIGN KEY (id_reserva)
        REFERENCES reservas(id_reserva) ON DELETE SET NULL,
    CONSTRAINT fk_pagos_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_pagos_empresa (id_empresa),
    INDEX idx_pagos_reserva (id_reserva),
    INDEX idx_pagos_usuario (id_usuario),
    INDEX idx_pagos_fecha (fecha_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 8. TABLA: MENSAJES_CONTACTO
-- ================================================================
CREATE TABLE IF NOT EXISTS mensajes_contacto (
    id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    asunto VARCHAR(200),
    mensaje TEXT NOT NULL,
    estado ENUM('leido', 'no_leido') DEFAULT 'no_leido',
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mensaje_paciente FOREIGN KEY (id_paciente)
        REFERENCES pacientes(id_paciente) ON DELETE SET NULL,
    INDEX idx_mensaje_paciente (id_paciente),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 9. TABLA: RESERVAS_RAPIDAS
-- ================================================================
CREATE TABLE IF NOT EXISTS reservas_rapidas (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NULL,
    id_reserva_sistema INT NULL,
    cedula VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    edad VARCHAR(20),
    servicio VARCHAR(50) NOT NULL,
    fecha DATE NOT NULL,
    hora VARCHAR(10) NULL,
    como_nos_conocio VARCHAR(50),
    mensaje TEXT,
    estado ENUM('pendiente', 'confirmada', 'cancelada') DEFAULT 'pendiente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reserva_web_paciente FOREIGN KEY (id_paciente)
        REFERENCES pacientes(id_paciente) ON DELETE SET NULL,
    CONSTRAINT fk_reserva_web_sistema FOREIGN KEY (id_reserva_sistema)
        REFERENCES reservas(id_reserva) ON DELETE SET NULL,
    INDEX idx_reserva_web_paciente (id_paciente),
    UNIQUE KEY uq_reserva_web_sistema (id_reserva_sistema),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 10. DATOS INICIALES - USUARIO ADMIN
-- ================================================================
-- Usuario: admin | Contraseña: admin (MD5: 21232f297a57a5a743894a0e4a801fc3)
INSERT IGNORE INTO usuarios (id_usuario, usuario, nombre, email, password, rol, estado) 
VALUES (1, 'admin', 'Administrador', 'admin@aureamed.com', '21232f297a57a5a743894a0e4a801fc3', 'admin', 'activo');

-- ================================================================
-- 11. DATOS INICIALES - EMPRESA
-- ================================================================
INSERT IGNORE INTO empresa (id_empresa, nombre, email, telefono, direccion, horario_atencion) 
VALUES (
    1, 
    'Centro Auditivo AureaMed', 
    'info@aureamed.com', 
    '+591-7-1234567', 
    'Av. 16 de Julio, Edificio Central, La Paz - Bolivia', 
    'Lunes a Viernes: 08:00 - 18:00 | Sábados: 09:00 - 13:00'
);

-- ================================================================
-- 12. DATOS INICIALES - SERVICIOS
-- ================================================================
INSERT IGNORE INTO servicios (id_servicio, nombre_servicio, descripcion, duracion_minutos, precio, estado) 
VALUES 
    (1, 'Prueba Audiológica', 'Evaluación completa de audición', 60, 150.00, 'activo'),
    (2, 'Audífonos - Consulta', 'Consulta para adaptación de audífonos', 45, 100.00, 'activo'),
    (3, 'Limpieza de Oído', 'Limpieza profesional del conducto auditivo', 30, 50.00, 'activo'),
    (4, 'Reparación de Audífonos', 'Servicio técnico de audífonos', 60, 80.00, 'activo'),
    (5, 'Moldeado de Audífono', 'Moldeado personalizado de audífono', 30, 120.00, 'activo');

-- ================================================================
-- 13. CORRECCIÓN DE FECHA DE NACIMIENTO
-- Corrige el año 1199 ingresado por error para evitar una edad inválida.
UPDATE pacientes
SET fecha_nac = '1999-12-12'
WHERE id_paciente = 5
  AND fecha_nac = '1199-12-12';

-- ================================================================
-- FINS DEL SCRIPT SQL
-- ================================================================
