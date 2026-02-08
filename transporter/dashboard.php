<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

// حماية الصفحة.............................................................................
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'transporter') {
    header("Location: ../auth/login.php");
    exit;
}

$transporter_id = $_SESSION['user_id'];
$current_page = "dashboard";

// جلب بيانات الناقل
$stmt = $conn->prepare("SELECT * FROM transporters WHERE transporter_id = ?");
$stmt->bind_param("i", $transporter_id);
$stmt->execute();
$transporter = $stmt->get_result()->fetch_assoc();

// الطلبات المتاحة
$stmtAvailable = $conn->prepare("
    SELECT COUNT(*) AS total_available
    FROM transport_requests
    WHERE status = 'pending' 
    AND (transporter_id IS NULL OR transporter_id = 0)
");
$stmtAvailable->execute();
$total_available = $stmtAvailable->get_result()->fetch_assoc()['total_available'];

// طلباتي الكاملة
$stmtMyTotal = $conn->prepare("
    SELECT COUNT(*) AS total_my
    FROM transport_requests
    WHERE transporter_id = ?
");
$stmtMyTotal->bind_param("i", $transporter_id);
$stmtMyTotal->execute();
$total_my = $stmtMyTotal->get_result()->fetch_assoc()['total_my'];

// الطلبات النشطة
$stmtActive = $conn->prepare("
    SELECT COUNT(*) AS total_active
    FROM transport_requests
    WHERE transporter_id = ?
      AND status IN ('accepted', 'in_progress')
");
$stmtActive->bind_param("i", $transporter_id);
$stmtActive->execute();
$total_active = $stmtActive->get_result()->fetch_assoc()['total_active'];
?>
<!DOCTYPE html>
<html lang="<?= ($lang_code === 'ar') ? 'ar' : 'en' ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="UTF-8">
    <title><?= ($lang_code === 'ar') ? "لوحة تحكم الناقل" : "Transporter Dashboard"; ?></title>

    <!-- CSS + Bootstrap + Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/brand.css?v=2">
    <link rel="stylesheet" href="../css/farmer.css?v=300">
    <?php if ($lang_code === 'ar'): ?>
        <link rel="stylesheet" href="../css/style_ar.css?v=300">
    <?php endif; ?>
</head>

<body data-role="transporter">

<!-- SIDEBAR -->
<?php include "../includes/transporter_sidebar.php"; ?>

<div class="menu-overlay"></div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- TOP BAR -->
    <?php include "../includes/transporter_topbar.php"; ?>
    <br>

    <!-- WELCOME BOX -->
    <div class="welcome-box mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

            <div>
                <h4 class="mb-1">
                    <?= ($lang_code === 'ar' ? "مرحبًا، " : "Welcome, ") . htmlspecialchars($transporter['name']); ?>
                </h4>

                <?php if (!empty($transporter['email'])): ?>
                    <p class="mb-1">
                        <strong><?= ($lang_code === 'ar' ? "البريد الإلكتروني:" : "Email:"); ?></strong>
                        <?= htmlspecialchars($transporter['email']); ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($transporter['vehicle_type'])): ?>
                    <p class="mb-0">
                        <strong><?= ($lang_code === 'ar' ? "نوع المركبة:" : "Vehicle Type:"); ?></strong>
                        <span class="badge bg-light text-dark"><?= htmlspecialchars($transporter['vehicle_type']); ?></span>
                    </p>
                <?php endif; ?>
            </div>

            <div class="quick-links d-flex flex-wrap gap-2">
                <a href="available_requests.php" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-list-ul"></i>
                    <?= ($lang_code === 'ar' ? "عرض الطلبات المتاحة" : "Available Requests"); ?>
                </a>

                <a href="my_requests.php" class="btn btn-outline-success btn-sm">
                    <i class="fa-solid fa-truck"></i>
                    <?= ($lang_code === 'ar' ? "عرض طلباتي" : "My Requests"); ?>
                </a>
            </div>

        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="row g-3">

        <!-- Available Requests -->
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="fa-solid fa-list-ul"></i>
                </div>
                <div>
                    <h6 class="mb-1">
                        <?= ($lang_code === 'ar' ? "الطلبات المتاحة" : "Available Requests"); ?>
                    </h6>
                    <div class="number text-success"><?= $total_available; ?></div>
                    <small class="text-muted">
                        <?= ($lang_code === 'ar'
                            ? "طلبات جاهزة للقبول."
                            : "Requests waiting for transporter."); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- My Total Requests -->
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="fa-solid fa-truck"></i>
                </div>
                <div>
                    <h6 class="mb-1">
                        <?= ($lang_code === 'ar' ? "كل طلباتي" : "All My Requests"); ?>
                    </h6>
                    <div class="number text-primary"><?= $total_my; ?></div>
                    <small class="text-muted">
                        <?= ($lang_code === 'ar'
                            ? "إجمالي الطلبات التي استلمتها."
                            : "All requests you accepted."); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Active Requests -->
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h6 class="mb-1">
                        <?= ($lang_code === 'ar' ? "الطلبات النشطة" : "Active Requests"); ?>
                    </h6>
                    <div class="number text-warning"><?= $total_active; ?></div>
                    <small class="text-muted">
                        <?= ($lang_code === 'ar'
                            ? "طلبات قيد التنفيذ."
                            : "Requests currently in progress."); ?>
                    </small>
                </div>
            </div>
        </div>

    </div>

</div>

<script src="../js/farmer.js"></script>

</body>
</html>
