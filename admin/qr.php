<?php
require_once __DIR__ . '/db_connection.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

$conn = aureamed_db_connect();
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'paciente';
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$qr_path = '';
$qr_error = '';
$qr_warning = '';
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
        if ($tipo === 'paciente') {
            $create_qr_tokens = "CREATE TABLE IF NOT EXISTS qr_pacientes (
                id_paciente INT PRIMARY KEY,
                token CHAR(64) NOT NULL UNIQUE,
                actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_qr_paciente FOREIGN KEY (id_paciente)
                    REFERENCES pacientes(id_paciente) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            if (!$conn->query($create_qr_tokens)) {
                $qr_error = 'No se pudo preparar el acceso seguro del QR.';
            } else {
                $token = bin2hex(random_bytes(32));
                $stmt_token = $conn->prepare("INSERT INTO qr_pacientes (id_paciente, token) VALUES (?, ?) ON DUPLICATE KEY UPDATE token = VALUES(token)");
                if (!$stmt_token) {
                    $qr_error = 'No se pudo preparar el QR del paciente: ' . $conn->error;
                } else {
                    $stmt_token->bind_param('is', $id, $token);
                    if (!$stmt_token->execute()) {
                        $qr_error = 'No se pudo guardar el acceso seguro del QR: ' . $stmt_token->error;
                    }
                    $stmt_token->close();
                }
            }

            $qr_base_url = getenv('AUREAMED_QR_BASE_URL');
            $qr_host = '';
            if ($qr_base_url !== false && trim($qr_base_url) !== '') {
                $parsed_base_url = parse_url(trim($qr_base_url));
                if (
                    $parsed_base_url === false
                    || !isset($parsed_base_url['scheme'], $parsed_base_url['host'])
                    || !in_array(strtolower($parsed_base_url['scheme']), array('http', 'https'), true)
                    || isset($parsed_base_url['user'])
                    || isset($parsed_base_url['pass'])
                    || isset($parsed_base_url['query'])
                    || isset($parsed_base_url['fragment'])
                    || !preg_match('/\A(?:[a-zA-Z0-9.-]+|[0-9a-fA-F:]+)\z/', $parsed_base_url['host'])
                    || (isset($parsed_base_url['port']) && ($parsed_base_url['port'] < 1 || $parsed_base_url['port'] > 65535))
                ) {
                    $qr_error = 'La URL base configurada para el QR no es válida.';
                } else {
                    $qr_host = $parsed_base_url['host'];
                    if (strpos($qr_host, ':') !== false) {
                        $qr_host = '[' . $qr_host . ']';
                    }
                    if (isset($parsed_base_url['port'])) {
                        $qr_host .= ':' . $parsed_base_url['port'];
                    }
                    $qr_base_url = strtolower($parsed_base_url['scheme']) . '://' . $qr_host
                        . (isset($parsed_base_url['path']) ? rtrim($parsed_base_url['path'], '/') : '');
                }
            } else {
                $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                $script_dir = str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/admin/qr.php'));
                if (!preg_match('/\A(?:[a-zA-Z0-9.-]+|\[[0-9a-fA-F:]+\])(?::\d{1,5})?\z/', $host)) {
                    $qr_error = 'El servidor no proporcionó un nombre de host válido para el QR.';
                } else {
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $qr_base_url = $scheme . '://' . $host . rtrim($script_dir, '/');
                    $qr_host = preg_replace('/:\d+\z/', '', $host);
                    $qr_host = trim($qr_host, '[]');
                }
            }
            if (preg_match('/\A(?:localhost|127\.0\.0\.1|::1)\z/i', $qr_host)) {
                $qr_warning = 'Este QR usa localhost y solo abre en este computador. Para escanearlo desde un teléfono, el sitio debe estar publicado o configurado para acceso seguro en la red local.';
            }
            if (!$qr_error) {
                $contenido = rtrim($qr_base_url, '/') . '/qr_paciente.php?token=' . $token;
            }
        } else {
            $contenido = 'AureaMed | ' . $catalogos[$tipo]['label'] . PHP_EOL;
            foreach ($datos as $campo => $valor) {
                $contenido .= ucfirst(str_replace('_', ' ', $campo)) . ': ' . $valor . PHP_EOL;
            }
        }

        if (!$qr_error) {
            require_once __DIR__ . '/comprobante_helpers.php';
            $qr_dir = __DIR__ . '/QR2026/qrs/';
            if (!is_dir($qr_dir) && !mkdir($qr_dir, 0775, true) && !is_dir($qr_dir)) {
                $qr_error = 'No se pudo preparar la carpeta para guardar el código QR.';
            }
            if (!$qr_error) {
                $nombre_archivo = $tipo . '_' . $id . '.svg';
                if (file_put_contents($qr_dir . $nombre_archivo, generarCodigoQrSvg($contenido, 'M', 2)) === false) {
                    $qr_error = 'No se pudo guardar el código QR.';
                } else {
                    $qr_path = 'QR2026/qrs/' . $nombre_archivo;
                }
            }
        }
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
                <h4 class="navbar-brand-title d-none d-sm-flex"><i class="fas fa-qrcode text-primary"></i> Ventas y Leyes</h4>
            </div>
            <div class="navbar-info-right"><a href="perfil.php"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($nombre_usuario); ?></a></div>
        </nav>
        <div class="container-fluid">
            <div class="table-container qr-page-card">
                <div class="table-container-header"><h5 class="table-container-title"><i class="fas fa-qrcode text-primary"></i> QR de información del sistema</h5></div>
                <div style="padding: 24px;">
                    <p class="text-muted">Seleccione un tipo de registro y genere un código QR con sus datos.</p>
                    <?php if ($qr_error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($qr_error); ?></div><?php endif; ?>
                    <?php if ($qr_warning): ?><div class="alert alert-warning"><?php echo htmlspecialchars($qr_warning); ?></div><?php endif; ?>
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
