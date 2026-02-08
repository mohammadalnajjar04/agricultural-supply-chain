<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php?role=admin");
    exit;
}

$is_ar = ($lang_code === 'ar');

// Totals
$total = 0.0;
$month = 0.0;
$year = 0.0;

$resT = $conn->query("SELECT COALESCE(SUM(platform_fee),0) AS t FROM transport_requests WHERE status IN ('delivered','completed')");
if ($resT) { $total = (float)($resT->fetch_assoc()['t'] ?? 0); }

$resM = $conn->query("SELECT COALESCE(SUM(platform_fee),0) AS t FROM transport_requests WHERE status IN ('delivered','completed') AND YEAR(delivery_date)=YEAR(CURDATE()) AND MONTH(delivery_date)=MONTH(CURDATE())");
if ($resM) { $month = (float)($resM->fetch_assoc()['t'] ?? 0); }

$resY = $conn->query("SELECT COALESCE(SUM(platform_fee),0) AS t FROM transport_requests WHERE status IN ('delivered','completed') AND YEAR(delivery_date)=YEAR(CURDATE())");
if ($resY) { $year = (float)($resY->fetch_assoc()['t'] ?? 0); }

// Latest delivered requests
$stmt = $conn->prepare("
    SELECT tr.request_id, tr.order_id, tr.delivery_date, tr.delivery_fee, tr.platform_fee, tr.driver_earning,
           tr.distance_type,
           p.name AS product_name,
           f.name AS farmer_name,
           s.name AS store_name,
           t.name AS transporter_name
    FROM transport_requests tr
    LEFT JOIN products p ON tr.product_id = p.product_id
    LEFT JOIN farmers  f ON tr.farmer_id = f.farmer_id
    LEFT JOIN stores   s ON tr.store_id = s.store_id
    LEFT JOIN transporters t ON tr.transporter_id = t.transporter_id
    WHERE tr.status IN ('delivered','completed')
    ORDER BY tr.request_id DESC
    LIMIT 100
");
$stmt->execute();
$rows = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="<?= $is_ar ? 'ar' : 'en' ?>" dir="<?= $is_ar ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $is_ar ? "أرباح المنصة" : "Platform Earnings" ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/brand.css?v=3">
  <?php if ($is_ar): ?>
    <link rel="stylesheet" href="../css/style_ar.css?v=2">
  <?php endif; ?>
</head>
<body data-role="admin">

<nav class="navbar brand-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand brand-badge" href="dashboard.php">
      <span class="brand-dot"></span>
      <span><?= $is_ar ? "أرباح المنصة" : "Platform Earnings" ?></span>
    </a>
    <div class="d-flex align-items-center gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="?lang=<?= $is_ar ? 'en' : 'ar' ?>">
        <i class="fa-solid fa-language"></i> <?= $is_ar ? "English" : "العربية" ?>
      </a>
      <a class="btn btn-sm btn-soft" href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> <?= $is_ar ? "رجوع" : "Back" ?></a>
    </div>
  </div>
</nav>

<main class="container py-4">
  <div class="stat-grid mb-3">
    <div class="cardx stat reveal"><div class="cardx-body">
      <div class="k"><i class="fa-solid fa-coins me-2"></i><?= number_format($total, 2) ?> JOD</div>
      <div class="l"><?= $is_ar ? "الإجمالي" : "Total" ?></div>
    </div></div>
    <div class="cardx stat reveal"><div class="cardx-body">
      <div class="k"><i class="fa-solid fa-calendar-days me-2"></i><?= number_format($month, 2) ?> JOD</div>
      <div class="l"><?= $is_ar ? "هذا الشهر" : "This month" ?></div>
    </div></div>
    <div class="cardx stat reveal"><div class="cardx-body">
      <div class="k"><i class="fa-solid fa-calendar me-2"></i><?= number_format($year, 2) ?> JOD</div>
      <div class="l"><?= $is_ar ? "هذه السنة" : "This year" ?></div>
    </div></div>
  </div>

  <div class="cardx reveal">
    <div class="cardx-body">
      <div class="fw-bold fs-5 mb-2"><?= $is_ar ? "آخر عمليات التوصيل" : "Latest deliveries" ?></div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th><?= $is_ar ? "تاريخ" : "Date" ?></th>
              <th><?= $is_ar ? "منتج" : "Product" ?></th>
              <th><?= $is_ar ? "مزارع" : "Farmer" ?></th>
              <th><?= $is_ar ? "متجر" : "Store" ?></th>
              <th><?= $is_ar ? "ناقل" : "Transporter" ?></th>
              <th><?= $is_ar ? "أجرة" : "Fee" ?></th>
              <th><?= $is_ar ? "عمولة" : "Platform" ?></th>
              <th><?= $is_ar ? "ربح الناقل" : "Driver" ?></th>
              <th><?= $is_ar ? "Zone" : "Zone" ?></th>
            </tr>
          </thead>
          <tbody>
            <?php while ($r = $rows->fetch_assoc()): ?>
              <tr>
                <td><?= (int)$r['request_id'] ?></td>
                <td><?= htmlspecialchars($r['delivery_date'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['product_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['farmer_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['store_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['transporter_name'] ?? '-') ?></td>
                <td><?= number_format((float)($r['delivery_fee'] ?? 0), 2) ?></td>
                <td><b><?= number_format((float)($r['platform_fee'] ?? 0), 2) ?></b></td>
                <td><?= number_format((float)($r['driver_earning'] ?? 0), 2) ?></td>
                <td><?= htmlspecialchars($r['distance_type'] ?? '-') ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<script src="../js/brand.js?v=3"></script>
</body>
</html>
