<?php

function aureamed_db_connect()
{
    $local_config = __DIR__ . '/db_config.local.php';
    $config = is_file($local_config)
        ? require $local_config
        : require __DIR__ . '/db_config.example.php';

    $required_keys = array('host', 'username', 'password', 'database');
    foreach ($required_keys as $key) {
        if (!isset($config[$key]) || !is_string($config[$key])) {
            throw new RuntimeException('La configuración de base de datos no es válida.');
        }
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $port = isset($config['port']) ? (int) $config['port'] : 3306;
    $conn = new mysqli(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database'],
        $port
    );

    if (!$conn->connect_error) {
        $conn->set_charset('utf8mb4');
    }

    return $conn;
}
