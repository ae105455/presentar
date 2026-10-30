<?php
require_once __DIR__ . '/../../admin/db_connection.php';
require_once __DIR__ . '/../../admin/web_request_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php#reclamo', true, 303);
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
$email = $obtener_campo('email');
$telefono = $obtener_campo('telefono');
$asunto = $obtener_campo('asunto');
$mensaje = $obtener_campo('mensaje');

if (
    $nombre === ''
    || $email === ''
    || $asunto === ''
    || $mensaje === ''
    || strlen($nombre) > 100
    || strlen($email) > 100
    || strlen($telefono) > 20
    || strlen($asunto) > 200
    || strlen($mensaje) > 5000
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || !preg_match('/\A[\p{L}]+(?:[\s\'-][\p{L}]+)*\z/u', $nombre)
) {
    http_response_code(422);
    $resultado = 'Revisa los campos obligatorios y el formato del correo e inténtalo nuevamente.';
    $exito = false;
} else {
    $stmt = $conn->prepare("INSERT INTO mensajes_contacto (nombre, email, telefono, asunto, mensaje) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        http_response_code(500);
        $resultado = 'No se pudo preparar el envío del mensaje. Inténtalo más tarde.';
        $exito = false;
        error_log('No se pudo preparar mensaje de contacto: ' . $conn->error);
    } else {
        $stmt->bind_param('sssss', $nombre, $email, $telefono, $asunto, $mensaje);
        if ($stmt->execute()) {
            $resultado = $asunto === 'Reclamo'
                ? 'Gracias. Tu reclamo fue recibido y enviado al equipo de AureaMed.'
                : 'Gracias. Tu mensaje fue enviado al equipo de AureaMed.';
            $exito = true;
        } else {
            http_response_code(500);
            $resultado = 'No se pudo guardar el mensaje. Inténtalo más tarde.';
            $exito = false;
            error_log('No se pudo guardar mensaje de contacto: ' . $stmt->error);
        }
        $stmt->close();
    }
}
$conn->close();
$titulo_resultado = !empty($exito) && isset($asunto) && $asunto === 'Reclamo'
    ? 'Reclamo recibido'
    : (!empty($exito) ? 'Mensaje recibido' : 'No se pudo enviar');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AureaMed | <?php echo htmlspecialchars($titulo_resultado, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="./global.css">
    <style>
        body { min-height:100vh; display:grid; place-items:center; padding:20px; background:#f4f6f8; }
        main { width:min(100%,560px); padding:32px; border-radius:20px; background:#fff; box-shadow:0 12px 35px rgba(0,0,0,.12); }
        h1 { color:#8b2c2c; font-size:1.6rem; }
        .result-actions { display:flex; flex-wrap:wrap; gap:12px; margin-top:24px; }
        .result-actions a { display:inline-flex; align-items:center; justify-content:center; min-height:44px; padding:10px 18px; border-radius:999px; background:#8b2c2c; color:#fff; text-decoration:none; }
        @media(max-width:480px) { main { padding:22px; } .result-actions a { width:100%; } }
    </style>
</head>
<body>
    <main>
        <h1><?php echo htmlspecialchars($titulo_resultado, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p><?php echo htmlspecialchars($resultado, ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="result-actions">
            <a href="./index.php#reclamo">Volver al formulario de reclamos</a>
            <a href="https://wa.me/59177534284?text=Hola%2C%20quiero%20hacer%20una%20consulta%20a%20AureaMed." target="_blank" rel="noopener noreferrer">Contactar por WhatsApp</a>
        </div>
    </main>
</body>
</html>
