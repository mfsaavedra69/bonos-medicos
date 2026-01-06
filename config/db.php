<?php
// config/db.php
// Configuración sencilla para MySQL (ajustar según tu entorno)
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'bonos';
$DB_USER = getenv('DB_USER') ?: 'bonos';
$DB_PASS = getenv('DB_PASS') ?: 'bonos';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    // Loguear error, no exponer datos en producción
    error_log('DB connect error: ' . $conn->connect_error);
    // No hacemos exit aquí para que la app pueda mostrar mensaje amigable
}

// función pequeña para obtener JSON y terminar
function json_response($data, $code = 200) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode($data);
    exit;
}
