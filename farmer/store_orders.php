<?php
session_start();
include "../includes/language.php";
include "../config/db.php";
include "../includes/pricing.php";

// Farmer guard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    header("Location: ../auth/login.php");
    exit;
}

$farmer_id = (int)$_SESSION['user_id'];
$current_page = "store_orders";
$is_ar = ($lang_code === 'ar');

$msg = '';
$err = '';

// Handle approve/reject
if (isset($_GET['action'], $_GET['order_id'])) {
    $action = strtolower(trim((string)$_GET['action']));
    $order_id = (int)$_GET['order_id'];

    if ($order_id > 0 && in_array($action, ['approve','reject'], true)) {
        try {
            $conn->begin_transaction();

            // Lock order row for this farmer
            $stmtO = $conn->prepare("
                SELECT o.order_id, o.store_id, o.product_id, o.quantity, o.status, o.total_price,
                       p.farmer_id, p.quantity AS stock_qty,
                       f.location AS farmer_location,
                       s.location AS store_location
                FROM orders o
                JOIN products p ON o.product_id = p.product_id
                JOIN farmers f ON p.farmer_id = f.farmer_id
                JOIN stores  s ON o.store_id = s.store_id
                WHERE o.order_id = ? AND p.farmer_id = ?
                FOR UPDATE
            ");
            $stmtO->bind_param("ii", $order_id, $farmer_id);
            $stmtO->execute();
            $o = $stmtO->get_result()->fetch_assoc();

            if (!$o) {
                $conn->rollback();
                $err = $is_ar ? "الطلب غير موجود." : "Order not found.";
            } else {
                $st = strtolower((string)($o['status'] ?? ''));
                if ($st !== 'pending') {
                    $conn->rollback();
                    $err = $is_ar ? "لا يمكن تعديل هذا الطلب لأنه ليس (Pending)." : "This order is not pending.";
                } elseif ($action === 'reject') {
                    $stmtU = $conn->prepare("UPDATE orders SET status='rejected' WHERE order_id=?");
                    $stmtU->bind_param("i", $order_id);
                    $stmtU->execute();
                    $conn->commit();
                    $msg = $is_ar ? "تم رفض الطلب." : "Order rejected.";
                } else {
                    // Approve: ensure stock available
                    $reqQty = (int)$o['quantity'];
                    $stock = (int)$o['stock_qty'];
                    if ($reqQty <= 0 || $stock < $reqQty) {
                        $conn->rollback();
                        $err = $is_ar ? "الكمية المطلوبة غير متوفرة حالياً." : "Not enough stock.";
                    } else {
                        // Deduct stock
                        $stmtStock = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ? AND quantity >= ?");
                        $stmtStock->bind_param("iii", $reqQty, $o['product_id'], $reqQty);
                        $stmtStock->execute();
                        if ($stmtStock->affected_rows !== 1) {
                            $conn->rollback();
                            $err = $is_ar ? "تعذر خصم الكمية. حاول مرة أخرى." : "Failed to deduct stock.";
                        } else {
                            // Calculate delivery pricing (fair model)
                            $pricing = calculate_delivery_pricing((string)$o['farmer_location'], (string)$o['store_location'], (float)$reqQty, 0.03);

                            // Update order
            $stmtU = $conn->prepare("
                                UPDATE orders
                                SET status='approved', delivery_fee=?, platform_fee=?, driver_earning=?
                                WHERE order_id=?
                            ");
                            $stmtU->bind_param(
                                "dddi",
                                $pricing['delivery_fee'],
                                $pricing['platform_fee'],
                                $pricing['driver_earning'],
                                $order_id
                            );
                            $stmtU->execute();

                            // Create transport request linked to order
                            $stmtTR = $conn->prepare("
                                INSERT INTO transport_requests
                                  (order_id, farmer_id, product_id, transporter_id, store_id, quantity, total_weight, transport_date, status, notes,
                                   delivery_fee, platform_fee, driver_earning, distance_type)
                                VALUES
                                  (?, ?, ?, NULL, ?, ?, ?, CURDATE(), 'pending', 'Auto-generated after farmer approval',
                                   ?, ?, ?, ?)
                            ");
                            $zone = (string)$pricing['zone'];
                            $stmtTR->bind_param(
                                "iiiiidddds",
                                $order_id,
                                $farmer_id,
                                $o['product_id'],
                                $o['store_id'],
                                $reqQty,
                                $reqQty,
                                $pricing['delivery_fee'],
                                $pricing['platform_fee'],
                                $pricing['driver_earning'],
                                $zone
                            );
                            $stmtTR->execute();

                            $conn->commit();
                            $msg = $is_ar ? "تم قبول الطلب وإنشاء طلب نقل تلقائياً. (أجرة التوصيل على المتجر)" : "Order approved and transport request created. (Delivery fee is paid by the store)";
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            if ($conn->errno) {
                $conn->rollback();
            }
            $err = $is_ar ? "حدث خطأ غير متوقع." : "Unexpected error.";
        }
    }
}

// Fetch incoming orders for this farmer
$stmt = $conn->prepare("
    SELECT o.order_id, o.order_date, o.total_price, o.quantity, o.status,
           o.delivery_fee, o.platform_fee, o.driver_earning,
           p.name AS product_name,
           s.name AS store_name, s.location AS store_location,
           f.location AS farmer_location
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN stores   s ON o.store_id = s.store_id
    JOIN farmers  f ON p.farmer_id = f.farmer_id
    WHERE p.farmer_id = ?
    ORDER BY o.order_id DESC
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$orders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="<?= $is_ar ? 'ar' : 'en' ?>" dir="<?= $is_ar ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $is_ar ? "طلبات المتاجر" : "Store Orders" ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=200">
    <link rel="stylesheet" href="../css/brand.css?v=3">
    <link rel="stylesheet" href="../css/farmer.css?v=230">
    <?php if ($is_ar): ?>
        <link rel="stylesheet" href="../css/style_ar.css?v=230">
    <?php endif; ?>
</head>
<body data-role="farmer">

<?php include "../includes/farmer_sidebar.php"; ?>
<div class="menu-overlay"></div>

<div class="main-content">
    <?php include "../includes/farmer_topbar.php"; ?><br>

    <div class="dashboard-box">
        <h3 class="mt-2 mb-3 page-main-title">
            <i class="fa-solid fa-store text-success"></i>
            <?= $is_ar ? "طلبات المتاجر" : "Store Orders" ?>
        </h3>

        <?php if ($msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <div class="row g-3">
            <?php if ($orders->num_rows === 0): ?>
                <div class="col-12">
                    <div class="alert alert-info mb-0"><?= $is_ar ? "لا توجد طلبات حتى الآن." : "No orders yet." ?></div>
                </div>
            <?php else: ?>
                <?php while ($o = $orders->fetch_assoc()):
                    $st = strtolower((string)$o['status']);
                    $badge = match ($st) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'delivered' => 'primary',
                        default => 'secondary',
                    };
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="product-card shadow-sm h-100">
                        <h5 class="product-title mb-2">
                            <i class="fa-solid fa-leaf"></i>
                            <?= htmlspecialchars($o['product_name']) ?>
                        </h5>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="text-muted small">
                                <?= $is_ar ? "طلب رقم" : "Order" ?> #<?= (int)$o['order_id'] ?>
                            </div>
                            <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars(ucfirst($st)) ?></span>
                        </div>

                        <p class="mb-1"><strong><?= $is_ar ? "المتجر:" : "Store:" ?></strong> <?= htmlspecialchars($o['store_name']) ?></p>
                        <p class="mb-1"><strong><?= $is_ar ? "موقع المتجر:" : "Store Location:" ?></strong> <?= htmlspecialchars($o['store_location']) ?></p>
                        <p class="mb-1"><strong><?= $is_ar ? "الكمية:" : "Quantity:" ?></strong> <?= (int)$o['quantity'] ?> kg</p>
                        <p class="mb-1"><strong><?= $is_ar ? "سعر المنتج:" : "Product total:" ?></strong> <?= number_format((float)$o['total_price'], 2) ?> JOD</p>
                        <p class="mb-1"><strong><?= $is_ar ? "تاريخ الطلب:" : "Order date:" ?></strong> <?= htmlspecialchars($o['order_date']) ?></p>

                        <?php if (!empty($o['delivery_fee'])): ?>
                            <hr class="my-2">
                            <p class="mb-1"><strong><?= $is_ar ? "أجرة التوصيل (على المتجر):" : "Delivery fee (paid by store):" ?></strong> <?= number_format((float)$o['delivery_fee'], 2) ?> JOD</p>
                            <p class="mb-0 text-muted small"><?= $is_ar ? "ربح الناقل:" : "Driver earning:" ?> <?= number_format((float)($o['driver_earning'] ?? 0), 2) ?> JOD</p>
                        <?php endif; ?>

                        <?php if ($st === 'pending'): ?>
                            <div class="d-flex gap-2 mt-3">
                                <a class="btn btn-success btn-sm w-50" href="?action=approve&order_id=<?= (int)$o['order_id'] ?>">
                                    <i class="fa-solid fa-check"></i> <?= $is_ar ? "قبول" : "Approve" ?>
                                </a>
                                <a class="btn btn-outline-danger btn-sm w-50" href="?action=reject&order_id=<?= (int)$o['order_id'] ?>" onclick="return confirm('<?= $is_ar ? "هل أنت متأكد من رفض الطلب؟" : "Reject this order?" ?>')">
                                    <i class="fa-solid fa-xmark"></i> <?= $is_ar ? "رفض" : "Reject" ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../js/farmer.js"></script>
</body>
</html>
