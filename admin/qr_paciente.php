<?php
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
    http_response_code(404);
    exit('No se encontró la ficha del paciente.');
}

$conn = new mysqli('localhost', 'root', '', 'aureamed');
if ($conn->connect_error) {
    http_response_code(500);
    exit('No se pudo cargar la ficha.');
}

$stmt = $conn->prepare("SELECT p.nombre, p.apellido, p.cedula, p.telefono, p.email, p.fecha_nac, q.actualizado_en
    FROM qr_pacientes q
    JOIN pacientes p ON p.id_paciente = q.id_paciente
    WHERE q.token = ?");
$stmt->bind_param('s', $token);
$stmt->execute();
$paciente = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$paciente) {
    http_response_code(404);
    exit('No se encontró la ficha del paciente.');
}

$edad = 'No registrada';
if (!empty($paciente['fecha_nac'])) {
    $nacimiento = new DateTimeImmutable($paciente['fecha_nac']);
    $edad_en_fecha = new DateTimeImmutable(substr($paciente['actualizado_en'], 0, 10));
    if ($nacimiento <= $edad_en_fecha) {
        $edad = $nacimiento->diff($edad_en_fecha)->y . ' años';
    }
}

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
    <title>AureaMed | Información del paciente</title>
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <style>
        body { margin:0; padding:24px 14px; background:#f5f7fa; color:#334155; font-family:Arial,sans-serif; }
        .patient-card { max-width:680px; margin:0 auto; padding:24px; background:#fff; border-radius:14px; box-shadow:0 8px 26px rgba(15,23,42,.09); }
        .brand { display:flex; align-items:center; gap:16px; padding-bottom:18px; border-bottom:3px solid #8b2c2c; }
        .brand img { max-width:125px; max-height:72px; object-fit:contain; }
        .brand h1 { margin:0; color:#5c1e1e; font-size:1.35rem; }
        .patient-data { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin:20px 0; }
        .data-item { padding:12px; border-radius:8px; background:#f7f8fa; overflow-wrap:anywhere; }
        .data-item strong { display:block; margin-bottom:4px; color:#5c1e1e; }
        .ear-photo { display:block; width:100%; max-height:420px; object-fit:cover; border-radius:10px; }
        @media (max-width:520px) { .patient-card { padding:18px; } .patient-data { grid-template-columns:1fr; } .brand img { max-width:95px; } }
    </style>
</head>
<body>
    <main class="patient-card">
        <header class="brand">
            <img src="./aurea.jpg" alt="Logo de AureaMed">
            <h1>Información del paciente</h1>
        </header>
        <section class="patient-data" aria-label="Datos del paciente">
            <div class="data-item"><strong>Nombre</strong><?php echo h($paciente['nombre'] . ' ' . $paciente['apellido']); ?></div>
            <div class="data-item"><strong>Edad</strong><?php echo h($edad); ?></div>
            <div class="data-item"><strong>Cédula</strong><?php echo h($paciente['cedula']); ?></div>
            <?php if (!empty($paciente['telefono'])): ?>
                <div class="data-item"><strong>Teléfono</strong><?php echo h($paciente['telefono']); ?></div>
            <?php endif; ?>
            <?php if (!empty($paciente['email'])): ?>
                <div class="data-item"><strong>Correo</strong><?php echo h($paciente['email']); ?></div>
            <?php endif; ?>
        </section>
        <img class="ear-photo" src="../aurea--main/docs/oido.jpg" alt="Persona usando un audífono detrás de la oreja">
    </main>
</body>
</html>
