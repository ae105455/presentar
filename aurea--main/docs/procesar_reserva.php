<?php
require_once __DIR__ . '/../../admin/db_connection.php';
require_once __DIR__ . '/../../admin/web_request_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./index.php#reservar', true, 303);
    exit();
}

$conn = aureamed_db_connect();
if ($conn->connect_error) {
    http_response_code(503);
    exit('No se pudo conectar con el sistema de reservas. Inténtalo más tarde.');
}

$obtener_campo = static function ($clave) {
    return isset($_POST[$clave]) && is_string($_POST[$clave]) ? trim($_POST[$clave]) : '';
};

asegurarTablasSolicitudesWeb($conn);
$nombre = $obtener_campo('nombre');
$cedula = $obtener_campo('cedula');
$email = $obtener_campo('email');
$telefono = $obtener_campo('telefono');
$edad = $obtener_campo('edad');
$servicio = $obtener_campo('servicio');
$fecha = $obtener_campo('fecha');
$hora = $obtener_campo('hora');
$como_nos_conocio = $obtener_campo('como_nos_conocio');
$mensaje = $obtener_campo('mensaje');
$fecha_objeto = DateTime::createFromFormat('!Y-m-d', $fecha);
$servicios_validos = array('audiometria', 'limpieza', 'reparacion', 'moldes', 'consulta');

if ($nombre === '' || $cedula === '' || $email === '' || $telefono === '' || $edad === '' || $servicio === '' || $fecha === '' || $hora === '') {
    $error = '❌ Por favor, completa todos los campos obligatorios.';
} elseif (
    strlen($nombre) > 100
    || !preg_match('/\A[\p{L}]+(?:[\s\'-][\p{L}]+)*\z/u', $nombre)
    || !preg_match('/\s/u', $nombre)
    || strlen($email) > 100
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($edad) > 20
    || strlen($como_nos_conocio) > 50
    || strlen($mensaje) > 5000
) {
    $error = '❌ Revisa que escribiste tu nombre completo, correo válido y datos dentro de la longitud permitida.';
} elseif (!preg_match('/\A[0-9]{8}\z/', $telefono)) {
    $error = '❌ El teléfono debe tener exactamente 8 dígitos, sin espacios ni guiones.';
} elseif (!in_array($edad, array('menor', 'joven', 'adulto', 'mayor'), true)) {
    $error = '❌ Selecciona un rango de edad válido.';
} elseif (strlen($cedula) > 20 || !preg_match('/\A[\p{L}\p{N}.-]+\z/u', $cedula)) {
    $error = '❌ La cédula no es válida.';
} elseif (!in_array($servicio, $servicios_validos, true)) {
    $error = '❌ Selecciona un servicio válido.';
} elseif (!$fecha_objeto || $fecha_objeto->format('Y-m-d') !== $fecha || $fecha < date('Y-m-d')) {
    $error = '❌ Selecciona una fecha válida desde hoy en adelante.';
} elseif (!preg_match('/\A(?:09|10|11|15|16|17):00\z/', $hora)) {
    $error = '❌ Selecciona un horario disponible.';
} else {
    $stmt = $conn->prepare("INSERT INTO reservas_rapidas
        (cedula, nombre, email, telefono, edad, servicio, fecha, hora, como_nos_conocio, mensaje)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        http_response_code(500);
        $error = '❌ No se pudo preparar la solicitud. Inténtalo más tarde.';
        error_log('No se pudo preparar reserva web: ' . $conn->error);
    } else {
        $stmt->bind_param('ssssssssss', $cedula, $nombre, $email, $telefono, $edad, $servicio, $fecha, $hora, $como_nos_conocio, $mensaje);
        if ($stmt->execute()) {
            $exito = '✅ ¡Reserva creada exitosamente! Te contactaremos pronto.';
        } else {
            http_response_code(500);
            $error = '❌ No se pudo guardar la solicitud. Inténtalo más tarde.';
            error_log('No se pudo guardar reserva web: ' . $stmt->error);
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Reserva enviada</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #800020, #eb6c05);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            background: white;
            border-radius: 28px;
            padding: 50px 40px;
            text-align: center;
            max-width: 500px;
            margin: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .icon { font-size: 80px; margin-bottom: 20px; }
        h2 { color: #800020; margin-bottom: 15px; font-size: 28px; }
        p { color: #555; margin-bottom: 30px; line-height: 1.6; }
        .btn {
            background: linear-gradient(135deg, #800020, #eb6c05);
            color: white;
            padding: 14px 35px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.2); }
        .error-icon { color: #dc3545; }
        .success-icon { color: #28a745; }
    </style>
</head>
<body>
    <div class="card">
        <?php if(isset($exito)): ?>
            <div class="icon success-icon">✅📅</div>
            <h2>¡Reserva Enviada!</h2>
            <p><?php echo htmlspecialchars($exito, ENT_QUOTES, 'UTF-8'); ?></p>
            <a href="./index.php" class="btn">← Volver al Inicio</a>
        <?php elseif(isset($error)): ?>
            <div class="icon error-icon">❌</div>
            <h2>Error</h2>
            <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
            <a href="./index.php" class="btn">← Volver al Inicio</a>
        <?php endif; ?>
    </div>
</body>
</html>