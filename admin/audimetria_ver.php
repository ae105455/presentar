<?php
require_once __DIR__ . '/db_connection.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$id_audiometria = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_audiometria) {
    header("Location: pacientes.php");
    exit();
}

$conn = aureamed_db_connect();
if ($conn->connect_error) {
    die("Error de conexión: " . htmlspecialchars($conn->connect_error, ENT_QUOTES, 'UTF-8'));
}
require_once __DIR__ . '/audiometria_helpers.php';
asegurarTablasAudiometria($conn);

$stmt = $conn->prepare("SELECT a.id_audiometria, a.id_paciente, a.fecha_audiometria,
    p.nombre, p.apellido, p.cedula, p.telefono, p.email, p.direccion, p.fecha_nac
    FROM audiometrias a
    JOIN pacientes p ON p.id_paciente = a.id_paciente
    WHERE a.id_audiometria = ?");
$stmt->bind_param("i", $id_audiometria);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$registro) {
    http_response_code(404);
    exit('No se encontró la ficha de audiometría.');
}
$edad = edadPaciente($registro['fecha_nac'], $registro['fecha_audiometria']);

$stmt = $conn->prepare("SELECT id_imagen FROM audiometria_imagenes WHERE id_audiometria = ? ORDER BY id_imagen LIMIT 1");
$stmt->bind_param("i", $id_audiometria);
$stmt->execute();
$imagenes = $stmt->get_result();
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Ficha de audiometría</title>
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <style>
        body { background:#f3f5f8; color:#263445; padding:28px 12px; font-family:Arial,sans-serif; }
        .report { max-width:900px; margin:auto; padding:34px; background:#fff; border:1px solid #dfe4ea; }
        .report-header { display:flex; align-items:center; gap:24px; padding-bottom:20px; border-bottom:3px solid #8b2c2c; }
        .report-header img { max-width:190px; max-height:85px; object-fit:contain; }
        .report-header h1 { color:#5c1e1e; font-size:1.65rem; margin:0; }
        .data-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin:22px 0; }
        .data-item { padding:12px; background:#f7f8fa; border-radius:6px; }
        .data-item strong { display:block; color:#5c1e1e; margin-bottom:4px; }
        .exam-images { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        .exam-images img { width:100%; max-height:520px; object-fit:contain; border:1px solid #dfe4ea; padding:5px; }
        .actions { max-width:900px; margin:0 auto 15px; }
        @media (max-width:600px) { .report { padding:18px; } .report-header img { max-width:110px; } .report-header h1 { font-size:1.2rem; } .data-grid { grid-template-columns:1fr; } .exam-images { grid-template-columns:1fr; } }
        @media print { body { background:#fff; padding:0; } .report { border:0; max-width:none; padding:0; } .actions { display:none; } .exam-images img { max-height:430px; break-inside:avoid; } }
    </style>
</head>
<body>
    <div class="actions">
        <a class="btn btn-outline-secondary" href="audimetria_historial.php?id_paciente=<?php echo (int)$registro['id_paciente']; ?>"><i class="fas fa-arrow-left"></i> Volver al historial</a>
        <button class="btn btn-primary" type="button" onclick="window.print()"><i class="fas fa-print"></i> Imprimir / Guardar PDF</button>
    </div>
    <main class="report">
        <header class="report-header">
            <img src="./aurea.jpg" alt="Logo de AureaMed">
            <div><h1>Centro Auditivo AureaMed</h1></div>
        </header>
        <section class="data-grid" aria-label="Datos del paciente y evaluación">
            <div class="data-item"><strong>Paciente</strong><?php echo h($registro['nombre'] . ' ' . $registro['apellido']); ?></div>
            <div class="data-item"><strong>Edad</strong><?php echo h($edad); ?></div>
            <div class="data-item"><strong>Cédula de identidad</strong><?php echo h($registro['cedula']); ?></div>
            <div class="data-item"><strong>Fecha de audiometría</strong><?php echo date('d/m/Y', strtotime($registro['fecha_audiometria'])); ?></div>
            <div class="data-item"><strong>Fecha de nacimiento</strong><?php echo !empty($registro['fecha_nac']) ? date('d/m/Y', strtotime($registro['fecha_nac'])) : 'No registrada'; ?></div>
            <div class="data-item"><strong>Teléfono</strong><?php echo h($registro['telefono'] ?: 'No registrado'); ?></div>
            <div class="data-item"><strong>Correo electrónico</strong><?php echo h($registro['email'] ?: 'No registrado'); ?></div>
            <?php if (!empty($registro['direccion'])): ?>
                <div class="data-item"><strong>Dirección</strong><?php echo h($registro['direccion']); ?></div>
            <?php endif; ?>
        </section>
        <section>
            <h2 class="h5 mb-3">Foto de la audiometría</h2>
            <div class="exam-images">
                <?php while ($imagen = $imagenes->fetch_assoc()): ?>
                    <img src="audimetria_imagen.php?id=<?php echo (int)$imagen['id_imagen']; ?>" alt="Imagen de audiometría">
                <?php endwhile; ?>
            </div>
        </section>
    </main>
</body>
</html>
