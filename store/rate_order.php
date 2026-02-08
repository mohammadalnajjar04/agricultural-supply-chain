<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

// Store-only protection
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'store') {
    header("Location: ../auth/login.php");
    exit;
}

$store_id = (int)$_SESSION['user_id'];
$current_page = "orders";

if (!isset($_GET['order_id'])) {
    header("Location: my_orders.php");
    exit;
}

$order_id = (int)$_GET['order_id'];

// Fetch order (must belong to this store)
$stmt = $conn->prepare("
    SELECT o.order_id, o.status, o.rating, p.name AS product_name
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    WHERE o.order_id = ? AND o.store_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $order_id, $store_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die(($lang_code === 'ar') ? "الطلب غير موجود" : "Order not found");
}

$status = strtolower(trim((string)$order['status']));
$already_rated = !is_null($order['rating']);
$can_rate = (!$already_rated && in_array($status, ['delivered','completed'], true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;

    if (!$can_rate) {
        $error_msg = ($lang_code === 'ar') ? "لا يمكن تقييم هذا الطلب الآن" : "You can't rate this order right now";
    } elseif ($rating < 1 || $rating > 5) {
        $error_msg = ($lang_code === 'ar') ? "اختر تقييم بين 1 و 5" : "Choose a rating between 1 and 5";
    } else {
        $stmtUp = $conn->prepare("UPDATE orders SET rating = ?, status = 'completed' WHERE order_id = ? AND store_id = ? AND rating IS NULL");
        $stmtUp->bind_param("iii", $rating, $order_id, $store_id);
        if ($stmtUp->execute()) {
            header("Location: my_orders.php?msg=rated");
            exit;
        }
        $error_msg = ($lang_code === 'ar') ? "حدث خطأ أثناء حفظ التقييم" : "Error saving rating";
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= ($lang_code === 'ar') ? "تقييم الطلب" : "Rate Order"; ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/brand.css?v=2">
<link rel="stylesheet" href="../css/farmer.css?v=200">
    <?php if ($lang_code === 'ar'): ?>
        <link rel="stylesheet" href="../css/style_ar.css">
    <?php endif; ?>
</head>
<body data-role="store">

<?php include "../includes/store_sidebar.php"; ?>

<div class="main-content">
    <?php include "../includes/store_topbar.php"; ?>
    <br>

    <div class="dashboard-box">
        <h3 class="mb-4">
            <i class="fa-solid fa-star"></i>
            <?= ($lang_code === 'ar') ? "تقييم الطلب" : "Rate Order"; ?>
        </h3>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="product-card">

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger"><?= $error_msg; ?></div>
                    <?php endif; ?>

                    <p><strong><?= ($lang_code === 'ar') ? "المنتج:" : "Product:"; ?></strong>
                        <?= htmlspecialchars($order['product_name']); ?></p>

                    <p><strong><?= ($lang_code === 'ar') ? "الحالة:" : "Status:"; ?></strong>
                        <?php
                        $st = strtolower(trim((string)$order['status']));
                        if ($lang_code === 'ar') {
                            $map = [
                                'pending' => 'قيد الانتظار',
                                'approved' => 'تمت الموافقة',
                                'rejected' => 'مرفوض',
                                'delivered' => 'تم التسليم',
                                'completed' => 'مكتمل'
                            ];
                            echo $map[$st] ?? $order['status'];
                        } else {
                            echo ucfirst($order['status']);
                        }
                    ?></p>

                    <?php if ($already_rated): ?>
                        <div class="alert alert-info mb-0">
                            <?= ($lang_code === 'ar') ? "تم تقييم هذا الطلب مسبقًا" : "This order has already been rated"; ?>
                        </div>
                        <div class="mt-3">
                            <a href="my_orders.php" class="btn btn-outline-secondary w-100">
                                <?= ($lang_code === 'ar') ? "رجوع" : "Back"; ?>
                            </a>
                        </div>
                    <?php elseif (!$can_rate): ?>
                        <div class="alert alert-warning mb-0">
                            <?= ($lang_code === 'ar') ? "يمكن التقييم فقط بعد التسليم" : "You can rate only after delivery"; ?>
                        </div>
                        <div class="mt-3">
                            <a href="my_orders.php" class="btn btn-outline-secondary w-100">
                                <?= ($lang_code === 'ar') ? "رجوع" : "Back"; ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="mt-3">
                            <label class="form-label"><?= ($lang_code === 'ar') ? "اختر التقييم (1-5)" : "Choose rating (1-5)"; ?></label>
                            <select name="rating" class="form-select" required>
                                <option value="">--</option>
                                <?php for ($i=1; $i<=5; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>

                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-success flex-grow-1">
                                    <i class="fa-solid fa-check"></i>
                                    <?= ($lang_code === 'ar') ? "حفظ" : "Save"; ?>
                                </button>
                                <a href="my_orders.php" class="btn btn-outline-secondary">
                                    <?= ($lang_code === 'ar') ? "إلغاء" : "Cancel"; ?>
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
