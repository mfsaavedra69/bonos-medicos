<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Buscar por código (útil para validación frontend)
        if (isset($_GET['codigo'])) {
            $codigo = trim($_GET['codigo']);
            $stmt = $conn->prepare('SELECT * FROM practicas WHERE codigo = ? LIMIT 1');
            $stmt->bind_param('s', $codigo);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            json_response($row ?: []);
        }

        // búsqueda por texto (q) para autocompletar
        if (isset($_GET['q'])) {
            $qraw = trim($_GET['q']);
            $like = "%" . $qraw . "%";
            $idInt = is_numeric($qraw) ? intval($qraw) : 0;
            $stmt = $conn->prepare('SELECT id, codigo, descripcion, precio FROM practicas WHERE codigo LIKE ? OR descripcion LIKE ? OR id = ? LIMIT 20');
            $stmt->bind_param('ssi', $like, $like, $idInt);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = [];
            while ($r = $res->fetch_assoc()) $rows[] = $r;
            json_response(['data' => $rows]);
        }

        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare('SELECT * FROM practicas WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            if ($row) {
                $stmt2 = $conn->prepare('SELECT especialidad_id FROM practica_especialidad WHERE practica_id = ?');
                $stmt2->bind_param('i', $id);
                $stmt2->execute();
                $r2 = $stmt2->get_result();
                $esp = [];
                while ($e = $r2->fetch_assoc()) $esp[] = intval($e['especialidad_id']);
                $row['especialidades'] = $esp;
            }
            json_response($row ?: []);
        }

        $result = $conn->query('SELECT * FROM practicas ORDER BY id DESC');
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        json_response(['data' => $rows]);
        break;

    case 'POST':
        $codigo = trim($_POST['codigo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0.0;
        $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;
        $especialidades = trim($_POST['especialidades'] ?? ''); // comma-separated ids

        if ($codigo === '' || $descripcion === '') json_response(['error' => 'Datos incompletos'], 422);

        // Validar unicidad de código
        $chk = $conn->prepare('SELECT id FROM practicas WHERE codigo = ? LIMIT 1');
        $chk->bind_param('s', $codigo);
        $chk->execute();
        $resChk = $chk->get_result();
        if ($resChk && $resChk->num_rows > 0) json_response(['error' => 'Código de práctica ya existe'], 422);

        $stmt = $conn->prepare('INSERT INTO practicas (codigo, descripcion, precio, estado, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->bind_param('ssdi', $codigo, $descripcion, $precio, $estado);
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            if ($especialidades !== '') {
                $ids = array_filter(array_map('intval', explode(',', $especialidades)));
                $stmtIns = $conn->prepare('INSERT INTO practica_especialidad (practica_id, especialidad_id) VALUES (?, ?)');
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
        $codigo = trim($putvars['codigo'] ?? '');
        $descripcion = trim($putvars['descripcion'] ?? '');
        $precio = isset($putvars['precio']) ? floatval($putvars['precio']) : 0.0;
        $estado = isset($putvars['estado']) ? intval($putvars['estado']) : 1;
        $especialidades = trim($putvars['especialidades'] ?? '');

        if (!$id || $codigo === '' || $descripcion === '') json_response(['error' => 'Datos inválidos'], 422);

        // Verificar que ningún otro registro tenga el mismo código
        $chk = $conn->prepare('SELECT id FROM practicas WHERE codigo = ? AND id <> ? LIMIT 1');
        $chk->bind_param('si', $codigo, $id);
        $chk->execute();
        $resChk = $chk->get_result();
        if ($resChk && $resChk->num_rows > 0) json_response(['error' => 'Código de práctica ya existe en otro registro'], 422);

        $stmt = $conn->prepare('UPDATE practicas SET codigo = ?, descripcion = ?, precio = ?, estado = ? WHERE id = ?');
        $stmt->bind_param('ssdii', $codigo, $descripcion, $precio, $estado, $id);
        if ($stmt->execute()) {
            // sincronizar especialidades
            $stmtDel = $conn->prepare('DELETE FROM practica_especialidad WHERE practica_id = ?');
            $stmtDel->bind_param('i', $id);
            $stmtDel->execute();
            if ($especialidades !== '') {
                $ids = array_filter(array_map('intval', explode(',', $especialidades)));
                $stmtIns = $conn->prepare('INSERT INTO practica_especialidad (practica_id, especialidad_id) VALUES (?, ?)');
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
        $stmt = $conn->prepare('DELETE FROM practicas WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
        break;

    default:
        json_response(['error' => 'Método no permitido'], 405);
}
