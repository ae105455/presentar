<?php
require_once __DIR__ . '/db_connection.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$id_paciente = filter_input(INPUT_GET, 'id_paciente', FILTER_VALIDATE_INT);
if (!$id_paciente) {
    header("Location: pacientes.php");
    exit();
}

$conn = aureamed_db_connect();
if ($conn->connect_error) {
    die("Error de conexión: " . htmlspecialchars($conn->connect_error, ENT_QUOTES, 'UTF-8'));
}
require_once __DIR__ . '/audiometria_helpers.php';
asegurarTablasAudiometria($conn);

$stmt = $conn->prepare("SELECT id_paciente, nombre, apellido, cedula, fecha_nac FROM pacientes WHERE id_paciente = ?");
$stmt->bind_param("i", $id_paciente);
$stmt->execute();
$paciente = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$paciente) {
    http_response_code(404);
    exit('No se encontró el paciente.');
}

$stmt = $conn->prepare("SELECT id_audiometria, fecha_audiometria FROM audiometrias WHERE id_paciente = ? ORDER BY fecha_audiometria DESC, id_audiometria DESC");
$stmt->bind_param("i", $id_paciente);
$stmt->execute();
$audiometrias = $stmt->get_result();
$mensaje = isset($_SESSION['mensaje_audiometria']) ? $_SESSION['mensaje_audiometria'] : '';
$tipo_mensaje = isset($_SESSION['tipo_mensaje_audiometria']) ? $_SESSION['tipo_mensaje_audiometria'] : 'success';
unset($_SESSION['mensaje_audiometria'], $_SESSION['tipo_mensaje_audiometria']);
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
    <title>AureaMed | Historial de audiometrías</title>
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <style>
        body { background: #f5f7fa; color: #334155; padding: 28px 12px; }
        .history-card { max-width: 1000px; margin: auto; background: #fff; border-radius: 14px; padding: 28px; box-shadow: 0 8px 28px rgba(15,23,42,.08); }
        .brand { display:flex; align-items:center; gap:18px; border-bottom:2px solid #8b2c2c; padding-bottom:18px; margin-bottom:22px; }
        .brand img { max-width:160px; max-height:72px; object-fit:contain; }
        .brand h1 { font-size:1.45rem; color:#5c1e1e; margin:0; }
        @media (max-width:575px) { .history-card { padding:18px; } .brand img { max-width:100px; } }
    </style>
</head>
<body>
    <main class="history-card">
        <header class="brand">
            <img src="./aurea.jpg" alt="Logo de AureaMed">
            <div><h1>Historial de audiometrías</h1><p class="mb-0"><?php echo h($paciente['nombre'] . ' ' . $paciente['apellido']); ?> · C.I. <?php echo h($paciente['cedula']); ?></p></div>
        </header>
        <p><a class="btn btn-outline-secondary btn-sm" href="pacientes.php"><i class="fas fa-arrow-left"></i> Volver a pacientes</a></p>
        <?php if ($mensaje !== ''): ?>
            <div class="alert alert-<?php echo h($tipo_mensaje); ?>"><?php echo h($mensaje); ?></div>
        <?php endif; ?>
        <?php if ($audiometrias->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover bg-white">
                    <thead class="thead-light"><tr><th>Fecha</th><th>Paciente</th><th>Edad</th><th>Audiometría</th></tr></thead>
                    <tbody>
                    <?php while ($registro = $audiometrias->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($registro['fecha_audiometria'])); ?></td>
                            <td><?php echo h($paciente['nombre'] . ' ' . $paciente['apellido']); ?></td>
                            <td><?php echo h(edadPaciente($paciente['fecha_nac'], $registro['fecha_audiometria'])); ?></td>
                            <td><a class="btn btn-sm btn-primary" href="audimetria_ver.php?id=<?php echo (int)$registro['id_audiometria']; ?>"><i class="fas fa-print"></i> Ver / imprimir</a></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-0">Todavía no hay audiometrías registradas para este paciente.</div>
        <?php endif; ?>
    </main>
</body>
</html>
