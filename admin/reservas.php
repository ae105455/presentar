<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/payment_helpers.php';
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
try {
    asegurarTablaPagos($conn);
    asegurarEstadoAtencionReservas($conn);
} catch (RuntimeException $error) {
    http_response_code(500);
    die(htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8'));
}

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Personal';
$csrf_estado_atencion = isset($_SESSION['csrf_estado_atencion'])
    ? $_SESSION['csrf_estado_atencion']
    : bin2hex(random_bytes(32));
$_SESSION['csrf_estado_atencion'] = $csrf_estado_atencion;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_atencion'])) {
    $csrf_recibido = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $id_reserva_atencion = isset($_POST['id_reserva']) ? (int)$_POST['id_reserva'] : 0;
    $estado_atencion = isset($_POST['estado_atencion']) && is_string($_POST['estado_atencion'])
        ? $_POST['estado_atencion']
        : '';
    $estados_atencion_validos = array('pendiente', 'atendida', 'no_asistio');

    if (!hash_equals($csrf_estado_atencion, $csrf_recibido)) {
        $_SESSION['mensaje_atencion'] = 'La sesión venció. Recarga la página e inténtalo nuevamente.';
        $_SESSION['tipo_mensaje_atencion'] = 'danger';
    } elseif ($id_reserva_atencion <= 0 || !in_array($estado_atencion, $estados_atencion_validos, true)) {
        $_SESSION['mensaje_atencion'] = 'Selecciona un estado de atención válido.';
        $_SESSION['tipo_mensaje_atencion'] = 'danger';
    } else {
        $verificar_cita = $conn->prepare("SELECT id_reserva FROM reservas WHERE id_reserva = ? AND estado <> 'cancelada'");
        if (!$verificar_cita) {
            $_SESSION['mensaje_atencion'] = 'No se pudo validar la cita: ' . $conn->error;
            $_SESSION['tipo_mensaje_atencion'] = 'danger';
        } else {
            $verificar_cita->bind_param('i', $id_reserva_atencion);
            $verificar_cita->execute();
            $cita_valida = $verificar_cita->get_result()->fetch_assoc();
            $verificar_cita->close();
            if (!$cita_valida) {
                $_SESSION['mensaje_atencion'] = 'La cita no existe o fue cancelada; no se puede actualizar su asistencia.';
                $_SESSION['tipo_mensaje_atencion'] = 'danger';
            }
        }

        if (isset($cita_valida) && $cita_valida) {
            $actualizar_atencion = $conn->prepare("UPDATE reservas SET estado_atencion = ? WHERE id_reserva = ? AND estado <> 'cancelada'");
            if (!$actualizar_atencion) {
                $_SESSION['mensaje_atencion'] = 'No se pudo preparar la actualización: ' . $conn->error;
                $_SESSION['tipo_mensaje_atencion'] = 'danger';
            } else {
                $actualizar_atencion->bind_param('si', $estado_atencion, $id_reserva_atencion);
                if ($actualizar_atencion->execute()) {
                    $_SESSION['mensaje_atencion'] = 'Se actualizó el control de atención de la cita #' . $id_reserva_atencion . '.';
                    $_SESSION['tipo_mensaje_atencion'] = 'success';
                } else {
                    $_SESSION['mensaje_atencion'] = 'No se pudo guardar el estado de atención: ' . $actualizar_atencion->error;
                    $_SESSION['tipo_mensaje_atencion'] = 'danger';
                }
                $actualizar_atencion->close();
            }
        } elseif (!isset($cita_valida)) {
            $_SESSION['mensaje_atencion'] = 'No se pudo validar la cita; inténtalo nuevamente.';
            $_SESSION['tipo_mensaje_atencion'] = 'danger';
        }
    }

    header('Location: reservas.php', true, 303);
    exit();
}

$mensaje_atencion = isset($_SESSION['mensaje_atencion']) ? $_SESSION['mensaje_atencion'] : '';
$tipo_mensaje_atencion = isset($_SESSION['tipo_mensaje_atencion']) ? $_SESSION['tipo_mensaje_atencion'] : 'success';
unset($_SESSION['mensaje_atencion'], $_SESSION['tipo_mensaje_atencion']);

$sql = "SELECT r.*, 
        p.nombre as paciente_nombre, 
        p.apellido as paciente_apellido,
        p.telefono as paciente_telefono,
        s.nombre_servicio,
        s.precio as servicio_precio,
        u.nombre as usuario_nombre,
        COALESCE(pagos.total_pagado, 0) as total_pagado,
        pagos.ultimo_pago_id
        FROM reservas r
        JOIN pacientes p ON r.id_paciente = p.id_paciente
        JOIN servicios s ON r.id_servicio = s.id_servicio
        JOIN usuarios u ON r.id_usuario = u.id_usuario
        LEFT JOIN (
            SELECT id_reserva, SUM(monto) as total_pagado, MAX(id_pago) as ultimo_pago_id
            FROM pagos
            GROUP BY id_reserva
        ) pagos ON pagos.id_reserva = r.id_reserva
        ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";

