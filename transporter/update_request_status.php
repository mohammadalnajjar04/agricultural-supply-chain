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
$action = (string)($_POST['action'] ?? '');
if (!$requestId || !in_array($action, ['start', 'deliver'], true)) {
    header('Location: my_requests.php?err=invalid_action');
    exit;
}

$from = $action === 'start' ? 'accepted' : 'in_progress';
$to = $action === 'start' ? 'in_progress' : 'delivered';
$inTransaction = false;
try {
    $conn->begin_transaction();
    $inTransaction = true;

    // Lock this request. Never trust a status fetched outside the transaction.
    $select = $conn->prepare('SELECT order_id, status FROM transport_requests WHERE request_id = ? AND transporter_id = ? FOR UPDATE');
    $select->bind_param('ii', $requestId, $transporterId);
    $select->execute();
    $request = $select->get_result()->fetch_assoc();
    if (!$request || strtolower((string)$request['status']) !== $from) {
        $conn->rollback();
        header('Location: my_requests.php?err=invalid_action');
        exit;
    }

    $orderId = (int)($request['order_id'] ?? 0);
    if ($orderId > 0) {
        // Linked order must be in a state compatible with advancing delivery.
        $orderCheck = $conn->prepare('SELECT status FROM orders WHERE order_id = ? FOR UPDATE');
        $orderCheck->bind_param('i', $orderId);
        $orderCheck->execute();
        $order = $orderCheck->get_result()->fetch_assoc();
        if (!$order || !in_array(strtolower((string)$order['status']), ['approved', 'in_progress'], true)) {
            $conn->rollback();
            header('Location: my_requests.php?err=order_state');
            exit;
        }
    }

    if ($to === 'delivered') {
        $update = $conn->prepare('UPDATE transport_requests SET status = ?, delivery_date = CURDATE() WHERE request_id = ? AND transporter_id = ? AND status = ?');
    } else {
        $update = $conn->prepare('UPDATE transport_requests SET status = ? WHERE request_id = ? AND transporter_id = ? AND status = ?');
    }
    $update->bind_param('siis', $to, $requestId, $transporterId, $from);
    $update->execute();
    if ($update->affected_rows !== 1) {
        throw new RuntimeException('Request status changed concurrently');
    }
    if ($orderId > 0) {
        $orderTo = $to === 'delivered' ? 'delivered' : 'in_progress';
        $orderUpdate = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND status IN ('approved','in_progress')");
        $orderUpdate->bind_param('si', $orderTo, $orderId);
        $orderUpdate->execute();
        if ($orderUpdate->affected_rows !== 1 && $to === 'delivered') {
            throw new RuntimeException('Linked order could not be delivered');
        }
    }
    $conn->commit();
    header('Location: my_requests.php?msg=updated');
    exit;
} catch (Throwable $e) {
    if ($inTransaction) { $conn->rollback(); }
    error_log('Transport status update failed: ' . $e->getMessage());
    header('Location: my_requests.php?err=db');
    exit;
}
