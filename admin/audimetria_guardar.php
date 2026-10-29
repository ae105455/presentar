<?php
require_once __DIR__ . '/db_connection.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

function volverConError(string $mensaje): void
{
    $_SESSION['mensaje_audiometria'] = $mensaje;
    $_SESSION['tipo_mensaje_audiometria'] = 'danger';
    header("Location: pacientes.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !isset($_POST['csrf_token'], $_SESSION['csrf_audiometria'])
    || !hash_equals($_SESSION['csrf_audiometria'], $_POST['csrf_token'])) {
    volverConError('La sesión del formulario venció. Intente registrar nuevamente la audiometría.');
}

$conn = aureamed_db_connect();
if ($conn->connect_error) {
    volverConError('No se pudo conectar con la base de datos.');
}
require_once __DIR__ . '/audiometria_helpers.php';
asegurarTablasAudiometria($conn);

$id_paciente = filter_input(INPUT_POST, 'id_paciente', FILTER_VALIDATE_INT);
if (!$id_paciente) {
    volverConError('No se pudo identificar al paciente.');
}
$fecha = date('Y-m-d');

$stmt = $conn->prepare("SELECT id_paciente FROM pacientes WHERE id_paciente = ?");
$stmt->bind_param("i", $id_paciente);
$stmt->execute();
$paciente = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$paciente) {
    volverConError('No se encontró el paciente seleccionado.');
}

if (!isset($_FILES['imagen'])) {
    volverConError('Seleccione la foto de la audiometría.');
}

$imagen_subida = $_FILES['imagen'];
if ($imagen_subida['error'] !== UPLOAD_ERR_OK || $imagen_subida['size'] > 5 * 1024 * 1024) {
    volverConError('La imagen no se pudo cargar o supera el límite de 5 MB.');
}

$mime_a_extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($imagen_subida['tmp_name']);
if (!isset($mime_a_extension[$mime])) {
    volverConError('Solo se permiten imágenes JPG, PNG o WEBP.');
}
$imagen = [
    'temporal' => $imagen_subida['tmp_name'],
    'archivo' => bin2hex(random_bytes(16)) . '.' . $mime_a_extension[$mime],
    'mime' => $mime
];

$directorio = __DIR__ . '/uploads/audiometrias';
if (!is_dir($directorio) && !mkdir($directorio, 0750, true) && !is_dir($directorio)) {
    volverConError('No se pudo preparar el almacenamiento privado de imágenes.');
}

$conn->begin_transaction();
$edad = 0;
$actual = '';
$recomendado = '';
$responsable = '';
$observaciones = '';
$stmt = $conn->prepare("INSERT INTO audiometrias (id_paciente, fecha_audiometria, edad, audifono_actual, audifono_recomendado, responsable, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    $conn->rollback();
    volverConError('No se pudo preparar el registro de la audiometría: ' . $conn->error);
}
$stmt->bind_param("isissss", $id_paciente, $fecha, $edad, $actual, $recomendado, $responsable, $observaciones);
if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();
    $conn->rollback();
    volverConError('No se pudo guardar la audiometría: ' . $error);
}
$id_audiometria = $stmt->insert_id;
$stmt->close();

$destino = $directorio . '/' . $imagen['archivo'];
if (!move_uploaded_file($imagen['temporal'], $destino)) {
    $conn->rollback();
    volverConError('No se pudo guardar la imagen de la audiometría.');
}

$stmt_imagen = $conn->prepare("INSERT INTO audiometria_imagenes (id_audiometria, archivo, tipo_mime) VALUES (?, ?, ?)");
if (!$stmt_imagen) {
    unlink($destino);
    $conn->rollback();
    volverConError('No se pudo preparar el registro de la imagen: ' . $conn->error);
}
$archivo = $imagen['archivo'];
$mime = $imagen['mime'];
$stmt_imagen->bind_param("iss", $id_audiometria, $archivo, $mime);
if (!$stmt_imagen->execute()) {
    $error = $stmt_imagen->error;
    $stmt_imagen->close();
    unlink($destino);
    $conn->rollback();
    volverConError('No se pudo asociar la imagen con la audiometría: ' . $error);
}
$stmt_imagen->close();
$conn->commit();

$_SESSION['mensaje_audiometria'] = 'Audiometría guardada correctamente.';
$_SESSION['tipo_mensaje_audiometria'] = 'success';
header("Location: audimetria_historial.php?id_paciente=" . (int)$id_paciente);
exit();
