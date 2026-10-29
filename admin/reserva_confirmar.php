<?php
require_once __DIR__ . '/db_connection.php';

session_start();
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$conn = aureamed_db_connect();
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ref = isset($_GET['ref']) ? trim($_GET['ref']) : 'reserva_pendientes';

if($id > 0) {
    $stmt = $conn->prepare("UPDATE reservas SET estado = 'confirmada' WHERE id_reserva = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();

if($ref === 'reservas') {
    header("Location: reservas.php");
} else {
    header("Location: reserva_pendientes.php");
}
exit();
?>