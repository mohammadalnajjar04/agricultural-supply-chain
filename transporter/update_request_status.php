<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'transporter') {
    header("Location: ../auth/login.php");
    exit;
}

$transporter_id = (int)$_SESSION['user_id'];

if (!isset($_GET['id'], $_GET['action'])) {
    header("Location: my_requests.php");
    exit;
}

$request_id = (int)$_GET['id'];
$action = $_GET['action'];

// Get current status + related store/product
$stmt = $conn->prepare("SELECT request_id, status, store_id, product_id, order_id FROM transport_requests WHERE request_id = ? AND transporter_id = ? LIMIT 1");
$stmt->bind_param("ii", $request_id, $transporter_id);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();

if (!$req) {
    header("Location: my_requests.php?err=not_found");
    exit;
}

$status = strtolower(trim((string)$req['status']));

$new_status = null;
if ($action === 'start' && $status === 'accepted') {
    $new_status = 'in_progress';
} elseif ($action === 'deliver' && in_array($status, ['in_progress','accepted'], true)) {
    $new_status = 'delivered';
}

if (!$new_status) {
    header("Location: my_requests.php?err=invalid_action");
    exit;
}

try {
    $conn->begin_transaction();

    if ($new_status === 'delivered') {
        $stmtUp = $conn->prepare("UPDATE transport_requests SET status = ?, delivery_date = CURDATE() WHERE request_id = ? AND transporter_id = ?");
        $stmtUp->bind_param("sii", $new_status, $request_id, $transporter_id);
    } else {
        $stmtUp = $conn->prepare("UPDATE transport_requests SET status = ? WHERE request_id = ? AND transporter_id = ?");
        $stmtUp->bind_param("sii", $new_status, $request_id, $transporter_id);
    }
    $stmtUp->execute();

    // If delivered, update linked order (preferred)
    if ($new_status === 'delivered') {
        $order_id = isset($req['order_id']) ? (int)$req['order_id'] : 0;
        if ($order_id > 0) {
            $stmtOrd = $conn->prepare("UPDATE orders SET status = 'delivered' WHERE order_id = ?");
            $stmtOrd->bind_param("i", $order_id);
            $stmtOrd->execute();
        }
    }

    $conn->commit();
    header("Location: my_requests.php?msg=updated");
    exit;

} catch (Throwable $e) {
    if ($conn->errno) {
        $conn->rollback();
    }
    header("Location: my_requests.php?err=db");
    exit;
}
