<?php

function asegurarTablaPagos($conn)
{
    $crear_empresa = "CREATE TABLE IF NOT EXISTS empresa (
        id_empresa INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL DEFAULT 'Centro Auditivo AureaMed',
        email VARCHAR(100),
        telefono VARCHAR(20),
        direccion TEXT,
        logo VARCHAR(255),
        horario_atencion VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!$conn->query($crear_empresa)) {
        throw new RuntimeException('No se pudo preparar la tabla de empresas.');
    }

    $crear_pagos = "CREATE TABLE IF NOT EXISTS pagos (
        id_pago INT AUTO_INCREMENT PRIMARY KEY,
        id_empresa INT NOT NULL,
        id_reserva INT NULL,
        id_usuario INT NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        fecha_pago DATE NOT NULL,
        metodo VARCHAR(100) NOT NULL DEFAULT 'Efectivo',
        nota TEXT NULL,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        numero_factura VARCHAR(30) NULL,
        token_comprobante CHAR(64) NULL,
        INDEX idx_pagos_empresa (id_empresa),
        INDEX idx_pagos_reserva (id_reserva),
        INDEX idx_pagos_usuario (id_usuario),
        UNIQUE KEY uq_pagos_token_comprobante (token_comprobante),
        CONSTRAINT fk_pagos_empresa FOREIGN KEY (id_empresa)
            REFERENCES empresa(id_empresa) ON DELETE RESTRICT,
        CONSTRAINT fk_pagos_reserva FOREIGN KEY (id_reserva)
            REFERENCES reservas(id_reserva) ON DELETE SET NULL,
        CONSTRAINT fk_pagos_usuario FOREIGN KEY (id_usuario)
            REFERENCES usuarios(id_usuario) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!$conn->query($crear_pagos)) {
        throw new RuntimeException('No se pudo preparar la tabla de pagos: ' . $conn->error);
    }

    $empresa_resultado = $conn->query("SELECT id_empresa FROM empresa ORDER BY id_empresa LIMIT 1");
    if (!$empresa_resultado) {
        throw new RuntimeException('No se pudo buscar la empresa para relacionar los pagos: ' . $conn->error);
    }
    $empresa = $empresa_resultado->fetch_assoc();
    if (!$empresa) {
        throw new RuntimeException('Registra primero el Centro Auditivo en Administración para poder relacionar los pagos.');
    }
    $id_empresa = (int)$empresa['id_empresa'];

    $columnas = array(
        'id_empresa' => 'INT NULL AFTER id_pago',
        'id_reserva' => 'INT NULL',
        'numero_factura' => 'VARCHAR(30) NULL',
        'nota' => 'TEXT NULL'
    );
    foreach ($columnas as $columna => $definicion) {
        $comprobar = $conn->query("SHOW COLUMNS FROM pagos LIKE '$columna'");
        if (!$comprobar) {
            throw new RuntimeException('No se pudo comprobar la columna ' . $columna . ' en pagos: ' . $conn->error);
        }
        if ($comprobar->num_rows === 0 && !$conn->query("ALTER TABLE pagos ADD COLUMN `$columna` $definicion")) {
            throw new RuntimeException('No se pudo agregar la columna ' . $columna . ' a pagos: ' . $conn->error);
        }
    }

    $stmt_empresa = $conn->prepare('UPDATE pagos SET id_empresa = ? WHERE id_empresa IS NULL');
    if (!$stmt_empresa) {
        throw new RuntimeException('No se pudo preparar la relación de pagos con la empresa: ' . $conn->error);
    }
    $stmt_empresa->bind_param('i', $id_empresa);
    if (!$stmt_empresa->execute()) {
        $error = $stmt_empresa->error;
        $stmt_empresa->close();
        throw new RuntimeException('No se pudieron relacionar los pagos existentes con la empresa: ' . $error);
    }
    $stmt_empresa->close();

    if (!$conn->query("ALTER TABLE pagos MODIFY id_reserva INT NULL")) {
        throw new RuntimeException('No se pudo preparar la relación opcional entre pagos y reservas: ' . $conn->error);
    }
    if (!$conn->query("UPDATE pagos p LEFT JOIN reservas r ON r.id_reserva = p.id_reserva
        SET p.id_reserva = NULL WHERE p.id_reserva IS NOT NULL AND r.id_reserva IS NULL")) {
        throw new RuntimeException('No se pudieron separar pagos que apuntaban a reservas inexistentes: ' . $conn->error);
    }
    if (!$conn->query("ALTER TABLE pagos MODIFY id_empresa INT NOT NULL")) {
        throw new RuntimeException('No se pudo exigir la relación de los pagos con la empresa: ' . $conn->error);
    }

    $indices = array(
        'idx_pagos_empresa' => 'id_empresa',
        'idx_pagos_reserva' => 'id_reserva',
        'idx_pagos_usuario' => 'id_usuario'
    );
    foreach ($indices as $nombre => $columna) {
        $comprobar = $conn->query("SHOW INDEX FROM pagos WHERE Key_name = '$nombre'");
        if (!$comprobar) {
            throw new RuntimeException('No se pudo comprobar el índice ' . $nombre . ': ' . $conn->error);
        }
        if ($comprobar->num_rows === 0 && !$conn->query("ALTER TABLE pagos ADD INDEX `$nombre` (`$columna`)")) {
            throw new RuntimeException('No se pudo crear el índice ' . $nombre . ': ' . $conn->error);
        }
    }

    $relaciones = array(
        'fk_pagos_empresa' => array('id_empresa', 'empresa', 'id_empresa', 'RESTRICT'),
        'fk_pagos_reserva' => array('id_reserva', 'reservas', 'id_reserva', 'SET NULL'),
        'fk_pagos_usuario' => array('id_usuario', 'usuarios', 'id_usuario', 'RESTRICT')
    );
    foreach ($relaciones as $nombre => $relacion) {
        list($columna, $tabla_referenciada, $columna_referenciada, $al_borrar) = $relacion;
        $comprobar = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = 'pagos'
                AND COLUMN_NAME = '$columna'
                AND REFERENCED_TABLE_NAME = '$tabla_referenciada'
                AND REFERENCED_COLUMN_NAME = '$columna_referenciada'
            LIMIT 1");
        if (!$comprobar) {
            throw new RuntimeException('No se pudo comprobar la relación ' . $nombre . ': ' . $conn->error);
        }
        if ($comprobar->num_rows === 0) {
            $sql = "ALTER TABLE pagos ADD CONSTRAINT `$nombre`
                FOREIGN KEY (`$columna`) REFERENCES `$tabla_referenciada` (`$columna_referenciada`)
                ON DELETE $al_borrar";
            if (!$conn->query($sql)) {
                throw new RuntimeException('No se pudo crear la relación ' . $nombre . ': ' . $conn->error);
            }
        }
    }

    return $id_empresa;
}
