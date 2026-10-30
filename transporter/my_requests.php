<?php
session_start();
include "../includes/language.php";
include "../config/db.php";
require_once __DIR__ . "/../includes/security.php";

// حماية الصفحة – فقط الناقل
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "transporter") {
    header("Location: ../auth/login.php");
    exit;
}

$transporter_id = $_SESSION['user_id'];
$current_page = "my_requests";

$stmt = $conn->prepare("
    SELECT tr.*, 
           p.name AS product_name,
           s.name AS store_name
    FROM transport_requests tr
    JOIN products p ON tr.product_id = p.product_id
    JOIN stores s ON tr.store_id = s.store_id
    WHERE tr.transporter_id = ?
    ORDER BY tr.request_id DESC
");
$stmt->bind_param("i", $transporter_id);
$stmt->execute();
$requests = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="<?= ($lang_code === 'ar') ? 'ar' : 'en' ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= ($lang_code === 'ar' ? "طلباتي" : "My Requests"); ?></title>

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../css/style.css?v=12">
    <link rel="stylesheet" href="../css/brand.css?v=2">
    <link rel="stylesheet" href="../css/transporter-requests-v2.css?v=1">
<link rel="stylesheet" href="../css/farmer.css?v=12">

    <?php if ($lang_code === 'ar'): ?>
        <link rel="stylesheet" href="../css/style_ar.css?v=12">
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

<div class="dashboard-box">

    <!-- PAGE TITLE -->
    <h3 class="page-title mb-3">
        <i class="fa-solid fa-truck"></i>
        <?= ($lang_code === 'ar' ? "طلباتي" : "My Transport Requests"); ?>
    </h3>

    <div class="transport-tools mb-3">
      <label class="visually-hidden" for="transport-search"><?= $lang_code === 'ar' ? 'بحث في طلبات النقل' : 'Search transport requests' ?></label>
      <input id="transport-search" class="form-control" type="search" autocomplete="off" placeholder="<?= $lang_code === 'ar' ? 'ابحث عن محصول أو متجر أو مزارع...' : 'Search crop, store or farmer...' ?>">
      <span id="transport-count" class="small text-muted" aria-live="polite"></span>
    </div>
    <?php if (isset($_GET['err'])): ?>
      <div class="alert alert-warning" role="alert"><?= $lang_code === 'ar' ? 'تعذّر تنفيذ العملية. حدّث الصفحة وحاول مجددًا.' : 'Action could not be completed. Refresh and retry.' ?></div>
    <?php endif; ?>
    <!-- REQUEST LIST -->
    <div class="row g-3" id="transport-request-grid">

        <?php if ($requests->num_rows === 0): ?>
            <div class="col-12">
                <div class="dashboard-box text-center">
                    <p class="text-muted mb-0">
                        <?= ($lang_code === 'ar'
                            ? "لا توجد طلبات نقل حتى الآن"
                            : "You have no transport requests yet."); ?>
                    </p>
                </div>
            </div>

        <?php else: ?>

            <?php while ($row = $requests->fetch_assoc()): ?>

                <!-- بطاقة الطلب -->
                <div class="col-md-6 col-lg-4 transport-item">
                    <article class="product-card transport-card">

                        <h5 class="product-title mb-2">
                            <i class="fa-solid fa-box"></i>
                            <?= htmlspecialchars($row['product_name']); ?>
                        </h5>

                        <p><strong><?= ($lang_code === 'ar' ? "المتجر:" : "Store:"); ?></strong>
                            <?= htmlspecialchars($row['store_name']); ?>
                        </p>

                        <p><strong><?= ($lang_code === 'ar' ? "الكمية:" : "Quantity:"); ?></strong>
                            <?= (int)$row['quantity']; ?>
                        </p>

                        <?php if (!empty($row['delivery_fee'])): ?>
                            <hr class="my-2">
                            <p class="mb-1"><strong><?= ($lang_code === 'ar' ? "أجرة التوصيل:" : "Delivery Fee:"); ?></strong>
                                <?= number_format((float)$row['delivery_fee'], 2); ?> JOD
                            </p>
                            <p class="mb-0 text-muted small"><?= ($lang_code === 'ar' ? "ربحك الصافي:" : "Your net earning:"); ?>
                                <strong><?= number_format((float)($row['driver_earning'] ?? 0), 2); ?> JOD</strong>
                            </p>
                        <?php endif; ?>

                        <p>
                            <strong><?= ($lang_code === 'ar' ? "التاريخ:" : "Date:"); ?></strong>
                            <span class="badge bg-light text-dark"><?= htmlspecialchars((string)$row['request_date'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>

                        <p>
                            <strong><?= ($lang_code === 'ar' ? "الحالة:" : "Status:"); ?></strong>

                            <?php
                                $status = strtolower(trim((string)$row['status']));
                                $badge = "secondary";
                                if ($status === "pending") $badge = "warning";
                                if ($status === "accepted") $badge = "info";
                                if ($status === "in_progress") $badge = "primary";
                                if ($status === "delivered" || $status === "completed") $badge = "success";
                            ?>

                            <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>

                        <?php if ($status === 'accepted'): ?>
                            <form method="POST" action="update_request_status.php" class="transport-action-form">
                              <?= csrf_field() ?>
                              <input type="hidden" name="id" value="<?= (int)$row['request_id'] ?>">
                              <input type="hidden" name="action" value="start">
                              <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="fa-solid fa-play"></i>
                                <?= ($lang_code === 'ar') ? "بدء التوصيل" : "Start Delivery"; ?>
                              </button>
                            </form>
                        <?php elseif ($status === 'in_progress'): ?>
                            <form method="POST" action="update_request_status.php" class="transport-action-form">
                              <?= csrf_field() ?>
                              <input type="hidden" name="id" value="<?= (int)$row['request_id'] ?>">
                              <input type="hidden" name="action" value="deliver">
                              <button type="submit" class="btn btn-success w-100">
                                <i class="fa-solid fa-circle-check"></i>
                                <?= ($lang_code === 'ar') ? "تم التسليم" : "Mark Delivered"; ?>
                              </button>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>

            <?php endwhile; ?>

        <?php endif; ?>

    </div>

</div>    

</div>

</body>
</html>
