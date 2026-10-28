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
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$buscar_sql = $conn->real_escape_string($buscar);

$sql = "SELECT p.id_paciente, p.cedula, p.nombre, p.apellido, p.email, p.telefono, p.fecha_registro, 
        COUNT(r.id_reserva) as total_reservas,
        SUM(CASE WHEN r.estado IN ('completada', 'atendida') THEN 1 ELSE 0 END) as total_atendidas
        FROM pacientes p
        LEFT JOIN reservas r ON p.id_paciente = r.id_paciente
        WHERE p.fecha_registro BETWEEN '" . $conn->real_escape_string($fecha_inicio) . "' AND '" . $conn->real_escape_string($fecha_fin) . "'
        AND ('" . $buscar_sql . "' = '' OR CONCAT(p.nombre, ' ', p.apellido) LIKE '%" . $buscar_sql . "%' OR p.cedula LIKE '%" . $buscar_sql . "%')
        GROUP BY p.id_paciente
        ORDER BY total_reservas DESC, p.nombre ASC";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Reporte de Pacientes</title>

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
                        <li><a href="mensajes.php"><i class="fas fa-envelope-open-text fa-fw"></i> &nbsp; Citas Web / Mensajes</a></li>
                        <li>
                            <a href="#" class="nav-btn-submenu active"><i class="fas fa-chart-bar fa-fw"></i> &nbsp; Reportes <i class="fas fa-chevron-down"></i></a>
                            <ul class="show-nav-lateral-submenu">
                                <li><a href="reporte_citas.php"><i class="fas fa-calendar-alt fa-fw"></i> &nbsp; Reporte de Citas</a></li>
                                <li><a href="reporte_pacientes.php" class="active"><i class="fas fa-user-check fa-fw"></i> &nbsp; Pacientes Atendidos</a></li>
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
                        <i class="fas fa-user-check text-primary"></i> Ventas y Leyes
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-user-check"></i> HISTORIAL DE PACIENTES</h3>
                <p>Consulte la frecuencia de consultas y citas médicas de cada paciente registrado.</p>
            </div>

            <div class="container-fluid">
                <form method="get" class="report-filter-form report-filter-form-wide">
                    <div class="report-filter-field report-search-field"><label for="buscar">Buscar</label><input type="search" id="buscar" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>" placeholder="Paciente o cédula..."></div>
                    <div class="report-filter-field"><label for="metodo">Método</label><select id="metodo" name="metodo"><option value="todos">Todos</option></select></div>
                    <div class="report-filter-field"><label for="fecha_inicio">Desde</label><input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required></div>
                    <div class="report-filter-field"><label for="fecha_fin">Hasta</label><input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" required></div>
                    <button type="submit" class="btn report-filter-submit"><i class="fas fa-filter"></i> Filtrar</button>
                    <button type="button" class="btn btn-primary report-export-button" onclick="window.print();"><i class="fas fa-file-pdf"></i> Generar PDF</button>
                    <button type="button" class="btn btn-success report-export-button" onclick="exportarTablaXLS('tabla-reporte-pacientes', 'reporte-pacientes');"><i class="fas fa-file-excel"></i> Exportar a XLS</button>
                    <a href="reporte_pacientes.php" class="report-filter-clear" title="Limpiar filtros" aria-label="Limpiar filtros">&times;</a>
                </form>
            </div>

            <div class="container-fluid">
                <ul class="page-nav-tabs">
                    <li><a href="reporte_citas.php"><i class="fas fa-calendar-alt"></i> REPORTE DE CITAS</a></li>
                    <li><a class="active" href="reporte_pacientes.php"><i class="fas fa-user-check"></i> PACIENTES ATENDIDOS</a></li>
                    <li><a href="reporte_servicios.php"><i class="fas fa-chart-pie"></i> SERVICIOS SOLICITADOS</a></li>
                </ul>    
            </div>
            
            <div class="container-fluid">
                <div class="table-container print-report-sheet">
                    <div class="print-report-header">
                        <img src="./aurea.jpg" alt="Logo AureaMed">
                        <div><h1>AureaMed | Reporte de Pacientes</h1><p>Historial de pacientes registrados en el sistema.</p><strong>Rango consultado: <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></strong></div>
                    </div>
                    <div class="table-container-header">
                        <h5 class="table-container-title"><i class="fas fa-users text-primary"></i> Frecuencia de Pacientes</h5>
                    </div>
                    <div class="table-responsive">
                        <table id="tabla-reporte-pacientes" class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>CÉDULA</th>
                                    <th>PACIENTE</th>
                                    <th>TELÉFONO</th>
                                    <th>EMAIL</th>
                                    <th>TOTAL CITAS</th>
                                    <th>REGISTRO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($resultado && $resultado->num_rows > 0): ?>
                                    <?php while($row = $resultado->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $row['id_paciente']; ?></strong></td>
                                            <td><span class="badge badge-light"><?php echo htmlspecialchars($row['cedula']); ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellido']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['telefono']) ?: '-'; ?></td>
                                            <td><?php echo htmlspecialchars($row['email']) ?: '-'; ?></td>
                                            <td><span class="badge badge-info" style="font-size: 13px; font-weight: 700; padding: 4px 10px;"><?php echo (int)$row['total_reservas']; ?> cita(s)</span></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['fecha_registro'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No hay pacientes registrados para generar el reporte.
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
    <script src="./js/reportes.js"></script>
    <script src="./js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
