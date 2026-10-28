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

// Estadísticas en tiempo real
$total_pacientes = 0;
$total_servicios = 0;
$total_reservas = 0;
$total_pendientes = 0;
$total_solicitudes_web = 0;
$total_usuarios = 0;

$res = $conn->query("SELECT COUNT(*) as total FROM pacientes");
if($res) $total_pacientes = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) as total FROM servicios WHERE estado = 'activo' OR estado = 1");
if($res) $total_servicios = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) as total FROM reservas");
if($res) $total_reservas = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) as total FROM reservas WHERE estado = 'pendiente'");
if($res) $total_pendientes = (int)$res->fetch_assoc()['total'];

$table_check = $conn->query("SHOW TABLES LIKE 'reservas_rapidas'");
if($table_check && $table_check->num_rows > 0) {
    $res = $conn->query("SELECT COUNT(*) as total FROM reservas_rapidas WHERE estado = 'pendiente'");
    if($res) $total_solicitudes_web = (int)$res->fetch_assoc()['total'];
}

$res = $conn->query("SELECT COUNT(*) as total FROM usuarios");
if($res) $total_usuarios = (int)$res->fetch_assoc()['total'];

// Últimas 6 reservas para la tabla
$ultimas_reservas = $conn->query("SELECT r.id_reserva, r.fecha_reserva, r.hora_reserva, r.estado,
                                         p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.telefono,
                                         s.nombre_servicio, s.precio
                                  FROM reservas r
                                  JOIN pacientes p ON r.id_paciente = p.id_paciente
                                  JOIN servicios s ON r.id_servicio = s.id_servicio
                                  ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC LIMIT 6");

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Personal';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Inicio Administrativo</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <link rel="stylesheet" href="./css/sweetalert2.min.css">
    <link rel="stylesheet" href="./css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <main class="main-container">
        
        <!-- Sidebar Navigation -->
        <aside class="nav-lateral">
            <div class="nav-lateral-bg show-nav-lateral"></div>
            <div class="nav-lateral-content">
                
                <!-- Logo & Brand Header -->
                <div class="sidebar-brand">
                    <div class="sidebar-brand-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <div class="sidebar-brand-text">
                        <h4>AureaMed</h4>
                        <span>Centro Auditivo</span>
                    </div>
                </div>

                <!-- User Profile Card  logo de aurea  esta aqui -->
                <div class="nav-lateral-avatar">
                    <i class="far fa-times-circle show-nav-lateral" title="Cerrar Menú"></i>
                    <img src="./aurea.jpg" alt="Logo de AureaMed">
                    <figcaption>
                        <div><?php echo htmlspecialchars($nombre_usuario); ?></div>
                        <small><i class="fas fa-shield-alt mr-1"></i><?php echo htmlspecialchars($rol_usuario); ?></small>
                    </figcaption>
                </div>
                
                <!-- Menu -->
                <nav class="nav-lateral-menu">
                    <p class="sidebar-section-label">Principal</p>
                    <ul>
                        <li>
                            <a href="panel.php" class="active">
                                <i class="fas fa-th-large fa-fw"></i> &nbsp; Inicio
                            </a>
                        </li>
                    </ul>

                    <p class="sidebar-section-label">Gestión Clínica</p>
                    <ul>
                        <li>
                            <a href="#" class="nav-btn-submenu">
                                <i class="fas fa-users fa-fw"></i> &nbsp; Pacientes <i class="fas fa-chevron-down"></i>
                            </a>
                            <ul>
                                <li><a href="paciente_nuevo.php"><i class="fas fa-user-plus fa-fw"></i> &nbsp; Registrar Paciente</a></li>
                                <li><a href="pacientes.php"><i class="fas fa-clipboard-list fa-fw"></i> &nbsp; Lista de Pacientes</a></li>
                                <li><a href="paciente_buscar.php"><i class="fas fa-search fa-fw"></i> &nbsp; Buscar Paciente</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#" class="nav-btn-submenu">
                                <i class="fas fa-stethoscope fa-fw"></i> &nbsp; Servicios <i class="fas fa-chevron-down"></i>
                            </a>
                            <ul>
                                <li><a href="servicio_nuevo.php"><i class="fas fa-plus-circle fa-fw"></i> &nbsp; Registrar Servicio</a></li>
                                <li><a href="servicios.php"><i class="fas fa-clipboard-list fa-fw"></i> &nbsp; Catálogo de Servicios</a></li>
                                <li><a href="servicio_buscar.php"><i class="fas fa-search fa-fw"></i> &nbsp; Buscar Servicio</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#" class="nav-btn-submenu">
                                <i class="fas fa-calendar-alt fa-fw"></i> &nbsp; Reservas / Citas <i class="fas fa-chevron-down"></i>
                            </a>
                            <ul>
                                <li><a href="reserva_nueva.php"><i class="fas fa-calendar-plus fa-fw"></i> &nbsp; Agendar Cita</a></li>
                                <li><a href="reservas.php"><i class="fas fa-calendar-check fa-fw"></i> &nbsp; Lista de Reservas</a></li>
                                <li><a href="reserva_pendientes.php"><i class="fas fa-clock fa-fw"></i> &nbsp; Citas Pendientes</a></li>
                                <li><a href="reserva_buscar.php"><i class="fas fa-search fa-fw"></i> &nbsp; Buscar Cita</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="#" class="nav-btn-submenu">
                                <i class="fas fa-file-invoice-dollar fa-fw"></i> &nbsp; Facturación <i class="fas fa-chevron-down"></i>
                            </a>
                            <ul>
                                <li><a href="facturas.php"><i class="fas fa-list-alt fa-fw"></i> &nbsp; Lista de Facturas</a></li>
                                <li><a href="pagos_servicio.php"><i class="fas fa-dollar-sign fa-fw"></i> &nbsp; Registrar Pago</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="mensajes.php">
                                <i class="fas fa-envelope-open-text fa-fw"></i> &nbsp; Citas Web / Mensajes
                                <?php if($total_solicitudes_web > 0): ?>
                                    <span class="badge badge-pill badge-warning ml-auto" style="font-size: 11px; background: #f59e0b; color: #fff; font-weight: 700; padding: 3px 8px;"><?php echo $total_solicitudes_web; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>

                    <p class="sidebar-section-label">Reportes y Ajustes</p>
                    <ul>
                        <li>
                            <a href="#" class="nav-btn-submenu">
                                <i class="fas fa-chart-bar fa-fw"></i> &nbsp; Reportes <i class="fas fa-chevron-down"></i>
                            </a>
                            <ul>
                                <li><a href="reporte_citas.php"><i class="fas fa-calendar-alt fa-fw"></i> &nbsp; Reporte de Citas</a></li>
                                <li><a href="reporte_pacientes.php"><i class="fas fa-user-check fa-fw"></i> &nbsp; Pacientes Atendidos</a></li>
                                <li><a href="reporte_servicios.php"><i class="fas fa-chart-pie fa-fw"></i> &nbsp; Demanda de Servicios</a></li>
                            </ul>
                        </li>

                        <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                        <li>
                            <a href="#" class="nav-btn-submenu">
                                <i class="fas fa-user-shield fa-fw"></i> &nbsp; Personal / Usuarios <i class="fas fa-chevron-down"></i>
                            </a>
                            <ul>
                                <li><a href="usuario_nuevo.php"><i class="fas fa-user-plus fa-fw"></i> &nbsp; Nuevo Usuario</a></li>
                                <li><a href="usuarios.php"><i class="fas fa-users-cog fa-fw"></i> &nbsp; Lista de Usuarios</a></li>
                                <li><a href="usuario_buscar.php"><i class="fas fa-search fa-fw"></i> &nbsp; Buscar Usuario</a></li>
                            </ul>
                        </li>
                        <?php endif; ?>

                        <li>
                            <a href="empresa.php">
                                <i class="fas fa-hospital-alt fa-fw"></i> &nbsp; Centro Auditivo
                            </a>
                        </li>
                        <li>
                            <a href="qr.php">
                                <i class="fas fa-qrcode fa-fw"></i> &nbsp; Generar códigos QR
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Main Page Content -->
        <section class="page-content">
            
            <!-- Top Navbar -->
            <header class="navbar-info">
                <div class="navbar-info-left">
                    <button type="button" class="btn-toggle-sidebar show-nav-lateral" title="Ocultar/Mostrar Menú">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h3 class="navbar-page-title d-none d-sm-flex">
                        <a href="panel.php" style="color: inherit; text-decoration: none;">
                            <i class="fas fa-clinic-medical text-primary"></i> AureaMed | Inicio Administrativo
                        </a>
                    </h3>
                </div>
                
                <div class="navbar-info-right">
                    <a href="perfil.php" class="user-badge-nav" title="Mi Cuenta">
                        <div class="user-avatar-mini">
                            <?php echo strtoupper(substr($nombre_usuario, 0, 1)); ?>
                        </div>
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span>
                    </a>
                    <a href="logout.php" class="btn-logout-nav btn-exit-system" title="Cerrar Sesión">
                        <i class="fas fa-power-off"></i>
                        <span class="d-none d-sm-inline">Salir</span>
                    </a>
                </div>
            </header>

            <div class="container-fluid" style="padding: 28px 32px;">
                
                <!-- Hero Welcome Banner -->
                <div class="dashboard-hero">
                    <div class="dashboard-hero-content">
                        <div class="dashboard-hero-text">
                            <h1>👋 ¡Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?>!</h1>
                            <p>Gestione eficientemente el flujo clínico de <strong>AureaMed</strong>. Monitoree citas programadas, historiales de pacientes y solicitudes web en tiempo real.</p>
                        </div>
                        <div class="dashboard-hero-actions">
                            <a href="reserva_nueva.php" class="btn-hero-primary">
                                <i class="fas fa-calendar-plus"></i> Agendar Cita
                            </a>
                            <a href="paciente_nuevo.php" class="btn-hero-secondary">
                                <i class="fas fa-user-plus"></i> Nuevo Paciente
                            </a>
                            <?php if($total_pendientes > 0): ?>
                            <a href="reserva_pendientes.php" class="btn-hero-warning">
                                <i class="fas fa-clock"></i> <?php echo $total_pendientes; ?> Pendientes
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- KPI Metric Cards Grid -->
                <div class="metrics-grid">
                    
                    <a href="pacientes.php" class="metric-card card-pacientes">
                        <div class="metric-content">
                            <div class="metric-label">Pacientes</div>
                            <div class="metric-number"><?php echo $total_pacientes; ?></div>
                            <div class="metric-footer-text">
                                <i class="fas fa-arrow-circle-right text-info"></i> Registrados en sistema
                            </div>
                        </div>
                        <div class="metric-icon-box">
                            <i class="fas fa-users"></i>
                        </div>
                    </a>

                    <a href="servicios.php" class="metric-card card-servicios">
                        <div class="metric-content">
                            <div class="metric-label">Servicios</div>
                            <div class="metric-number"><?php echo $total_servicios; ?></div>
                            <div class="metric-footer-text">
                                <i class="fas fa-check-circle text-success"></i> Catálogo activo
                            </div>
                        </div>
                        <div class="metric-icon-box">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                    </a>

                    <a href="reservas.php" class="metric-card card-reservas">
                        <div class="metric-content">
                            <div class="metric-label">Reservas Totales</div>
                            <div class="metric-number"><?php echo $total_reservas; ?></div>
                            <div class="metric-footer-text">
                                <i class="fas fa-calendar-check text-primary"></i> Historial de citas
                            </div>
                        </div>
                        <div class="metric-icon-box">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </a>

                    <a href="reserva_pendientes.php" class="metric-card card-pendientes">
                        <div class="metric-content">
                            <div class="metric-label">Citas Pendientes</div>
                            <div class="metric-number" style="color: #d97706;"><?php echo $total_pendientes; ?></div>
                            <div class="metric-footer-text">
                                <i class="fas fa-clock text-warning"></i> Por confirmar
                            </div>
                        </div>
                        <div class="metric-icon-box">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </a>

                    <a href="mensajes.php" class="metric-card card-mensajes">
                        <div class="metric-content">
                            <div class="metric-label">Citas Web</div>
                            <div class="metric-number" style="color: #db2777;"><?php echo $total_solicitudes_web; ?></div>
                            <div class="metric-footer-text">
                                <i class="fas fa-globe text-danger"></i> Solicitudes del portal
                            </div>
                        </div>
                        <div class="metric-icon-box">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                    </a>

                    <a href="usuarios.php" class="metric-card card-usuarios">
                        <div class="metric-content">
                            <div class="metric-label">Equipo / Personal</div>
                            <div class="metric-number" style="color: #7c3aed;"><?php echo $total_usuarios; ?></div>
                            <div class="metric-footer-text">
                                <i class="fas fa-user-shield text-purple"></i> Cuentas de acceso
                            </div>
                        </div>
                        <div class="metric-icon-box">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </a>

                </div>

                <!-- Shortcuts Section -->
                <h4 class="section-header-title">
                    <i class="fas fa-bolt text-warning"></i> Accesos Rápidos
                </h4>
                <div class="shortcuts-grid">
                    <a href="reserva_nueva.php" class="shortcut-card">
                        <div class="shortcut-icon"><i class="fas fa-calendar-plus"></i></div>
                        <div>
                            <div class="shortcut-title">Agendar Consulta</div>
                            <div class="shortcut-subtitle">Programar nueva cita médica</div>
                        </div>
                    </a>
                    <a href="paciente_nuevo.php" class="shortcut-card">
                        <div class="shortcut-icon"><i class="fas fa-user-plus"></i></div>
                        <div>
                            <div class="shortcut-title">Registrar Paciente</div>
                            <div class="shortcut-subtitle">Crear nuevo historial clínico</div>
                        </div>
                    </a>
                    <a href="reserva_pendientes.php" class="shortcut-card">
                        <div class="shortcut-icon" style="background: #fffbeb; color: #d97706;"><i class="fas fa-clock"></i></div>
                        <div>
                            <div class="shortcut-title">Citas Pendientes</div>
                            <div class="shortcut-subtitle">Confirmar o cancelar citas</div>
                        </div>
                    </a>
                    <a href="reporte_citas.php" class="shortcut-card">
                        <div class="shortcut-icon" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-file-invoice"></i></div>
                        <div>
                            <div class="shortcut-title">Reportes Clínicos</div>
                            <div class="shortcut-subtitle">Estadísticas e impresiones</div>
                        </div>
                    </a>
                </div>

                <!-- Recent Appointments Table Card -->
                <div class="table-container">
                    <div class="table-container-header">
                        <h5 class="table-container-title">
                            <i class="fas fa-calendar-check text-primary"></i> Citas Médicas Recientes
                        </h5>
                        <div>
                            <a href="reserva_nueva.php" class="btn btn-sm btn-success mr-2">
                                <i class="fas fa-plus"></i> Agendar Cita
                            </a>
                            <a href="reservas.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-list"></i> Ver Todas
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th># ID</th>
                                    <th>PACIENTE</th>
                                    <th>SERVICIO AUDITIVO</th>
                                    <th>FECHA DE ATENCIÓN</th>
                                    <th>HORA</th>
                                    <th>TARIFA (Bs.)</th>
                                    <th>ESTADO</th>
                                    <th>ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($ultimas_reservas && $ultimas_reservas->num_rows > 0): ?>
                                    <?php while($row = $ultimas_reservas->fetch_assoc()): ?>
                                        <?php
                                        $badge_class = 'badge-pendiente';
                                        switch($row['estado']) {
                                            case 'confirmada': $badge_class = 'badge-confirmada'; break;
                                            case 'atendida': 
                                            case 'completada': $badge_class = 'badge-atendida'; break;
                                            case 'cancelada': $badge_class = 'badge-cancelada'; break;
                                        }
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo $row['id_reserva']; ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; margin-right: 10px;">
                                                        <?php echo strtoupper(substr($row['paciente_nombre'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($row['paciente_nombre'] . ' ' . $row['paciente_apellido']); ?></strong>
                                                        <small class="d-block text-muted"><?php echo htmlspecialchars($row['telefono']) ?: 'Sin teléfono'; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge badge-light" style="font-size: 12.5px; font-weight: 600; padding: 4px 8px;"><?php echo htmlspecialchars($row['nombre_servicio']); ?></span></td>
                                            <td><strong><?php echo date('d/m/Y', strtotime($row['fecha_reserva'])); ?></strong></td>
                                            <td><span class="badge badge-secondary" style="font-size: 12px;"><?php echo substr($row['hora_reserva'], 0, 5); ?></span></td>
                                            <td><strong>Bs. <?php echo number_format($row['precio'], 2); ?></strong></td>
                                            <td>
                                                <span class="<?php echo $badge_class; ?>">
                                                    <i class="fas fa-circle" style="font-size: 7px;"></i> <?php echo ucfirst($row['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="reserva_editar.php?id=<?php echo $row['id_reserva']; ?>" class="btn btn-primary btn-sm" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="pagos_servicio.php?id_reserva=<?php echo $row['id_reserva']; ?>" class="btn btn-info btn-sm" title="Cobro / Recibo">
                                                        <i class="fas fa-dollar-sign"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="fas fa-calendar-times fa-3x mb-3 text-muted d-block"></i>
                                            No hay citas registradas en este momento.
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
    <script src="./js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>
<?php $conn->close(); ?>