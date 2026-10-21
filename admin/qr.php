<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'aureamed');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'paciente';
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$qr_path = '';
$qr_error = '';
$datos = null;

$catalogos = array(
    'paciente' => array(
        'label' => 'Paciente',
        'query' => 'SELECT id_paciente AS id, nombre, apellido, cedula, telefono, email FROM pacientes WHERE id_paciente = ?'
    ),
    'servicio' => array(
        'label' => 'Servicio',
        'query' => 'SELECT id_servicio AS id, nombre_servicio AS nombre, precio, duracion_minutos, estado FROM servicios WHERE id_servicio = ?'
    ),
    'personal' => array(
        'label' => 'Personal',
        'query' => 'SELECT id_usuario AS id, nombre, usuario, rol, estado FROM usuarios WHERE id_usuario = ?'
    )
);

if (!isset($catalogos[$tipo])) {
    $tipo = 'paciente';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $stmt = $conn->prepare($catalogos[$tipo]['query']);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $datos = $resultado->fetch_assoc();
    $stmt->close();

    if ($datos) {
        $contenido = 'AureaMed | ' . $catalogos[$tipo]['label'] . PHP_EOL;
        foreach ($datos as $campo => $valor) {
            $contenido .= ucfirst(str_replace('_', ' ', $campo)) . ': ' . $valor . PHP_EOL;
        }

        require_once __DIR__ . '/QR2026/phpqrcode/qrlib.php';
        $qr_dir = __DIR__ . '/QR2026/qrs/';
        if (!is_dir($qr_dir)) {
            mkdir($qr_dir, 0775, true);
        }
        $nombre_archivo = $tipo . '_' . $id . '.png';
        QRcode::png($contenido, $qr_dir . $nombre_archivo, QR_ECLEVEL_M, 6, 2);
        $qr_path = 'QR2026/qrs/' . $nombre_archivo;
    } else {
        $qr_error = 'No se encontró el registro seleccionado.';
    }
}

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Personal';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Generar código QR</title>
    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .qr-page-card { max-width: 760px; margin: 32px auto; }
        .qr-form { display: grid; grid-template-columns: 1fr 1fr auto; gap: 14px; align-items: end; }
        .qr-result { margin-top: 28px; text-align: center; }
        .qr-result img { width: 260px; height: 260px; image-rendering: pixelated; }
        @media (max-width: 700px) { .qr-form { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main class="main-container">
    <section class="nav-lateral">
        <div class="nav-lateral-bg show-nav-lateral"></div>
        <div class="nav-lateral-content">
            <figure class="nav-lateral-avatar">
                <i class="far fa-times-circle show-nav-lateral"></i>
                <img src="./aurea.jpg" alt="Logo de AureaMed">
                <figcaption><?php echo htmlspecialchars($nombre_usuario); ?><br><small><?php echo htmlspecialchars($rol_usuario); ?> &bull; AureaMed</small></figcaption>
            </figure>
            <nav class="nav-lateral-menu">
                <ul>
                    <li><a href="panel.php"><i class="fas fa-th-large fa-fw"></i> &nbsp; Inicio</a></li>
                    <li><a href="pacientes.php"><i class="fas fa-users fa-fw"></i> &nbsp; Pacientes</a></li>
                    <li><a href="servicios.php"><i class="fas fa-stethoscope fa-fw"></i> &nbsp; Servicios</a></li>
                    <li><a href="reservas.php"><i class="fas fa-calendar-alt fa-fw"></i> &nbsp; Reservas</a></li>
                    <li><a href="qr.php" class="active"><i class="fas fa-qrcode fa-fw"></i> &nbsp; Generar código QR</a></li>
                    <li><a href="logout.php"><i class="fas fa-power-off fa-fw"></i> &nbsp; Salir</a></li>
                </ul>
            </nav>
        </div>
    </section>
    <section class="page-content">
        <nav class="navbar-info">
            <div class="navbar-info-left">
                <button type="button" class="show-nav-lateral btn-icon" title="Ocultar/Mostrar Menú"><i class="fas fa-bars"></i></button>
                <h4 class="navbar-brand-title d-none d-sm-flex"><i class="fas fa-qrcode text-primary"></i> Generar códigos QR</h4>
            </div>
            <div class="navbar-info-right"><a href="perfil.php"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($nombre_usuario); ?></a></div>
        </nav>
        <div class="container-fluid">
            <div class="table-container qr-page-card">
                <div class="table-container-header"><h5 class="table-container-title"><i class="fas fa-qrcode text-primary"></i> QR de información del sistema</h5></div>
                <div style="padding: 24px;">
                    <p class="text-muted">Seleccione un tipo de registro y genere un código QR con sus datos.</p>
                    <?php if ($qr_error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($qr_error); ?></div><?php endif; ?>
                    <form method="post" class="qr-form">
                        <div><label for="tipo">Tipo de información</label><select class="form-control" id="tipo" name="tipo"><option value="paciente" <?php echo $tipo === 'paciente' ? 'selected' : ''; ?>>Paciente</option><option value="servicio" <?php echo $tipo === 'servicio' ? 'selected' : ''; ?>>Servicio</option><option value="personal" <?php echo $tipo === 'personal' ? 'selected' : ''; ?>>Personal</option></select></div>
                        <div><label for="id">ID del registro</label><input class="form-control" id="id" name="id" type="number" min="1" required value="<?php echo $id ?: ''; ?>"></div>
                        <button class="btn btn-primary" type="submit"><i class="fas fa-qrcode"></i> Generar QR</button>
                    </form>
                    <?php if ($qr_path): ?><div class="qr-result"><img src="<?php echo htmlspecialchars($qr_path); ?>" alt="Código QR generado"><p class="mt-3"><a class="btn btn-success" download href="<?php echo htmlspecialchars($qr_path); ?>"><i class="fas fa-download"></i> Descargar QR</a></p></div><?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<script src="./js/jquery-3.4.1.min.js"></script>
<script src="./js/bootstrap.min.js"></script>
<script src="./js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
