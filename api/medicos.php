<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare('SELECT * FROM medicos WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            if ($row) {
                // cargar especialidades asociadas
                $stmt2 = $conn->prepare('SELECT especialidad_id FROM medico_especialidad WHERE medico_id = ?');
                $stmt2->bind_param('i', $id);
                $stmt2->execute();
                $r2 = $stmt2->get_result();
                $esp = [];
                while ($e = $r2->fetch_assoc()) $esp[] = intval($e['especialidad_id']);
                $row['especialidades'] = $esp;
            }
            json_response($row ?: []);
        }
        $result = $conn->query('SELECT * FROM medicos ORDER BY id DESC');
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        json_response(['data' => $rows]);
        break;
    case 'POST':
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $matricula = trim($_POST['matricula'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;
        $especialidades = trim($_POST['especialidades'] ?? ''); // comma-separated ids

        if ($nombre === '' || $apellido === '' || $matricula === '') json_response(['error' => 'Datos incompletos'], 422);

        $stmt = $conn->prepare('INSERT INTO medicos (nombre, apellido, matricula, telefono, email, estado) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssi', $nombre, $apellido, $matricula, $telefono, $email, $estado);
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            // sincronizar especialidades
            if ($especialidades !== '') {
                $ids = array_filter(array_map('intval', explode(',', $especialidades)));
                $stmtDel = $conn->prepare('DELETE FROM medico_especialidad WHERE medico_id = ?');
                $stmtDel->bind_param('i', $id);
                $stmtDel->execute();
                $stmtIns = $conn->prepare('INSERT INTO medico_especialidad (medico_id, especialidad_id) VALUES (?, ?)');
                foreach ($ids as $espId) {
                    $stmtIns->bind_param('ii', $id, $espId);
                    $stmtIns->execute();
                }
            }
            json_response(['success' => true, 'id' => $id]);
        }
        json_response(['error' => $stmt->error], 500);
        break;
    case 'PUT':
        parse_str(file_get_contents('php://input'), $putvars);
        $id = intval($putvars['id'] ?? 0);
        $nombre = trim($putvars['nombre'] ?? '');
        $apellido = trim($putvars['apellido'] ?? '');
        $matricula = trim($putvars['matricula'] ?? '');
        $telefono = trim($putvars['telefono'] ?? '');
        $email = trim($putvars['email'] ?? '');
        $estado = isset($putvars['estado']) ? intval($putvars['estado']) : 1;
        $especialidades = trim($putvars['especialidades'] ?? '');

        if (!$id || $nombre === '' || $apellido === '' || $matricula === '') json_response(['error' => 'Datos inválidos'], 422);

        $stmt = $conn->prepare('UPDATE medicos SET nombre = ?, apellido = ?, matricula = ?, telefono = ?, email = ?, estado = ? WHERE id = ?');
        $stmt->bind_param('ssssiii', $nombre, $apellido, $matricula, $telefono, $email, $estado, $id);
        if ($stmt->execute()) {
            // sincronizar especialidades
            $stmtDel = $conn->prepare('DELETE FROM medico_especialidad WHERE medico_id = ?');
            $stmtDel->bind_param('i', $id);
            $stmtDel->execute();
            if ($especialidades !== '') {
                $ids = array_filter(array_map('intval', explode(',', $especialidades)));
                $stmtIns = $conn->prepare('INSERT INTO medico_especialidad (medico_id, especialidad_id) VALUES (?, ?)');
                foreach ($ids as $espId) {
                    $stmtIns->bind_param('ii', $id, $espId);
                    $stmtIns->execute();
                }
            }
            json_response(['success' => true]);
        }
        json_response(['error' => $stmt->error], 500);
        break;
    case 'DELETE':
        parse_str(file_get_contents('php://input'), $delvars);
        $id = intval($delvars['id'] ?? 0);
        if (!$id) json_response(['error' => 'ID requerido'], 422);
        $stmt = $conn->prepare('DELETE FROM medicos WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
        break;
    default:
        json_response(['error' => 'Método no permitido'], 405);
}
