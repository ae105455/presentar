<?php

function asegurarEstadoAtencionReservas($conn)
{
    $comprobar = $conn->query("SHOW COLUMNS FROM reservas LIKE 'estado_atencion'");
    if (!$comprobar) {
        throw new RuntimeException('No se pudo comprobar el estado de atención de las citas: ' . $conn->error);
    }

    if (
        $comprobar->num_rows === 0
        && !$conn->query("ALTER TABLE reservas ADD COLUMN estado_atencion
            ENUM('pendiente', 'atendida', 'no_asistio') NOT NULL DEFAULT 'pendiente' AFTER estado")
    ) {
        throw new RuntimeException('No se pudo agregar el estado de atención a las citas: ' . $conn->error);
    }
}
