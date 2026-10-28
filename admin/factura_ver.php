<?php
session_start();
$token_comprobante = isset($_GET['token']) ? trim($_GET['token']) : '';
$es_comprobante_publico = preg_match('/\A[a-f0-9]{64}\z/i', $token_comprobante) === 1;
if(!$es_comprobante_publico && !isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'aureamed');
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
require_once __DIR__ . '/comprobante_helpers.php';
if (!$es_comprobante_publico && !asegurarTokensComprobante($conn)) {
    http_response_code(503);
    exit('No se pudo preparar el comprobante.');
}
if ($es_comprobante_publico) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: private, no-store');
}

$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Personal';

$id_pago = $es_comprobante_publico ? 0 : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$auto_print = isset($_GET['print']) && $_GET['print'] == '1';

if(!$es_comprobante_publico && $id_pago <= 0) {
    header("Location: facturas.php");
    exit();
}

// Obtener datos completos del pago
$sql = "SELECT p.*,
        CONCAT('FAC-', LPAD(p.id_pago, 6, '0')) as num_factura,
        r.id_reserva, r.fecha_reserva, r.hora_reserva, r.estado as estado_reserva, r.observaciones,
        x.nombre as paciente_nombre, x.apellido as paciente_apellido, x.cedula, x.telefono, x.email, x.direccion,
        s.nombre_servicio, s.precio as precio_servicio, s.duracion_minutos,
        u.nombre as cajero_nombre, u.email as cajero_email
        FROM pagos p
        JOIN reservas r ON p.id_reserva = r.id_reserva
        JOIN pacientes x ON r.id_paciente = x.id_paciente
        JOIN servicios s ON r.id_servicio = s.id_servicio
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE " . ($es_comprobante_publico ? "p.token_comprobante = '" . $conn->real_escape_string($token_comprobante) . "'" : "p.id_pago = $id_pago");

$result = $conn->query($sql);
if(!$result || $result->num_rows == 0) {
    if ($es_comprobante_publico) {
        http_response_code(404);
        exit('Comprobante no encontrado.');
    }
    header("Location: facturas.php");
    exit();
}
$pago = $result->fetch_assoc();

// Datos de la empresa
$empresa = $conn->query("SELECT * FROM empresa LIMIT 1")->fetch_assoc();
$empresa_nombre = $empresa['nombre'] ?? 'Centro Auditivo AureaMed';
$empresa_ruc = $empresa['ruc'] ?? 'RUC: 000000000001';
$empresa_telefono = $empresa['telefono'] ?? '+593 99 000 0000';
$empresa_email = $empresa['email'] ?? 'info@aureamed.com';
$empresa_direccion = $empresa['direccion'] ?? 'Guayaquil, Ecuador';
$empresa_web = $empresa['web'] ?? 'www.aureamed.com';

