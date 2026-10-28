<?php
session_start();
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// Solo administradores pueden eliminar servicios
if($_SESSION['rol'] != 'admin') {
    header("Location: panel.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'aureamed');
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id > 0) {
    // Verificar si el servicio tiene reservas asociadas
    $check = $conn->prepare("SELECT id_reserva FROM reservas WHERE id_servicio = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    
    if($check->num_rows > 0) {
        // Tiene reservas, solo deshabilitar
        $stmt = $conn->prepare("UPDATE servicios SET estado = 'inactivo' WHERE id_servicio = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $mensaje = "El servicio tiene reservas asociadas. Se ha deshabilitado en lugar de eliminar.";
    } else {
        // No tiene reservas, eliminar completamente
        $stmt = $conn->prepare("DELETE FROM servicios WHERE id_servicio = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $mensaje = "Servicio eliminado exitosamente.";
    }
    $check->close();
}

$conn->close();
header("Location: servicios.php");
exit();
?>