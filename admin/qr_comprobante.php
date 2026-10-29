<?php
require_once __DIR__ . '/db_connection.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if (!preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
    http_response_code(404);
    exit();
}

$conn = aureamed_db_connect();
if ($conn->connect_error) {
    http_response_code(503);
    exit();
}

$consulta = $conn->prepare('SELECT id_pago FROM pagos WHERE token_comprobante = ? LIMIT 1');
if (!$consulta) {
    http_response_code(404);
    $conn->close();
    exit();
}
$consulta->bind_param('s', $token);
$consulta->execute();
$existe = $consulta->get_result()->num_rows === 1;
$consulta->close();
$conn->close();

if (!$existe) {
    http_response_code(404);
    exit();
}

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
if (!preg_match('/\A[a-zA-Z0-9.\-:\[\]]+\z/', $host)) {
    $host = 'localhost';
}
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$receiptUrl = $scheme . '://' . $host . $basePath . '/factura_ver.php?token=' . rawurlencode($token);

require_once __DIR__ . '/comprobante_helpers.php';
header('Cache-Control: private, no-store');
header('Content-Type: image/svg+xml; charset=utf-8');
echo generarCodigoQrSvg($receiptUrl, 'M', 2);