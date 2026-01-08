<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$method = $_SERVER['REQUEST_METHOD'];

// Helper: obtener permisos (menu ids) de un usuario
function get_user_menus($conn, $usuario_id) {
    $stmt = $conn->prepare('SELECT menu_id FROM usuario_menu WHERE usuario_id = ?');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $ids = [];
    while ($r = $res->fetch_assoc()) $ids[] = intval($r['menu_id']);
    return $ids;
}

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare('SELECT id, nombre, usuario, rol, activo, primer_ingreso, created_at, updated_at FROM usuarios WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            if ($row) {
                $row['menus'] = get_user_menus($conn, $id);
            }
            json_response($row ?: []);
        }

        $result = $conn->query('SELECT id, nombre, usuario, rol, activo, primer_ingreso, created_at, updated_at FROM usuarios ORDER BY id DESC');
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        json_response(['data' => $rows]);
        break;

    case 'POST':
        $nombre = trim($_POST['nombre'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol = trim($_POST['rol'] ?? 'user');
        $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;
        $menus = trim($_POST['menus'] ?? ''); // comma-separated menu IDs

        if ($nombre === '' || $usuario === '' || $password === '') json_response(['error' => 'Datos incompletos'], 422);

        // check unique usuario
        $stmtCheck = $conn->prepare('SELECT id FROM usuarios WHERE usuario = ?');
        $stmtCheck->bind_param('s', $usuario);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) json_response(['error' => 'El usuario ya existe'], 422);

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO usuarios (nombre, usuario, password, rol, activo, primer_ingreso) VALUES (?, ?, ?, ?, ?, 1)');
        $stmt->bind_param('ssssi', $nombre, $usuario, $hash, $rol, $activo);
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            // sincronizar menus
            if ($menus !== '') {
                $ids = array_filter(array_map('intval', explode(',', $menus)));
                $stmtIns = $conn->prepare('INSERT INTO usuario_menu (usuario_id, menu_id) VALUES (?, ?)');
                foreach ($ids as $m) {
                    $stmtIns->bind_param('ii', $id, $m);
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
        $usuario = trim($putvars['usuario'] ?? '');
        $password = $putvars['password'] ?? null; // optional
        $rol = trim($putvars['rol'] ?? 'user');
        $activo = isset($putvars['activo']) ? intval($putvars['activo']) : 1;
        $menus = trim($putvars['menus'] ?? '');

        if (!$id || $nombre === '' || $usuario === '') json_response(['error' => 'Datos inválidos'], 422);

        // verificar si username existe y no sea de otro usuario
        $stmtCheck = $conn->prepare('SELECT id FROM usuarios WHERE usuario = ? AND id != ?');
        $stmtCheck->bind_param('si', $usuario, $id);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) json_response(['error' => 'El usuario ya existe'], 422);

        if ($password !== null && $password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE usuarios SET nombre = ?, usuario = ?, password = ?, rol = ?, activo = ?, primer_ingreso = 0 WHERE id = ?');
            $stmt->bind_param('ssssii', $nombre, $usuario, $hash, $rol, $activo, $id);
        } else {
            $stmt = $conn->prepare('UPDATE usuarios SET nombre = ?, usuario = ?, rol = ?, activo = ? WHERE id = ?');
            $stmt->bind_param('sssii', $nombre, $usuario, $rol, $activo, $id);
        }

        if ($stmt->execute()) {
            // sincronizar menus: borrar existentes y reinsertar
            $stmtDel = $conn->prepare('DELETE FROM usuario_menu WHERE usuario_id = ?');
            $stmtDel->bind_param('i', $id);
            $stmtDel->execute();
            if ($menus !== '') {
                $ids = array_filter(array_map('intval', explode(',', $menus)));
                $stmtIns = $conn->prepare('INSERT INTO usuario_menu (usuario_id, menu_id) VALUES (?, ?)');
                foreach ($ids as $m) {
                    $stmtIns->bind_param('ii', $id, $m);
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
        $stmt = $conn->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
        break;

    default:
        json_response(['error' => 'Método no permitido'], 405);
}
