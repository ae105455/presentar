<?php
function asegurarTokensComprobante($conn)
{
    $columnCheck = $conn->query("SHOW COLUMNS FROM pagos LIKE 'token_comprobante'");
    if (!$columnCheck) {
        return false;
    }

    if ($columnCheck->num_rows === 0 && !$conn->query("ALTER TABLE pagos ADD token_comprobante CHAR(64) NULL DEFAULT NULL UNIQUE")) {
        return false;
    }

    $pendientes = $conn->query("SELECT id_pago FROM pagos WHERE token_comprobante IS NULL OR token_comprobante = ''");
    $actualizar = $conn->prepare("UPDATE pagos SET token_comprobante = ? WHERE id_pago = ?");
    if (!$pendientes || !$actualizar) {
        return false;
    }

    while ($pago = $pendientes->fetch_assoc()) {
        $token = bin2hex(random_bytes(32));
        $id_pago = (int)$pago['id_pago'];
        $actualizar->bind_param('si', $token, $id_pago);
        if (!$actualizar->execute()) {
            $actualizar->close();
            return false;
        }
    }

    $actualizar->close();
    return true;
}

function obtenerMatrizQr($contenido, $nivel, $margen)
{
    require_once __DIR__ . '/QR2026/phpqrcode/qrlib.php';
    $matriz = QRcode::text($contenido, false, $nivel, 1, $margen);
    if (!is_array($matriz) || count($matriz) === 0) {
        throw new RuntimeException('No se pudo generar la matriz del código QR.');
    }

    return $matriz;
}

function generarCodigoQrSvg($contenido, $nivel = 'M', $margen = 2)
{
    $matriz = obtenerMatrizQr($contenido, $nivel, $margen);
    $dimension = count($matriz);
    $modulos = [];

    foreach ($matriz as $fila => $linea) {
        if (strlen($linea) !== $dimension) {
            throw new RuntimeException('La matriz del código QR no es válida.');
        }

        for ($columna = 0; $columna < $dimension; $columna++) {
            if ($linea[$columna] === '1') {
                $modulos[] = 'M' . $columna . ' ' . $fila . 'h1v1h-1z';
            }
        }
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $dimension . '" height="' . $dimension
        . '" viewBox="0 0 ' . $dimension . ' ' . $dimension . '" shape-rendering="crispEdges">'
        . '<rect width="100%" height="100%" fill="#fff"/>'
        . '<path d="' . implode('', $modulos) . '" fill="#000"/>'
        . '</svg>';
}

function generarCodigoQrPng($contenido, $nivel = 'M', $margen = 2, $escala = 4)
{
    if ($escala < 1) {
        throw new InvalidArgumentException('La escala del código QR debe ser mayor que cero.');
    }

    $matriz = obtenerMatrizQr($contenido, $nivel, $margen);
    $dimension = count($matriz);
    $ancho = $dimension * $escala;
    $datos = '';

    foreach ($matriz as $linea) {
        if (strlen($linea) !== $dimension) {
            throw new RuntimeException('La matriz del código QR no es válida.');
        }

        $fila = '';
        for ($columna = 0; $columna < $dimension; $columna++) {
            $fila .= str_repeat($linea[$columna] === '1' ? "\x00" : "\xff", $escala);
        }

        for ($repeticion = 0; $repeticion < $escala; $repeticion++) {
            $datos .= "\x00" . $fila;
        }
    }

    $comprimir = gzcompress($datos);
    if ($comprimir === false) {
        throw new RuntimeException('No se pudo comprimir la imagen del código QR.');
    }

    $crearBloque = static function($tipo, $contenido) {
        return pack('N', strlen($contenido)) . $tipo . $contenido . pack('N', crc32($tipo . $contenido));
    };

    return "\x89PNG\r\n\x1a\n"
        . $crearBloque('IHDR', pack('NNCCCCC', $ancho, $ancho, 8, 0, 0, 0, 0))
        . $crearBloque('IDAT', $comprimir)
        . $crearBloque('IEND', '');
}