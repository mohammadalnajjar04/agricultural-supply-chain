<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php?role=admin");
    exit;
}

$is_ar = ($lang_code === 'ar');
$msg = '';
$err = '';

function map_role_table(string $role): array {
    switch ($role) {
        case 'farmer': return ['table' => 'farmers', 'id' => 'farmer_id'];
        case 'transporter': return ['table' => 'transporters', 'id' => 'transporter_id'];
        case 'store': return ['table' => 'stores', 'id' => 'store_id'];
        default: return ['table' => '', 'id' => ''];
    }
}

if (isset($_GET['action'], $_GET['role'], $_GET['id'])) {
    $action = strtolower(trim($_GET['action']));
    $role = strtolower(trim($_GET['role']));
    $id = (int)$_GET['id'];

    $m = map_role_table($role);
    if ($id > 0 && $m['table'] !== '' && in_array($action, ['approve','reject'], true)) {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';
        $sql = "UPDATE {$m['table']} SET status = ? WHERE {$m['id']} = ?";
        $stmt = $GLOBALS['conn']->prepare($sql);
        $stmt->bind_param("si", $new_status, $id);
        if ($stmt->execute()) {
            $msg = ($new_status === 'approved')
                ? ($is_ar ? "تم اعتماد الحساب بنجاح." : "Account approved successfully.")
                : ($is_ar ? "تم رفض الحساب." : "Account rejected.");
        } else {
            $err = $is_ar ? "حدث خطأ أثناء تحديث الحالة." : "Failed to update status.";
        }
    } else {
        $err = $is_ar ? "طلب غير صالح." : "Invalid request.";
    }
}

