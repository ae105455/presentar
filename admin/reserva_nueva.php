<?php
session_start();
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'aureamed');
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Personal';

$pacientes = $conn->query("SELECT id_paciente, nombre, apellido, cedula FROM pacientes ORDER BY nombre ASC");
$servicios = $conn->query("SELECT id_servicio, nombre_servicio, precio, duracion_minutos FROM servicios WHERE estado = 'activo' OR estado = 1 ORDER BY nombre_servicio ASC");

$pre_paciente = isset($_GET['id_paciente']) ? (int)$_GET['id_paciente'] : 0;
$pre_servicio = isset($_GET['id_servicio']) ? (int)$_GET['id_servicio'] : 0;

$error = '';
$exito = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_paciente = (int)$_POST['id_paciente'];
    $id_servicio = (int)$_POST['id_servicio'];
    $fecha_reserva = $_POST['fecha_reserva'];
    $hora_reserva = $_POST['hora_reserva'];
    $estado = isset($_POST['estado']) ? $_POST['estado'] : 'pendiente';
    $observaciones = trim($_POST['observaciones']);
    $id_usuario = (int)$_SESSION['usuario_id'];
    
    if($id_paciente <= 0 || $id_servicio <= 0) {
        $error = "Debe seleccionar un paciente y un servicio de la lista.";
    } elseif(empty($fecha_reserva) || empty($hora_reserva)) {
        $error = "La fecha y la hora de la reserva son obligatorias.";
    } else {
        $check = $conn->prepare("SELECT id_reserva FROM reservas WHERE fecha_reserva = ? AND hora_reserva = ? AND estado NOT IN ('cancelada')");
        $check->bind_param("ss", $fecha_reserva, $hora_reserva);
        $check->execute();
        $check->store_result();
        
        if($check->num_rows > 0) {
            $error = "Ya existe una cita médica agendada para esa fecha y hora. Elija otro horario.";
        } else {
            $stmt = $conn->prepare("INSERT INTO reservas (id_paciente, id_servicio, id_usuario, fecha_reserva, hora_reserva, estado, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiissss", $id_paciente, $id_servicio, $id_usuario, $fecha_reserva, $hora_reserva, $estado, $observaciones);
            
            if($stmt->execute()) {
                $exito = "Reserva agendada exitosamente en el sistema.";
                $_POST = array();
            } else {
                $error = "Error al crear la reserva: " . $stmt->error;
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
    <title>AureaMed | Nueva Reserva</title>

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
                                <li><a href="reserva_nueva.php" class="active"><i class="fas fa-calendar-plus fa-fw"></i> &nbsp; Nueva Reserva</a></li>
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
                        <i class="fas fa-calendar-plus text-primary"></i> Ventas y Leyes
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-calendar-plus"></i> AGENDAR NUEVA CITA MÉDICA</h3>
                <p>Seleccione el paciente, el servicio auditivo, fecha y hora para programar la atención.</p>
            </div>

            <div class="container-fluid">
                <ul class="page-nav-tabs">
                    <li><a class="active" href="reserva_nueva.php"><i class="fas fa-plus"></i> NUEVA RESERVA</a></li>
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
                        <a href="reservas.php" class="btn btn-sm btn-success ml-2">Ver Lista de Reservas</a>
                        <button type="button" class="close" data-dismiss="alert" style="background:none;border:none;font-size:20px;"><span>&times;</span></button>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-10 offset-lg-1">
                        <form method="POST" action="" class="form-neon">
                            <fieldset>
                                <legend><i class="fas fa-calendar-alt"></i> Datos de la Cita Médica</legend>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_paciente">Paciente <span class="text-danger">*</span></label>
                                            <select class="form-control" id="id_paciente" name="id_paciente" required>
                                                <option value="">-- Seleccione un paciente --</option>
                                                <?php if($pacientes && $pacientes->num_rows > 0): ?>
                                                    <?php while($p = $pacientes->fetch_assoc()): ?>
                                                        <option value="<?php echo $p['id_paciente']; ?>" <?php echo ($pre_paciente == $p['id_paciente']) ? 'selected' : ''; ?>>
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
                                                <option value="">-- Seleccione un servicio --</option>
                                                <?php if($servicios && $servicios->num_rows > 0): ?>
                                                    <?php while($s = $servicios->fetch_assoc()): ?>
                                                        <option value="<?php echo $s['id_servicio']; ?>" <?php echo ($pre_servicio == $s['id_servicio']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($s['nombre_servicio'] . ' - Bs. ' . number_format($s['precio'], 2) . ' (' . $s['duracion_minutos'] . ' min)'); ?>
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
                                                   value="<?php echo isset($_POST['fecha_reserva']) ? $_POST['fecha_reserva'] : date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="hora_reserva">Hora de la Cita <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control" id="hora_reserva" name="hora_reserva" 
                                                   value="<?php echo isset($_POST['hora_reserva']) ? $_POST['hora_reserva'] : '09:00'; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="estado">Estado Inicial</label>
                                            <select class="form-control" id="estado" name="estado">
                                                <option value="pendiente">Pendiente (Por confirmar)</option>
                                                <option value="confirmada" selected>Confirmada</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="observaciones">Observaciones / Motivo de Consulta</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" 
                                              placeholder="Detalles sobre síntomas, antecedentes auditivos o notas del paciente..." rows="3"><?php echo isset($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones']) : ''; ?></textarea>
                                </div>

                                <div class="form-group text-center mt-4 mb-0">
                                    <button type="submit" class="btn btn-success btn-lg mr-2">
                                        <i class="fas fa-calendar-check"></i> CONFIRMAR Y GUARDAR RESERVA
                                    </button>
                                    <a href="reservas.php" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-times"></i> CANCELAR
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