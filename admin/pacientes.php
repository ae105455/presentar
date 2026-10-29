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
require_once __DIR__ . '/audiometria_helpers.php';
asegurarTablasAudiometria($conn);

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Personal';
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_audiometria'] = $csrf_token;
$mensaje_audiometria = isset($_SESSION['mensaje_audiometria']) ? $_SESSION['mensaje_audiometria'] : '';
$tipo_mensaje_audiometria = isset($_SESSION['tipo_mensaje_audiometria']) ? $_SESSION['tipo_mensaje_audiometria'] : '';
unset($_SESSION['mensaje_audiometria'], $_SESSION['tipo_mensaje_audiometria']);
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '2000-01-01';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
$buscar_sql = $conn->real_escape_string($buscar);
$fecha_inicio_sql = $conn->real_escape_string($fecha_inicio);
$fecha_fin_sql = $conn->real_escape_string($fecha_fin);

$sql = "SELECT id_paciente, cedula, nombre, apellido, telefono, email, fecha_nac, fecha_registro
        FROM pacientes 
    WHERE fecha_registro >= '" . $fecha_inicio_sql . "'
    AND fecha_registro < DATE_ADD('" . $fecha_fin_sql . "', INTERVAL 1 DAY)
    AND ('" . $buscar_sql . "' = '' OR nombre LIKE '%" . $buscar_sql . "%' OR apellido LIKE '%" . $buscar_sql . "%' OR cedula LIKE '%" . $buscar_sql . "%')
        ORDER BY id_paciente DESC";
