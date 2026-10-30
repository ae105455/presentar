<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/web_request_helpers.php';
require_once __DIR__ . '/attendance_helpers.php';

session_start();
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$conn = aureamed_db_connect();
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
asegurarTablasSolicitudesWeb($conn);
asegurarEstadoAtencionReservas($conn);

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Personal';
if (!isset($_SESSION['csrf_solicitudes_web'])) {
    $_SESSION['csrf_solicitudes_web'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_solicitudes_web'];
$mensaje_web = isset($_SESSION['mensaje_web']) ? $_SESSION['mensaje_web'] : '';
$tipo_mensaje_web = isset($_SESSION['tipo_mensaje_web']) ? $_SESSION['tipo_mensaje_web'] : 'success';
unset($_SESSION['mensaje_web'], $_SESSION['tipo_mensaje_web']);
$transaccion_iniciada = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';
    $token_recibido = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    try {
        if (!isset($_SESSION['csrf_solicitudes_web']) || !hash_equals($_SESSION['csrf_solicitudes_web'], $token_recibido)) {
            throw new RuntimeException('La sesión del formulario venció. Recarga la página e inténtalo nuevamente.');
        }

        if ($accion === 'actualizar_atencion_web') {
            $estado_atencion = isset($_POST['estado_atencion']) && is_string($_POST['estado_atencion'])
                ? $_POST['estado_atencion']
                : '';
            $etiquetas_atencion = array(
                'pendiente' => 'Pendiente',
                'atendida' => 'Atendido',
                'no_asistio' => 'No asistió'
            );
            if (!isset($etiquetas_atencion[$estado_atencion])) {
                throw new RuntimeException('Elige un estado de atención válido.');
            }

            $stmt = $conn->prepare("SELECT r.id_reserva
                FROM reservas_rapidas rw
                INNER JOIN reservas r ON r.id_reserva = rw.id_reserva_sistema
                WHERE rw.id_reserva = ? AND rw.estado <> 'cancelada' AND r.estado <> 'cancelada'");
            if (!$stmt) {
                throw new RuntimeException('No se pudo buscar la cita enlazada: ' . $conn->error);
            }
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                throw new RuntimeException('No se pudo buscar la cita enlazada: ' . $error);
            }
            $cita = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$cita) {
                throw new RuntimeException('Primero agenda la cita; luego podrás marcarla como atendida.');
            }

            $id_reserva = (int)$cita['id_reserva'];
            $stmt = $conn->prepare("UPDATE reservas SET estado_atencion = ? WHERE id_reserva = ? AND estado <> 'cancelada'");
            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar el cambio de atención: ' . $conn->error);
            }
            $stmt->bind_param('si', $estado_atencion, $id_reserva);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                throw new RuntimeException('No se pudo guardar la atención: ' . $error);
            }
            $stmt->close();
            $_SESSION['mensaje_web'] = 'Cita actualizada: ' . $etiquetas_atencion[$estado_atencion] . '.';
            $_SESSION['tipo_mensaje_web'] = 'success';
        } elseif ($accion === 'guardar_paciente_web') {
            if (!$conn->begin_transaction()) {
                throw new RuntimeException('No se pudo iniciar el registro del paciente.');
            }
            $transaccion_iniciada = true;
            $stmt = $conn->prepare("SELECT * FROM reservas_rapidas WHERE id_reserva = ? AND estado <> 'cancelada' FOR UPDATE");
            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar la solicitud: ' . $conn->error);
            }
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                throw new RuntimeException('No se pudo leer la solicitud: ' . $error);
            }
            $solicitud = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$solicitud) {
                throw new RuntimeException('La solicitud no existe o fue cancelada.');
            }

            $cedula = isset($_POST['cedula']) && is_string($_POST['cedula']) ? trim($_POST['cedula']) : '';
            $nombre_paciente = isset($_POST['nombre_paciente']) && is_string($_POST['nombre_paciente'])
                ? trim($_POST['nombre_paciente'])
                : '';
            $apellido_paciente = isset($_POST['apellido_paciente']) && is_string($_POST['apellido_paciente'])
                ? trim($_POST['apellido_paciente'])
                : '';
            $nombre_completo = trim($nombre_paciente . ' ' . $apellido_paciente);
            $nombre_incompleto = ($nombre_paciente !== '') !== ($apellido_paciente !== '');
            if (
                $cedula === ''
                || strlen($cedula) > 20
                || !preg_match('/\A[\p{L}\p{N}.-]+\z/u', $cedula)
                || $nombre_incompleto
                || strlen($nombre_paciente) > 100
                || strlen($apellido_paciente) > 100
                || strlen($nombre_completo) > 100
                || ($nombre_paciente !== '' && !preg_match('/\A[\p{L}]+(?:[\s\'-][\p{L}]+)*\z/u', $nombre_paciente))
                || ($apellido_paciente !== '' && !preg_match('/\A[\p{L}]+(?:[\s\'-][\p{L}]+)*\z/u', $apellido_paciente))
            ) {
                throw new RuntimeException('Ingresa una cédula válida. Si completas el nombre, indica también el apellido.');
            }
            $nombre_guardado = $nombre_completo !== '' ? $nombre_completo : trim((string)$solicitud['nombre']);
            $solicitud['cedula'] = $cedula;
            $solicitud['nombre'] = $nombre_guardado;
            if ($nombre_completo !== '') {
                $solicitud['nombre_paciente'] = $nombre_paciente;
                $solicitud['apellido_paciente'] = $apellido_paciente;
            } else {
                unset($solicitud['nombre_paciente'], $solicitud['apellido_paciente']);
            }
            $id_paciente = obtenerOCrearPacienteReservaWeb($conn, $solicitud);
            if ((int)$solicitud['id_paciente'] !== $id_paciente) {
                $stmt = $conn->prepare("UPDATE reservas_rapidas SET id_paciente = ?, cedula = ?, nombre = ? WHERE id_reserva = ?");
                if (!$stmt) {
                    throw new RuntimeException('No se pudo enlazar el paciente con la solicitud: ' . $conn->error);
                }
                $stmt->bind_param('issi', $id_paciente, $cedula, $nombre_guardado, $id);
                if (!$stmt->execute()) {
                    $error = $stmt->error;
                    $stmt->close();
                    throw new RuntimeException('No se pudo enlazar el paciente con la solicitud: ' . $error);
                }
                if ($stmt->affected_rows !== 1) {
                    $stmt->close();
                    throw new RuntimeException('La solicitud cambió y no se pudo enlazar el paciente. Recarga la página e inténtalo otra vez.');
                }
                $stmt->close();
            }
            if (!$conn->commit()) {
                throw new RuntimeException('No se pudo guardar la ficha del paciente.');
            }
            $transaccion_iniciada = false;
            $_SESSION['mensaje_web'] = 'Paciente guardado en el sistema. Ya puedes abrir su ficha o confirmar la cita.';
            $_SESSION['tipo_mensaje_web'] = 'success';
        } elseif ($accion === 'confirmar_reserva_web') {
            $stmt = $conn->prepare("SELECT * FROM reservas_rapidas WHERE id_reserva = ? AND estado <> 'cancelada' AND id_reserva_sistema IS NULL FOR UPDATE");
            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar la solicitud: ' . $conn->error);
            }
            $stmt->bind_param('i', $id);
            if (!$conn->begin_transaction()) {
                throw new RuntimeException('No se pudo iniciar la confirmación de la solicitud.');
            }
            $transaccion_iniciada = true;
            if (!$stmt->execute()) {
                throw new RuntimeException('No se pudo leer la solicitud: ' . $stmt->error);
            }
            $solicitud = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$solicitud) {
                throw new RuntimeException('La solicitud no existe, ya fue vinculada o está cancelada.');
            }

            $id_servicio = isset($_POST['id_servicio']) ? (int)$_POST['id_servicio'] : 0;
            $fecha_cita = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
            $hora_cita = isset($_POST['hora']) ? trim($_POST['hora']) : '';
            $fecha_objeto = DateTime::createFromFormat('!Y-m-d', $fecha_cita);
            if (!$fecha_objeto || $fecha_objeto->format('Y-m-d') !== $fecha_cita || $fecha_cita < date('Y-m-d')) {
                throw new RuntimeException('Selecciona una fecha de cita válida.');
            }
            if (!preg_match('/\A(?:09|10|11|15|16|17):00\z/', $hora_cita)) {
                throw new RuntimeException('Selecciona un horario válido.');
            }

            $stmt_servicio = $conn->prepare("SELECT id_servicio FROM servicios WHERE id_servicio = ? AND estado IN ('activo', '1')");
            if (!$stmt_servicio) {
                throw new RuntimeException('No se pudo preparar la validación del servicio: ' . $conn->error);
            }
            $stmt_servicio->bind_param('i', $id_servicio);
            $stmt_servicio->execute();
            $servicio_valido = $stmt_servicio->get_result()->fetch_assoc();
            $stmt_servicio->close();
            if (!$servicio_valido) {
                throw new RuntimeException('Selecciona un servicio activo antes de confirmar.');
            }

            $stmt_horario = $conn->prepare("SELECT id_reserva FROM reservas WHERE fecha_reserva = ? AND hora_reserva = ? AND estado <> 'cancelada' LIMIT 1");
            if (!$stmt_horario) {
                throw new RuntimeException('No se pudo comprobar el horario: ' . $conn->error);
            }
            $stmt_horario->bind_param('ss', $fecha_cita, $hora_cita);
            $stmt_horario->execute();
            $horario_ocupado = $stmt_horario->get_result()->fetch_assoc();
            $stmt_horario->close();
            if ($horario_ocupado) {
                throw new RuntimeException('Ese horario ya está ocupado. Elige otra fecha u hora antes de confirmar.');
            }

            $id_paciente = isset($_POST['id_paciente']) ? (int)$_POST['id_paciente'] : (int)$solicitud['id_paciente'];
            if ($id_paciente > 0) {
                $stmt_paciente = $conn->prepare("SELECT id_paciente FROM pacientes WHERE id_paciente = ? LIMIT 1");
                if (!$stmt_paciente) {
                    throw new RuntimeException('No se pudo validar el paciente seleccionado: ' . $conn->error);
                }
                $stmt_paciente->bind_param('i', $id_paciente);
                $stmt_paciente->execute();
                $paciente_seleccionado = $stmt_paciente->get_result()->fetch_assoc();
                $stmt_paciente->close();
                if (!$paciente_seleccionado) {
                    throw new RuntimeException('El paciente seleccionado ya no existe.');
                }
            }
            if (!$id_paciente) {
                $id_paciente = obtenerOCrearPacienteReservaWeb($conn, $solicitud);
            }

            $id_usuario = (int)$_SESSION['usuario_id'];
            $observaciones = trim('Solicitud web #' . $id . '. ' . (string)$solicitud['mensaje']);
            $stmt_cita = $conn->prepare("INSERT INTO reservas (id_paciente, id_servicio, id_usuario, fecha_reserva, hora_reserva, estado, observaciones) VALUES (?, ?, ?, ?, ?, 'confirmada', ?)");
            if (!$stmt_cita) {
                throw new RuntimeException('No se pudo preparar la cita: ' . $conn->error);
            }
            $stmt_cita->bind_param('iiisss', $id_paciente, $id_servicio, $id_usuario, $fecha_cita, $hora_cita, $observaciones);
            if (!$stmt_cita->execute()) {
                throw new RuntimeException('No se pudo crear la cita: ' . $stmt_cita->error);
            }
            $id_reserva_sistema = (int)$stmt_cita->insert_id;
            $stmt_cita->close();

            $stmt_actualizar = $conn->prepare("UPDATE reservas_rapidas SET id_paciente = ?, id_reserva_sistema = ?, estado = 'confirmada' WHERE id_reserva = ? AND id_reserva_sistema IS NULL");
            if (!$stmt_actualizar) {
                throw new RuntimeException('No se pudo enlazar la cita con la solicitud web: ' . $conn->error);
            }
            $stmt_actualizar->bind_param('iii', $id_paciente, $id_reserva_sistema, $id);
            if (!$stmt_actualizar->execute() || $stmt_actualizar->affected_rows !== 1) {
                throw new RuntimeException('La solicitud ya fue atendida o no se pudo enlazar con la cita.');
            }
            $stmt_actualizar->close();
            if (!$conn->commit()) {
                throw new RuntimeException('No se pudo guardar la cita y su vínculo con la solicitud.');
            }
            $transaccion_iniciada = false;
            $_SESSION['mensaje_web'] = 'Solicitud confirmada. El paciente y la cita ya están enlazados en el sistema.';
            $_SESSION['tipo_mensaje_web'] = 'success';
        } elseif ($accion === 'asociar_mensaje') {
            $id_paciente = isset($_POST['id_paciente']) ? (int)$_POST['id_paciente'] : 0;
            $stmt = $conn->prepare("UPDATE mensajes_contacto SET id_paciente = NULLIF(?, 0) WHERE id_mensaje = ?");
            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar la asociación del mensaje: ' . $conn->error);
            }
            $stmt->bind_param('ii', $id_paciente, $id);
            if (!$stmt->execute()) {
                throw new RuntimeException('No se pudo asociar el mensaje: ' . $stmt->error);
            }
            $stmt->close();
            $_SESSION['mensaje_web'] = 'La relación del mensaje con el paciente fue actualizada.';
            $_SESSION['tipo_mensaje_web'] = 'success';
        } elseif ($accion === 'leer_mensaje' || $accion === 'eliminar_mensaje') {
            $sql = $accion === 'leer_mensaje'
                ? "UPDATE mensajes_contacto SET estado = 'leido' WHERE id_mensaje = ?"
                : "DELETE FROM mensajes_contacto WHERE id_mensaje = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar la acción del mensaje: ' . $conn->error);
            }
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                throw new RuntimeException('No se pudo completar la acción del mensaje: ' . $stmt->error);
            }
            $stmt->close();
            $_SESSION['mensaje_web'] = $accion === 'leer_mensaje' ? 'Mensaje marcado como leído.' : 'Mensaje eliminado.';
            $_SESSION['tipo_mensaje_web'] = 'success';
        } elseif ($accion === 'eliminar_reserva_web') {
            $stmt = $conn->prepare("DELETE FROM reservas_rapidas WHERE id_reserva = ? AND id_reserva_sistema IS NULL");
            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar la eliminación: ' . $conn->error);
            }
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                throw new RuntimeException('No se pudo eliminar la solicitud: ' . $stmt->error);
            }
            $stmt->close();
            $_SESSION['mensaje_web'] = 'Solicitud eliminada.';
            $_SESSION['tipo_mensaje_web'] = 'success';
        } else {
            throw new RuntimeException('La acción solicitada no es válida.');
        }
    } catch (Throwable $error) {
        if ($transaccion_iniciada) {
            $conn->rollback();
        }
        $_SESSION['mensaje_web'] = $error->getMessage();
        $_SESSION['tipo_mensaje_web'] = 'danger';
    }

    header('Location: mensajes.php', true, 303);
    exit();
}

