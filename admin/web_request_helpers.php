<?php

function asegurarTablasSolicitudesWeb($conn)
{
    $crear_tablas = array(
        "CREATE TABLE IF NOT EXISTS mensajes_contacto (
            id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
            id_paciente INT NULL,
            nombre VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            telefono VARCHAR(20),
            asunto VARCHAR(200),
            mensaje TEXT NOT NULL,
            estado ENUM('leido', 'no_leido') DEFAULT 'no_leido',
            fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_mensaje_paciente (id_paciente),
            INDEX idx_estado (estado),
            INDEX idx_fecha (fecha_envio),
            CONSTRAINT fk_mensaje_paciente FOREIGN KEY (id_paciente)
                REFERENCES pacientes(id_paciente) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS reservas_rapidas (
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
            hora VARCHAR(10) NOT NULL,
            como_nos_conocio VARCHAR(50),
            mensaje TEXT,
            estado ENUM('pendiente', 'confirmada', 'cancelada') DEFAULT 'pendiente',
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_reserva_web_paciente (id_paciente),
            UNIQUE KEY uq_reserva_web_sistema (id_reserva_sistema),
            INDEX idx_estado (estado),
            INDEX idx_fecha (fecha),
            CONSTRAINT fk_reserva_web_paciente FOREIGN KEY (id_paciente)
                REFERENCES pacientes(id_paciente) ON DELETE SET NULL,
            CONSTRAINT fk_reserva_web_sistema FOREIGN KEY (id_reserva_sistema)
                REFERENCES reservas(id_reserva) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    foreach ($crear_tablas as $sql) {
        if (!$conn->query($sql)) {
            throw new RuntimeException('No se pudieron preparar las tablas de solicitudes web: ' . $conn->error);
        }
    }

    $columnas = array(
        'mensajes_contacto' => array(
            'id_paciente' => 'INT NULL'
        ),
        'reservas_rapidas' => array(
            'id_paciente' => 'INT NULL',
            'id_reserva_sistema' => 'INT NULL',
            'cedula' => 'VARCHAR(20) NULL',
            'hora' => 'VARCHAR(10) NULL'
        )
    );

    foreach ($columnas as $tabla => $definiciones) {
        foreach ($definiciones as $columna => $definicion) {
            $comprobar = $conn->query("SHOW COLUMNS FROM `$tabla` LIKE '$columna'");
            if (!$comprobar) {
                throw new RuntimeException('No se pudo comprobar el esquema de solicitudes web: ' . $conn->error);
            }
            if ($comprobar->num_rows === 0 && !$conn->query("ALTER TABLE `$tabla` ADD COLUMN `$columna` $definicion")) {
                throw new RuntimeException('No se pudo actualizar la tabla ' . $tabla . ': ' . $conn->error);
            }
        }
    }

    $indices = array(
        'mensajes_contacto' => array('idx_mensaje_paciente' => 'INDEX idx_mensaje_paciente (id_paciente)'),
        'reservas_rapidas' => array(
            'idx_reserva_web_paciente' => 'INDEX idx_reserva_web_paciente (id_paciente)',
            'uq_reserva_web_sistema' => 'UNIQUE KEY uq_reserva_web_sistema (id_reserva_sistema)'
        )
    );
    foreach ($indices as $tabla => $definiciones) {
        foreach ($definiciones as $nombre => $definicion) {
            $comprobar = $conn->query("SHOW INDEX FROM `$tabla` WHERE Key_name = '$nombre'");
            if (!$comprobar) {
                throw new RuntimeException('No se pudieron comprobar los índices de solicitudes web: ' . $conn->error);
            }
            if ($comprobar->num_rows === 0 && !$conn->query("ALTER TABLE `$tabla` ADD $definicion")) {
                throw new RuntimeException('No se pudo crear el índice ' . $nombre . ': ' . $conn->error);
            }
        }
    }

    $claves = array(
        'mensajes_contacto' => array(
            'fk_mensaje_paciente' => 'FOREIGN KEY fk_mensaje_paciente (id_paciente) REFERENCES pacientes (id_paciente) ON DELETE SET NULL'
        ),
        'reservas_rapidas' => array(
            'fk_reserva_web_paciente' => 'FOREIGN KEY fk_reserva_web_paciente (id_paciente) REFERENCES pacientes (id_paciente) ON DELETE SET NULL',
            'fk_reserva_web_sistema' => 'FOREIGN KEY fk_reserva_web_sistema (id_reserva_sistema) REFERENCES reservas (id_reserva) ON DELETE SET NULL'
        )
    );
    foreach ($claves as $tabla => $definiciones) {
        foreach ($definiciones as $nombre => $definicion) {
            $nombre_sql = $conn->real_escape_string($nombre);
            $comprobar = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = '$tabla' AND CONSTRAINT_NAME = '$nombre_sql'");
            if (!$comprobar) {
                throw new RuntimeException('No se pudieron comprobar las relaciones de solicitudes web: ' . $conn->error);
            }
            if ($comprobar->num_rows === 0 && !$conn->query("ALTER TABLE `$tabla` ADD CONSTRAINT $nombre $definicion")) {
                throw new RuntimeException('No se pudo crear la relación ' . $nombre . ': ' . $conn->error);
            }
        }
    }
}

function obtenerServicioReservaWeb($conn, $codigo)
{
    $terminos = array(
        'audiometria' => array('%audiometr%', '%audiolog%'),
        'limpieza' => array('%limpieza%'),
        'reparacion' => array('%reparaci%', '%calibraci%'),
        'calibracion' => array('%calibraci%', '%reparaci%'),
        'moldes' => array('%molde%'),
        'consulta' => array('%consulta%')
    );
    if (!isset($terminos[$codigo])) {
        return 0;
    }

    foreach ($terminos[$codigo] as $termino) {
        $stmt = $conn->prepare("SELECT id_servicio FROM servicios WHERE estado IN ('activo', '1') AND LOWER(nombre_servicio) LIKE ? ORDER BY id_servicio LIMIT 1");
        if (!$stmt) {
            throw new RuntimeException('No se pudo preparar la búsqueda del servicio: ' . $conn->error);
        }
        $stmt->bind_param('s', $termino);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('No se pudo buscar el servicio: ' . $error);
        }
        $resultado = $stmt->get_result();
        $servicio = $resultado->fetch_assoc();
        $stmt->close();
        if ($servicio) {
            return (int)$servicio['id_servicio'];
        }
    }

    return 0;
}

function obtenerOCrearPacienteReservaWeb($conn, $solicitud)
{
    $id_paciente_existente = isset($solicitud['id_paciente']) ? (int)$solicitud['id_paciente'] : 0;
    if ($id_paciente_existente > 0) {
        $stmt = $conn->prepare("SELECT id_paciente FROM pacientes WHERE id_paciente = ? LIMIT 1");
        if (!$stmt) {
            throw new RuntimeException('No se pudo validar la ficha del paciente: ' . $conn->error);
        }
        $stmt->bind_param('i', $id_paciente_existente);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('No se pudo validar la ficha del paciente: ' . $error);
        }
        $paciente = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($paciente) {
            return (int)$paciente['id_paciente'];
        }
    }

    $cedula = isset($solicitud['cedula']) ? trim((string)$solicitud['cedula']) : '';
    $nombre_completo = isset($solicitud['nombre']) ? trim((string)$solicitud['nombre']) : '';
    if ($cedula === '') {
        throw new RuntimeException('Falta la cédula del paciente. Completa ese dato antes de guardarlo.');
    }

    $stmt = $conn->prepare("SELECT id_paciente FROM pacientes WHERE cedula = ? LIMIT 1 FOR UPDATE");
    if (!$stmt) {
        throw new RuntimeException('No se pudo buscar al paciente por cédula: ' . $conn->error);
    }
    $stmt->bind_param('s', $cedula);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('No se pudo buscar al paciente por cédula: ' . $error);
    }
    $paciente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($paciente) {
        $id_paciente = (int)$paciente['id_paciente'];
        $telefono = isset($solicitud['telefono']) ? trim((string)$solicitud['telefono']) : '';
        $email = isset($solicitud['email']) ? trim((string)$solicitud['email']) : '';
        $stmt = $conn->prepare("UPDATE pacientes
            SET telefono = CASE WHEN telefono IS NULL OR telefono = '' THEN NULLIF(?, '') ELSE telefono END,
                email = CASE WHEN email IS NULL OR email = '' THEN NULLIF(?, '') ELSE email END
            WHERE id_paciente = ?");
        if (!$stmt) {
            throw new RuntimeException('No se pudieron completar los datos de contacto del paciente: ' . $conn->error);
        }
        $stmt->bind_param('ssi', $telefono, $email, $id_paciente);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('No se pudieron completar los datos de contacto del paciente: ' . $error);
        }
        $stmt->close();
        return $id_paciente;
    }

    if ($nombre_completo === '') {
        throw new RuntimeException('Completa el nombre y apellido del paciente para crear su ficha.');
    }

    if (isset($solicitud['nombre_paciente'], $solicitud['apellido_paciente'])) {
        $nombre = trim((string)$solicitud['nombre_paciente']);
        $apellido = trim((string)$solicitud['apellido_paciente']);
    } else {
        $partes_nombre = preg_split('/\s+/', $nombre_completo, 2);
        $nombre = isset($partes_nombre[0]) ? $partes_nombre[0] : '';
        $apellido = isset($partes_nombre[1]) ? $partes_nombre[1] : '';
    }
    if ($nombre === '' || $apellido === '') {
        throw new RuntimeException('Escribe nombre y apellido para crear la ficha del paciente.');
    }

    $telefono = isset($solicitud['telefono']) ? trim((string)$solicitud['telefono']) : '';
    $email = isset($solicitud['email']) ? trim((string)$solicitud['email']) : '';
    $genero = 'Otro';
    $stmt = $conn->prepare("INSERT INTO pacientes (cedula, nombre, apellido, telefono, email, genero) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar el registro del paciente: ' . $conn->error);
    }
    $stmt->bind_param('ssssss', $cedula, $nombre, $apellido, $telefono, $email, $genero);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('No se pudo registrar el paciente: ' . $error);
    }
    $id_paciente = (int)$stmt->insert_id;
    $stmt->close();
    return $id_paciente;
}