$resultado = $conn->query($sql);
$conteos_audiometrias = [];
$conteos_resultado = $conn->query("SELECT id_paciente, COUNT(*) AS total FROM audiometrias GROUP BY id_paciente");
if (!$conteos_resultado) {
    die("Error al consultar el historial de audiometrías: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
while ($conteo = $conteos_resultado->fetch_assoc()) {
    $conteos_audiometrias[(int)$conteo['id_paciente']] = (int)$conteo['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Lista de Pacientes</title>

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
                            <a href="#" class="nav-btn-submenu active"><i class="fas fa-users fa-fw"></i> &nbsp; Pacientes <i class="fas fa-chevron-down"></i></a>
                            <ul class="show-nav-lateral-submenu">
                                <li><a href="paciente_nuevo.php"><i class="fas fa-user-plus fa-fw"></i> &nbsp; Registrar Paciente</a></li>
                                <li><a href="pacientes.php" class="active"><i class="fas fa-clipboard-list fa-fw"></i> &nbsp; Lista de Pacientes</a></li>
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
                        <i class="fas fa-users text-primary"></i> Ventas y Leyes
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-users"></i> LISTA DE PACIENTES</h3>
                <p>Consulte, edite y gestione los pacientes registrados en el Centro Auditivo AureaMed.</p>
            </div>

            <div class="container-fluid">
                <?php if ($mensaje_audiometria !== ''): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($tipo_mensaje_audiometria, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                        <?php echo htmlspecialchars($mensaje_audiometria, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <form method="get" class="report-filter-form report-filter-form-wide patients-filter">
                    <div class="report-filter-field report-search-field">
                        <label for="buscar">Buscar</label>
                        <input type="search" id="buscar" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>" placeholder="Paciente, cédula...">
                    </div>
                    <div class="report-filter-field">
                        <label for="fecha_inicio">Desde</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
                    </div>
                    <div class="report-filter-field">
                        <label for="fecha_fin">Hasta</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" required>
                    </div>
                    <button type="submit" class="btn report-filter-submit"><i class="fas fa-filter"></i> Filtrar</button>
                    <a href="pacientes.php" class="report-filter-clear" title="Limpiar filtros" aria-label="Limpiar filtros">&times;</a>
                </form>
            </div>

            <div class="container-fluid">
                <ul class="page-nav-tabs">
                    <li><a href="paciente_nuevo.php"><i class="fas fa-plus"></i> REGISTRAR PACIENTE</a></li>
                    <li><a class="active" href="pacientes.php"><i class="fas fa-clipboard-list"></i> LISTA DE PACIENTES</a></li>
                    <li><a href="paciente_buscar.php"><i class="fas fa-search"></i> BUSCAR PACIENTE</a></li>
                </ul>    
            </div>
            
            <div class="container-fluid">
                <div class="table-container">
                    <div class="table-container-header">
                        <h5 class="table-container-title"><i class="fas fa-users text-primary"></i> Pacientes Registrados</h5>
                        <a href="paciente_nuevo.php" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Nuevo Paciente</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>CÉDULA</th>
                                    <th>NOMBRE</th>
                                    <th>APELLIDO</th>
                                    <th>EDAD</th>
                                    <th>TELÉFONO</th>
                                    <th>EMAIL</th>
                                    <th>REGISTRO</th>
                                    <th>AUDIOMETRÍAS</th>
                                    <th>ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($resultado && $resultado->num_rows > 0): ?>
                                    <?php $contador = 1; while($row = $resultado->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $row['id_paciente']; ?></strong></td>
                                            <td><span class="badge badge-light" style="font-size: 13px;"><?php echo htmlspecialchars($row['cedula']); ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['apellido']); ?></td>
                                            <td><?php echo htmlspecialchars(edadPaciente($row['fecha_nac']), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($row['telefono']) ?: '-'; ?></td>
                                            <td><?php echo htmlspecialchars($row['email']) ?: '-'; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['fecha_registro'])); ?></td>
                                            <td>
                                                <a href="audimetria_historial.php?id_paciente=<?php echo (int)$row['id_paciente']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-file-medical"></i>
                                                    <?php echo isset($conteos_audiometrias[(int)$row['id_paciente']]) ? $conteos_audiometrias[(int)$row['id_paciente']] : 0; ?> ver
                                                </a>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-success btn-sm btn-nueva-audiometria"
                                                            data-toggle="modal" data-target="#modalAudiometria"
                                                            data-id="<?php echo (int)$row['id_paciente']; ?>"
                                                            data-nombre="<?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellido'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            title="Registrar audiometría">
                                                        <i class="fas fa-file-medical"></i>
                                                    </button>
                                                    <a href="paciente_editar.php?id=<?php echo $row['id_paciente']; ?>" class="btn btn-primary btn-sm" title="Editar Paciente">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger btn-sm btn-eliminar-paciente" 
                                                            data-id="<?php echo $row['id_paciente']; ?>" 
                                                            data-nombre="<?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellido']); ?>"
                                                            title="Eliminar Paciente">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4 text-muted">
                                            <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                                            No hay pacientes registrados todavía.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="modalAudiometria" tabindex="-1" role="dialog" aria-labelledby="modalAudiometriaTitulo" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <form action="audimetria_guardar.php" method="post" enctype="multipart/form-data">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalAudiometriaTitulo">Registrar audiometría</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="id_paciente" id="audiometria_id_paciente">
                                <p class="font-weight-bold" id="audiometria_nombre_paciente"></p>
                                <div class="form-group">
                                    <label for="imagen_audiometria">Foto de la audiometría (JPG, PNG o WEBP; máximo 5 MB)</label>
                                    <input class="form-control-file" type="file" id="imagen_audiometria" name="imagen" accept="image/jpeg,image/png,image/webp" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar audiometría</button>
                            </div>
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
    <script>
        $(document).ready(function() {
            $('.btn-nueva-audiometria').on('click', function() {
                $('#audiometria_id_paciente').val($(this).data('id'));
                $('#audiometria_nombre_paciente').text($(this).data('nombre'));
            });

            $('.btn-eliminar-paciente').on('click', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                Swal.fire({
                    title: '¿Eliminar paciente?',
                    html: `¿Está seguro de eliminar a <strong>${nombre}</strong>?<br><small class="text-danger">Esta acción eliminará también las reservas asociadas.</small>`,
                    icon: 'warning',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed || result.value === true || result.value) {
                        window.location.href = `paciente_eliminar.php?id=${id}`;
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>