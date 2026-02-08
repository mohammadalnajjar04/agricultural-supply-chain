<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

// حماية الصفحة...................................................................
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../auth/login.php");
    exit;
}

$farmer_id    = $_SESSION['user_id'];
$current_page = "dashboard"; // ⭐ لتفعيل التبويب الصحيح في farmer_tabs.php

// --- جلب بيانات المزارع ---
$stmt = $conn->prepare("SELECT * FROM farmers WHERE farmer_id = ?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();

// --- عدد المنتجات ---
$stmtProd = $conn->prepare("SELECT COUNT(*) AS total_products FROM products WHERE farmer_id = ?");
$stmtProd->bind_param("i", $farmer_id);
$stmtProd->execute();
$total_products = $stmtProd->get_result()->fetch_assoc()['total_products'];

// --- إجمالي طلبات النقل ---
$stmtReqAll = $conn->prepare("SELECT COUNT(*) AS total_requests FROM transport_requests WHERE farmer_id = ?");
$stmtReqAll->bind_param("i", $farmer_id);
$stmtReqAll->execute();
$total_requests = $stmtReqAll->get_result()->fetch_assoc()['total_requests'];

// --- الطلبات المعلقة ---
$stmtReqPending = $conn->prepare("
    SELECT COUNT(*) AS pending_requests
    FROM transport_requests
    WHERE farmer_id = ? AND status = 'pending'
");
$stmtReqPending->bind_param("i", $farmer_id);
$stmtReqPending->execute();
$pending_requests = $stmtReqPending->get_result()->fetch_assoc()['pending_requests'];
?>
<!DOCTYPE html>
<html lang="<?= ($lang_code === 'ar') ? 'ar' : 'en' ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="UTF-8">
    <title><?= ($lang_code === 'ar') ? "لوحة تحكم المزارع" : "Farmer Dashboard"; ?></title>

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

<body data-role="farmer">

<!-- ============ SIDEBAR ============ -->
<?php include "../includes/farmer_sidebar.php"; ?>


<div class="menu-overlay"></div>

<!-- ============ MAIN CONTENT ============ -->
<div class="main-content">

    <!-- TOP BAR (العنوان + اللغة + زر Dashboard) -->
    <?php include "../includes/farmer_topbar.php"; ?></br>

    <!-- WELCOME BOX -->
    <div class="welcome-box mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

            <div>
                <h4 class="mb-1">
                    <?= ($lang_code === 'ar' ? "مرحبًا، " : "Welcome, ") . htmlspecialchars($farmer['name']); ?>
                </h4>

                <p class="mb-1">
                    <strong><?= ($lang_code === 'ar' ? "البريد الإلكتروني:" : "Email:"); ?></strong>
                    <?= htmlspecialchars($farmer['email']); ?>
                </p>

                <p class="mb-0">
                    <strong><?= ($lang_code === 'ar' ? "موقع المزرعة:" : "Farm Location:"); ?></strong>
                    <span class="badge bg-light text-dark">
                        <?= htmlspecialchars($farmer['location']); ?>
                    </span>
                </p>
            </div>

            <div class="quick-links d-flex flex-wrap gap-2">
                <a href="add_product.php" class="btn btn-success btn-sm">
                    <i class="fa fa-plus"></i>
                    <?= ($lang_code === 'ar' ? "إضافة منتج جديد" : "Add New Product"); ?>
                </a>

                <a href="transport_request.php" class="btn btn-outline-success btn-sm">
                    <i class="fa fa-truck"></i>
                    <?= ($lang_code === 'ar' ? "إنشاء طلب نقل" : "Create Transport Request"); ?>
                </a>
            </div>

        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="row g-3">

        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <div>
                    <h6 class="mb-1">
                        <?= ($lang_code === 'ar' ? "إجمالي المنتجات" : "Total Products"); ?>
                    </h6>
                    <div class="number text-success"><?= $total_products; ?></div>
                    <small class="text-muted">
                        <?= ($lang_code === 'ar'
                            ? "عدد المنتجات المسجّلة في نظامك."
                            : "Total products currently listed in your farm."); ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="fa-solid fa-truck"></i>
                </div>
                <div>
                    <h6 class="mb-1">
                        <?= ($lang_code === 'ar' ? "إجمالي طلبات النقل" : "Total Transport Requests"); ?>
                    </h6>
                    <div class="number text-primary"><?= $total_requests; ?></div>
                    <small class="text-muted">
                        <?= ($lang_code === 'ar'
                            ? "كل طلبات النقل التي قمت بإنشائها."
                            : "All transport requests you have created."); ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h6 class="mb-1">
                        <?= ($lang_code === 'ar' ? "الطلبات المعلقة" : "Pending Requests"); ?>
                    </h6>
                    <div class="number text-warning"><?= $pending_requests; ?></div>
                    <small class="text-muted">
                        <?= ($lang_code === 'ar'
                            ? "طلبات النقل التي بانتظار قبول الناقل."
                            : "Requests still waiting for transporter response."); ?>
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
