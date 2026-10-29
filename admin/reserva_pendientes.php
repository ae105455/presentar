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

$sql = "SELECT r.*, 
        p.nombre as paciente_nombre, 
        p.apellido as paciente_apellido,
        p.telefono as paciente_telefono,
        s.nombre_servicio,
        s.precio
        FROM reservas r
        JOIN pacientes p ON r.id_paciente = p.id_paciente
        JOIN servicios s ON r.id_servicio = s.id_servicio
        WHERE r.estado = 'pendiente'
        ORDER BY r.fecha_reserva ASC, r.hora_reserva ASC";

$resultado = $conn->query($sql);
$total_pendientes = $resultado ? $resultado->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Reservas Pendientes</title>

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
                                <li><a href="reservas.php"><i class="fas fa-calendar-check fa-fw"></i> &nbsp; Lista de Reservas</a></li>
                                <li><a href="reserva_pendientes.php" class="active"><i class="fas fa-clock fa-fw"></i> &nbsp; Reservas Pendientes</a></li>
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
                        <i class="fas fa-clock text-warning"></i> Ventas y Leyes
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-clock text-warning"></i> RESERVAS PENDIENTES DE CONFIRMACIÓN</h3>
                <p>Revise las solicitudes de citas que aún requieren ser confirmadas o canceladas.</p>
            </div>

            <div class="container-fluid">
                <ul class="page-nav-tabs">
                    <li><a href="reserva_nueva.php"><i class="fas fa-plus"></i> NUEVA RESERVA</a></li>
                    <li><a href="reservas.php"><i class="fas fa-clipboard-list"></i> LISTA DE RESERVAS</a></li>
                    <li><a class="active" href="reserva_pendientes.php"><i class="fas fa-clock"></i> RESERVAS PENDIENTES (<?php echo $total_pendientes; ?>)</a></li>
                    <li><a href="reserva_buscar.php"><i class="fas fa-search"></i> BUSCAR RESERVA</a></li>
                </ul>    
            </div>
            
            <div class="container-fluid">
                <div class="table-container">
                    <div class="table-container-header">
                        <h5 class="table-container-title">
                            <i class="fas fa-hourglass-half text-warning"></i> Citas Médicas por Confirmar (Total: <?php echo $total_pendientes; ?>)
                        </h5>
                        <a href="reserva_nueva.php" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Nueva Cita</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>PACIENTE</th>
                                    <th>TELÉFONO</th>
                                    <th>SERVICIO</th>
                                    <th>FECHA CITA</th>
                                    <th>HORA</th>
                                    <th>ESTADO</th>
                                    <th>ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($resultado && $resultado->num_rows > 0): ?>
                                    <?php while($row = $resultado->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $row['id_reserva']; ?></strong></td>
                                            <td>
                                                <i class="fas fa-user-circle text-muted mr-1"></i>
                                                <strong><?php echo htmlspecialchars($row['paciente_nombre'] . ' ' . $row['paciente_apellido']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['paciente_telefono']) ?: '-'; ?></td>
                                            <td><span class="badge badge-light"><?php echo htmlspecialchars($row['nombre_servicio']); ?></span></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['fecha_reserva'])); ?></td>
                                            <td><strong><?php echo substr($row['hora_reserva'], 0, 5); ?></strong></td>
                                            <td><span class="badge-pendiente">⏳ Pendiente</span></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="reserva_confirmar.php?id=<?php echo $row['id_reserva']; ?>" class="btn btn-success btn-sm" title="Confirmar Cita">
                                                        <i class="fas fa-check"></i> Confirmar
                                                    </a>
                                                    <a href="reserva_editar.php?id=<?php echo $row['id_reserva']; ?>" class="btn btn-primary btn-sm" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="reserva_cancelar.php?id=<?php echo $row['id_reserva']; ?>&ref=reserva_pendientes" class="btn btn-danger btn-sm" title="Cancelar Cita" onclick="return confirm('¿Está seguro de cancelar esta reserva pendiente?');">
                                                        <i class="fas fa-times"></i> Cancelar
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="fas fa-check-circle fa-2x mb-2 text-success d-block"></i>
                                            ¡Excelente! No hay reservas pendientes por confirmar en este momento.
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
</body>
</html>
<?php $conn->close(); ?>