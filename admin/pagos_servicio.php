<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/payment_helpers.php';

session_start();
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$conn = aureamed_db_connect();
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
try {
    $id_empresa_actual = asegurarTablaPagos($conn);
} catch (RuntimeException $error) {
    http_response_code(500);
    die(htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8'));
}
require_once __DIR__ . '/comprobante_helpers.php';

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Personal';

if (!asegurarTokensComprobante($conn)) {
    die('No se pudo preparar el acceso seguro a los comprobantes.');
}

$payment_error = '';
$id_reserva = 0;

// Registrar pago
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_pago'])) {
    $id_reserva = isset($_POST['id_reserva']) ? (int)$_POST['id_reserva'] : 0;
    $fecha_pago = isset($_POST['fecha_pago']) && is_string($_POST['fecha_pago']) ? trim($_POST['fecha_pago']) : date('Y-m-d');
    $monto_pago = isset($_POST['monto_pago']) ? (float)$_POST['monto_pago'] : 0.00;
    $metodo_pago = isset($_POST['metodo_pago']) && is_string($_POST['metodo_pago']) ? trim($_POST['metodo_pago']) : 'Efectivo';
    $nota_pago = isset($_POST['nota_pago']) && is_string($_POST['nota_pago']) ? trim($_POST['nota_pago']) : '';
    $id_usuario = (int)$_SESSION['usuario_id'];
    $token_comprobante = bin2hex(random_bytes(32));

    $fecha_pago_objeto = DateTime::createFromFormat('!Y-m-d', $fecha_pago);
    if ($id_reserva <= 0 || $id_usuario <= 0) {
        $payment_error = 'No se pudo identificar la reserva para registrar el pago.';
    } elseif (!$fecha_pago_objeto || $fecha_pago_objeto->format('Y-m-d') !== $fecha_pago) {
        $payment_error = 'Selecciona una fecha de cobro válida.';
    } elseif ($monto_pago <= 0) {
        $payment_error = 'El monto del pago debe ser mayor que cero.';
    } elseif ($metodo_pago === '' || strlen($metodo_pago) > 100 || strlen($nota_pago) > 5000) {
        $payment_error = 'Revisa el método de pago y las notas del comprobante.';
    } elseif (!$conn->begin_transaction()) {
        $payment_error = 'No se pudo iniciar el registro del pago.';
    } else {
        $reserva_lock = $conn->prepare('SELECT id_reserva FROM reservas WHERE id_reserva = ? FOR UPDATE');
        if (!$reserva_lock) {
            $conn->rollback();
            $payment_error = 'No se pudo validar la reserva: ' . $conn->error;
        } else {
            $reserva_lock->bind_param('i', $id_reserva);
            if (!$reserva_lock->execute()) {
                $payment_error = 'No se pudo validar la reserva: ' . $reserva_lock->error;
                $reserva_lock->close();
                $conn->rollback();
            } else {
                $reserva_valida = $reserva_lock->get_result()->fetch_assoc();
                $reserva_lock->close();
                if (!$reserva_valida) {
                    $conn->rollback();
                    $payment_error = 'La reserva ya no existe.';
                } else {
                    $pago_existente_stmt = $conn->prepare('SELECT id_pago FROM pagos WHERE id_reserva = ? ORDER BY id_pago DESC LIMIT 1');
                    if (!$pago_existente_stmt) {
                        $conn->rollback();
                        $payment_error = 'No se pudo comprobar si la reserva ya tiene un pago: ' . $conn->error;
                    } else {
                        $pago_existente_stmt->bind_param('i', $id_reserva);
                        if (!$pago_existente_stmt->execute()) {
                            $payment_error = 'No se pudo comprobar si la reserva ya tiene un pago: ' . $pago_existente_stmt->error;
                            $pago_existente_stmt->close();
                            $conn->rollback();
                        } else {
                            $pago_existente = $pago_existente_stmt->get_result()->fetch_assoc();
                            $pago_existente_stmt->close();
                            if ($pago_existente) {
                                $conn->rollback();
                                header('Location: factura_ver.php?id=' . (int)$pago_existente['id_pago'], true, 303);
                                exit();
                            }

                            $insert = $conn->prepare("INSERT INTO pagos (id_empresa, id_reserva, id_usuario, monto, fecha_pago, metodo, nota, token_comprobante) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            if (!$insert) {
                                $conn->rollback();
                                $payment_error = 'No se pudo preparar el registro del pago: ' . $conn->error;
                            } else {
                                $insert->bind_param('iiidssss', $id_empresa_actual, $id_reserva, $id_usuario, $monto_pago, $fecha_pago, $metodo_pago, $nota_pago, $token_comprobante);
                                if (!$insert->execute()) {
                                    $payment_error = 'No se pudo guardar el pago: ' . $insert->error;
                                    $insert->close();
                                    $conn->rollback();
                                } else {
                                    $payment_id = (int)$insert->insert_id;
                                    $insert->close();
                                    $actualizar_reserva = $conn->prepare("UPDATE reservas SET estado = 'completada' WHERE id_reserva = ?");
                                    if (!$actualizar_reserva) {
                                        $payment_error = 'No se pudo completar la reserva: ' . $conn->error;
                                        $conn->rollback();
                                    } else {
                                        $actualizar_reserva->bind_param('i', $id_reserva);
                                        if (!$actualizar_reserva->execute()) {
                                            $payment_error = 'No se pudo completar la reserva: ' . $actualizar_reserva->error;
                                            $actualizar_reserva->close();
                                            $conn->rollback();
                                        } else {
                                            $actualizar_reserva->close();
                                            if (!$conn->commit()) {
                                                $payment_error = 'No se pudo confirmar el pago y generar el comprobante.';
                                            } else {
                                                header('Location: factura_ver.php?id=' . $payment_id, true, 303);
                                                exit();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

if(!$id_reserva) {
    $id_reserva = isset($_POST['id_reserva']) ? (int)$_POST['id_reserva'] : 0;
}
if(!$id_reserva) {
    $id_reserva = isset($_GET['id_reserva']) ? (int)$_GET['id_reserva'] : 0;
}
if(!$id_reserva) {
    $id_reserva = isset($_GET['id']) ? (int)$_GET['id'] : 0;
}

if($id_reserva <= 0) {
    $buscar_paciente = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
    $filtro_pago = isset($_GET['pago']) && in_array($_GET['pago'], ['pagado', 'pendiente'], true) ? $_GET['pago'] : '';
    $condiciones = [];
    if($buscar_paciente !== '') {
        $condiciones[] = "(p.nombre LIKE ? OR p.apellido LIKE ? OR p.cedula LIKE ? OR s.nombre_servicio LIKE ? OR CONCAT('FAC-', LPAD(ultimo_pago.ultimo_pago_id, 6, '0')) LIKE ?)";
    }
    if($filtro_pago === 'pagado') {
        $condiciones[] = 'pagos.total_pagado IS NOT NULL';
    } elseif($filtro_pago === 'pendiente') {
        $condiciones[] = 'pagos.total_pagado IS NULL';
    }
    $where_busqueda = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

    $sql_busqueda = "SELECT r.id_reserva, r.fecha_reserva, r.hora_reserva, r.estado,
            p.nombre, p.apellido, p.cedula, s.nombre_servicio, s.precio,
            pagos.total_pagado, ultimo_pago.ultimo_pago_id
        FROM reservas r
        JOIN pacientes p ON p.id_paciente = r.id_paciente
        JOIN servicios s ON s.id_servicio = r.id_servicio
        LEFT JOIN (
            SELECT id_reserva, SUM(monto) AS total_pagado
            FROM pagos
            GROUP BY id_reserva
        ) pagos ON pagos.id_reserva = r.id_reserva
        LEFT JOIN (
            SELECT id_reserva, MAX(id_pago) AS ultimo_pago_id
            FROM pagos
            GROUP BY id_reserva
        ) ultimo_pago ON ultimo_pago.id_reserva = r.id_reserva
        $where_busqueda
        ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";
    $consulta_busqueda = $conn->prepare($sql_busqueda);
    if(!$consulta_busqueda) {
        die("Error al preparar la búsqueda de pagos: " . $conn->error);
    }
    if($buscar_paciente !== '') {
        $termino_busqueda = '%' . $buscar_paciente . '%';
        $consulta_busqueda->bind_param('sssss', $termino_busqueda, $termino_busqueda, $termino_busqueda, $termino_busqueda, $termino_busqueda);
    }
    if(!$consulta_busqueda->execute()) {
        die("Error al buscar pagos del paciente: " . $consulta_busqueda->error);
    }
    $reservas_busqueda = $consulta_busqueda->get_result();

    if(isset($_GET['pdf']) && $_GET['pdf'] === '1') {
        require_once __DIR__ . '/REPPDF/libs/fpdf/fpdf.php';
        $toPdfText = static function($text) {
            $converted = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$text);
            return $converted === false ? (string)$text : $converted;
        };

        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->SetTitle('Búsqueda de pagos de pacientes');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $toPdfText('AureaMed - Pagos de pacientes'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 7, $toPdfText('Generado: ' . date('d/m/Y H:i')), 0, 1, 'R');
        $pdf->Cell(0, 7, $toPdfText('Búsqueda: ' . ($buscar_paciente !== '' ? $buscar_paciente : 'Todos') . ' | Estado: ' . ($filtro_pago === 'pagado' ? 'Pagado' : ($filtro_pago === 'pendiente' ? 'No pagado' : 'Todos'))), 0, 1);
        $pdf->Ln(3);

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(226, 232, 240);
        $pdf->Cell(45, 9, $toPdfText('PACIENTE'), 1, 0, 'C', true);
        $pdf->Cell(30, 9, $toPdfText('CÉDULA'), 1, 0, 'C', true);
        $pdf->Cell(55, 9, $toPdfText('SERVICIO'), 1, 0, 'C', true);
        $pdf->Cell(27, 9, $toPdfText('FECHA'), 1, 0, 'C', true);
        $pdf->Cell(35, 9, $toPdfText('ESTADO'), 1, 0, 'C', true);
        $pdf->Cell(35, 9, $toPdfText('MONTO (Bs.)'), 1, 0, 'C', true);
        $pdf->Cell(35, 9, $toPdfText('FACTURA'), 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 9);
        while($fila = $reservas_busqueda->fetch_assoc()) {
            $paciente = $fila['nombre'] . ' ' . $fila['apellido'];
            $servicio = $fila['nombre_servicio'];
            $fecha = date('d/m/Y', strtotime($fila['fecha_reserva']));
            $pagado = !empty($fila['ultimo_pago_id']);
            $estado_pago = $pagado ? 'Pagado' : 'No pagado';
            $monto = $pagado ? (float)$fila['total_pagado'] : (float)$fila['precio'];
            $factura = $pagado ? 'FAC-' . str_pad((string)$fila['ultimo_pago_id'], 6, '0', STR_PAD_LEFT) : '-';

            $pdf->Cell(45, 8, $toPdfText($paciente), 1);
            $pdf->Cell(30, 8, $toPdfText($fila['cedula']), 1);
            $pdf->Cell(55, 8, $toPdfText($servicio), 1);
            $pdf->Cell(27, 8, $fecha, 1, 0, 'C');
            $pdf->Cell(35, 8, $toPdfText($estado_pago), 1, 0, 'C');
            $pdf->Cell(35, 8, number_format($monto, 2), 1, 0, 'R');
            $pdf->Cell(35, 8, $factura, 1, 1, 'C');
        }

        if(ob_get_length()) {
            ob_end_clean();
        }
        $pdf->Output('D', 'pagos_pacientes_' . date('Y-m-d') . '.pdf');
        $consulta_busqueda->close();
        $conn->close();
        exit();
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>AureaMed | Buscar Pago de Paciente</title>
        <link rel="stylesheet" href="./css/normalize.css">
        <link rel="stylesheet" href="./css/bootstrap.min.css">
        <link rel="stylesheet" href="./css/all.css">
        <link rel="stylesheet" href="./css/style.css">
    </head>
    <body>
        <main class="full-box main-container">
            <section class="nav-lateral">
                <div class="nav-lateral-bg show-nav-lateral"></div>
                <div class="nav-lateral-content">
                    <figure class="nav-lateral-avatar">
                        <i class="far fa-times-circle show-nav-lateral" title="Cerrar menú"></i>
                        <img src="./aurea.jpg" alt="Logo de AureaMed">
                        <figcaption>
                            <?php echo htmlspecialchars($nombre_usuario); ?><br>
                            <small><?php echo htmlspecialchars($rol_usuario); ?> &bull; AureaMed</small>
                        </figcaption>
                    </figure>
                    <div class="nav-lateral-bar"></div>
                    <nav class="nav-lateral-menu" aria-label="Menú principal"></nav>
                </div>
            </section>
            <section class="page-content">
                <nav class="navbar-info">
                    <div class="navbar-info-left">
                        <button type="button" class="show-nav-lateral btn-icon" title="Ocultar/Mostrar Menú" aria-label="Ocultar o mostrar menú">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h4 class="navbar-brand-title d-none d-sm-flex">
                            <i class="fas fa-file-invoice-dollar text-primary"></i> Ventas y Leyes
                        </h4>
                    </div>
                    <div class="navbar-info-right">
                        <a href="perfil.php" title="Mi perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                        <a href="logout.php" class="btn-exit-system" title="Cerrar sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                    </div>
                </nav>

                <div class="page-header">
                    <h3><i class="fas fa-search"></i> BUSCAR PAGO DE PACIENTE</h3>
                    <p>Busque por nombre, apellido, cédula o número de factura para consultar el estado del pago.</p>
                </div>
                <div class="container-fluid mb-3">
                    <a href="reservas.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver a Reservas</a>
                </div>
                <div class="container-fluid">
                <div class="table-container mb-3">
                    <form method="GET" action="pagos_servicio.php" class="p-3">
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-6">
                                <label for="buscar">Paciente, cédula o factura</label>
                                <input type="search" class="form-control" id="buscar" name="buscar"
                                       placeholder="Nombre, apellido, cédula o FAC-000001"
                                       value="<?php echo htmlspecialchars($buscar_paciente); ?>">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="pago">Estado del pago</label>
                                <select class="form-control" id="pago" name="pago">
                                    <option value="">Todos</option>
                                    <option value="pendiente" <?php echo $filtro_pago === 'pendiente' ? 'selected' : ''; ?>>No pagado</option>
                                    <option value="pagado" <?php echo $filtro_pago === 'pagado' ? 'selected' : ''; ?>>Pagado</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
                                <a href="pagos_servicio.php" class="btn btn-light">Limpiar</a>
                            </div>
                        </div>
                    </form>
                    <form method="GET" action="pagos_servicio.php" class="px-3 pb-3">
                        <input type="hidden" name="buscar" value="<?php echo htmlspecialchars($buscar_paciente); ?>">
                        <input type="hidden" name="pago" value="<?php echo htmlspecialchars($filtro_pago); ?>">
                        <button type="submit" name="pdf" value="1" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Descargar resultados en PDF
                        </button>
                    </form>
                </div>
                <div class="table-container">
                    <div class="table-container-header">
                        <h5 class="table-container-title"><i class="fas fa-file-invoice-dollar"></i> Pagos de pacientes</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>PACIENTE</th>
                                    <th>CÉDULA</th>
                                    <th>SERVICIO</th>
                                    <th>FECHA</th>
                                    <th>ESTADO DEL PAGO</th>
                                    <th>ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($reservas_busqueda && $reservas_busqueda->num_rows > 0): ?>
                                    <?php while($fila = $reservas_busqueda->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($fila['nombre'] . ' ' . $fila['apellido']); ?></td>
                                            <td><?php echo htmlspecialchars($fila['cedula']); ?></td>
                                            <td><?php echo htmlspecialchars($fila['nombre_servicio']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($fila['fecha_reserva'])); ?></td>
                                            <td>
                                                <?php if($fila['ultimo_pago_id']): ?>
                                                    <span class="badge badge-success">Pagado · Bs. <?php echo number_format((float)$fila['total_pagado'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">No pagado · Bs. <?php echo number_format((float)$fila['precio'], 2); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($fila['ultimo_pago_id']): ?>
                                                    <a href="factura_ver.php?id=<?php echo (int)$fila['ultimo_pago_id']; ?>" class="btn btn-success btn-sm">
                                                        <i class="fas fa-file-invoice"></i> Ver comprobante
                                                    </a>
                                                <?php else: ?>
                                                    <a href="pagos_servicio.php?id_reserva=<?php echo (int)$fila['id_reserva']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-cash-register"></i> Registrar pago
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No se encontraron pagos ni reservas para esa búsqueda.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
    <?php
    $consulta_busqueda->close();
    $conn->close();
    exit();
}

$sql = "SELECT r.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.cedula, p.telefono, p.email, s.nombre_servicio, s.precio 
        FROM reservas r 
        JOIN pacientes p ON r.id_paciente = p.id_paciente 
        JOIN servicios s ON r.id_servicio = s.id_servicio 
        WHERE r.id_reserva = $id_reserva";
$result = $conn->query($sql);
if(!$result || $result->num_rows == 0) {
    header("Location: reservas.php");
    exit();
}
$reserva = $result->fetch_assoc();

$payment_history = [];
$historyResult = $conn->query("SELECT p.*, u.nombre as usuario_nombre FROM pagos p JOIN usuarios u ON p.id_usuario = u.id_usuario WHERE p.id_reserva = $id_reserva ORDER BY p.fecha_pago DESC");
if($historyResult) {
    while($row = $historyResult->fetch_assoc()) {
        $payment_history[] = $row;
    }
}
$ultimo_pago_reserva = !empty($payment_history) ? $payment_history[0] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Pago y Comprobante #<?php echo $id_reserva; ?></title>

    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <link rel="stylesheet" href="./css/sweetalert2.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .receipt-box {
            background: #ffffff;
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .receipt-header {
            border-bottom: 2px solid #003366;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        @media print {
            @page { size: A4 portrait; margin: 12mm; }
            body * {
                visibility: hidden !important;
            }
            .nav-lateral, .navbar-info, .page-header, .page-nav-tabs, .form-pago-col, .btn-print-hide {
                display: none !important;
            }
            .main-container, .page-content {
                display: block !important;
                width: 100% !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .receipt-box {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                border: 1px solid #000 !important;
                box-shadow: none !important;
            }
            .receipt-box, .receipt-box * {
                visibility: visible !important;
            }
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
                            <a href="#" class="nav-btn-submenu active"><i class="fas fa-calendar-alt fa-fw"></i> &nbsp; Reservas <i class="fas fa-chevron-down"></i></a>
                            <ul class="show-nav-lateral-submenu">
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
                        <i class="fas fa-dollar-sign text-primary"></i> Ventas y Leyes
                    </h4>
                </div>
                <div class="navbar-info-right">
                    <a href="perfil.php" title="Mi Perfil"><i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
                    <a href="logout.php" class="btn-exit-system" title="Cerrar Sesión"><i class="fas fa-power-off"></i> <span class="d-none d-md-inline">Salir</span></a>
                </div>
            </nav>

            <div class="page-header">
                <h3><i class="fas fa-file-invoice-dollar"></i> PAGO Y COMPROBANTE DE ATENCIÓN</h3>
                <p>Emita el recibo oficial de pago para la cita médica #<?php echo $id_reserva; ?>.</p>
            </div>

            <div class="container-fluid mb-3 btn-print-hide">
                <a href="reservas.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver a Reservas</a>
            </div>

            <?php if($payment_error !== ''): ?>
                <div class="container-fluid btn-print-hide">
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($payment_error, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endif; ?>

            <div class="container-fluid">
                <?php if($ultimo_pago_reserva): ?>
                    <div class="receipt-box">
                        <h4 class="mb-2" style="color:#003366;font-weight:800;">Esta cita ya tiene un pago registrado</h4>
                        <p class="mb-3">Para evitar comprobantes duplicados, esta reserva no admite otro cobro. Abre el único comprobante para imprimirlo o descargarlo en PDF.</p>
                        <a href="factura_ver.php?id=<?php echo (int)$ultimo_pago_reserva['id_pago']; ?>" class="btn btn-primary">
                            <i class="fas fa-file-invoice" aria-hidden="true"></i> Ver, imprimir o descargar comprobante
                        </a>
                    </div>
                <?php else: ?>
                <div class="row">
                    <!-- Formulario de Registro de Pago -->
                    <div class="col-lg-5 mb-4 form-pago-col">
                        <form method="POST" action="pagos_servicio.php" class="form-neon">
                            <fieldset>
                                <legend><i class="fas fa-cash-register"></i> Registrar Cobro</legend>
                                <input type="hidden" name="id_reserva" value="<?php echo $id_reserva; ?>">
                                <input type="hidden" name="guardar_pago" value="1">

                                <div class="form-group">
                                    <label>Paciente:</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($reserva['paciente_nombre'] . ' ' . $reserva['paciente_apellido']); ?>" readonly style="background-color: #f1f5f9;">
                                </div>

                                <div class="form-group">
                                    <label>Servicio Realizado:</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($reserva['nombre_servicio']); ?>" readonly style="background-color: #f1f5f9;">
                                </div>

                                <div class="form-group">
                                    <label for="monto_pago">Monto a Cobrar (Bs.) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.50" min="0" class="form-control font-weight-bold" id="monto_pago" name="monto_pago" value="<?php echo htmlspecialchars($reserva['precio']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="fecha_pago">Fecha de Cobro <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="metodo_pago">Método de Pago <span class="text-danger">*</span></label>
                                    <select class="form-control" id="metodo_pago" name="metodo_pago" required>
                                        <option value="Efectivo">Efectivo</option>
                                        <option value="Transferencia / QR">Transferencia Bancaria / QR</option>
                                        <option value="Tarjeta de Débito/Crédito">Tarjeta de Débito / Crédito</option>
                                        <option value="Seguro Médico">Convenio / Seguro Médico</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="nota_pago">Notas del Comprobante</label>
                                    <textarea class="form-control" id="nota_pago" name="nota_pago" rows="2" placeholder="Número de transferencia, referencia o detalle..."></textarea>
                                </div>

                                <div class="form-group text-center mt-4 mb-0">
                                    <button type="submit" class="btn btn-success btn-block btn-lg">
                                        <i class="fas fa-check-circle"></i> GUARDAR PAGO Y COMPLETAR CITA
                                    </button>
                                </div>
                            </fieldset>
                        </form>
                    </div>

                    <!-- Vista Previa de Comprobante / Recibo -->
                    <div class="col-lg-7 mb-4">
                        <div class="receipt-box">
                            <div class="receipt-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1" style="color: #003366; font-weight: 800;">AUREAMED</h4>
                                    <p class="text-muted small mb-0">Centro Auditivo y Fonoaudiología Especializada</p>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-primary" style="font-size: 13px;">COMPROBANTE #<?php echo $id_reserva; ?></span>
                                    <p class="text-muted small mb-0 mt-1"><?php echo date('d/m/Y'); ?></p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted text-uppercase d-block font-weight-bold">Paciente:</small>
                                    <strong><?php echo htmlspecialchars($reserva['paciente_nombre'] . ' ' . $reserva['paciente_apellido']); ?></strong>
                                    <small class="d-block text-muted">CI: <?php echo htmlspecialchars($reserva['cedula']); ?></small>
                                </div>
                                <div class="col-6 text-right">
                                    <small class="text-muted text-uppercase d-block font-weight-bold">Fecha de Atención:</small>
                                    <strong><?php echo date('d/m/Y', strtotime($reserva['fecha_reserva'])); ?> - <?php echo substr($reserva['hora_reserva'], 0, 5); ?></strong>
                                    <small class="d-block text-muted">Tel: <?php echo htmlspecialchars($reserva['telefono']) ?: 'S/N'; ?></small>
                                </div>
                            </div>

                            <table class="table table-bordered mb-3">
                                <thead style="background: #f8fafc;">
                                    <tr>
                                        <th>Descripción del Servicio</th>
                                        <th class="text-right" style="width: 130px;">Total (Bs.)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($reserva['nombre_servicio']); ?></strong>
                                            <?php if(!empty($reserva['observaciones'])): ?>
                                                <small class="d-block text-muted"><?php echo htmlspecialchars($reserva['observaciones']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right"><strong>Bs. <?php echo number_format($reserva['precio'], 2); ?></strong></td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th class="text-right">TOTAL PAGADO:</th>
                                        <th class="text-right text-success" style="font-size: 16px;">Bs. <?php echo number_format($reserva['precio'], 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>

                            <?php if(!empty($payment_history)): ?>
                                <div class="mb-3">
                                    <h6 class="font-weight-bold text-muted small text-uppercase mb-2"><i class="fas fa-history"></i> Historial de Transacciones:</h6>
                                    <ul class="list-group list-group-flush small">
                                        <?php foreach($payment_history as $hist): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                                <span><i class="fas fa-check text-success mr-1"></i> <?php echo date('d/m/Y', strtotime($hist['fecha_pago'])); ?> &bull; <?php echo htmlspecialchars($hist['metodo']); ?> (Cobrado por: <?php echo htmlspecialchars($hist['usuario_nombre']); ?>)</span>
                                                <strong>Bs. <?php echo number_format($hist['monto'], 2); ?></strong>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <div class="receipt-footer text-center pt-3 border-top btn-print-hide">
                                <p class="text-muted mb-0">Al guardar el pago se abrirá el comprobante único, listo para imprimir o descargar en PDF.</p>
                            </div>
                            <div class="receipt-footer text-center pt-3 btn-print-hide">
                                <a href="reservas.php" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-check"></i> Finalizar
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
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