// Estadísticas
$total_reservas = 0;
$total_pendientes = 0;
$total_confirmadas = 0;

$res_total = $conn->query("SELECT COUNT(*) as total FROM reservas_rapidas");
if($res_total) $total_reservas = (int)$res_total->fetch_assoc()['total'];

$res_pend = $conn->query("SELECT COUNT(*) as total FROM reservas_rapidas WHERE estado <> 'cancelada' AND id_reserva_sistema IS NULL");
if($res_pend) $total_pendientes = (int)$res_pend->fetch_assoc()['total'];

$res_confirmadas = $conn->query("SELECT COUNT(*) as total FROM reservas_rapidas WHERE id_reserva_sistema IS NOT NULL");
if($res_confirmadas) $total_confirmadas = (int)$res_confirmadas->fetch_assoc()['total'];

$vincular_pacientes_existentes = $conn->query("UPDATE reservas_rapidas rw
    INNER JOIN pacientes p ON p.cedula = rw.cedula
    SET rw.id_paciente = p.id_paciente
    WHERE rw.id_paciente IS NULL
      AND rw.estado <> 'cancelada'
      AND rw.cedula IS NOT NULL
      AND rw.cedula <> ''");
if (!$vincular_pacientes_existentes) {
    die("Error al vincular pacientes ya registrados: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}

$reservas = $conn->query("SELECT rw.*, p.id_paciente AS paciente_enlazado,
        r.estado_atencion AS estado_atencion_cita
    FROM reservas_rapidas rw
    LEFT JOIN pacientes p ON p.id_paciente = rw.id_paciente
    LEFT JOIN reservas r ON r.id_reserva = rw.id_reserva_sistema
    ORDER BY rw.fecha_registro DESC");
if (!$reservas) {
    die("Error al consultar las reservas web: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
$mensajes_contacto = $conn->query("SELECT m.*, p.nombre AS paciente_nombre, p.apellido AS paciente_apellido
    FROM mensajes_contacto m LEFT JOIN pacientes p ON p.id_paciente = m.id_paciente
    ORDER BY m.fecha_envio DESC");
if (!$mensajes_contacto) {
    die("Error al consultar los mensajes web: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
$total_mensajes = (int)$conn->query("SELECT COUNT(*) AS total FROM mensajes_contacto")->fetch_assoc()['total'];
$total_no_leidos = (int)$conn->query("SELECT COUNT(*) AS total FROM mensajes_contacto WHERE estado = 'no_leido'")->fetch_assoc()['total'];
$servicios_para_cita = $conn->query("SELECT id_servicio, nombre_servicio FROM servicios WHERE estado IN ('activo', '1') ORDER BY nombre_servicio");
$servicios_activos = array();
if ($servicios_para_cita) {
    while ($servicio_activo = $servicios_para_cita->fetch_assoc()) {
        $servicios_activos[] = $servicio_activo;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Citas Web y Mensajes</title>

    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <link rel="stylesheet" href="./css/sweetalert2.min.css">
    <link rel="stylesheet" href="./css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>">
    <style>
        .web-request-confirm, .web-patient-registration-details { position: relative; }
        .web-request-confirm > summary, .web-patient-registration-details > summary { display: inline-flex; align-items: center; justify-content: center; cursor: pointer; list-style: none; min-height: 38px; }
        .web-request-confirm > summary::-webkit-details-marker { display: none; }
        .web-patient-registration-details > summary::-webkit-details-marker { display: none; }
        .web-request-confirm-form { display: grid; gap: 10px; min-width: 260px; padding: 18px; margin-top: 8px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 16px 40px rgba(15,23,42,.24); }
        .web-request-confirm[open] .web-request-confirm-form { position: fixed; top: 50%; left: 50%; z-index: 2000; width: min(440px, calc(100vw - 32px)); min-width: 0; max-height: calc(100vh - 32px); margin: 0; overflow-y: auto; transform: translate(-50%, -50%); }
        .web-patient-registration-details .web-patient-registration { display: grid; gap: 8px; min-width: 240px; padding: 14px; margin-top: 8px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 8px 24px rgba(15,23,42,.12); }
        .web-request-confirm-form label { display: grid; gap: 4px; font-size: 12px; font-weight: 700; }
        .web-request-confirm-form input, .web-request-confirm-form select, .web-message-link-form select { min-height: 40px; width: 100%; padding: 7px 9px; border: 1px solid #cbd5e1; border-radius: 7px; background: #fff; }
        .web-request-patient { display: grid; gap: 6px; min-width: 190px; }
        .web-request-patient strong { color: #1e293b; }
        .web-request-patient small, .web-request-appointment small { color: #64748b; }
        .web-request-appointment { display: grid; gap: 5px; min-width: 150px; }
        .web-request-appointment strong { color: #1e293b; }
        .web-request-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; min-width: 170px; }
        .web-patient-actions form, .web-request-actions form { margin: 0; }
        .web-request-actions .btn { min-height: 40px; }
        .web-patient-registration { display: grid; gap: 6px; width: 100%; min-width: 190px; }
        .web-patient-registration input { width: 100%; min-height: 38px; padding: 7px 9px; border: 1px solid #cbd5e1; border-radius: 7px; background: #fff; }
        .web-patient-registration label { display: grid; gap: 3px; margin: 0; color: #475569; font-size: 11px; font-weight: 700; }
        .web-new-patient-title { color: #334155; font-size: 12px; }
        .web-patient-saved { display: inline-flex; align-items: center; gap: 6px; width: fit-content; padding: 5px 9px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; }
        .web-patient-new { display: inline-flex; align-items: center; gap: 6px; width: fit-content; padding: 5px 9px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 700; }
        .web-request-status { display: grid; justify-items: start; gap: 7px; min-width: 165px; }
        .web-appointment-link { color: #1d4ed8; font-size: 11px; font-weight: 700; }
        .web-attendance-actions { display: flex; flex-wrap: wrap; gap: 5px; }
        .web-attendance-button { min-height: 34px; padding: 6px 9px; border: 1px solid #cbd5e1; border-radius: 7px; background: #fff; color: #475569; font: inherit; font-size: 11px; font-weight: 700; cursor: pointer; }
        .web-attendance-button-done { border-color: #86efac; background: #dcfce7; color: #166534; }
        .web-attendance-button:hover, .web-attendance-button:focus-visible { filter: brightness(.96); outline: 2px solid rgba(37,99,235,.25); outline-offset: 1px; }
        .web-message-summary { display: grid; gap: 5px; min-width: 150px; max-width: 360px; }
        .web-message-summary strong { color: #1e293b; }
        .web-message-preview { display: -webkit-box; overflow: hidden; color: #64748b; font-size: 12px; line-height: 1.45; overflow-wrap: anywhere; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .web-complaint-label { display: inline-flex; align-items: center; width: fit-content; padding: 3px 8px; border-radius: 999px; background: #fff1f2; color: #be123c; font-size: 11px; font-weight: 700; }
        .web-message-detail-button { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 40px; padding: 7px 10px; border: 1px solid #bfdbfe; border-radius: 10px; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 700; }
        .web-message-detail-button:hover, .web-message-detail-button:focus-visible { border-color: #60a5fa; background: #dbeafe; color: #1e40af; }
        .web-message-problem { padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; color: #334155; white-space: pre-wrap; overflow-wrap: anywhere; }
        .web-message-contact-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
        .web-message-contact-actions a { display: inline-flex; align-items: center; gap: 7px; min-height: 40px; padding: 8px 12px; border-radius: 8px; font-weight: 700; }
        .web-message-email-action { background: #eff6ff; color: #1d4ed8; }
        .web-message-whatsapp-action { background: #dcfce7; color: #166534; }
        .web-message-link-form { display: grid; gap: 6px; min-width: 190px; }
        @media (max-width: 576px) {
            .web-message-link-form { min-width: 210px; }
            .web-request-actions { min-width: 240px; }
        }
    </style>
</head>
<body>
    
    <main class="full-box main-container">
        <!-- Sidebar -->
        <section class="nav-lateral">
            <div class="nav-lateral-bg show-nav-lateral"></div>
            <div class="nav-lateral-content">
                <figure class="nav-lateral-avatar">
                    <i class="far fa-times-circle show-nav-lateral"></i>
                    <img src="./aurea.jpg" alt="Logo de AureaMed">
                    <figcaption>
                        <?php echo htmlspecialchars($nombre_usuario); ?><br>
                        <small><?php echo htmlspecialchars($rol_usuario); ?> &bull; AureaMed</small>
                    </figcaption>
                </figure>
                <div class="nav-lateral-bar"></div>
                <nav class="nav-lateral-menu">
                    <ul>
                        <li><a href="panel.php"><i class="fas fa-th-large fa-fw"></i> &nbsp; Inicio</a></li>
                        <li>
                            <a href="#" class="nav-btn-submenu"><i class="fas fa-users fa-fw"></i> &nbsp; Pacientes <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="paciente_nuevo.php"><i class="fas fa-user-plus fa-fw"></i> &nbsp; Registrar Paciente</a></li>
                                <li><a href="pacientes.php"><i class="fas fa-clipboard-list fa-fw"></i> &nbsp; Lista de Pacientes</a></li>
                                <li><a href="paciente_buscar.php"><i class="fas fa-search fa-fw"></i> &nbsp; Buscar Paciente</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="#" class="nav-btn-submenu"><i class="fas fa-stethoscope fa-fw"></i> &nbsp; Servicios <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="servicio_nuevo.php"><i class="fas fa-plus-circle fa-fw"></i> &nbsp; Registrar Servicio</a></li>
                                <li><a href="servicios.php"><i class="fas fa-clipboard-list fa-fw"></i> &nbsp; Lista de Servicios</a></li>
                                <li><a href="servicio_buscar.php"><i class="fas fa-search fa-fw"></i> &nbsp; Buscar Servicio</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="#" class="nav-btn-submenu"><i class="fas fa-calendar-alt fa-fw"></i> &nbsp; Reservas <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="reserva_nueva.php"><i class="fas fa-calendar-plus fa-fw"></i> &nbsp; Nueva Reserva</a></li>
                                <li><a href="reservas.php"><i class="fas fa-calendar-check fa-fw"></i> &nbsp; Lista de Reservas</a></li>
                                <li><a href="reserva_pendientes.php"><i class="fas fa-clock fa-fw"></i> &nbsp; Reservas Pendientes</a></li>
                                <li><a href="reserva_buscar.php"><i class="fas fa-search fa-fw"></i> &nbsp; Buscar Reserva</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="#" class="nav-btn-submenu"><i class="fas fa-file-invoice-dollar fa-fw"></i> &nbsp; Facturación <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="facturas.php"><i class="fas fa-list-alt fa-fw"></i> &nbsp; Lista de Facturas</a></li>
                                <li><a href="pagos_servicio.php"><i class="fas fa-dollar-sign fa-fw"></i> &nbsp; Registrar Pago</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="mensajes.php" class="active">
                                <i class="fas fa-envelope-open-text fa-fw"></i> &nbsp; Citas / Reclamos Web
                                <?php if($total_pendientes > 0): ?>
                                    <span class="badge badge-pill badge-warning ml-auto" style="font-size: 11px; background: #ffc107; color: #000; font-weight: 700;"><?php echo $total_pendientes; ?></span>
                                <?php endif; ?>
                                <?php if($total_no_leidos > 0): ?>
                                    <span class="badge badge-pill ml-1" title="Reclamos y mensajes sin leer" style="font-size: 11px; background: #dc3545; color: #fff; font-weight: 700;"><?php echo $total_no_leidos; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-btn-submenu"><i class="fas fa-chart-bar fa-fw"></i> &nbsp; Reportes <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="reporte_citas.php"><i class="fas fa-calendar-alt fa-fw"></i> &nbsp; Reporte de Citas</a></li>
                                <li><a href="reporte_pacientes.php"><i class="fas fa-user-check fa-fw"></i> &nbsp; Pacientes Atendidos</a></li>
                                <li><a href="reporte_servicios.php"><i class="fas fa-chart-pie fa-fw"></i> &nbsp; Servicios Solicitados</a></li>
                            </ul>
                        </li>
                        <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                        <li>
                            <a href="#" class="nav-btn-submenu"><i class="fas fa-user-shield fa-fw"></i> &nbsp; Usuarios <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="usuario_nuevo.php"><i class="fas fa-user-plus fa-fw"></i> &nbsp; Nuevo Usuario</a></li>
                                <li><a href="usuarios.php"><i class="fas fa-users-cog fa-fw"></i> &nbsp; Lista de Usuarios</a></li>
                                <li><a href="usuario_buscar.php"><i class="fas fa-search fa-fw"></i> &nbsp; Buscar Usuario</a></li>
                            </ul>
                        </li>
                        <?php endif; ?>
                        <li><a href="empresa.php"><i class="fas fa-hospital-alt fa-fw"></i> &nbsp; Centro Auditivo</a></li>
                    </ul>
                </nav>
            </div>
        </section>

        <!-- Contenido -->
        <section class="page-content">
            <nav class="navbar-info">
                <div class="navbar-info-left">
                    <button type="button" class="show-nav-lateral btn-icon" title="Ocultar/Mostrar Menú">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="navbar-brand-title d-none d-sm-flex">
                        <i class="fas fa-globe text-primary"></i> Ventas y Leyes
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-envelope-open-text"></i> SOLICITUDES Y CITAS WEB</h3>
                <p>Agenda las citas y marca fácilmente cuando el paciente ya fue atendido.</p>
            </div>

            <div class="container-fluid">
                <?php if ($mensaje_web !== ''): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($tipo_mensaje_web, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                        <?php echo htmlspecialchars($mensaje_web, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <!-- Métricas de Citas Web -->
                <div class="metrics-grid">
                    <div class="metric-card card-pacientes">
                        <div class="metric-info">
                            <div class="metric-title">Solicitudes recibidas</div>
                            <div class="metric-value"><?php echo $total_reservas; ?></div>
                            <div class="metric-subtext"><i class="fas fa-globe text-primary"></i> Recibidas desde la web</div>
                        </div>
                        <div class="metric-icon-box"><i class="fas fa-inbox"></i></div>
                    </div>

                    <div class="metric-card card-pendientes">
                        <div class="metric-info">
                            <div class="metric-title">Sin cita agendada</div>
                            <div class="metric-value" style="color: #d97706;"><?php echo $total_pendientes; ?></div>
                            <div class="metric-subtext"><i class="fas fa-clock text-warning"></i> Falta agendar fecha y hora</div>
                        </div>
                        <div class="metric-icon-box"><i class="fas fa-clock"></i></div>
                    </div>

                    <div class="metric-card card-servicios">
                        <div class="metric-info">
                            <div class="metric-title">Citas agendadas</div>
                            <div class="metric-value" style="color: #10b981;"><?php echo $total_confirmadas; ?></div>
                            <div class="metric-subtext"><i class="fas fa-check-circle text-success"></i> Ya aparecen en el calendario</div>
                        </div>
                        <div class="metric-icon-box"><i class="fas fa-check-double"></i></div>
                    </div>

                    <div class="metric-card card-pacientes">
                        <div class="metric-info">
                            <div class="metric-title">Mensajes de contacto</div>
                            <div class="metric-value"><?php echo $total_mensajes; ?></div>
                            <div class="metric-subtext"><i class="fas fa-envelope text-primary"></i> <?php echo $total_no_leidos; ?> sin leer</div>
                        </div>
                        <div class="metric-icon-box"><i class="fas fa-envelope"></i></div>
                    </div>
                </div>

                <!-- Tabla de Solicitudes -->
                <div class="table-container">
                    <div class="table-container-header">
                        <h5 class="table-container-title">
                            <i class="fas fa-list text-primary"></i> Solicitudes de citas
                        </h5>
                        <small class="text-muted">Revisa la cita y pulsa “Agendar cita”. El paciente nuevo se guardará automáticamente.</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>PACIENTE</th>
                                    <th>CITA SOLICITADA</th>
                                    <th>ESTADO DE LA CITA</th>
                                    <th>QUÉ HACER</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($reservas && $reservas->num_rows > 0): ?>
                                    <?php while($row = $reservas->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="web-request-patient">
                                                    <strong><?php echo htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                    <small>Cédula: <?php echo htmlspecialchars($row['cedula'] ?: 'No indicada', ENT_QUOTES, 'UTF-8'); ?></small>
                                                    <?php
                                                    $partes_nombre_web = preg_split('/\s+/', trim((string)$row['nombre']), 2);
                                                    $nombre_web = isset($partes_nombre_web[0]) ? $partes_nombre_web[0] : '';
                                                    $apellido_web = isset($partes_nombre_web[1]) ? $partes_nombre_web[1] : '';
                                                    $falta_cedula_web = empty($row['cedula']);
                                                    $falta_apellido_web = $apellido_web === '';
                                                    ?>
                                                    <?php if (!empty($row['id_paciente'])): ?>
                                                        <span class="web-patient-saved"><i class="fas fa-user-check" aria-hidden="true"></i> Paciente registrado</span>
                                                        <a class="btn btn-outline-primary btn-sm" href="paciente_editar.php?id=<?php echo (int)$row['id_paciente']; ?>">
                                                            <i class="fas fa-folder-open" aria-hidden="true"></i> Ver ficha
                                                        </a>
                                                    <?php elseif ($row['estado'] !== 'cancelada' && !$falta_cedula_web && !$falta_apellido_web): ?>
                                                        <span class="web-patient-new"><i class="fas fa-user-plus" aria-hidden="true"></i> Nuevo: se guarda al agendar</span>
                                                    <?php elseif ($row['estado'] !== 'cancelada'): ?>
                                                        <details class="web-patient-registration-details">
                                                            <summary class="btn btn-outline-warning btn-sm">Completar datos</summary>
                                                            <form method="post" class="web-patient-registration">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                                <input type="hidden" name="accion" value="guardar_paciente_web">
                                                                <input type="hidden" name="id" value="<?php echo (int)$row['id_reserva']; ?>">
                                                                <small class="text-muted"><?php echo $falta_cedula_web ? 'Falta la cédula' : 'Falta el apellido'; ?> para guardar este paciente.</small>
                                                                <label>Cédula
                                                                    <input type="text" name="cedula" maxlength="20" value="<?php echo htmlspecialchars((string)$row['cedula'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Número de documento" autocomplete="off" required>
                                                                </label>
                                                                <label>Nombre
                                                                    <input type="text" name="nombre_paciente" maxlength="100" value="<?php echo htmlspecialchars($nombre_web, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nombre del paciente" autocomplete="given-name">
                                                                </label>
                                                                <label>Apellido
                                                                    <input type="text" name="apellido_paciente" maxlength="100" value="<?php echo htmlspecialchars($apellido_web, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Apellido del paciente" autocomplete="family-name">
                                                                </label>
                                                                <button type="submit" class="btn btn-outline-primary btn-sm">Guardar datos</button>
                                                            </form>
                                                        </details>
                                                    <?php else: ?>
                                                        <span class="text-muted">Solicitud cancelada</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="web-request-appointment">
                                                    <strong><?php echo htmlspecialchars($row['servicio'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                    <span><?php echo date('d/m/Y', strtotime($row['fecha'])); ?><?php echo !empty($row['hora']) ? ' · ' . htmlspecialchars($row['hora'], ENT_QUOTES, 'UTF-8') : ''; ?></span>
                                                    <small>Solicitada el <?php echo date('d/m/Y', strtotime($row['fecha_registro'])); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['id_reserva_sistema'])): ?>
                                                    <div class="web-request-status">
                                                        <span class="badge-confirmada">Cita agendada</span>
                                                        <a class="web-appointment-link" href="reservas.php#cita-<?php echo (int)$row['id_reserva_sistema']; ?>">Ver cita #<?php echo (int)$row['id_reserva_sistema']; ?></a>
                                                        <?php
                                                        $estado_atencion_web = $row['estado_atencion_cita'] ?: 'pendiente';
                                                        $clase_atencion_web = $estado_atencion_web === 'atendida'
                                                            ? 'attendance-badge attendance-done'
                                                            : ($estado_atencion_web === 'no_asistio' ? 'attendance-badge attendance-missed' : 'attendance-badge attendance-waiting');
                                                        $etiqueta_atencion_web = $estado_atencion_web === 'atendida'
                                                            ? 'Atendido'
                                                            : ($estado_atencion_web === 'no_asistio' ? 'No asistió' : 'Pendiente');
                                                        ?>
                                                        <span class="<?php echo $clase_atencion_web; ?>">
                                                            <i class="fas <?php echo $estado_atencion_web === 'atendida' ? 'fa-user-check' : ($estado_atencion_web === 'no_asistio' ? 'fa-user-times' : 'fa-clock'); ?>"></i>
                                                            <?php echo $etiqueta_atencion_web; ?>
                                                        </span>
                                                        <?php if ($row['estado'] !== 'cancelada'): ?>
                                                            <form method="post" class="web-attendance-actions" aria-label="Cambiar atención de la cita">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                                <input type="hidden" name="accion" value="actualizar_atencion_web">
                                                                <input type="hidden" name="id" value="<?php echo (int)$row['id_reserva']; ?>">
                                                                <?php if ($estado_atencion_web !== 'atendida'): ?>
                                                                    <button type="submit" name="estado_atencion" value="atendida" class="web-attendance-button web-attendance-button-done">
                                                                        <i class="fas fa-check" aria-hidden="true"></i> Marcar atendido
                                                                    </button>
                                                                <?php endif; ?>
                                                                <?php if ($estado_atencion_web !== 'no_asistio'): ?>
                                                                    <button type="submit" name="estado_atencion" value="no_asistio" class="web-attendance-button">
                                                                        No asistió
                                                                    </button>
                                                                <?php endif; ?>
                                                                <?php if ($estado_atencion_web !== 'pendiente'): ?>
                                                                    <button type="submit" name="estado_atencion" value="pendiente" class="web-attendance-button">
                                                                        Volver a pendiente
                                                                    </button>
                                                                <?php endif; ?>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($row['estado'] === 'cancelada'): ?>
                                                    <span class="badge badge-secondary">Cancelada</span>
                                                <?php else: ?>
                                                    <span class="badge-pendiente">Falta agendar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="web-request-actions">
                                                    <button type="button" class="btn btn-info btn-sm btn-ver-reserva"
                                                            data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                                            data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                                            data-telefono="<?php echo htmlspecialchars($row['telefono']); ?>"
                                                            data-servicio="<?php echo htmlspecialchars($row['servicio']); ?>"
                                                            data-fecha="<?php echo date('d/m/Y', strtotime($row['fecha'])); ?>"
                                                            data-hora="<?php echo htmlspecialchars($row['hora']); ?>"
                                                            data-mensaje="<?php echo htmlspecialchars($row['mensaje']); ?>"
                                                            title="Ver Detalle y Mensaje">
                                                        <i class="fas fa-eye" aria-hidden="true"></i> Ver solicitud
                                                    </button>
                                                    <?php if (empty($row['id_reserva_sistema']) && $row['estado'] !== 'cancelada'): ?>
                                                        <details class="web-request-confirm">
                                                            <summary class="btn btn-success btn-sm"><i class="fas fa-calendar-check" aria-hidden="true"></i> Agendar cita</summary>
                                                            <form method="post" class="web-request-confirm-form">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                                <input type="hidden" name="accion" value="confirmar_reserva_web">
                                                                <input type="hidden" name="id" value="<?php echo (int)$row['id_reserva']; ?>">
                                                                <small class="text-muted">Confirma el servicio, la fecha y la hora. El paciente se guardará o se vinculará automáticamente.</small>
                                                                <label>Servicio
                                                                    <select name="id_servicio" required>
                                                                        <option value="">Selecciona un servicio</option>
                                                                        <?php $servicio_sugerido = obtenerServicioReservaWeb($conn, $row['servicio']); ?>
                                                                        <?php foreach ($servicios_activos as $servicio_activo): ?>
                                                                            <option value="<?php echo (int)$servicio_activo['id_servicio']; ?>"<?php echo $servicio_sugerido === (int)$servicio_activo['id_servicio'] ? ' selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars($servicio_activo['nombre_servicio'], ENT_QUOTES, 'UTF-8'); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </label>
                                                                <label>Fecha
                                                                    <input type="date" name="fecha" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($row['fecha'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                                </label>
                                                                <label>Hora
                                                                    <select name="hora" required>
                                                                        <option value="">Selecciona una hora</option>
                                                                        <?php foreach (array('09:00', '10:00', '11:00', '15:00', '16:00', '17:00') as $hora_disponible): ?>
                                                                            <option value="<?php echo $hora_disponible; ?>"<?php echo $row['hora'] === $hora_disponible ? ' selected' : ''; ?>>
                                                                                <?php echo $hora_disponible; ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </label>
                                                                <button type="submit" class="btn btn-success"><i class="fas fa-check" aria-hidden="true"></i> Confirmar cita</button>
                                                            </form>
                                                        </details>
                                                    <?php endif; ?>
                                                    <?php if (empty($row['id_reserva_sistema']) && $row['estado'] !== 'cancelada'): ?>
                                                        <form method="post" onsubmit="return confirm('¿Eliminar esta solicitud web?');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="accion" value="eliminar_reserva_web">
                                                            <input type="hidden" name="id" value="<?php echo (int)$row['id_reserva']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            No hay solicitudes de citas web registradas en la bandeja.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-container-header">
                        <h5 class="table-container-title"><i class="fas fa-envelope text-primary"></i> Reclamos y mensajes recibidos desde la web</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Nombre y reclamo</th>
                                    <th>Ver detalle</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($mensajes_contacto->num_rows > 0): ?>
                                    <?php while ($mensaje = $mensajes_contacto->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_envio'])); ?></td>
                                            <td>
                                                <div class="web-message-summary">
                                                    <strong><?php echo htmlspecialchars($mensaje['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                    <?php if (strtolower(trim((string)$mensaje['asunto'])) === 'reclamo'): ?>
                                                        <span class="web-complaint-label"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> Reclamo</span>
                                                    <?php else: ?>
                                                        <span><?php echo htmlspecialchars($mensaje['asunto'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                    <small class="web-message-preview"><?php echo htmlspecialchars($mensaje['mensaje'], ENT_QUOTES, 'UTF-8'); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php $es_reclamo_web = strtolower(trim((string)$mensaje['asunto'])) === 'reclamo'; ?>
                                                <button
                                                    type="button"
                                                    class="web-message-detail-button btn-ver-mensaje"
                                                    title="<?php echo $es_reclamo_web ? 'Ver reclamo completo' : 'Ver mensaje completo'; ?>"
                                                    aria-label="<?php echo $es_reclamo_web ? 'Ver reclamo de ' : 'Ver mensaje de '; ?><?php echo htmlspecialchars($mensaje['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-nombre="<?php echo htmlspecialchars($mensaje['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-asunto="<?php echo htmlspecialchars($mensaje['asunto'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-mensaje="<?php echo htmlspecialchars($mensaje['mensaje'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-email="<?php echo htmlspecialchars($mensaje['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-telefono="<?php echo htmlspecialchars($mensaje['telefono'], ENT_QUOTES, 'UTF-8'); ?>"
                                                >
                                                    <i class="fas fa-comment-medical" aria-hidden="true"></i>
                                                    <?php echo $es_reclamo_web ? 'Ver reclamo' : 'Ver mensaje'; ?>
                                                </button>
                                            </td>
                                            <td><?php echo $mensaje['estado'] === 'leido' ? 'Leído' : 'Nuevo'; ?></td>
                                            <td>
                                                <div class="patient-actions">
                                                    <?php if ($mensaje['estado'] !== 'leido'): ?>
                                                        <form method="post">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="accion" value="leer_mensaje">
                                                            <input type="hidden" name="id" value="<?php echo (int)$mensaje['id_mensaje']; ?>">
                                                            <button class="btn btn-sm btn-outline-success" type="submit">Marcar leído</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="post" onsubmit="return confirm('¿Eliminar este mensaje?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input type="hidden" name="accion" value="eliminar_mensaje">
                                                        <input type="hidden" name="id" value="<?php echo (int)$mensaje['id_mensaje']; ?>">
                                                        <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">Todavía no hay mensajes de contacto recibidos desde la web.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal Detalle de Cita Web -->
    <div class="modal fade" id="modalReserva" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #003366, #0056b3); color: white; padding: 18px 24px;">
                    <h5 class="modal-title" style="font-weight: 700; font-size: 17px;"><i class="fas fa-calendar-check mr-2"></i> Detalle de Solicitud Web</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="font-size: 24px; opacity: 0.9; background: none; border: none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <p class="mb-2"><strong>👤 Paciente:</strong> <span id="reservaNombre"></span></p>
                    <p class="mb-2"><strong>📧 Correo:</strong> <span id="reservaEmail"></span></p>
                    <p class="mb-2"><strong>📞 Teléfono:</strong> <span id="reservaTelefono"></span></p>
                    <p class="mb-2"><strong>🩺 Servicio:</strong> <span id="reservaServicio"></span></p>
                    <p class="mb-2"><strong>📅 Fecha Solicitada:</strong> <span id="reservaFecha"></span></p>
                    <p class="mb-2"><strong>⏰ Horario:</strong> <span id="reservaHora"></span></p>
                    <hr>
                    <p class="mb-2"><strong>💬 Mensaje Adicional del Paciente:</strong></p>
                    <div id="reservaMensaje" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px; border-radius: 10px; font-size: 14px; color: #334155;"></div>
                </div>
                <div class="modal-footer" style="padding: 15px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalMensajeContacto" tabindex="-1" role="dialog" aria-labelledby="modalMensajeContactoTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #003366, #0056b3); color: white; padding: 18px 24px;">
                    <h5 class="modal-title" id="modalMensajeContactoTitulo" style="font-weight: 700; font-size: 17px;"><i class="fas fa-comment-medical mr-2" aria-hidden="true"></i> Reclamo o mensaje recibido</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar" style="font-size: 24px; opacity: 0.9; background: none; border: none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <p class="mb-2"><strong>Nombre:</strong> <span id="mensajeContactoNombre"></span></p>
                    <p class="mb-3"><strong>Asunto:</strong> <span id="mensajeContactoAsunto"></span></p>
                    <div class="web-message-problem" id="mensajeContactoTexto"></div>
                    <div class="web-message-contact-actions">
                        <a id="mensajeContactoEmail" class="web-message-email-action" href="#">
                            <i class="fas fa-envelope" aria-hidden="true"></i> Responder por correo
                        </a>
                        <a id="mensajeContactoWhatsapp" class="web-message-whatsapp-action" href="#" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-whatsapp" aria-hidden="true"></i> Contactar por WhatsApp
                        </a>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 15px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="./js/jquery-3.4.1.min.js"></script>
    <script src="./js/popper.min.js"></script>
    <script src="./js/bootstrap.min.js"></script>
    <script src="./js/sweetalert2.min.js"></script>
    <script src="./js/main.js"></script>
    <script>
        $(document).ready(function() {
            $('.btn-ver-reserva').on('click', function(e) {
                e.preventDefault();
                $('#reservaNombre').text($(this).data('nombre'));
                $('#reservaEmail').text($(this).data('email'));
                $('#reservaTelefono').text($(this).data('telefono') || 'No especificado');
                $('#reservaServicio').text($(this).data('servicio'));
                $('#reservaFecha').text($(this).data('fecha'));
                $('#reservaHora').text($(this).data('hora') || 'Sin preferencia');
                $('#reservaMensaje').text($(this).data('mensaje') || 'Sin mensaje adicional.');
                $('#modalReserva').modal('show');
            });

            $('.btn-ver-mensaje').on('click', function() {
                var boton = $(this);
                var telefono = String(boton.data('telefono') || '').replace(/\D/g, '');
                var email = String(boton.data('email') || '');
                if (telefono.length === 8) {
                    telefono = '591' + telefono;
                }
                $('#mensajeContactoNombre').text(boton.data('nombre') || 'Paciente');
                $('#mensajeContactoAsunto').text(boton.data('asunto') || 'Sin asunto');
                $('#mensajeContactoTexto').text(boton.data('mensaje') || 'El paciente no agregó detalles.');
                $('#mensajeContactoEmail').attr('href', 'mailto:' + email);
                $('#mensajeContactoWhatsapp').attr(
                    'href',
                    telefono ? 'https://wa.me/' + telefono : '#'
                ).toggle(telefono.length >= 8);
                $('#modalMensajeContacto').modal('show');
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>