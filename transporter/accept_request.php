<?php
session_start();
include "../config/db.php";

// حماية الصفحة – فقط الناقل
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "transporter") {
    header("Location: ../auth/login.php");
    exit;
}

$transporter_id = $_SESSION['user_id'];

// التحقق من وجود رقم الطلب
if (!isset($_GET['id'])) {
    header("Location: available_requests.php");
    exit;
}

$request_id = intval($_GET['id']);

// قبول الطلب: تعيين transporter_id وتغيير الحالة إلى accepted
// (وإن كان الطلب مرتبط بأوردر، نحدّث حالة الأوردر إلى in_progress)
try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("
        UPDATE transport_requests
        SET transporter_id = ?, status = 'accepted'
        WHERE request_id = ? AND transporter_id IS NULL AND LOWER(status) = 'pending'
    ");
    $stmt->bind_param("ii", $transporter_id, $request_id);
    $stmt->execute();

    // update order if linked
    $stmt2 = $conn->prepare("SELECT order_id FROM transport_requests WHERE request_id = ? LIMIT 1");
    $stmt2->bind_param("i", $request_id);
    $stmt2->execute();
    $row = $stmt2->get_result()->fetch_assoc();
    $order_id = isset($row['order_id']) ? (int)$row['order_id'] : 0;
    if ($order_id > 0) {
        $stmt3 = $conn->prepare("UPDATE orders SET status='in_progress' WHERE order_id=? AND status IN ('approved','pending','in_progress')");
        $stmt3->bind_param("i", $order_id);
        $stmt3->execute();
    }

    $conn->commit();
} catch (Throwable $e) {
    if ($conn->errno) {
        $conn->rollback();
    }
}

// نرجع للصفحة مع رسالة نجاح (حتى لو لم يعدّل أي سطر لن يصير خطأ)
header("Location: available_requests.php?msg=accepted");
exit;
