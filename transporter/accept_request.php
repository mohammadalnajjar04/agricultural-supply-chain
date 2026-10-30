<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/security.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'transporter') {
    header('Location: ../auth/login.php');
    exit;
}
require_valid_csrf();
$transporterId = (int)$_SESSION['user_id'];
$requestId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$requestId) {
    header('Location: available_requests.php?err=invalid_request');
    exit;
}
$inTransaction = false;
try {
    $conn->begin_transaction();
    $inTransaction = true;
    $get = $conn->prepare('SELECT order_id, status, transporter_id FROM transport_requests WHERE request_id = ? FOR UPDATE');
    $get->bind_param('i', $requestId);
    $get->execute();
    $request = $get->get_result()->fetch_assoc();
    if (!$request || strtolower((string)$request['status']) !== 'pending' || $request['transporter_id'] !== null) {
        $conn->rollback();
        header('Location: available_requests.php?err=already_taken');
        exit;
    }
    $orderId = (int)($request['order_id'] ?? 0);
    if ($orderId > 0) {
        $orderSelect = $conn->prepare('SELECT status FROM orders WHERE order_id = ? FOR UPDATE');
        $orderSelect->bind_param('i', $orderId);
        $orderSelect->execute();
        $order = $orderSelect->get_result()->fetch_assoc();
        if (!$order || strtolower((string)$order['status']) !== 'approved') {
            $conn->rollback();
            header('Location: available_requests.php?err=order_state');
            exit;
        }
    }
    $update = $conn->prepare("UPDATE transport_requests SET transporter_id = ?, status = 'accepted' WHERE request_id = ? AND transporter_id IS NULL AND status = 'pending'");
    $update->bind_param('ii', $transporterId, $requestId);
    $update->execute();
    if ($update->affected_rows !== 1) { throw new RuntimeException('Concurrent acceptance'); }
    if ($orderId > 0) {
        $updateOrder = $conn->prepare("UPDATE orders SET status='in_progress' WHERE order_id = ? AND status = 'approved'");
        $updateOrder->bind_param('i', $orderId);
        $updateOrder->execute();
        if ($updateOrder->affected_rows !== 1) { throw new RuntimeException('Linked order status changed'); }
    }
    $conn->commit();
    header('Location: available_requests.php?msg=accepted');
    exit;
} catch (Throwable $e) {
    if ($inTransaction) { $conn->rollback(); }
    error_log('Transport acceptance failed: ' . $e->getMessage());
    header('Location: available_requests.php?err=db');
    exit;
}
