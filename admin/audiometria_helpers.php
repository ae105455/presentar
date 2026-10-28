<?php
function asegurarTablasAudiometria(mysqli $conn): void
{
    $queries = [
        "CREATE TABLE IF NOT EXISTS audiometrias (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS audiometria_imagenes (
            id_imagen INT AUTO_INCREMENT PRIMARY KEY,
            id_audiometria INT NOT NULL,
            archivo VARCHAR(40) NOT NULL,
            tipo_mime VARCHAR(30) NOT NULL,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_imagen_audiometria FOREIGN KEY (id_audiometria)
                REFERENCES audiometrias(id_audiometria) ON DELETE CASCADE,
            INDEX idx_imagen_audiometria (id_audiometria)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($queries as $query) {
        if (!$conn->query($query)) {
            die("Error al preparar el historial de audiometrías: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
        }
    }

    $columnas_requeridas = [
        'audiometrias' => [
            'edad' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
            'audifono_actual' => 'VARCHAR(180) NOT NULL DEFAULT ""',
            'audifono_recomendado' => 'VARCHAR(180) NOT NULL DEFAULT ""',
            'responsable' => 'VARCHAR(150) NOT NULL DEFAULT ""',
            'observaciones' => 'TEXT NULL',
        ],
        'audiometria_imagenes' => [
            'archivo' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'tipo_mime' => 'VARCHAR(30) NOT NULL DEFAULT "image/jpeg"',
        ],
    ];

    foreach ($columnas_requeridas as $tabla => $columnas) {
        $resultado = $conn->query("SHOW COLUMNS FROM `{$tabla}`");
        if (!$resultado) {
            continue;
        }

        $existentes = [];
        while ($columna = $resultado->fetch_assoc()) {
            $existentes[] = $columna['Field'];
        }

        foreach ($columnas as $nombre => $tipo) {
            if (!in_array($nombre, $existentes, true)) {
                $alter = "ALTER TABLE `{$tabla}` ADD COLUMN `{$nombre}` {$tipo}";
                if (!$conn->query($alter)) {
                    die("Error al migrar la estructura de {$tabla}: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
                }
            }

        }
    }
}

function edadPaciente(?string $fechaNacimiento, ?string $fechaReferencia = null): string
{
    if (!$fechaNacimiento) {
        return 'No registrada';
    }

    $nacimiento = new DateTimeImmutable($fechaNacimiento);
    $referencia = $fechaReferencia
        ? new DateTimeImmutable($fechaReferencia)
        : new DateTimeImmutable('today');

    if ($nacimiento > $referencia) {
        return 'No registrada';
    }

    return $nacimiento->diff($referencia)->y . ' años';
}
