<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare('SELECT b.*, a.nombre AS afiliado_nombre, a.apellido AS afiliado_apellido FROM bonos b LEFT JOIN afiliados a ON a.id = b.afiliado_id WHERE b.id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $bono = $res->fetch_assoc();
            if ($bono) {
                $stmt2 = $conn->prepare('SELECT bi.*, p.descripcion AS practica_descripcion FROM bono_items bi LEFT JOIN practicas p ON p.id = bi.practica_id WHERE bi.bono_id = ?');
                $stmt2->bind_param('i', $id);
                $stmt2->execute();
                $r2 = $stmt2->get_result();
                $items = [];
                while ($it = $r2->fetch_assoc()) $items[] = $it;
                $bono['items'] = $items;
            }
            json_response($bono ?: []);
        }

        // summary diario
        if (isset($_GET['summary'])) {
            $date = null;
            if (isset($_GET['date']) && trim($_GET['date']) !== '') $date = trim($_GET['date']);
            if ($date === 'today' || $date === null && isset($_GET['summary']) && $_GET['summary'] == '1') $date = date('Y-m-d');
            if ($date) {
                $stmtS = $conn->prepare('SELECT COUNT(*) AS count, COALESCE(SUM(total),0) AS total_sum FROM bonos WHERE fecha = ?');
                $stmtS->bind_param('s', $date);
                $stmtS->execute();
                $s = $stmtS->get_result()->fetch_assoc();
                json_response(['date' => $date, 'count' => intval($s['count']), 'total' => number_format(floatval($s['total_sum']),2,'.','')]);
            }
        }

        // listado simple para DataTables (incluye conteo de items), soporte de distintos esquemas (fecha vs fecha_emision)
        $has_fecha = ($conn->query("SHOW COLUMNS FROM bonos LIKE 'fecha'") && $conn->query("SHOW COLUMNS FROM bonos LIKE 'fecha'")->num_rows > 0);
        if ($has_fecha) {
            $sql = "SELECT b.*, CONCAT(a.nombre, ' ', a.apellido) AS afiliado, COALESCE((SELECT COUNT(*) FROM bono_items bi WHERE bi.bono_id = b.id), 0) AS items_count FROM bonos b LEFT JOIN afiliados a ON a.id = b.afiliado_id ORDER BY b.id DESC";
        } else {
            $sql = "SELECT b.*, DATE(b.fecha_emision) AS fecha, CONCAT(a.nombre, ' ', a.apellido) AS afiliado, COALESCE((SELECT COUNT(*) FROM bono_items bi WHERE bi.bono_id = b.id), 0) AS items_count FROM bonos b LEFT JOIN afiliados a ON a.id = b.afiliado_id ORDER BY b.id DESC";
        }
        $res = $conn->query($sql);
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        json_response(['data' => $rows]);
        break;

    case 'POST':
        $afiliado_id = intval($_POST['afiliado_id'] ?? 0);
        $fecha = trim($_POST['fecha'] ?? date('Y-m-d'));
        $items = $_POST['items'] ?? '[]';
        $items = json_decode($items, true) ?: [];

        if (!$afiliado_id || empty($items)) json_response(['error' => 'Datos incompletos'], 422);

        $conn->begin_transaction();
        // compatibilidad con esquemas que usan 'fecha' o 'fecha_emision'
        $colRes = $conn->query("SHOW COLUMNS FROM bonos LIKE 'fecha'");
        $has_fecha = ($colRes && $colRes->num_rows > 0);
        if ($has_fecha) {
            $stmt = $conn->prepare('INSERT INTO bonos (afiliado_id, fecha, total, estado) VALUES (?, ?, 0, 1)');
            $stmt->bind_param('is', $afiliado_id, $fecha);
        } else {
            // tabla tiene fecha_emision (datetime) y estado como texto
            $fecha_dt = $fecha . ' 00:00:00';
            $estado_str = 'emitido';
            $stmt = $conn->prepare('INSERT INTO bonos (afiliado_id, fecha_emision, total, estado) VALUES (?, ?, 0, ?)');
            $stmt->bind_param('iss', $afiliado_id, $fecha_dt, $estado_str);
        }
        if (!$stmt->execute()) { $conn->rollback(); json_response(['error' => $stmt->error], 500); }
        $bono_id = $conn->insert_id;

        // compatibilidad con esquemas de bono_items
        $has_desc_col = ($conn->query("SHOW COLUMNS FROM bono_items LIKE 'descripcion'") && $conn->query("SHOW COLUMNS FROM bono_items LIKE 'descripcion'")->num_rows > 0);
        $has_precio_unit = ($conn->query("SHOW COLUMNS FROM bono_items LIKE 'precio_unitario'") && $conn->query("SHOW COLUMNS FROM bono_items LIKE 'precio_unitario'")->num_rows > 0);

        $total = 0.0;
        foreach ($items as $it) {
            $pr = isset($it['practica_id']) && $it['practica_id'] ? intval($it['practica_id']) : null;
            $desc = substr(trim($it['descripcion'] ?? ''), 0, 255);
            $cant = max(1, intval($it['cantidad'] ?? 1));
            // si viene con practica_id, tomar precio desde la tabla de practicas
            if ($pr) {
                $stmtP = $conn->prepare('SELECT precio, descripcion FROM practicas WHERE id = ? LIMIT 1');
                $stmtP->bind_param('i', $pr);
                $stmtP->execute();
                $rp = $stmtP->get_result()->fetch_assoc();
                if ($rp) {
                    $precio = floatval($rp['precio']);
                    if ($desc === '') $desc = $rp['descripcion'];
                } else {
                    $precio = floatval($it['precio'] ?? 0);
                }
            } else {
                $precio = floatval($it['precio'] ?? 0);
            }
            $subtotal = round($cant * $precio, 2);

            if ($has_desc_col && !$has_precio_unit) {
                $stmtIns = $conn->prepare('INSERT INTO bono_items (bono_id, practica_id, descripcion, cantidad, precio, subtotal, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmtIns->bind_param('iisidd', $bono_id, $pr, $desc, $cant, $precio, $subtotal);
            } elseif ($has_precio_unit) {
                // esquema con precio_unitario
                $stmtIns = $conn->prepare('INSERT INTO bono_items (bono_id, practica_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
                $stmtIns->bind_param('iiidd', $bono_id, $pr, $cant, $precio, $subtotal);
            } else {
                // fallback
                $stmtIns = $conn->prepare('INSERT INTO bono_items (bono_id, practica_id, descripcion, cantidad, precio, subtotal, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmtIns->bind_param('iisidd', $bono_id, $pr, $desc, $cant, $precio, $subtotal);
            }

            if (!$stmtIns->execute()) { $conn->rollback(); json_response(['error' => $stmtIns->error], 500); }
            $total += $subtotal;
        }

        $stmtUpd = $conn->prepare('UPDATE bonos SET total = ? WHERE id = ?');
        $stmtUpd->bind_param('di', $total, $bono_id);
        if (!$stmtUpd->execute()) { $conn->rollback(); json_response(['error' => $stmtUpd->error], 500); }

        $conn->commit();
        // Cargar bono completo para devolverlo
        $stmtB = $conn->prepare('SELECT b.*, a.nombre AS afiliado_nombre, a.apellido AS afiliado_apellido, a.dni AS afiliado_dni FROM bonos b LEFT JOIN afiliados a ON a.id = b.afiliado_id WHERE b.id = ?');
        $stmtB->bind_param('i', $bono_id);
        $stmtB->execute();
        $bono = $stmtB->get_result()->fetch_assoc();
        if ($bono) {
            $stmtIt = $conn->prepare('SELECT bi.*, p.descripcion AS practica_descripcion FROM bono_items bi LEFT JOIN practicas p ON p.id = bi.practica_id WHERE bi.bono_id = ?');
            $stmtIt->bind_param('i', $bono_id);
            $stmtIt->execute();
            $rIt = $stmtIt->get_result(); $its = [];
            while ($it = $rIt->fetch_assoc()) $its[] = $it;
            $bono['items'] = $its;
        }
        json_response(['success' => true, 'id' => $bono_id, 'bono' => $bono]);
        break;

    case 'PUT':
        parse_str(file_get_contents('php://input'), $putvars);
        $id = intval($putvars['id'] ?? 0);
        $afiliado_id = intval($putvars['afiliado_id'] ?? 0);
        $fecha = trim($putvars['fecha'] ?? date('Y-m-d'));
        $items = $putvars['items'] ?? '[]';
        $items = json_decode($items, true) ?: [];

        if (!$id || !$afiliado_id) json_response(['error' => 'Datos inválidos'], 422);

        $conn->begin_transaction();
        // compatibilidad: fecha vs fecha_emision
        $colRes = $conn->query("SHOW COLUMNS FROM bonos LIKE 'fecha'");
        $has_fecha = ($colRes && $colRes->num_rows > 0);
        if ($has_fecha) {
            $stmt = $conn->prepare('UPDATE bonos SET afiliado_id = ?, fecha = ? WHERE id = ?');
            $stmt->bind_param('isi', $afiliado_id, $fecha, $id);
        } else {
            $fecha_dt = $fecha . ' 00:00:00';
            $stmt = $conn->prepare('UPDATE bonos SET afiliado_id = ?, fecha_emision = ? WHERE id = ?');
            $stmt->bind_param('isi', $afiliado_id, $fecha_dt, $id);
        }
        if (!$stmt->execute()) { $conn->rollback(); json_response(['error' => $stmt->error], 500); }

        $stmtDel = $conn->prepare('DELETE FROM bono_items WHERE bono_id = ?');
        $stmtDel->bind_param('i', $id);
        if (!$stmtDel->execute()) { $conn->rollback(); json_response(['error' => $stmtDel->error], 500); }

        $stmtIns = $conn->prepare('INSERT INTO bono_items (bono_id, practica_id, descripcion, cantidad, precio, subtotal, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $total = 0.0;
        foreach ($items as $it) {
            $pr = isset($it['practica_id']) && $it['practica_id'] ? intval($it['practica_id']) : null;
            $desc = substr(trim($it['descripcion'] ?? ''), 0, 255);
            $cant = max(1, intval($it['cantidad'] ?? 1));
            // si viene con practica_id, tomar precio desde la tabla de practicas
            if ($pr) {
                $stmtP = $conn->prepare('SELECT precio, descripcion FROM practicas WHERE id = ? LIMIT 1');
                $stmtP->bind_param('i', $pr);
                $stmtP->execute();
                $rp = $stmtP->get_result()->fetch_assoc();
                if ($rp) {
                    $precio = floatval($rp['precio']);
                    if ($desc === '') $desc = $rp['descripcion'];
                } else {
                    $precio = floatval($it['precio'] ?? 0);
                }
            } else {
                $precio = floatval($it['precio'] ?? 0);
            }
            $subtotal = round($cant * $precio, 2);
            if ($conn->query("SHOW COLUMNS FROM bono_items LIKE 'descripcion'")->num_rows > 0 && $conn->query("SHOW COLUMNS FROM bono_items LIKE 'precio_unitario'")->num_rows == 0) {
                $stmtIns = $conn->prepare('INSERT INTO bono_items (bono_id, practica_id, descripcion, cantidad, precio, subtotal, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmtIns->bind_param('iisidd', $id, $pr, $desc, $cant, $precio, $subtotal);
            } elseif ($conn->query("SHOW COLUMNS FROM bono_items LIKE 'precio_unitario'")->num_rows > 0) {
                $stmtIns = $conn->prepare('INSERT INTO bono_items (bono_id, practica_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
                $stmtIns->bind_param('iiidd', $id, $pr, $cant, $precio, $subtotal);
            } else {
                $stmtIns = $conn->prepare('INSERT INTO bono_items (bono_id, practica_id, descripcion, cantidad, precio, subtotal, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmtIns->bind_param('iisidd', $id, $pr, $desc, $cant, $precio, $subtotal);
            }
            if (!$stmtIns->execute()) { $conn->rollback(); json_response(['error' => $stmtIns->error], 500); }
            $total += $subtotal;
        }

        $stmtUpd = $conn->prepare('UPDATE bonos SET total = ? WHERE id = ?');
        $stmtUpd->bind_param('di', $total, $id);
        if (!$stmtUpd->execute()) { $conn->rollback(); json_response(['error' => $stmtUpd->error], 500); }

        $conn->commit();
        // devolver bono actualizado
        $stmtB = $conn->prepare('SELECT b.*, a.nombre AS afiliado_nombre, a.apellido AS afiliado_apellido, a.dni AS afiliado_dni FROM bonos b LEFT JOIN afiliados a ON a.id = b.afiliado_id WHERE b.id = ?');
        $stmtB->bind_param('i', $id);
        $stmtB->execute();
        $bono = $stmtB->get_result()->fetch_assoc();
        if ($bono) {
            $stmtIt = $conn->prepare('SELECT bi.*, p.descripcion AS practica_descripcion FROM bono_items bi LEFT JOIN practicas p ON p.id = bi.practica_id WHERE bi.bono_id = ?');
            $stmtIt->bind_param('i', $id);
            $stmtIt->execute();
            $rIt = $stmtIt->get_result(); $its = [];
            while ($it = $rIt->fetch_assoc()) $its[] = $it;
            $bono['items'] = $its;
        }
        json_response(['success' => true, 'bono' => $bono]);
        break;

    case 'DELETE':
        parse_str(file_get_contents('php://input'), $delvars);
        $id = intval($delvars['id'] ?? 0);
        if (!$id) json_response(['error' => 'ID requerido'], 422);
        $stmt = $conn->prepare('DELETE FROM bonos WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) json_response(['success' => true]);
        json_response(['error' => $stmt->error], 500);
        break;

    default:
        json_response(['error' => 'Método no permitido'], 405);
}
