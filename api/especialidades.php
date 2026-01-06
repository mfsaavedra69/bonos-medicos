<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Lista o item
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare('SELECT * FROM especialidades WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            json_response($row ?: []);
        }
        $result = $conn->query('SELECT * FROM especialidades ORDER BY id DESC');
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        // Devolver en formato { data: [...] } para compatibilidad con DataTables
        json_response(['data' => $rows]);
        break;
    case 'POST':
        // Crear
        $nombre = trim($_POST['nombre'] ?? '');
        $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;
        if ($nombre === '') json_response(['error' => 'Nombre requerido'], 422);
        $stmt = $conn->prepare('INSERT INTO especialidades (nombre, estado) VALUES (?, ?)');
        $stmt->bind_param('si', $nombre, $estado);
        if ($stmt->execute()) json_response(['success' => true, 'id' => $conn->insert_id]);
        json_response(['error' => $stmt->error], 500);
        break;
    case 'PUT':
        // Actualizar
        parse_str(file_get_contents('php://input'), $putvars);
        $id = intval($putvars['id'] ?? 0);
        $nombre = trim($putvars['nombre'] ?? '');
        $estado = isset($putvars['estado']) ? intval($putvars['estado']) : 1;
        if (!$id || $nombre === '') json_response(['error' => 'Datos inválidos'], 422);
        $stmt = $conn->prepare('UPDATE especialidades SET nombre = ?, estado = ? WHERE id = ?');
        $stmt->bind_param('sii', $nombre, $estado, $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
        break;
    case 'DELETE':
        parse_str(file_get_contents('php://input'), $delvars);
        $id = intval($delvars['id'] ?? 0);
        if (!$id) json_response(['error' => 'ID requerido'], 422);
        $stmt = $conn->prepare('DELETE FROM especialidades WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
        break;
    default:
        json_response(['error' => 'Método no permitido'], 405);
}
