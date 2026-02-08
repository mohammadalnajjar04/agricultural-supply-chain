<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

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
    <title><?= ($lang_code === 'ar' ? "طلباتي" : "My Requests"); ?></title>

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../css/style.css?v=12">
    <link rel="stylesheet" href="../css/brand.css?v=2">
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

    <!-- REQUEST LIST -->
    <div class="row g-3">

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
                <div class="col-md-6 col-lg-4">
                    <div class="product-card">

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
                            <span class="badge bg-light text-dark"><?= $row['request_date']; ?></span>
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

                            <span class="badge bg-<?= $badge ?>"><?= ucfirst($status); ?></span>
                        </p>

                        <?php if ($status === 'accepted'): ?>
                            <a class="btn btn-outline-primary w-100" href="update_request_status.php?id=<?= (int)$row['request_id']; ?>&action=start">
                                <i class="fa-solid fa-play"></i>
                                <?= ($lang_code === 'ar') ? "بدء التوصيل" : "Start Delivery"; ?>
                            </a>
                        <?php elseif ($status === 'in_progress'): ?>
                            <a class="btn btn-success w-100" href="update_request_status.php?id=<?= (int)$row['request_id']; ?>&action=deliver">
                                <i class="fa-solid fa-circle-check"></i>
                                <?= ($lang_code === 'ar') ? "تم التسليم" : "Mark Delivered"; ?>
                            </a>
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