function fetch_pending(string $table, string $id_col): array {
    $sql = "SELECT {$id_col} AS id, name, email, location, phone, status, verification_doc FROM {$table} WHERE status = 'pending' ORDER BY {$id_col} DESC";
    $res = $GLOBALS['conn']->query($sql);
    $rows = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

$pending_farmers = fetch_pending('farmers','farmer_id');
$pending_transporters = fetch_pending('transporters','transporter_id');
$pending_stores = fetch_pending('stores','store_id');

$tab = strtolower(trim($_GET['tab'] ?? 'farmer'));
if (!in_array($tab, ['farmer','transporter','store'], true)) {
    $tab = 'farmer';
}

$count_farmer = count($pending_farmers);
$count_transporter = count($pending_transporters);
$count_store = count($pending_stores);
$count_total = $count_farmer + $count_transporter + $count_store;

// Total users in system (all statuses)
$total_farmers = 0;
$total_transporters = 0;
$total_stores = 0;
try {
    $r1 = $conn->query("SELECT COUNT(*) AS c FROM farmers");
    if ($r1) { $total_farmers = (int)($r1->fetch_assoc()['c'] ?? 0); }
    $r2 = $conn->query("SELECT COUNT(*) AS c FROM transporters");
    if ($r2) { $total_transporters = (int)($r2->fetch_assoc()['c'] ?? 0); }
    $r3 = $conn->query("SELECT COUNT(*) AS c FROM stores");
    if ($r3) { $total_stores = (int)($r3->fetch_assoc()['c'] ?? 0); }
} catch (Throwable $e) {
    // ignore
}
$total_users_system = $total_farmers + $total_transporters + $total_stores;


// Platform earnings (delivery commissions)
$earn_total = 0.0;
$earn_month = 0.0;
try {
    $resE = $conn->query("SELECT COALESCE(SUM(platform_fee),0) AS t FROM transport_requests WHERE status IN ('delivered','completed')");
    if ($resE) { $earn_total = (float)($resE->fetch_assoc()['t'] ?? 0); }
    $resM = $conn->query("SELECT COALESCE(SUM(platform_fee),0) AS t FROM transport_requests WHERE status IN ('delivered','completed') AND DATE_FORMAT(COALESCE(delivery_date, transport_date), '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
    if ($resM) { $earn_month = (float)($resM->fetch_assoc()['t'] ?? 0); }
} catch (Throwable $e) {
    // ignore if schema not updated yet
}
?>
<!DOCTYPE html>
<html lang="<?= $is_ar ? 'ar' : 'en' ?>" dir="<?= $is_ar ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $is_ar ? "لوحة تحكم المشرف" : "Admin Dashboard" ?></title>
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
    <a class="navbar-brand brand-badge" href="../index.php">
      <span class="brand-dot"></span>
      <span><?= $is_ar ? "لوحة المشرف" : "Admin Panel" ?></span>
    </a>
    <div class="d-flex align-items-center gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="?lang=<?= $is_ar ? 'en' : 'ar' ?>">
        <i class="fa-solid fa-language"></i> <?= $is_ar ? "English" : "العربية" ?>
      </a>
      <a class="btn btn-sm btn-soft" href="../auth/logout.php">
        <i class="fa-solid fa-right-from-bracket"></i> <?= $is_ar ? "تسجيل الخروج" : "Logout" ?>
      </a>
    </div>
  </div>
</nav>

<main class="container py-4">
  <div class="hero hero-max reveal mb-4">
    <div class="hero-orb" style="top:-120px; left:-140px"></div>
    <div class="hero-orb orb-2" style="top:-160px; right:-180px"></div>
    <div class="hero-inner">
      <span class="pill mb-3"><i class="fa-solid fa-shield-halved"></i> <?= $is_ar ? "مراجعة الحسابات" : "Account verification" ?></span>
      <div class="section-kicker mb-2"><?= $is_ar ? "لوحة تحكم المشرف" : "Admin panel" ?></div>
      <h1 class="hero-title mb-2"><?= $is_ar ? "اعتماد المستخدمين الجدد" : "Approve new users" ?></h1>
      <p class="hero-sub mb-0"><?= $is_ar ? "نظام توثيق يرفع الموثوقية: الحسابات تبدأ (Pending) حتى مراجعة الإثبات." : "A verification flow to build trust: new accounts are (Pending) until proof is reviewed." ?></p>
    </div>
  </div>

  <div class="stat-grid mb-3">
    <div class="cardx stat reveal"><div class="cardx-body">
      <div class="k"><i class="fa-solid fa-hourglass-half me-2"></i><?= (int)$count_total ?></div>
      <div class="l"><?= $is_ar ? "طلبات بانتظار المراجعة" : "Pending approvals" ?></div>
    </div></div>
    <div class="cardx stat reveal"><div class="cardx-body">
      <div class="k"><i class="fa-solid fa-tractor me-2"></i><?= (int)$count_farmer ?></div>
      <div class="l"><?= $is_ar ? "مزارعون" : "Farmers" ?></div>
    </div></div>
    <div class="cardx stat reveal"><div class="cardx-body">
      <div class="k"><i class="fa-solid fa-truck me-2"></i><?= (int)$count_transporter ?></div>
      <div class="l"><?= $is_ar ? "ناقلون" : "Transporters" ?></div>
    </div></div>
    <div class="cardx stat reveal"><div class="cardx-body">
      <div class="k"><i class="fa-solid fa-store me-2"></i><?= (int)$count_store ?></div>
      <div class="l"><?= $is_ar ? "متاجر" : "Stores" ?></div>
    </div></div>

    <div class="cardx stat reveal"><div class="cardx-body">
      <div class="k"><i class="fa-solid fa-coins me-2"></i><?= number_format((float)$earn_total, 2) ?> JOD</div>
      <div class="l"><?= $is_ar ? "أرباح المنصة (إجمالي)" : "Platform earnings (total)" ?></div>
      <div class="text-muted small mt-1">
        <?= $is_ar ? "هذا الشهر:" : "This month:" ?> <b><?= number_format((float)$earn_month, 2) ?> JOD</b>
        · <a href="platform_earnings.php" class="link"><?= $is_ar ? "تفاصيل" : "Details" ?></a>
      </div>
    </div></div>
  </div>

  <div class="cardx mb-3 reveal">
    <div class="cardx-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <div class="fw-bold fs-5 mb-1"><?= $is_ar ? "تصفية حسب الدور" : "Filter by role" ?></div>
          <div class="text-muted"><?= $is_ar ? "اختر الدور لعرض طلبات الاعتماد" : "Select a role to review pending requests" ?></div>
        </div>
        <div class="segmented">
          <a class="seg <?= $tab==='farmer' ? 'active' : '' ?>" href="?tab=farmer&lang=<?= $is_ar ? 'ar' : 'en' ?>"><i class="fa-solid fa-tractor"></i> <?= $is_ar ? "مزارعون" : "Farmers" ?> <span class="badge-soft"><?= (int)$count_farmer ?></span></a>
          <a class="seg <?= $tab==='transporter' ? 'active' : '' ?>" href="?tab=transporter&lang=<?= $is_ar ? 'ar' : 'en' ?>" style="--brand:#1e63d8; --brand-2:#0e46a8"><i class="fa-solid fa-truck"></i> <?= $is_ar ? "ناقلون" : "Transporters" ?> <span class="badge-soft"><?= (int)$count_transporter ?></span></a>
          <a class="seg <?= $tab==='store' ? 'active' : '' ?>" href="?tab=store&lang=<?= $is_ar ? 'ar' : 'en' ?>" style="--brand:#f4b400; --brand-2:#c28a00"><i class="fa-solid fa-store"></i> <?= $is_ar ? "متاجر" : "Stores" ?> <span class="badge-soft"><?= (int)$count_store ?></span></a>
        </div>
      </div>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <?php
    function render_table(string $title, string $role, array $rows, bool $is_ar) {
      $count = count($rows);
      echo '<div class="cardx mb-3 reveal"><div class="cardx-body">';
      echo '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">';
      echo '<div><div class="fw-bold fs-5">' . htmlspecialchars($title) . '</div>';
      echo '<div class="text-muted">' . ($is_ar ? "طلبات اعتماد" : "Pending requests") . ': <b>' . $count . '</b></div></div>';
      echo '</div><hr>';

      if ($count === 0) {
        echo '<div class="text-muted">' . ($is_ar ? "لا يوجد طلبات حالياً." : "No pending accounts right now.") . '</div>';
        echo '</div></div>';
        return;
      }

      echo '<div class="table-clean">';
      echo '<div class="table-responsive">';
      echo '<table class="table table-hover align-middle">';
      echo '<thead><tr>';
      echo '<th>#</th><th>' . ($is_ar ? "الاسم" : "Name") . '</th><th>Email</th><th>' . ($is_ar ? "الموقع" : "Location") . '</th><th>' . ($is_ar ? "الهاتف" : "Phone") . '</th><th>' . ($is_ar ? "إثبات" : "Proof") . '</th><th>' . ($is_ar ? "إجراء" : "Action") . '</th>';
      echo '</tr></thead><tbody>';

      foreach ($rows as $r) {
        $proof = $r['verification_doc'] ? ('../' . ltrim($r['verification_doc'], '/')) : '';
        echo '<tr>';
        echo '<td>' . (int)$r['id'] . '</td>';
        echo '<td>' . htmlspecialchars($r['name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($r['email'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($r['location'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($r['phone'] ?? '') . '</td>';
        echo '<td>';
        if ($proof) {
          echo '<a class="btn btn-sm btn-soft" target="_blank" href="' . htmlspecialchars($proof) . '"><i class="fa-solid fa-file"></i> ' . ($is_ar ? "عرض" : "View") . '</a>';
        } else {
          echo '<span class="text-muted">—</span>';
        }
        echo '</td>';
        $keep = '&tab=' . urlencode($GLOBALS['tab']) . '&lang=' . urlencode($GLOBALS['lang_code']);
        echo '<td class="d-flex gap-2 flex-wrap">';
        echo '<a class="btn btn-sm btn-success" href="?action=approve&role=' . urlencode($role) . '&id=' . (int)$r['id'] . $keep . '"><i class="fa-solid fa-check"></i> ' . ($is_ar ? "اعتماد" : "Approve") . '</a>';
        echo '<a class="btn btn-sm btn-outline-danger" href="?action=reject&role=' . urlencode($role) . '&id=' . (int)$r['id'] . $keep . '"><i class="fa-solid fa-xmark"></i> ' . ($is_ar ? "رفض" : "Reject") . '</a>';
        echo '</td>';
        echo '</tr>';
      }

      echo '</tbody></table></div></div>';
      echo '</div></div>';
    }

    if ($tab === 'farmer') {
      render_table($is_ar ? "مزارعون" : "Farmers", 'farmer', $pending_farmers, $is_ar);
    } elseif ($tab === 'transporter') {
      render_table($is_ar ? "ناقلون" : "Transporters", 'transporter', $pending_transporters, $is_ar);
    } else {
      render_table($is_ar ? "متاجر" : "Stores", 'store', $pending_stores, $is_ar);
    }
  ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/brand.js?v=3"></script>
</body>
</html>
