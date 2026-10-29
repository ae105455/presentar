<?php
require_once __DIR__ . '/db_connection.php';

session_start();
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// Solo administradores pueden eliminar usuarios
if($_SESSION['rol'] != 'admin') {
    header("Location: panel.php");
    exit();
}

$conn = aureamed_db_connect();
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // No permitir autoeliminación
    if ($id === $_SESSION['usuario_id']) {
        // Intento de autoeliminación: no permitido
        $conn->close();
        header("Location: usuarios.php?msg=cannot_self");
        exit();
    }

    // Verificar si el usuario tiene reservas asociadas
    $check = $conn->prepare("SELECT id_reserva FROM reservas WHERE id_usuario = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            $check->close();
            $conn->close();
            header("Location: usuarios.php?msg=deleted");
            exit();
        } else {
            $stmt->close();
            $check->close();
            $conn->close();
            header("Location: usuarios.php?msg=error");
            exit();
        }
    } else {
        $check->close();
        $conn->close();
        header("Location: usuarios.php?msg=has_reservas");
        exit();
    }
}

$conn->close();
header("Location: usuarios.php");
exit();
?>