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

$id_servicio = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id_servicio == 0) {
    header("Location: servicios.php");
    exit();
}

$sql = "SELECT * FROM servicios WHERE id_servicio = $id_servicio";
$resultado = $conn->query($sql);

if($resultado->num_rows == 0) {
    header("Location: servicios.php");
    exit();
}

$servicio = $resultado->fetch_assoc();

$error = '';
$exito = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_servicio = trim($_POST['nombre_servicio']);
    $descripcion = trim($_POST['descripcion']);
    $duracion_minutos = (int)$_POST['duracion_minutos'];
    $precio = (float)$_POST['precio'];
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'activo';
    
    if(empty($nombre_servicio)) {
        $error = "El nombre del servicio es obligatorio.";
    } elseif($duracion_minutos <= 0) {
        $error = "La duración debe ser mayor a 0 minutos.";
    } elseif($precio < 0) {
        $error = "El precio no puede ser negativo.";
    } else {
        $check = $conn->prepare("SELECT id_servicio FROM servicios WHERE nombre_servicio = ? AND id_servicio != ?");
        $check->bind_param("si", $nombre_servicio, $id_servicio);
        $check->execute();
        $check->store_result();
        
        if($check->num_rows > 0) {
            $error = "Ya existe otro servicio con ese mismo nombre.";
        } else {
            $stmt = $conn->prepare("UPDATE servicios SET nombre_servicio = ?, descripcion = ?, duracion_minutos = ?, precio = ?, estado = ? WHERE id_servicio = ?");
            $stmt->bind_param("ssidsi", $nombre_servicio, $descripcion, $duracion_minutos, $precio, $estado, $id_servicio);
            
            if($stmt->execute()) {
                $exito = "Servicio actualizado exitosamente.";
                $sql_act = "SELECT * FROM servicios WHERE id_servicio = $id_servicio";
                $res_act = $conn->query($sql_act);
                $servicio = $res_act->fetch_assoc();
            } else {
                $error = "Error al actualizar el servicio: " . $stmt->error;
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
    <title>AureaMed | Editar Servicio</title>

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
                            <a href="#" class="nav-btn-submenu active"><i class="fas fa-stethoscope fa-fw"></i> &nbsp; Servicios <i class="fas fa-chevron-down"></i></a>
                            <ul class="show-nav-lateral-submenu">
                                <li><a href="servicio_nuevo.php"><i class="fas fa-plus-circle fa-fw"></i> &nbsp; Registrar Servicio</a></li>
                                <li><a href="servicios.php" class="active"><i class="fas fa-clipboard-list fa-fw"></i> &nbsp; Lista de Servicios</a></li>
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
                        <i class="fas fa-edit text-primary"></i> Ventas y Leyes
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-edit"></i> EDITAR SERVICIO</h3>
                <p>Modifique la tarifa, duración, descripción o estado de este servicio médico.</p>
            </div>

            <div class="container-fluid">
                <ul class="page-nav-tabs">
                    <li><a href="servicio_nuevo.php"><i class="fas fa-plus"></i> NUEVO SERVICIO</a></li>
                    <li><a href="servicios.php"><i class="fas fa-clipboard-list"></i> LISTA DE SERVICIOS</a></li>
                    <li><a href="servicio_buscar.php"><i class="fas fa-search"></i> BUSCAR SERVICIO</a></li>
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
                        <button type="button" class="close" data-dismiss="alert" style="background:none;border:none;font-size:20px;"><span>&times;</span></button>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-10 offset-lg-1">
                        <form method="POST" action="" class="form-neon">
                            <fieldset>
                                <legend><i class="fas fa-stethoscope"></i> Ficha del Servicio: <?php echo htmlspecialchars($servicio['nombre_servicio']); ?></legend>
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="nombre_servicio">Nombre del Servicio <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nombre_servicio" name="nombre_servicio" 
                                                   value="<?php echo htmlspecialchars($servicio['nombre_servicio']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="estado">Estado</label>
                                            <select class="form-control" id="estado" name="estado">
                                                <option value="activo" <?php echo ($servicio['estado'] == 'activo' || $servicio['estado'] == '1') ? 'selected' : ''; ?>>Activo</option>
                                                <option value="inactivo" <?php echo ($servicio['estado'] == 'inactivo' || $servicio['estado'] == '0') ? 'selected' : ''; ?>>Inactivo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="duracion_minutos">Duración Estimada (Minutos) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="duracion_minutos" name="duracion_minutos" 
                                                   value="<?php echo (int)$servicio['duracion_minutos']; ?>" min="5" max="240" step="5" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio">Precio / Tarifa (Bs.) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="precio" name="precio" 
                                                   value="<?php echo htmlspecialchars($servicio['precio']); ?>" step="0.50" min="0" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($servicio['descripcion']); ?></textarea>
                                </div>

                                <div class="form-group text-center mt-4 mb-0">
                                    <button type="submit" class="btn btn-success btn-lg mr-2">
                                        <i class="fas fa-sync-alt"></i> ACTUALIZAR SERVICIO
                                    </button>
                                    <a href="servicios.php" class="btn btn-secondary btn-lg">
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