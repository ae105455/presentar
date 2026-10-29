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

$busqueda = '';
$resultado = null;
$mostrar_resultados = false;

if(isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $busqueda = trim($_GET['buscar']);
    $mostrar_resultados = true;
    
    $sql = "SELECT id_paciente, cedula, nombre, apellido, telefono, email, fecha_registro 
            FROM pacientes 
            WHERE cedula LIKE ? 
            OR nombre LIKE ? 
            OR apellido LIKE ? 
            OR telefono LIKE ?
            ORDER BY nombre ASC";
    
    $busqueda_like = "%$busqueda%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $busqueda_like, $busqueda_like, $busqueda_like, $busqueda_like);
    $stmt->execute();
    $resultado = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Buscar Pacientes</title>

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
                            <a href="#" class="nav-btn-submenu active"><i class="fas fa-users fa-fw"></i> &nbsp; Pacientes <i class="fas fa-chevron-down"></i></a>
                            <ul class="show-nav-lateral-submenu">
                                <li><a href="paciente_nuevo.php"><i class="fas fa-user-plus fa-fw"></i> &nbsp; Registrar Paciente</a></li>
                                <li><a href="pacientes.php"><i class="fas fa-clipboard-list fa-fw"></i> &nbsp; Lista de Pacientes</a></li>
                                <li><a href="paciente_buscar.php" class="active"><i class="fas fa-search fa-fw"></i> &nbsp; Buscar Paciente</a></li>
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
                        <i class="fas fa-search text-primary"></i> Ventas y Leyes
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-search"></i> BUSCAR PACIENTE</h3>
                <p>Encuentre expedientes de pacientes por Cédula de Identidad, Nombre, Apellido o Teléfono.</p>
            </div>

            <div class="container-fluid">
                <ul class="page-nav-tabs">
                    <li><a href="paciente_nuevo.php"><i class="fas fa-plus"></i> REGISTRAR PACIENTE</a></li>
                    <li><a href="pacientes.php"><i class="fas fa-clipboard-list"></i> LISTA DE PACIENTES</a></li>
                    <li><a class="active" href="paciente_buscar.php"><i class="fas fa-search"></i> BUSCAR PACIENTE</a></li>
                </ul>    
            </div>

            <!-- Caja de Búsqueda -->
            <div class="container-fluid">
                <div class="search-box-card">
                    <form action="" method="GET">
                        <div class="row align-items-center">
                            <div class="col-md-9 mb-2 mb-md-0">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-primary"></i></span>
                                    </div>
                                    <input type="text" class="form-control border-left-0" name="buscar" 
                                           placeholder="Escriba Cédula, Nombre, Apellido o Teléfono..." 
                                           value="<?php echo htmlspecialchars($busqueda); ?>" required autofocus>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> BUSCAR
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if($mostrar_resultados): ?>
                    <div class="table-container">
                        <div class="table-container-header">
                            <h5 class="table-container-title">
                                <i class="fas fa-poll text-primary"></i> Resultados para: "<strong><?php echo htmlspecialchars($busqueda); ?></strong>"
                            </h5>
                            <a href="paciente_buscar.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-broom"></i> Limpiar Búsqueda
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>CÉDULA</th>
                                        <th>NOMBRE</th>
                                        <th>APELLIDO</th>
                                        <th>TELÉFONO</th>
                                        <th>EMAIL</th>
                                        <th>ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($resultado && $resultado->num_rows > 0): ?>
                                        <?php while($row = $resultado->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong>#<?php echo $row['id_paciente']; ?></strong></td>
                                                <td><span class="badge badge-light"><?php echo htmlspecialchars($row['cedula']); ?></span></td>
                                                <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($row['apellido']); ?></td>
                                                <td><?php echo htmlspecialchars($row['telefono']) ?: '-'; ?></td>
                                                <td><?php echo htmlspecialchars($row['email']) ?: '-'; ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="paciente_editar.php?id=<?php echo $row['id_paciente']; ?>" class="btn btn-primary btn-sm" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="reserva_nueva.php?id_paciente=<?php echo $row['id_paciente']; ?>" class="btn btn-success btn-sm" title="Agendar Cita">
                                                            <i class="fas fa-calendar-plus"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="fas fa-search-minus fa-2x mb-2 d-block"></i>
                                                No se encontraron pacientes que coincidan con la búsqueda.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
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