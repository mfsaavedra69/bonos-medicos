<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // búsqueda por texto (q) para autocompletar afiliados
        if (isset($_GET['q'])) {
            $qraw = trim($_GET['q']);
            $like = "%" . $qraw . "%";
            $stmt = $conn->prepare("SELECT a.*, (SELECT GROUP_CONCAT(CONCAT(d.nombre,' ',d.apellido, IF(d.dni IS NOT NULL AND d.dni != '', CONCAT(' (', d.dni, ')'), '')) SEPARATOR ' | ') FROM afiliado_dependientes d WHERE d.afiliado_id = a.id) AS dependientes_text FROM afiliados a WHERE a.dni LIKE ? OR a.nombre LIKE ? OR a.apellido LIKE ? OR EXISTS (SELECT 1 FROM afiliado_dependientes ad WHERE ad.afiliado_id = a.id AND (ad.nombre LIKE ? OR ad.apellido LIKE ? OR ad.dni LIKE ?)) ORDER BY a.id DESC LIMIT 20");
            $stmt->bind_param('ssssss', $like, $like, $like, $like, $like, $like);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = [];
            while ($r = $res->fetch_assoc()) {
                if (!isset($r['dependientes_text']) || $r['dependientes_text'] === null) $r['dependientes_text'] = '';
                $rows[] = $r;
            }
            json_response(['data' => $rows]);
        }

        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare('SELECT * FROM afiliados WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            if ($row) {
                // cargar dependientes
                $stmt2 = $conn->prepare('SELECT id, nombre, apellido, relacion, fecha_nacimiento, dni FROM afiliado_dependientes WHERE afiliado_id = ?');
                $stmt2->bind_param('i', $id);
                $stmt2->execute();
                $r2 = $stmt2->get_result();
                $deps = [];
                while ($d = $r2->fetch_assoc()) $deps[] = $d;
                $row['dependientes'] = $deps;
            }
            json_response($row ?: []);
        }

        // Obtener lista y concatenar dependientes en un solo campo para búsqueda (incluye DNI cuando exista)
        $sql = "SELECT a.*, (SELECT GROUP_CONCAT(CONCAT(d.nombre,' ',d.apellido, IF(d.dni IS NOT NULL AND d.dni != '', CONCAT(' (', d.dni, ')'), '')) SEPARATOR ' | ') FROM afiliado_dependientes d WHERE d.afiliado_id = a.id) AS dependientes_text FROM afiliados a ORDER BY a.id DESC";
        $result = $conn->query($sql);
        $rows = [];
        while ($r = $result->fetch_assoc()) {
            if (!isset($r['dependientes_text']) || $r['dependientes_text'] === null) $r['dependientes_text'] = '';
            $rows[] = $r;
        }
        json_response(['data' => $rows]);
        break;

    case 'POST':
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $cuil = trim($_POST['cuil'] ?? '');
        $fecha_alta = trim($_POST['fecha_alta'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;
        $dependientes = $_POST['dependientes'] ?? '[]'; // JSON string

        if ($nombre === '' || $apellido === '' || $cuil === '') json_response(['error' => 'Datos incompletos'], 422);

        $stmt = $conn->prepare('INSERT INTO afiliados (dni, nombre, apellido, fecha_nacimiento, telefono, email, estado, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        // usamos dni para almacenar cuil si la estructura original usaba DNI
        // tipos: dni(s), nombre(s), apellido(s), fecha_nacimiento(s), telefono(s), email(s), estado(i)
        $stmt->bind_param('ssssssi', $cuil, $nombre, $apellido, $fecha_alta, $telefono, $email, $estado);
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            // insertar dependientes
            $deps = json_decode($dependientes, true) ?: [];
            $stmtIns = $conn->prepare('INSERT INTO afiliado_dependientes (afiliado_id, nombre, apellido, relacion, fecha_nacimiento, dni) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($deps as $d) {
                $n = $d['nombre'] ?? '';
                $a = $d['apellido'] ?? '';
                $rel = $d['relacion'] ?? '';
                $fn = $d['fecha_nacimiento'] ?? null;
                $dni = $d['dni'] ?? null;
                $stmtIns->bind_param('isssss', $id, $n, $a, $rel, $fn, $dni);
                $stmtIns->execute();
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
        $cuil = trim($putvars['cuil'] ?? '');
        $fecha_alta = trim($putvars['fecha_alta'] ?? '');
        $telefono = trim($putvars['telefono'] ?? '');
        $email = trim($putvars['email'] ?? '');
        $estado = isset($putvars['estado']) ? intval($putvars['estado']) : 1;
        $dependientes = $putvars['dependientes'] ?? '[]';

        if (!$id || $nombre === '' || $apellido === '' || $cuil === '') json_response(['error' => 'Datos inválidos'], 422);

        $stmt = $conn->prepare('UPDATE afiliados SET dni = ?, nombre = ?, apellido = ?, fecha_nacimiento = ?, telefono = ?, email = ?, estado = ? WHERE id = ?');
        // tipos: dni(s), nombre(s), apellido(s), fecha_nacimiento(s), telefono(s), email(s), estado(i), id(i)
        $stmt->bind_param('ssssssii', $cuil, $nombre, $apellido, $fecha_alta, $telefono, $email, $estado, $id);
        if ($stmt->execute()) {
            // sincronizar dependientes: eliminar y volver a insertar
            $stmtDel = $conn->prepare('DELETE FROM afiliado_dependientes WHERE afiliado_id = ?');
            $stmtDel->bind_param('i', $id);
            $stmtDel->execute();

            $deps = json_decode($dependientes, true) ?: [];
            $stmtIns = $conn->prepare('INSERT INTO afiliado_dependientes (afiliado_id, nombre, apellido, relacion, fecha_nacimiento, dni) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($deps as $d) {
                $n = $d['nombre'] ?? '';
                $a = $d['apellido'] ?? '';
                $rel = $d['relacion'] ?? '';
                $fn = $d['fecha_nacimiento'] ?? null;
                $dni = $d['dni'] ?? null;
                $stmtIns->bind_param('isssss', $id, $n, $a, $rel, $fn, $dni);
                $stmtIns->execute();
            }
            json_response(['success' => true]);
        }
        json_response(['error' => $stmt->error], 500);
        break;

    case 'DELETE':
        parse_str(file_get_contents('php://input'), $delvars);
        $id = intval($delvars['id'] ?? 0);
        if (!$id) json_response(['error' => 'ID requerido'], 422);
        $stmt = $conn->prepare('DELETE FROM afiliados WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
        break;

    default:
        json_response(['error' => 'Método no permitido'], 405);
}
