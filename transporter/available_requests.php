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
// مهم لتفعيل التبويب الصحيح في الـ sidebar/topbar
$current_page = "available";

// جلب كل الطلبات المعلقة
$stmt = $conn->prepare("
    SELECT tr.*, 
           p.name AS product_name,
           s.name AS store_name,
           f.name AS farmer_name,
           f.location AS farmer_location,
           s.location AS store_location
    FROM transport_requests tr
    JOIN products  p ON tr.product_id = p.product_id
    JOIN stores    s ON tr.store_id   = s.store_id
    JOIN farmers   f ON tr.farmer_id  = f.farmer_id
    WHERE LOWER(tr.status) = 'pending'
    ORDER BY tr.request_date DESC
");
$stmt->execute();
$requests = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="<?= ($lang_code === 'ar') ? 'ar' : 'en' ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="UTF-8">
    <title><?= ($lang_code === 'ar' ? "الطلبات المتاحة" : "Available Transport Requests"); ?></title>

    <!-- STYLES -->
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
        <i class="fa-solid fa-list-check"></i>
        <?= ($lang_code === 'ar' ? "الطلبات المتاحة للنقل" : "Available Transport Requests"); ?>
    </h3>

    <!-- SUCCESS MESSAGE -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'accepted'): ?>
        <div class="alert alert-success">
            <?= ($lang_code === 'ar' ? "تم قبول الطلب بنجاح" : "Request accepted successfully."); ?>
        </div>
    <?php endif; ?>

    <!-- REQUEST LIST -->
    <div class="row g-3">

        <?php if ($requests->num_rows === 0): ?>
            <div class="col-12">
                <div class="dashboard-box text-center">
                    <p class="text-muted mb-0">
                        <?= ($lang_code === 'ar'
                            ? "لا توجد طلبات متاحة حالياً"
                            : "No pending requests available."); ?>
                    </p>
                </div>
            </div>

        <?php else: ?>

            <?php while ($row = $requests->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="product-card">

                        <h5 class="product-title mb-2">
                            <i class="fa-solid fa-box"></i>
                            <?= htmlspecialchars($row['product_name']); ?>
                        </h5>

                        <p><strong><?= ($lang_code === 'ar' ? "المتجر:" : "Store:"); ?></strong>
                            <?= htmlspecialchars($row['store_name']); ?>
                        </p>

                        <p><strong><?= ($lang_code === 'ar' ? "المزارع:" : "Farmer:"); ?></strong>
                            <?= htmlspecialchars($row['farmer_name']); ?>
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
                                <?= number_format((float)($row['driver_earning'] ?? 0), 2); ?> JOD
                            </p>
                            <?php if (!empty($row['distance_type'])): ?>
                                <p class="mt-2 mb-0"><span class="badge bg-light text-dark">
                                    <?= ($lang_code === 'ar' ? "المسافة:" : "Zone:"); ?> <?= htmlspecialchars($row['distance_type']); ?>
                                </span></p>
                            <?php endif; ?>
                        <?php endif; ?>



                        <p>
                            <strong><?= ($lang_code === 'ar' ? "التاريخ:" : "Date:"); ?></strong>
                            <span class="badge bg-light text-dark">
                                <?= htmlspecialchars($row['request_date']); ?>
                            </span>
                        </p>

                        <div class="mt-3 text-end">
                            <a href="accept_request.php?id=<?= (int)$row['request_id']; ?>"
                               class="btn btn-success btn-sm">
                                <i class="fa-solid fa-check"></i>
                                <?= ($lang_code === 'ar' ? "قبول الطلب" : "Accept Request"); ?>
                            </a>
                        </div>

                    </div>
                </div>
            <?php endwhile; ?>

        <?php endif; ?>

    </div>
</div>    

</div>

</body>
</html>
