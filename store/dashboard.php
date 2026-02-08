<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

// حماية الصفحة – فقط المتجر........................................................
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'store') {
    header("Location: ../auth/login.php");
    exit;
}

$store_id     = $_SESSION['user_id'];
$current_page = "dashboard"; // لتفعيل التبويب الصحيح

// -------- جلب بيانات المتجر --------
$stmt = $conn->prepare("SELECT * FROM stores WHERE store_id = ?");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$store = $stmt->get_result()->fetch_assoc();

// -------- إحصائيات --------

// إجمالي الطلبات
$stmtTotal = $conn->prepare("
    SELECT COUNT(*) AS total_orders
    FROM orders
    WHERE store_id = ?
");
$stmtTotal->bind_param("i", $store_id);
$stmtTotal->execute();
$total_orders = $stmtTotal->get_result()->fetch_assoc()['total_orders'] ?? 0;

// الطلبات المعلقة
$stmtPending = $conn->prepare("
    SELECT COUNT(*) AS pending_orders
    FROM orders
    WHERE store_id = ? AND status = 'pending'
");
$stmtPending->bind_param("i", $store_id);
$stmtPending->execute();
$pending_orders = $stmtPending->get_result()->fetch_assoc()['pending_orders'] ?? 0;

// الطلبات المكتملة
$stmtCompleted = $conn->prepare("
    SELECT COUNT(*) AS completed_orders
    FROM orders
    WHERE store_id = ? AND status = 'completed'
");
$stmtCompleted->bind_param("i", $store_id);
$stmtCompleted->execute();
$completed_orders = $stmtCompleted->get_result()->fetch_assoc()['completed_orders'] ?? 0;

?>
<!DOCTYPE html>
<html lang="<?= ($lang_code === 'ar') ? 'ar' : 'en' ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="UTF-8">
    <title><?= ($lang_code === 'ar') ? "لوحة تحكم المتجر" : "Store Dashboard"; ?></title>

    <!-- Bootstrap + Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/brand.css?v=2">
    <link rel="stylesheet" href="../css/farmer.css?v=200">
    <?php if ($lang_code === 'ar'): ?>
        <link rel="stylesheet" href="../css/style_ar.css?v=200">
    <?php endif; ?>
</head>

<body data-role="store">

<!-- ============ SIDEBAR ============ -->
<?php include "../includes/store_sidebar.php"; ?>

<div class="menu-overlay"></div>

<!-- ============ MAIN CONTENT ============ -->
<div class="main-content">

    <!-- TOP BAR -->
    <?php include "../includes/store_topbar.php"; ?></br>

    <!-- WELCOME BOX -->
    <div class="welcome-box mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

            <div>
                <h4 class="mb-1">
                    <?= ($lang_code === 'ar' ? "مرحبًا، " : "Welcome, ") . htmlspecialchars($store['name']); ?>
                </h4>

                <p class="mb-1">
                    <strong><?= ($lang_code === 'ar' ? "البريد الإلكتروني:" : "Email:"); ?></strong>
                    <?= htmlspecialchars($store['email']); ?>
                </p>

                <p class="mb-0">
                    <strong><?= ($lang_code === 'ar' ? "موقع المتجر:" : "Store Location:"); ?></strong>
                    <span class="badge bg-light text-dark">
                        <?= htmlspecialchars($store['location']); ?>
                    </span>
                </p>
            </div>

            <div class="quick-links d-flex flex-wrap gap-2">
                <a href="available_products.php" class="btn btn-success btn-sm">
                    <i class="fa fa-shopping-basket"></i>
                    <?= ($lang_code === 'ar' ? "المنتجات المتاحة" : "Available Products"); ?>
                </a>

                <a href="my_orders.php" class="btn btn-outline-success btn-sm">
                    <i class="fa fa-list-check"></i>
                    <?= ($lang_code === 'ar' ? "طلباتي" : "My Orders"); ?>
                </a>
            </div>

        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="row g-3">

        <!-- إجمالي الطلبات -->
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <div>
                    <h6 class="mb-1">
                        <?= ($lang_code === 'ar' ? "إجمالي الطلبات" : "Total Orders"); ?>
                    </h6>
                    <div class="number text-primary"><?= (int)$total_orders; ?></div>
                    <small class="text-muted">
                        <?= ($lang_code === 'ar'
                            ? "جميع الطلبات التي قمت بها."
                            : "All orders made by your store."); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- الطلبات المعلقة -->
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h6 class="mb-1">
                        <?= ($lang_code === 'ar' ? "الطلبات المعلقة" : "Pending Orders"); ?>
                    </h6>
                    <div class="number text-warning"><?= (int)$pending_orders; ?></div>
                    <small class="text-muted">
                        <?= ($lang_code === 'ar'
                            ? "طلبات بانتظار التنفيذ."
                            : "Orders waiting to be processed."); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- الطلبات المكتملة -->
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <h6 class="mb-1">
                        <?= ($lang_code === 'ar' ? "الطلبات المكتملة" : "Completed Orders"); ?>
                    </h6>
                    <div class="number text-success"><?= (int)$completed_orders; ?></div>
                    <small class="text-muted">
                        <?= ($lang_code === 'ar'
                            ? "الطلبات التي تم تسليمها."
                            : "Orders successfully delivered."); ?>
                    </small>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- JS -->
<script src="../js/farmer.js"></script>

</body>
</html>
