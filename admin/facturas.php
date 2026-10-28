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
require_once __DIR__ . '/comprobante_helpers.php';

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Personal';

// Crear tabla de pagos si no existe
$conn->query("CREATE TABLE IF NOT EXISTS pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT NOT NULL,
    id_usuario INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo VARCHAR(100) NOT NULL DEFAULT 'Efectivo',
    nota TEXT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    numero_factura VARCHAR(30) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Agregar columna numero_factura si no existe
$colCheck = $conn->query("SHOW COLUMNS FROM pagos LIKE 'numero_factura'");
if($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE pagos ADD numero_factura VARCHAR(30) NULL AFTER fecha_registro");
}
$colCheck2 = $conn->query("SHOW COLUMNS FROM pagos LIKE 'nota'");
if($colCheck2 && $colCheck2->num_rows === 0) {
    $conn->query("ALTER TABLE pagos ADD nota TEXT NULL AFTER metodo");
}

// Generar número de factura para pagos que no tienen
$conn->query("UPDATE pagos SET numero_factura = CONCAT('FAC-', LPAD(id_pago, 6, '0')) WHERE numero_factura IS NULL OR numero_factura = ''");
$tokens_comprobante_ok = asegurarTokensComprobante($conn);

// Filtros
$filtro_metodo = isset($_GET['metodo']) ? $conn->real_escape_string($_GET['metodo']) : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? $conn->real_escape_string($_GET['fecha_desde']) : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? $conn->real_escape_string($_GET['fecha_hasta']) : '';
$buscar = isset($_GET['buscar']) ? $conn->real_escape_string($_GET['buscar']) : '';

$where = "WHERE 1=1";
if($filtro_metodo) $where .= " AND p.metodo = '$filtro_metodo'";
if($filtro_fecha_desde) $where .= " AND p.fecha_pago >= '$filtro_fecha_desde'";
if($filtro_fecha_hasta) $where .= " AND p.fecha_pago <= '$filtro_fecha_hasta'";
if($buscar) $where .= " AND (x.nombre LIKE '%$buscar%' OR x.apellido LIKE '%$buscar%' OR x.cedula LIKE '%$buscar%' OR p.numero_factura LIKE '%$buscar%')";

$sql = "SELECT p.*, 
        CONCAT('FAC-', LPAD(p.id_pago, 6, '0')) as num_factura,
        r.fecha_reserva, r.hora_reserva,
        x.nombre as paciente_nombre, x.apellido as paciente_apellido, x.cedula,
        s.nombre_servicio, s.precio as precio_servicio,
        u.nombre as cajero_nombre
        FROM pagos p
        JOIN reservas r ON p.id_reserva = r.id_reserva
        JOIN pacientes x ON r.id_paciente = x.id_paciente
        JOIN servicios s ON r.id_servicio = s.id_servicio
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        $where
        ORDER BY p.fecha_registro DESC";

$resultado = $conn->query($sql);
$pagos = [];
$total_recaudado = 0;
if($resultado) {
    while($row = $resultado->fetch_assoc()) {
        $pagos[] = $row;
        $total_recaudado += $row['monto'];
    }
}

// Estadísticas
$stats = $conn->query("SELECT COUNT(*) as total, SUM(monto) as suma, metodo FROM pagos GROUP BY metodo");
$por_metodo = [];
if($stats) {
    while($r = $stats->fetch_assoc()) $por_metodo[] = $r;
}

$total_facturas = $conn->query("SELECT COUNT(*) as c FROM pagos")->fetch_assoc()['c'];
$total_hoy = $conn->query("SELECT SUM(monto) as s FROM pagos WHERE fecha_pago = CURDATE()")->fetch_assoc()['s'] ?? 0;
$total_mes = $conn->query("SELECT SUM(monto) as s FROM pagos WHERE MONTH(fecha_pago)=MONTH(NOW()) AND YEAR(fecha_pago)=YEAR(NOW())")->fetch_assoc()['s'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Facturas y Comprobantes</title>
    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <link rel="stylesheet" href="./css/sweetalert2.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .stat-card-mini {
            background: #fff;
            border-radius: 14px;
            padding: 20px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-card-mini .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .stat-card-mini .value {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }
        .stat-card-mini .label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .factura-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .badge-efectivo { background:#dcfce7; color:#166534; }
        .badge-tarjeta { background:#dbeafe; color:#1e40af; }
        .badge-transferencia { background:#f3e8ff; color:#6b21a8; }
        .badge-cheque { background:#fff7ed; color:#9a3412; }
        .badge-otro { background:#f1f5f9; color:#475569; }
        .filter-bar {
            background: #fff;
            border-radius: 14px;
            padding: 18px 22px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .table-facturas th {
            background: #f8fafc;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px 14px;
            white-space: nowrap;
        }
        .table-facturas td {
            padding: 13px 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13.5px;
        }
        .table-facturas tr:hover td {
            background: #f8fafc;
        }
        .num-factura {
            font-family: 'Courier New', monospace;
            font-weight: 800;
            color: #004b87;
            font-size: 13px;
        }
        .factura-number-cell {
            display: flex;
            align-items: center;
            gap: 9px;
            white-space: nowrap;
        }
        .factura-qr-link img {
            display: block;
            width: 42px;
            height: 42px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background: #fff;
        }
        .monto-pago {
            font-weight: 800;
            font-size: 15px;
            color: #065f46;
        }
        .btn-ver-factura {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #004b87;
            color: #fff !important;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
        }
        .btn-ver-factura:hover {
            background: #0066b2;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,75,135,0.25);
        }
        .btn-imprimir {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #f1f5f9;
            color: #475569 !important;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
        }
        .btn-imprimir:hover {
            background: #e2e8f0;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        .empty-state i {
            font-size: 52px;
            margin-bottom: 16px;
            display: block;
        }
    </style>
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
                        <a href="#" class="nav-btn-submenu active"><i class="fas fa-file-invoice-dollar fa-fw"></i> &nbsp; Facturación <i class="fas fa-chevron-down"></i></a>
                        <ul class="show-nav-lateral-submenu">
                            <li><a href="facturas.php" class="active"><i class="fas fa-list-alt fa-fw"></i> &nbsp; Lista de Facturas</a></li>
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
                    <i class="fas fa-file-invoice-dollar text-primary"></i> Ventas y Leyes
                </h4>
            </div>
            <div class="navbar-info-right">
                <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
            </div>
        </nav>

        <div class="page-header">
            <h3><i class="fas fa-file-invoice-dollar"></i> FACTURAS Y COMPROBANTES</h3>
            <p>Busque por paciente, cédula o número de factura. El QR abre el comprobante para que el paciente pueda guardarlo.</p>
        </div>

        <div class="container-fluid">
            <ul class="page-nav-tabs">
                <li><a class="active" href="facturas.php"><i class="fas fa-list-alt"></i> LISTA DE FACTURAS</a></li>
                <li><a href="reservas.php"><i class="fas fa-dollar-sign"></i> REGISTRAR PAGO</a></li>
            </ul>

            <!-- Estadísticas -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card-mini">
                        <div class="icon" style="background:#dbeafe;color:#1e40af"><i class="fas fa-file-invoice"></i></div>
                        <div>
                            <div class="value"><?php echo $total_facturas; ?></div>
                            <div class="label">Total Facturas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card-mini">
                        <div class="icon" style="background:#dcfce7;color:#166534"><i class="fas fa-dollar-sign"></i></div>
                        <div>
                            <div class="value"> Bs <?php echo number_format($total_hoy ?? 0, 2); ?></div>
                            <div class="label">Recaudado Hoy</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card-mini">
                        <div class="icon" style="background:#fef3c7;color:#92400e"><i class="fas fa-calendar-alt"></i></div>
                        <div>
                            <div class="value"> Bs <?php echo number_format($total_mes ?? 0, 2); ?></div>
                            <div class="label">Este Mes</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card-mini">
                        <div class="icon" style="background:#f3e8ff;color:#6b21a8"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <div class="value"> Bs <?php echo number_format($total_recaudado, 2); ?></div>
                            <div class="label">Total (Filtro)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filter-bar">
                <form method="GET" action="facturas.php" class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold" style="font-size:12px;color:#64748b;">BUSCAR</label>
                        <input type="search" name="buscar" class="form-control" placeholder="Nombre, cédula o FAC-000001" value="<?php echo htmlspecialchars($buscar); ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-bold" style="font-size:12px;color:#64748b;">MÉTODO</label>
                        <select name="metodo" class="form-control">
                            <option value="">Todos</option>
                            <option value="Efectivo" <?php if($filtro_metodo=='Efectivo') echo 'selected'; ?>>Efectivo</option>
                            <option value="Tarjeta" <?php if($filtro_metodo=='Tarjeta') echo 'selected'; ?>>Tarjeta</option>
                            <option value="Transferencia" <?php if($filtro_metodo=='Transferencia') echo 'selected'; ?>>Transferencia</option>
                            <option value="Cheque" <?php if($filtro_metodo=='Cheque') echo 'selected'; ?>>Cheque</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-bold" style="font-size:12px;color:#64748b;">DESDE</label>
                        <input type="date" name="fecha_desde" class="form-control" value="<?php echo $filtro_fecha_desde; ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-bold" style="font-size:12px;color:#64748b;">HASTA</label>
                        <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $filtro_fecha_hasta; ?>">
                    </div>
                    <div class="col-6 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filtrar</button>
                        <a href="facturas.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                    </div>
                </form>
            </div>

            <!-- Tabla de facturas -->
            <div class="data-card" style="overflow-x:auto;">
                <?php if(count($pagos) > 0): ?>
                <table class="table table-facturas w-100 mb-0">
                    <thead>
                        <tr>
                            <th># Factura</th>
                            <th>Fecha Pago</th>
                            <th>Paciente</th>
                            <th>Cédula</th>
                            <th>Servicio</th>
                            <th>Método</th>
                            <th>Monto</th>
                            <th>Cajero</th>
                            <th style="text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pagos as $p): ?>
                        <tr>
                            <td>
                                <div class="factura-number-cell">
                                    <span class="num-factura"><?php echo htmlspecialchars($p['num_factura']); ?></span>
                                    <?php if($tokens_comprobante_ok && !empty($p['token_comprobante'])): ?>
                                    <a class="factura-qr-link" href="factura_ver.php?token=<?php echo urlencode($p['token_comprobante']); ?>" target="_blank" rel="noopener" title="Abrir comprobante para el paciente">
                                        <img src="qr_comprobante.php?token=<?php echo urlencode($p['token_comprobante']); ?>" alt="QR del comprobante <?php echo htmlspecialchars($p['num_factura']); ?>">
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($p['fecha_pago'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($p['paciente_apellido'] . ', ' . $p['paciente_nombre']); ?></strong>
                            </td>
                            <td style="color:#64748b;"><?php echo htmlspecialchars($p['cedula']); ?></td>
                            <td style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($p['nombre_servicio']); ?>">
                                <?php echo htmlspecialchars($p['nombre_servicio']); ?>
                            </td>
                            <td>
                                <?php
                                $metodo = $p['metodo'];
                                $badgeClass = 'badge-otro';
                                if($metodo == 'Efectivo') $badgeClass = 'badge-efectivo';
                                elseif($metodo == 'Tarjeta') $badgeClass = 'badge-tarjeta';
                                elseif($metodo == 'Transferencia') $badgeClass = 'badge-transferencia';
                                elseif($metodo == 'Cheque') $badgeClass = 'badge-cheque';
                                ?>
                                <span class="factura-badge <?php echo $badgeClass; ?>">
                                    <i class="fas fa-<?php echo $metodo=='Efectivo'?'money-bill-wave':($metodo=='Tarjeta'?'credit-card':($metodo=='Transferencia'?'exchange-alt':'file-alt')); ?>"></i>
                                    <?php echo htmlspecialchars($metodo); ?>
                                </span>
                            </td>
                            <td><span class="monto-pago"> Bs <?php echo number_format($p['monto'], 2); ?></span></td>
                            <td style="color:#64748b;font-size:12.5px;"><?php echo htmlspecialchars($p['cajero_nombre']); ?></td>
                            <td style="text-align:center;white-space:nowrap;">
                                <a href="factura_ver.php?id=<?php echo $p['id_pago']; ?>" class="btn-ver-factura" title="Ver Factura">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                <a href="factura_ver.php?id=<?php echo $p['id_pago']; ?>&print=1" class="btn-imprimir" title="Imprimir" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" style="text-align:right;font-weight:700;color:#64748b;font-size:13px;padding:12px 14px;">TOTAL RECAUDADO (<?php echo count($pagos); ?> facturas):</td>
                            <td colspan="3" style="font-weight:800;font-size:17px;color:#065f46;padding:12px 14px;"> Bs <?php echo number_format($total_recaudado, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-invoice" style="color:#cbd5e1;"></i>
                    <h5 style="color:#94a3b8;font-weight:700;">No se encontraron facturas</h5>
                    <p style="color:#cbd5e1;font-size:14px;">
                        <?php if($buscar || $filtro_metodo || $filtro_fecha_desde): ?>
                            No hay resultados para los filtros aplicados.
                            <a href="facturas.php" style="color:#004b87;">Limpiar filtros</a>
                        <?php else: ?>
                            Aún no se han registrado pagos. <a href="reservas.php" style="color:#004b87;">Ir a Reservas</a> para registrar un pago.
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /container -->
    </section>
</main>

<script src="./js/jquery-3.4.1.min.js"></script>
<script src="./js/popper.min.js"></script>
<script src="./js/bootstrap.min.js"></script>
<script src="./js/sweetalert2.min.js"></script>
<script src="./js/main.js"></script>
</body>
</html>
