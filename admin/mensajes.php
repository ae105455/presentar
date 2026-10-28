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

// Asegurar que la tabla exista
$conn->query("CREATE TABLE IF NOT EXISTS reservas_rapidas (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    edad VARCHAR(20),
    servicio VARCHAR(50) NOT NULL,
    fecha DATE NOT NULL,
    hora VARCHAR(10),
    como_nos_conocio VARCHAR(50),
    mensaje TEXT,
    estado ENUM('pendiente', 'confirmada', 'cancelada') DEFAULT 'pendiente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Confirmar reserva rápida
if(isset($_GET['confirmar']) && is_numeric($_GET['confirmar'])) {
    $id = (int)$_GET['confirmar'];
    $conn->query("UPDATE reservas_rapidas SET estado = 'confirmada' WHERE id_reserva = $id");
    header("Location: mensajes.php");
    exit();
}

// Eliminar reserva rápida
if(isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $conn->query("DELETE FROM reservas_rapidas WHERE id_reserva = $id");
    header("Location: mensajes.php");
    exit();
}

// Estadísticas
$total_reservas = 0;
$total_pendientes = 0;
$total_confirmadas = 0;

$res_total = $conn->query("SELECT COUNT(*) as total FROM reservas_rapidas");
if($res_total) $total_reservas = (int)$res_total->fetch_assoc()['total'];

$res_pend = $conn->query("SELECT COUNT(*) as total FROM reservas_rapidas WHERE estado = 'pendiente'");
if($res_pend) $total_pendientes = (int)$res_pend->fetch_assoc()['total'];

$total_confirmadas = $total_reservas - $total_pendientes;

// Obtener registros
$reservas = $conn->query("SELECT * FROM reservas_rapidas ORDER BY fecha_registro DESC");
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
                        <li>
                            <a href="mensajes.php" class="active">
                                <i class="fas fa-envelope-open-text fa-fw"></i> &nbsp; Citas Web / Mensajes
                                <?php if($total_pendientes > 0): ?>
                                    <span class="badge badge-pill badge-warning ml-auto" style="font-size: 11px; background: #ffc107; color: #000; font-weight: 700;"><?php echo $total_pendientes; ?></span>
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
                <p>Gestione las reservas de citas y mensajes enviados por pacientes desde el sitio web público.</p>
            </div>

            <div class="container-fluid">
                <!-- Métricas de Citas Web -->
                <div class="metrics-grid">
                    <div class="metric-card card-pacientes">
                        <div class="metric-info">
                            <div class="metric-title">Total Solicitudes</div>
                            <div class="metric-value"><?php echo $total_reservas; ?></div>
                            <div class="metric-subtext"><i class="fas fa-globe text-primary"></i> Recibidas desde la web</div>
                        </div>
                        <div class="metric-icon-box"><i class="fas fa-inbox"></i></div>
                    </div>

                    <div class="metric-card card-pendientes">
                        <div class="metric-info">
                            <div class="metric-title">Citas Pendientes</div>
                            <div class="metric-value" style="color: #d97706;"><?php echo $total_pendientes; ?></div>
                            <div class="metric-subtext"><i class="fas fa-clock text-warning"></i> Requieren atención</div>
                        </div>
                        <div class="metric-icon-box"><i class="fas fa-clock"></i></div>
                    </div>

                    <div class="metric-card card-servicios">
                        <div class="metric-info">
                            <div class="metric-title">Confirmadas</div>
                            <div class="metric-value" style="color: #10b981;"><?php echo $total_confirmadas; ?></div>
                            <div class="metric-subtext"><i class="fas fa-check-circle text-success"></i> Procesadas</div>
                        </div>
                        <div class="metric-icon-box"><i class="fas fa-check-double"></i></div>
                    </div>
                </div>

                <!-- Tabla de Solicitudes -->
                <div class="table-container">
                    <div class="table-container-header">
                        <h5 class="table-container-title">
                            <i class="fas fa-list text-primary"></i> Bandeja de Citas Rápidas
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>FECHA REG.</th>
                                    <th>NOMBRE</th>
                                    <th>EMAIL</th>
                                    <th>TELÉFONO</th>
                                    <th>SERVICIO</th>
                                    <th>FECHA CITA</th>
                                    <th>ESTADO</th>
                                    <th>ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($reservas && $reservas->num_rows > 0): ?>
                                    <?php while($row = $reservas->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $row['id_reserva']; ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['fecha_registro'])); ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['telefono']) ?: '-'; ?></td>
                                            <td><span class="badge badge-light"><?php echo htmlspecialchars($row['servicio']); ?></span></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?> <?php echo htmlspecialchars($row['hora']) ? '<strong>(' . htmlspecialchars($row['hora']) . ')</strong>' : ''; ?></td>
                                            <td>
                                                <?php if($row['estado'] == 'pendiente'): ?>
                                                    <span class="badge-pendiente">⏳ Pendiente</span>
                                                <?php else: ?>
                                                    <span class="badge-confirmada">✅ Confirmada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-info btn-sm btn-ver-reserva" 
                                                            data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                                            data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                                            data-telefono="<?php echo htmlspecialchars($row['telefono']); ?>"
                                                            data-servicio="<?php echo htmlspecialchars($row['servicio']); ?>"
                                                            data-fecha="<?php echo date('d/m/Y', strtotime($row['fecha'])); ?>"
                                                            data-hora="<?php echo htmlspecialchars($row['hora']); ?>"
                                                            data-mensaje="<?php echo htmlspecialchars($row['mensaje']); ?>"
                                                            title="Ver Detalle y Mensaje">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if($row['estado'] == 'pendiente'): ?>
                                                        <a href="mensajes.php?confirmar=<?php echo $row['id_reserva']; ?>" class="btn btn-success btn-sm" title="Confirmar Cita">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="mensajes.php?eliminar=<?php echo $row['id_reserva']; ?>" class="btn btn-danger btn-sm" title="Eliminar Solicitud" onclick="return confirm('¿Está seguro de eliminar esta solicitud web?');">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            No hay solicitudes de citas web registradas en la bandeja.
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
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>