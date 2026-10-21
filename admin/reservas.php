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
$createPagosTable = "CREATE TABLE IF NOT EXISTS pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT NOT NULL,
    id_usuario INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo VARCHAR(100) NOT NULL,
    nota TEXT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if(!$conn->query($createPagosTable)) {
    die("Error al preparar la tabla de pagos: " . $conn->error);
}

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Personal';

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
                        <i class="fas fa-calendar-alt text-primary"></i> Gestión de Citas y Reservas
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-calendar-alt"></i> LISTA DE RESERVAS</h3>
                <p>Consulte el cronograma general de consultas, tratamientos y citas agendadas.</p>
            </div>

            <div class="container-fluid">
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
                        <h5 class="table-container-title"><i class="fas fa-calendar-check text-primary"></i> Todas las Citas Registradas</h5>
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
                                    <th>ESTADO</th>
                                    <th>PAGO</th>
                                    <th>ATENDIDO POR</th>
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
                                        <tr>
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
                                        <td colspan="9" class="text-center py-4 text-muted">
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