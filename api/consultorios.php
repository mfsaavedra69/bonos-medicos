<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare('SELECT * FROM consultorios WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            json_response($row ?: []);
        }

        $result = $conn->query('SELECT * FROM consultorios ORDER BY id DESC');
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        json_response(['data' => $rows]);
        break;
    case 'POST':
        $nombre = trim($_POST['nombre'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;

        if ($nombre === '') json_response(['error' => 'Nombre requerido'], 422);

        $stmt = $conn->prepare('INSERT INTO consultorios (nombre, direccion, telefono, email, estado, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->bind_param('ssssi', $nombre, $direccion, $telefono, $email, $estado);
        if ($stmt->execute()) json_response(['success' => true, 'id' => $conn->insert_id]);
        json_response(['error' => $stmt->error], 500);
        break;
    case 'PUT':
        parse_str(file_get_contents('php://input'), $putvars);
        $id = intval($putvars['id'] ?? 0);
        $nombre = trim($putvars['nombre'] ?? '');
        $direccion = trim($putvars['direccion'] ?? '');
        $telefono = trim($putvars['telefono'] ?? '');
        $email = trim($putvars['email'] ?? '');
        $estado = isset($putvars['estado']) ? intval($putvars['estado']) : 1;

        if (!$id || $nombre === '') json_response(['error' => 'Datos inválidos'], 422);

        $stmt = $conn->prepare('UPDATE consultorios SET nombre = ?, direccion = ?, telefono = ?, email = ?, estado = ? WHERE id = ?');
        $stmt->bind_param('ssssii', $nombre, $direccion, $telefono, $email, $estado, $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
        break;
    case 'DELETE':
        parse_str(file_get_contents('php://input'), $delvars);
        $id = intval($delvars['id'] ?? 0);
        if (!$id) json_response(['error' => 'ID requerido'], 422);
        $stmt = $conn->prepare('DELETE FROM consultorios WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
        break;
    default:
        json_response(['error' => 'Método no permitido'], 405);
}