$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Lista de Reservas</title>

    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <link rel="stylesheet" href="./css/sweetalert2.min.css">
    <link rel="stylesheet" href="./css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>">
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
                            <a href="#" class="nav-btn-submenu active"><i class="fas fa-calendar-alt fa-fw"></i> &nbsp; Reservas <i class="fas fa-chevron-down"></i></a>
                            <ul class="show-nav-lateral-submenu">
                                <li><a href="reserva_nueva.php"><i class="fas fa-calendar-plus fa-fw"></i> &nbsp; Nueva Reserva</a></li>
                                <li><a href="reservas.php" class="active"><i class="fas fa-calendar-check fa-fw"></i> &nbsp; Lista de Reservas</a></li>
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
                        <li><a href="mensajes.php"><i class="fas fa-envelope-open-text fa-fw"></i> &nbsp; Citas Web / Mensajes</a></li>
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
                        <i class="fas fa-calendar-alt text-primary"></i> Ventas y Leyes
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-calendar-alt"></i> LISTA DE RESERVAS</h3>
                <p>Revise las citas, el estado del pago y registre si cada paciente fue atendido.</p>
            </div>

            <div class="container-fluid">
                <?php if ($mensaje_atencion !== ''): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($tipo_mensaje_atencion, ENT_QUOTES, 'UTF-8'); ?>" role="status">
                        <?php echo htmlspecialchars($mensaje_atencion, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <ul class="page-nav-tabs">
                    <li><a href="reserva_nueva.php"><i class="fas fa-plus"></i> NUEVA RESERVA</a></li>
                    <li><a class="active" href="reservas.php"><i class="fas fa-clipboard-list"></i> LISTA DE RESERVAS</a></li>
                    <li><a href="reserva_pendientes.php"><i class="fas fa-clock"></i> RESERVAS PENDIENTES</a></li>
                    <li><a href="reserva_buscar.php"><i class="fas fa-search"></i> BUSCAR RESERVA</a></li>
                </ul>    
            </div>
            
            <div class="container-fluid">
                <div class="table-container">
                    <div class="table-container-header">
                        <div>
                            <h5 class="table-container-title"><i class="fas fa-calendar-check text-primary"></i> Todas las Citas Registradas</h5>
                            <small class="text-muted">Toca una opción para registrar la asistencia. Este dato no cambia el pago.</small>
                        </div>
                        <a href="reserva_nueva.php" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Agendar Nueva Cita</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>PACIENTE</th>
                                    <th>SERVICIO</th>
                                    <th>FECHA CITA</th>
                                    <th>HORA</th>
                                    <th>ESTADO DE CITA</th>
                                    <th>REGISTRO DE ATENCIÓN</th>
                                    <th>PAGO</th>
                                    <th>REGISTRÓ CITA</th>
                                    <th>ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($resultado && $resultado->num_rows > 0): ?>
                                    <?php while($row = $resultado->fetch_assoc()): ?>
                                        <?php
                                        $badge_class = 'badge-pendiente';
                                        switch($row['estado']) {
                                            case 'confirmada': $badge_class = 'badge-confirmada'; break;
                                            case 'atendida': 
                                            case 'completada': $badge_class = 'badge-atendida'; break;
                                            case 'cancelada': $badge_class = 'badge-cancelada'; break;
                                        }
                                        ?>
                                        <tr id="cita-<?php echo (int)$row['id_reserva']; ?>">
                                            <td><strong>#<?php echo $row['id_reserva']; ?></strong></td>
                                            <td>
                                                <i class="fas fa-user-circle text-muted mr-1"></i>
                                                <strong><?php echo htmlspecialchars($row['paciente_nombre'] . ' ' . $row['paciente_apellido']); ?></strong>
                                            </td>
                                            <td><span class="badge badge-light"><?php echo htmlspecialchars($row['nombre_servicio']); ?></span></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['fecha_reserva'])); ?></td>
                                            <td><strong><?php echo substr($row['hora_reserva'], 0, 5); ?></strong></td>
                                            <td><span class="<?php echo $badge_class; ?>"><?php echo ucfirst($row['estado']); ?></span></td>
                                            <td>
                                                <?php
                                                $estado_atencion = isset($row['estado_atencion']) ? $row['estado_atencion'] : 'pendiente';
                                                $opciones_atencion = array(
                                                    'pendiente' => 'Pendiente',
                                                    'atendida' => 'Atendido',
                                                    'no_asistio' => 'No asistió'
                                                );
                                                ?>
                                                <div class="attendance-cell">
                                                    <?php if ($row['estado'] !== 'cancelada'): ?>
                                                        <form method="post" class="attendance-actions" aria-label="Registrar atención de <?php echo htmlspecialchars($row['paciente_nombre'] . ' ' . $row['paciente_apellido'], ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_estado_atencion, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <input type="hidden" name="actualizar_atencion" value="1">
                                                            <input type="hidden" name="id_reserva" value="<?php echo (int)$row['id_reserva']; ?>">
                                                            <?php foreach ($opciones_atencion as $valor_atencion => $texto_atencion): ?>
                                                                <?php
                                                                $icono_atencion = $valor_atencion === 'atendida'
                                                                    ? 'fa-user-check'
                                                                    : ($valor_atencion === 'no_asistio' ? 'fa-user-times' : 'fa-clock');
                                                                $clase_opcion = $valor_atencion === $estado_atencion
                                                                    ? 'attendance-option attendance-option-current attendance-option-' . $valor_atencion
                                                                    : 'attendance-option';
                                                                ?>
                                                                <button
                                                                    type="submit"
                                                                    class="<?php echo $clase_opcion; ?>"
                                                                    name="estado_atencion"
                                                                    value="<?php echo htmlspecialchars($valor_atencion, ENT_QUOTES, 'UTF-8'); ?>"
                                                                    aria-pressed="<?php echo $valor_atencion === $estado_atencion ? 'true' : 'false'; ?>"
                                                                    <?php echo $valor_atencion === $estado_atencion ? 'disabled' : ''; ?>
                                                                >
                                                                    <i class="fas <?php echo $icono_atencion; ?>" aria-hidden="true"></i>
                                                                    <?php echo htmlspecialchars($texto_atencion, ENT_QUOTES, 'UTF-8'); ?>
                                                                </button>
                                                            <?php endforeach; ?>
                                                        </form>
                                                        <small class="attendance-hint">Elige una opción para guardar</small>
                                                    <?php else: ?>
                                                        <span class="attendance-badge attendance-missed"><i class="fas fa-ban" aria-hidden="true"></i> Cita cancelada</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if($row['ultimo_pago_id']): ?>
                                                    <span class="badge badge-success">Pagado · Bs. <?php echo number_format((float)$row['total_pagado'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">No pagado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($row['usuario_nombre']); ?></small></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="reserva_editar.php?id=<?php echo $row['id_reserva']; ?>" class="btn btn-primary btn-sm" title="Editar Reserva">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="pagos_servicio.php?id_reserva=<?php echo $row['id_reserva']; ?>" class="btn btn-info btn-sm" title="Pago / Comprobante">
                                                        <i class="fas fa-dollar-sign"></i>
                                                    </a>
                                                    <?php if($row['ultimo_pago_id']): ?>
                                                    <a href="factura_ver.php?id=<?php echo (int)$row['ultimo_pago_id']; ?>" class="btn btn-success btn-sm" title="Ver factura">
                                                        <i class="fas fa-file-invoice"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php if($row['estado'] !== 'cancelada'): ?>
                                                    <button type="button" class="btn btn-danger btn-sm btn-cancelar-reserva" 
                                                            data-id="<?php echo $row['id_reserva']; ?>" 
                                                            data-paciente="<?php echo htmlspecialchars($row['paciente_nombre'] . ' ' . $row['paciente_apellido']); ?>"
                                                            title="Cancelar Reserva">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4 text-muted">
                                            <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                                            No hay citas o reservas registradas en el sistema.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <script src="./js/jquery-3.4.1.min.js"></script>
    <script src="./js/popper.min.js"></script>
    <script src="./js/bootstrap.min.js"></script>
    <script src="./js/sweetalert2.min.js"></script>
    <script src="./js/main.js"></script>
    <script>
        $(document).ready(function() {
            $('.btn-cancelar-reserva').on('click', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var paciente = $(this).data('paciente');
                
                Swal.fire({
                    title: '¿Cancelar cita médica?',
                    html: `¿Está seguro de cambiar el estado a <strong>Cancelada</strong> para la cita de <strong>${paciente}</strong>?`,
                    icon: 'warning',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-ban"></i> Sí, cancelar cita',
                    cancelButtonText: 'No, mantener',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed || result.value === true || result.value) {
                        window.location.href = `reserva_cancelar.php?id=${id}&ref=reservas`;
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>