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

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Personal';

$id_reserva = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id_reserva == 0) {
    header("Location: reservas.php");
    exit();
}

$sql = "SELECT r.*, 
        p.nombre as paciente_nombre, 
        p.apellido as paciente_apellido,
        p.cedula,
        p.telefono,
        s.nombre_servicio,
        s.precio,
        u.nombre as usuario_nombre
        FROM reservas r
        JOIN pacientes p ON r.id_paciente = p.id_paciente
        JOIN servicios s ON r.id_servicio = s.id_servicio
        JOIN usuarios u ON r.id_usuario = u.id_usuario
        WHERE r.id_reserva = $id_reserva";

$resultado = $conn->query($sql);

if($resultado->num_rows == 0) {
    header("Location: reservas.php");
    exit();
}

$reserva = $resultado->fetch_assoc();

$pacientes = $conn->query("SELECT id_paciente, nombre, apellido, cedula FROM pacientes ORDER BY nombre ASC");
$servicios = $conn->query("SELECT id_servicio, nombre_servicio, precio, duracion_minutos FROM servicios WHERE estado = 'activo' OR estado = 1 ORDER BY nombre_servicio ASC");

$error = '';
$exito = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_paciente = (int)$_POST['id_paciente'];
    $id_servicio = (int)$_POST['id_servicio'];
    $fecha_reserva = $_POST['fecha_reserva'];
    $hora_reserva = $_POST['hora_reserva'];
    $estado = $_POST['estado'];
    $observaciones = trim($_POST['observaciones']);
    
    if($id_paciente <= 0 || $id_servicio <= 0) {
        $error = "Debe seleccionar un paciente y un servicio.";
    } elseif(empty($fecha_reserva) || empty($hora_reserva)) {
        $error = "La fecha y hora de la reserva son obligatorias.";
    } else {
        $check = $conn->prepare("SELECT id_reserva FROM reservas WHERE fecha_reserva = ? AND hora_reserva = ? AND id_reserva != ? AND estado NOT IN ('cancelada')");
        $check->bind_param("ssi", $fecha_reserva, $hora_reserva, $id_reserva);
        $check->execute();
        $check->store_result();
        
        if($check->num_rows > 0) {
            $error = "Ya existe otra cita médica registrada en ese mismo horario.";
        } else {
            $stmt = $conn->prepare("UPDATE reservas SET id_paciente = ?, id_servicio = ?, fecha_reserva = ?, hora_reserva = ?, estado = ?, observaciones = ? WHERE id_reserva = ?");
            $stmt->bind_param("iissssi", $id_paciente, $id_servicio, $fecha_reserva, $hora_reserva, $estado, $observaciones, $id_reserva);
            
            if($stmt->execute()) {
                $exito = "Reserva actualizada exitosamente.";
                $sql_act = "SELECT r.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.cedula, p.telefono, s.nombre_servicio, s.precio FROM reservas r JOIN pacientes p ON r.id_paciente = p.id_paciente JOIN servicios s ON r.id_servicio = s.id_servicio WHERE r.id_reserva = $id_reserva";
                $res_act = $conn->query($sql_act);
                $reserva = $res_act->fetch_assoc();
            } else {
                $error = "Error al actualizar la reserva: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Editar Reserva</title>

    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <link rel="stylesheet" href="./css/sweetalert2.min.css">
    <link rel="stylesheet" href="./css/style.css">
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
                        <i class="fas fa-edit text-primary"></i> Ventas y Leyes
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-edit"></i> EDITAR DETALLES DE RESERVA</h3>
                <p>Reagende la fecha, modifique el servicio asignado o actualice el estado de la cita médica.</p>
            </div>

            <div class="container-fluid">
                <ul class="page-nav-tabs">
                    <li><a href="reserva_nueva.php"><i class="fas fa-plus"></i> NUEVA RESERVA</a></li>
                    <li><a href="reservas.php"><i class="fas fa-clipboard-list"></i> LISTA DE RESERVAS</a></li>
                    <li><a href="reserva_pendientes.php"><i class="fas fa-clock"></i> RESERVAS PENDIENTES</a></li>
                    <li><a href="reserva_buscar.php"><i class="fas fa-search"></i> BUSCAR RESERVA</a></li>
                </ul>    
            </div>

            <?php if($error): ?>
                <div class="container-fluid">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>❌ Error:</strong> <?php echo $error; ?>
                        <button type="button" class="close" data-dismiss="alert" style="background:none;border:none;font-size:20px;"><span>&times;</span></button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($exito): ?>
                <div class="container-fluid">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>✅ Éxito:</strong> <?php echo $exito; ?>
                        <a href="pagos_servicio.php?id_reserva=<?php echo $id_reserva; ?>" class="btn btn-sm btn-info ml-2"><i class="fas fa-dollar-sign"></i> Ir a Pago / Comprobante</a>
                        <button type="button" class="close" data-dismiss="alert" style="background:none;border:none;font-size:20px;"><span>&times;</span></button>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-10 offset-lg-1">
                        <form method="POST" action="" class="form-neon">
                            <fieldset>
                                <legend><i class="fas fa-calendar-alt"></i> Cita #<?php echo $id_reserva; ?> - Paciente: <?php echo htmlspecialchars($reserva['paciente_nombre'] . ' ' . $reserva['paciente_apellido']); ?></legend>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_paciente">Paciente <span class="text-danger">*</span></label>
                                            <select class="form-control" id="id_paciente" name="id_paciente" required>
                                                <?php if($pacientes && $pacientes->num_rows > 0): ?>
                                                    <?php while($p = $pacientes->fetch_assoc()): ?>
                                                        <option value="<?php echo $p['id_paciente']; ?>" <?php echo ($reserva['id_paciente'] == $p['id_paciente']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido'] . ' (CI: ' . $p['cedula'] . ')'); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_servicio">Servicio Solicitado <span class="text-danger">*</span></label>
                                            <select class="form-control" id="id_servicio" name="id_servicio" required>
                                                <?php if($servicios && $servicios->num_rows > 0): ?>
                                                    <?php while($s = $servicios->fetch_assoc()): ?>
                                                        <option value="<?php echo $s['id_servicio']; ?>" <?php echo ($reserva['id_servicio'] == $s['id_servicio']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($s['nombre_servicio'] . ' - Bs. ' . number_format($s['precio'], 2)); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="fecha_reserva">Fecha de la Cita <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="fecha_reserva" name="fecha_reserva" 
                                                   value="<?php echo $reserva['fecha_reserva']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="hora_reserva">Hora <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control" id="hora_reserva" name="hora_reserva" 
                                                   value="<?php echo substr($reserva['hora_reserva'], 0, 5); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="estado">Estado de la Cita</label>
                                            <select class="form-control" id="estado" name="estado">
                                                <option value="pendiente" <?php echo ($reserva['estado'] == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                                <option value="confirmada" <?php echo ($reserva['estado'] == 'confirmada') ? 'selected' : ''; ?>>Confirmada</option>
                                                <option value="atendida" <?php echo ($reserva['estado'] == 'atendida') ? 'selected' : ''; ?>>Atendida</option>
                                                <option value="completada" <?php echo ($reserva['estado'] == 'completada') ? 'selected' : ''; ?>>Completada (Pagada)</option>
                                                <option value="cancelada" <?php echo ($reserva['estado'] == 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="observaciones">Observaciones / Anotaciones</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($reserva['observaciones']); ?></textarea>
                                </div>

                                <div class="form-group text-center mt-4 mb-0">
                                    <button type="submit" class="btn btn-success btn-lg mr-2">
                                        <i class="fas fa-sync-alt"></i> ACTUALIZAR RESERVA
                                    </button>
                                    <a href="reservas.php" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-arrow-left"></i> VOLVER A LA LISTA
                                    </a>
                                </div>
                            </fieldset>
                        </form>
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
</body>
</html>
<?php $conn->close(); ?>