if (isset($_GET['pdf']) && $_GET['pdf'] === '1') {
    require_once __DIR__ . '/REPPDF/libs/fpdf/fpdf.php';

    $toPdfText = static function($text) {
        $converted = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$text);
        return $converted === false ? (string)$text : $converted;
    };

    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    if (!preg_match('/\A[a-zA-Z0-9.\-:\[\]]+\z/', $host)) {
        $host = 'localhost';
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    $receiptUrl = $scheme . '://' . $host . $basePath . '/factura_ver.php?token=' . rawurlencode($pago['token_comprobante']);
    $qrFile = tempnam(sys_get_temp_dir(), 'aureamed_receipt_');
    if ($qrFile === false) {
        http_response_code(500);
        exit('No se pudo preparar el código QR del comprobante.');
    }

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(17, 15, 17);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();
    $pdf->SetDrawColor(225, 215, 210);
    $pdf->SetFillColor(251, 240, 229);
    $pdf->Image(__DIR__ . '/aurea.jpg', 17, 17, 55);

    $pdf->SetTextColor(125, 42, 42);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetXY(105, 18);
    $pdf->Cell(88, 8, $toPdfText('COMPROBANTE DE PAGO'), 0, 1, 'R');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetX(105);
    $pdf->Cell(88, 7, $toPdfText($pago['num_factura']), 0, 1, 'R');
    $pdf->SetTextColor(71, 85, 105);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetX(105);
    $pdf->Cell(88, 6, $toPdfText('Emitido: ' . date('d/m/Y H:i', strtotime($pago['fecha_registro']))), 0, 1, 'R');
    $pdf->SetTextColor(125, 42, 42);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetX(105);
    $pdf->Cell(88, 7, $toPdfText('PAGO CONFIRMADO'), 0, 1, 'R');
    $pdf->SetDrawColor(243, 155, 45);
    $pdf->SetLineWidth(1.2);
    $pdf->Line(17, 52, 193, 52);

    $pdf->SetDrawColor(225, 215, 210);
    $pdf->SetLineWidth(0.3);
    $pdf->SetFillColor(251, 240, 229);
    $pdf->Rect(17, 61, 85, 39, 'DF');
    $pdf->Rect(108, 61, 85, 39, 'DF');
    $pdf->SetTextColor(125, 42, 42);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(22, 65);
    $pdf->Cell(75, 6, $toPdfText('CENTRO DE ATENCION'), 0, 1);
    $pdf->SetTextColor(51, 65, 85);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetX(22);
    $pdf->MultiCell(75, 5, $toPdfText($empresa_nombre . "\n" . $empresa_ruc . "\n" . $empresa_direccion . "\n" . $empresa_telefono));
    $pdf->SetTextColor(125, 42, 42);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(113, 65);
    $pdf->Cell(75, 6, $toPdfText('PACIENTE'), 0, 1);
    $pdf->SetTextColor(51, 65, 85);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetX(113);
    $pdf->MultiCell(75, 5, $toPdfText($pago['paciente_apellido'] . ' ' . $pago['paciente_nombre'] . "\nCedula: " . $pago['cedula'] . "\n" . ($pago['telefono'] ?: '')));

    $pdf->SetFillColor(125, 42, 42);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(17, 110);
    $pdf->Cell(82, 9, $toPdfText('SERVICIO'), 1, 0, 'L', true);
    $pdf->Cell(47, 9, $toPdfText('FECHA'), 1, 0, 'C', true);
    $pdf->Cell(47, 9, $toPdfText('IMPORTE'), 1, 1, 'R', true);
    $pdf->SetTextColor(51, 65, 85);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetX(17);
    $pdf->Cell(82, 12, $toPdfText($pago['nombre_servicio']), 1, 0, 'L');
    $pdf->Cell(47, 12, date('d/m/Y', strtotime($pago['fecha_reserva'])), 1, 0, 'C');
    $pdf->Cell(47, 12, $toPdfText('Bs. ' . number_format((float)$pago['monto'], 2)), 1, 1, 'R');

    $pdf->SetFillColor(125, 42, 42);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(105, 135);
    $pdf->Cell(88, 13, $toPdfText('TOTAL PAGADO     Bs. ' . number_format((float)$pago['monto'], 2)), 0, 1, 'R', true);

    $pdf->SetTextColor(51, 65, 85);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY(17, 160);
    $pdf->Cell(176, 7, $toPdfText('Metodo de pago: ' . $pago['metodo']), 0, 1);
    $pdf->Cell(176, 7, $toPdfText('Atendido por: ' . $pago['cajero_nombre']), 0, 1);
    if (!empty($pago['nota'])) {
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(176, 7, $toPdfText('Observaciones'), 0, 1);
        $pdf->SetFont('Arial', '', 9);
        $pdf->MultiCell(176, 5, $toPdfText($pago['nota']));
    }

    $pdf->SetDrawColor(225, 215, 210);
    $pdf->Line(17, 225, 193, 225);
    $pdf->SetTextColor(125, 42, 42);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(17, 233);
    $pdf->Cell(125, 7, $toPdfText('Gracias por elegir ' . $empresa_nombre), 0, 1);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetX(17);
    $pdf->MultiCell(125, 5, $toPdfText('Este comprobante confirma su pago. Escanee el QR para consultarlo nuevamente.'));

    $qrPng = generarCodigoQrPng($receiptUrl, 'M', 2, 4);
    if (file_put_contents($qrFile, $qrPng) === false) {
        unlink($qrFile);
        http_response_code(500);
        exit('No se pudo preparar el código QR del comprobante.');
    }
    try {
        $pdf->Image($qrFile, 158, 232, 30, 30, 'PNG');
    } finally {
        unlink($qrFile);
    }

    $pdf->Output('D', 'Comprobante_' . $pago['num_factura'] . '.pdf');
    $conn->close();
    exit();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Comprobante <?php echo htmlspecialchars($pago['num_factura']); ?></title>
    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        /* ── Pantalla ── */
        .factura-wrapper {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px 0 60px;
        }
        .invoice-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        /* Cabecera azul */
        .invoice-header {
            background: linear-gradient(135deg, #071527 0%, #004b87 55%, #0066b2 100%);
            padding: 36px 40px 28px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }
        .invoice-brand-name {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -0.5px;
            margin: 0 0 4px;
            color: #fff;
        }
        .invoice-brand-sub {
            font-size: 12px;
            color: rgba(255,255,255,0.65);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .invoice-brand-contact {
            margin-top: 10px;
            font-size: 12.5px;
            color: rgba(255,255,255,0.75);
            line-height: 1.8;
        }
        .invoice-title-block {
            text-align: right;
        }
        .invoice-title-block h2 {
            font-size: 30px;
            font-weight: 900;
            margin: 0;
            letter-spacing: -0.5px;
            color: #f59e0b;
        }
        .invoice-num {
            font-size: 16px;
            font-weight: 700;
            color: rgba(255,255,255,0.9);
            margin-top: 4px;
            font-family: 'Courier New', monospace;
        }
        .invoice-date-info {
            margin-top: 10px;
            font-size: 12.5px;
            color: rgba(255,255,255,0.65);
            line-height: 1.8;
            text-align: right;
        }

        /* Cuerpo */
        .invoice-body {
            padding: 32px 40px;
        }

        /* Cliente/Emisor */
        .parties-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 28px;
        }
        .party-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 18px 20px;
            border: 1px solid #e2e8f0;
        }
        .party-box-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            margin-bottom: 10px;
        }
        .party-box-name {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .party-box-detail {
            font-size: 13px;
            color: #64748b;
            line-height: 1.8;
        }

        /* Tabla de detalle */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .invoice-table thead th {
            background: #0c2340;
            color: #ffffff;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
        }
        .invoice-table thead th:first-child { border-radius: 10px 0 0 0; }
        .invoice-table thead th:last-child { border-radius: 0 10px 0 0; text-align: right; }
        .invoice-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13.5px;
            color: #334155;
        }
        .invoice-table tbody td:last-child { text-align: right; font-weight: 700; }
        .invoice-table tbody tr:last-child td { border-bottom: none; }

        /* Totales */
        .totals-block {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 28px;
        }
        .totals-table {
            min-width: 300px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 18px;
            font-size: 13.5px;
            border-bottom: 1px solid #f1f5f9;
        }
        .totals-row:last-child { border-bottom: none; }
        .totals-row .label { color: #64748b; font-weight: 600; }
        .totals-row .amount { font-weight: 700; color: #0f172a; }
        .totals-row.total-final {
            background: #004b87;
            padding: 14px 18px;
        }
        .totals-row.total-final .label { color: #ffffff; font-weight: 800; font-size: 15px; }
        .totals-row.total-final .amount { color: #f59e0b; font-weight: 900; font-size: 18px; }

        /* Pago info */
        .payment-info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }
        .info-badge-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 16px 20px;
        }
        .info-badge-box .title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #0284c7;
            margin-bottom: 6px;
        }
        .info-badge-box .value {
            font-size: 16px;
            font-weight: 800;
            color: #0c2340;
        }

        /* Estado pagado */
        .paid-stamp {
            position: absolute;
            top: 50%;
            right: 40px;
            transform: translateY(-50%) rotate(-18deg);
            border: 4px solid #16a34a;
            border-radius: 8px;
            padding: 6px 18px;
            font-size: 28px;
            font-weight: 900;
            color: #16a34a;
            opacity: 0.18;
            pointer-events: none;
            letter-spacing: 2px;
            white-space: nowrap;
        }

        /* Nota al pie */
        .invoice-footer {
            background: #f8fafc;
            border-top: 2px solid #e2e8f0;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .invoice-footer-note {
            font-size: 12px;
            color: #94a3b8;
            max-width: 480px;
        }
        .qr-placeholder {
            width: 64px;
            height: 64px;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
            font-size: 28px;
        }
        .invoice-qr img {
            display: block;
            width: 88px;
            height: 88px;
            margin: 0 auto;
            background: #fff;
        }
        body.public-receipt .nav-lateral,
        body.public-receipt .navbar-info {
            display: none !important;
        }
        body.public-receipt .page-content {
            width: 100% !important;
            margin: 0 !important;
            padding: 18px !important;
        }

        body {
            background: #f4f7fa;
            color: #334155;
        }
        .main-container {
            display: block;
            min-height: 100vh;
        }
        .nav-lateral,
        .navbar-info {
            display: none !important;
        }
        .page-content {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .invoice-page-shell {
            max-width: 820px;
            margin: 0 auto;
            padding: 28px 20px 48px;
        }
        .invoice-card {
            border: 1px solid #dbe4ed;
            border-radius: 8px;
            box-shadow: 0 8px 26px rgba(27, 52, 40, 0.07);
        }
        .invoice-header {
            align-items: center;
            background: #fff;
            border-top: 5px solid #f39b2d;
            border-bottom: 1px solid #dbe4ed;
            color: #334155;
            padding: 26px 32px;
        }
        .invoice-brand-name,
        .invoice-title-block h2 {
            color: #7d2a2a;
        }
        .invoice-brand-name {
            margin-bottom: 8px;
        }
        .invoice-logo {
            display: block;
            width: 250px;
            max-width: 100%;
            height: 74px;
            object-fit: cover;
            object-position: center 68%;
            transform: translateY(8px);
        }
        .invoice-brand-sub,
        .invoice-brand-contact,
        .invoice-date-info {
            color: #64748b;
            letter-spacing: 0;
        }
        .invoice-title-block h2 {
            font-size: 22px;
            letter-spacing: 0;
        }
        .invoice-num {
            color: #7d2a2a;
            border-bottom: 2px solid #f39b2d;
            display: inline-block;
            padding-bottom: 3px;
        }
        .invoice-body {
            padding: 26px 32px;
        }
        .parties-row {
            gap: 14px;
            margin-bottom: 22px;
        }
        .party-box {
            background: #fff;
            border: 1px solid #dbe4ed;
            border-radius: 6px;
        }
        .party-box-label {
            color: #7d2a2a;
            letter-spacing: 0;
        }
        .invoice-table {
            border: 1px solid #dbe4ed;
            border-radius: 6px;
            overflow: hidden;
        }
        .invoice-table thead th {
            background: #fbf0e5;
            color: #7d2a2a;
            letter-spacing: 0;
        }
        .invoice-table thead th:first-child,
        .invoice-table thead th:last-child {
            border-radius: 0;
        }
        .totals-table {
            min-width: min(100%, 300px);
            border-radius: 6px;
        }
        .totals-row.total-final {
            background: #7d2a2a;
            border-left: 5px solid #f39b2d;
        }
        .totals-row.total-final .amount {
            color: #fff;
        }
        .info-badge-box {
            background: #f7fafc;
            border: 1px solid #dbe4ed;
            border-radius: 6px;
        }
        .invoice-footer {
            background: #f8fafc;
            border-top: 1px solid #dbe4ed;
            padding: 18px 32px;
        }
        .invoice-footer-note {
            color: #64748b;
            line-height: 1.65;
        }
        .invoice-qr img {
            width: 96px;
            height: 96px;
            padding: 4px;
            border: 1px solid #dbe4ed;
            border-radius: 4px;
        }
        .action-buttons {
            justify-content: space-between;
            align-items: center;
            max-width: 760px;
            margin: 0 auto 14px;
        }
        .btn-back,
        .btn-print-main,
        .btn-pdf {
            border-radius: 6px;
            text-decoration: none !important;
        }
        .btn-pdf {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #f39b2d;
            border-bottom: 3px solid #d87c16;
            color: #342d2c !important;
            font-weight: 700;
            font-size: 13.5px;
        }
        .btn-pdf:hover {
            background: #e88b1d;
            color: #342d2c !important;
        }
        .btn-print-main {
            background: #7d2a2a;
            border-bottom: 3px solid #f39b2d;
        }
        .btn-print-main:hover {
            background: #662222;
        }
        @media (max-width: 620px) {
            .invoice-page-shell {
                padding: 14px 12px 28px;
            }
            .invoice-header,
            .invoice-body {
                padding: 20px;
            }
            .invoice-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .invoice-logo {
                transform: none;
            }
            .invoice-title-block,
            .invoice-date-info {
                text-align: left;
            }
            .parties-row,
            .payment-info-row {
                grid-template-columns: 1fr;
            }
            .invoice-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            .invoice-footer {
                padding: 18px 20px;
            }
        }

        /* Botones de acción en pantalla */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #f1f5f9;
            color: #334155 !important;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13.5px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            text-decoration: none !important;
        }
        .btn-back:hover { background: #e2e8f0; }
        .btn-print-main {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: #7d2a2a;
            color: #fff !important;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13.5px;
            transition: all 0.2s;
            text-decoration: none !important;
            border: none;
            cursor: pointer;
        }
        .btn-print-main:hover {
            background: #662222;
            box-shadow: 0 4px 14px rgba(125,42,42,0.22);
            transform: translateY(-1px);
            color: #fff !important;
        }
        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #065f46;
            color: #fff !important;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13.5px;
            transition: all 0.2s;
            text-decoration: none !important;
            border: none;
            cursor: pointer;
        }
        .btn-download:hover { background: #047857; transform: translateY(-1px); color:#fff!important; }

        /* ── IMPRESIÓN ── */
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body { background: #fff !important; }
            body * { visibility: visible !important; }
            .nav-lateral, .navbar-info, .page-header, .page-nav-tabs, .action-buttons, .no-print {
                display: none !important;
            }
            .main-container,
            .page-content {
                display: block !important;
                width: 100%;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .invoice-page-shell {
                position: static;
                max-width: none;
                width: 100%;
                margin: 0;
                padding: 0 !important;
            }
            .invoice-page-shell {
                padding: 0 !important;
            }
            .factura-wrapper {
                padding: 0 !important;
                max-width: none !important;
            }
            .invoice-card {
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            .invoice-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body class="<?php echo $es_comprobante_publico ? 'public-receipt' : ''; ?>">
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

        <div class="invoice-page-shell">

            <!-- Botones de acción -->
            <div class="action-buttons">
                <?php if(!$es_comprobante_publico): ?>
                <a href="facturas.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver a Facturas</a>
                <?php endif; ?>
                <a class="btn-pdf" href="factura_ver.php<?php echo $es_comprobante_publico ? '?token=' . urlencode($token_comprobante) . '&pdf=1' : '?id=' . (int)$id_pago . '&pdf=1'; ?>">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </a>
                <button onclick="window.print()" class="btn-print-main"><i class="fas fa-print"></i> Imprimir o guardar comprobante</button>
            </div>

            <!-- Factura -->
            <div class="factura-wrapper">
                <div class="invoice-card">
                    <!-- Encabezado -->
                    <div class="invoice-header">
                        <div>
                            <div class="invoice-brand-name">
                                <img class="invoice-logo" src="./aurea.jpg" alt="Logo de AureaMed">
                            </div>
                            <div class="invoice-brand-sub"><?php echo htmlspecialchars($empresa_nombre); ?></div>
                            <div class="invoice-brand-contact">
                                <i class="fas fa-map-marker-alt" style="width:16px;"></i> <?php echo htmlspecialchars($empresa_direccion); ?><br>
                                <i class="fas fa-phone" style="width:16px;"></i> <?php echo htmlspecialchars($empresa_telefono); ?><br>
                                <i class="fas fa-envelope" style="width:16px;"></i> <?php echo htmlspecialchars($empresa_email); ?>
                            </div>
                        </div>
                        <div class="invoice-title-block">
                            <h2>COMPROBANTE DE PAGO</h2>
                            <div class="invoice-num"><?php echo htmlspecialchars($pago['num_factura']); ?></div>
                            <div class="invoice-date-info">
                                <strong>Emitido:</strong> <?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?>
                                <span style="margin-left:8px;"> <?php echo date('H:i', strtotime($pago['fecha_registro'])); ?></span><br>
                                <span style="background:#fbf0e5;color:#7d2a2a;padding:4px 10px;border-radius:4px;font-size:11px;font-weight:700;margin-top:8px;display:inline-block;border-left:3px solid #f39b2d;">PAGO CONFIRMADO</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cuerpo -->
                    <div class="invoice-body">

                        <!-- Emisor / Receptor -->
                        <div class="parties-row">
                            <div class="party-box">
                                <div class="party-box-label"><i class="fas fa-building"></i> &nbsp; Centro de atención</div>
                                <div class="party-box-name"><?php echo htmlspecialchars($empresa_nombre); ?></div>
                                <div class="party-box-detail">
                                    <?php echo htmlspecialchars($empresa_ruc); ?><br>
                                    <?php echo htmlspecialchars($empresa_direccion); ?><br>
                                    <?php echo htmlspecialchars($empresa_telefono); ?>
                                </div>
                            </div>
                            <div class="party-box">
                                <div class="party-box-label"><i class="fas fa-user"></i> &nbsp; Paciente</div>
                                <div class="party-box-name"><?php echo htmlspecialchars($pago['paciente_apellido'] . ' ' . $pago['paciente_nombre']); ?></div>
                                <div class="party-box-detail">
                                    <strong>Cédula:</strong> <?php echo htmlspecialchars($pago['cedula']); ?><br>
                                    <?php if(!empty($pago['telefono'])): ?><strong>Tel:</strong> <?php echo htmlspecialchars($pago['telefono']); ?><br><?php endif; ?>
                                    <?php if(!empty($pago['email'])): ?><?php echo htmlspecialchars($pago['email']); ?><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Detalle del servicio -->
                        <table class="invoice-table">
                            <thead>
                                <tr>
                                    <th>Servicio</th>
                                    <th style="text-align:center;">Fecha de atención</th>
                                    <th style="text-align:right;">Importe pagado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong style="color:#0f172a;"><?php echo htmlspecialchars($pago['nombre_servicio']); ?></strong><br>
                                        <small style="color:#64748b;">Servicio de AureaMed</small>
                                    </td>
                                    <td style="text-align:center;color:#475569;">
                                        <?php echo date('d/m/Y', strtotime($pago['fecha_reserva'])); ?>
                                        <?php if($pago['hora_reserva']): ?><br><small><?php echo date('H:i', strtotime($pago['hora_reserva'])); ?></small><?php endif; ?>
                                    </td>
                                    <td>Bs. <?php echo number_format($pago['monto'], 2); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Totales -->
                        <div class="totals-block">
                            <div class="totals-table">
                                <div class="totals-row total-final">
                                    <span class="label">TOTAL PAGADO</span>
                                    <span class="amount">Bs. <?php echo number_format($pago['monto'], 2); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Método de pago / Reserva -->
                        <div class="payment-info-row">
                            <div class="info-badge-box">
                                <div class="title"><i class="fas fa-credit-card"></i> &nbsp; Método de Pago</div>
                                <div class="value">
                                    <?php
                                    $icons = ['Efectivo'=>'money-bill-wave','Tarjeta'=>'credit-card','Transferencia'=>'exchange-alt','Cheque'=>'file-alt'];
                                    $ic = $icons[$pago['metodo']] ?? 'dollar-sign';
                                    ?>
                                    <i class="fas fa-<?php echo $ic; ?>" style="margin-right:6px;color:#0284c7;"></i>
                                    <?php echo htmlspecialchars($pago['metodo']); ?>
                                </div>
                            </div>
                            <div class="info-badge-box" style="background:#f0fdf4;border-color:#bbf7d0;">
                                <div class="title" style="color:#16a34a;"><i class="fas fa-check-circle"></i> &nbsp; Estado del Pago</div>
                                <div class="value" style="color:#15803d;">
                                    <i class="fas fa-check-circle" style="margin-right:6px;"></i> PAGADO
                                </div>
                            </div>
                        </div>

                        <!-- Nota/Observaciones -->
                        <?php if(!empty($pago['nota'])): ?>
                        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px 18px;margin-bottom:20px;">
                            <div style="font-size:11.5px;font-weight:800;color:#92400e;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                                <i class="fas fa-sticky-note"></i> Observaciones
                            </div>
                            <div style="font-size:13.5px;color:#78350f;"><?php echo nl2br(htmlspecialchars($pago['nota'])); ?></div>
                        </div>
                        <?php endif; ?>

                        <!-- Cajero -->
                        <div style="font-size:12.5px;color:#94a3b8;text-align:right;margin-bottom:8px;">
                            Atendido por <strong style="color:#475569;"><?php echo htmlspecialchars($pago['cajero_nombre']); ?></strong>
                            <span class="no-print">&nbsp;|&nbsp; Reserva #<?php echo $pago['id_reserva']; ?></span>
                        </div>

                    </div><!-- /invoice-body -->

                    <!-- Pie de factura -->
                    <div class="invoice-footer">
                        <div class="invoice-footer-note">
                            <strong style="color:#7d2a2a;">Gracias por elegir <?php echo htmlspecialchars($empresa_nombre); ?>.</strong><br>
                            Este comprobante confirma su pago. Guárdelo o escanee el QR para consultarlo nuevamente.
                            Consultas: <?php echo htmlspecialchars($empresa_telefono); ?>.
                        </div>
                        <div class="invoice-qr" style="text-align:center;">
                            <img src="qr_comprobante.php?token=<?php echo urlencode($pago['token_comprobante']); ?>" alt="QR para abrir este comprobante">
                            <div style="font-size:10px;color:#7d2a2a;margin-top:5px;">Escanee para consultar</div>
                        </div>
                    </div>

                </div><!-- /invoice-card -->
            </div><!-- /factura-wrapper -->

        </div>
    </section>
</main>

<script src="./js/jquery-3.4.1.min.js"></script>
<script src="./js/popper.min.js"></script>
<script src="./js/bootstrap.min.js"></script>
<script src="./js/main.js"></script>
<?php if($auto_print): ?>
<script>window.addEventListener('load', function() { setTimeout(function(){ window.print(); }, 800); });</script>
<?php endif; ?>
</body>
</html>
