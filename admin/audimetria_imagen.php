<?php
require_once __DIR__ . '/db_connection.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit('Acceso no autorizado.');
}

$id_imagen = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_imagen) {
    http_response_code(400);
    exit('Identificador de imagen no válido.');
}

$conn = aureamed_db_connect();
if ($conn->connect_error) {
    http_response_code(500);
    exit('No se pudo acceder a la base de datos.');
}
require_once __DIR__ . '/audiometria_helpers.php';
asegurarTablasAudiometria($conn);

$stmt = $conn->prepare("SELECT archivo, tipo_mime FROM audiometria_imagenes WHERE id_imagen = ?");
$stmt->bind_param("i", $id_imagen);
$stmt->execute();
$imagen = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$imagen || !preg_match('/\A[a-f0-9]{32}\.(jpg|png|webp)\z/', $imagen['archivo'])) {
    http_response_code(404);
    exit('No se encontró la imagen.');
}
$tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($imagen['tipo_mime'], $tipos_permitidos, true)) {
    http_response_code(415);
    exit('Tipo de imagen no permitido.');
}
$ruta = __DIR__ . '/uploads/audiometrias/' . $imagen['archivo'];
if (!is_file($ruta)) {
    http_response_code(404);
    exit('No se encontró el archivo de imagen.');
}

header('Content-Type: ' . $imagen['tipo_mime']);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($ruta);
