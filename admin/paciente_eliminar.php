<?php
require_once __DIR__ . '/db_connection.php';

session_start();
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: pacientes.php?msg=error");
    exit();
}

$id_paciente = intval($_GET['id']);

$conn = aureamed_db_connect();
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Verificar que el paciente existe
$check_stmt = $conn->prepare("SELECT id_paciente FROM pacientes WHERE id_paciente = ?");
$check_stmt->bind_param("i", $id_paciente);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if($check_result->num_rows == 0) {
    $check_stmt->close();
    $conn->close();
    header("Location: pacientes.php?msg=notfound");
    exit();
}
$check_stmt->close();

// Eliminar el paciente
$stmt = $conn->prepare("DELETE FROM pacientes WHERE id_paciente = ?");
$stmt->bind_param("i", $id_paciente);

if($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: pacientes.php?msg=deleted");
    exit();
} else {
    $stmt->close();
    $conn->close();
    header("Location: pacientes.php?msg=error");
    exit();
}
?>
