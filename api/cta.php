<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // GET /api/cta.php?afiliado_id=123
        if (!isset($_GET['afiliado_id'])) json_response(['error' => 'afiliado_id requerido'], 422);
        $afiliado_id = intval($_GET['afiliado_id']);
        $stmt = $conn->prepare('SELECT * FROM cta_movimientos WHERE afiliado_id = ? ORDER BY fecha DESC, created_at DESC');
        $stmt->bind_param('i', $afiliado_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        // calcular saldo: cargos - pagos
        $stmtS = $conn->prepare("SELECT COALESCE(SUM(CASE WHEN tipo='cargo' THEN amount ELSE 0 END),0) as cargos, COALESCE(SUM(CASE WHEN tipo='pago' THEN amount ELSE 0 END),0) as pagos FROM cta_movimientos WHERE afiliado_id = ?");
        $stmtS->bind_param('i', $afiliado_id);
        $stmtS->execute();
        $s = $stmtS->get_result()->fetch_assoc();
        $saldo = floatval($s['cargos']) - floatval($s['pagos']);
        json_response(['data' => $rows, 'saldo' => number_format($saldo,2,'.','')]);
        break;

    default:
        json_response(['error' => 'Método no permitido'], 405);
}
