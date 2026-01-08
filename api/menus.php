<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare('SELECT * FROM menus WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            json_response($row ?: []);
        }
        $result = $conn->query('SELECT * FROM menus ORDER BY orden ASC');
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        json_response(['data' => $rows]);
        break;

    case 'POST':
        $clave = trim($_POST['clave'] ?? '');
        $etiqueta = trim($_POST['etiqueta'] ?? '');
        $ruta = trim($_POST['ruta'] ?? '');
        $orden = intval($_POST['orden'] ?? 0);

        if ($clave === '' || $etiqueta === '' || $ruta === '') json_response(['error' => 'Datos incompletos'], 422);

        $stmt = $conn->prepare('INSERT INTO menus (clave, etiqueta, ruta, orden) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('sssi', $clave, $etiqueta, $ruta, $orden);
        if ($stmt->execute()) json_response(['success' => true, 'id' => $conn->insert_id]);
        json_response(['error' => $stmt->error], 500);
        break;

    case 'PUT':
        parse_str(file_get_contents('php://input'), $putvars);
        $id = intval($putvars['id'] ?? 0);
        $clave = trim($putvars['clave'] ?? '');
        $etiqueta = trim($putvars['etiqueta'] ?? '');
        $ruta = trim($putvars['ruta'] ?? '');
        $orden = intval($putvars['orden'] ?? 0);

        if (!$id || $clave === '' || $etiqueta === '' || $ruta === '') json_response(['error' => 'Datos inválidos'], 422);

        $stmt = $conn->prepare('UPDATE menus SET clave = ?, etiqueta = ?, ruta = ?, orden = ? WHERE id = ?');
        $stmt->bind_param('sssii', $clave, $etiqueta, $ruta, $orden, $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
        break;

    case 'DELETE':
        parse_str(file_get_contents('php://input'), $delvars);
        $id = intval($delvars['id'] ?? 0);
        if (!$id) json_response(['error' => 'ID requerido'], 422);
        $stmt = $conn->prepare('DELETE FROM menus WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
        break;

    default:
        json_response(['error' => 'Método no permitido'], 405);
}
