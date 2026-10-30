<?php
require_once __DIR__ . "/../includes/security.php";
session_start();
include "../includes/language.php";
include "../config/db.php";

// حماية الوصول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../auth/login.php");
    exit;
}

$farmer_id = $_SESSION['user_id'];
$current_page = "my_products"; // ⭐ لتحريك التاب المناسب

// جلب بيانات المزارع (للحصول على موقعه)
$stmtF = $conn->prepare("SELECT name, email, location FROM farmers WHERE farmer_id = ?");
$stmtF->bind_param("i", $farmer_id);
$stmtF->execute();
$farmer = $stmtF->get_result()->fetch_assoc();
$farm_location = $farmer['location']; // ⭐ نفس الموقع لجميع المنتجات

// جلب المنتجات الخاصة بالمزارع
$stmt = $conn->prepare("SELECT * FROM products WHERE farmer_id = ? ORDER BY is_active DESC, product_id DESC");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$products = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="<?= ($lang_code === 'ar') ? 'ar' : 'en' ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="UTF-8">
    <title><?= ($lang_code==='ar'?'منتجاتي':'My Products'); ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/farmer-products-v2.css?v=1">
    <link rel="stylesheet" href="../css/brand.css?v=2">
<link rel="stylesheet" href="../css/farmer.css?v=200">

    <?php if ($lang_code === 'ar'): ?>
        <link rel="stylesheet" href="../css/style_ar.css?v=200">
    <?php endif; ?>
</head>

<body data-role="farmer">

<!-- SIDEBAR -->
<?php include "../includes/farmer_sidebar.php"; ?>

<div class="menu-overlay"></div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- TOP BAR -->
    <?php include "../includes/farmer_topbar.php"; ?></br>

    <div class="dashboard-box">

    <section class="fpm-shell">
        <div class="fpm-heading">
            <div>
                <h1><?= $lang_code==='ar'?'إدارة المحاصيل':'Manage Crops' ?></h1>
                <p class="fpm-subtitle"><?= $lang_code==='ar'?'تابع أسعار المحاصيل وكمياتها وحالة عرضها.':'Track crop prices, stock and visibility.' ?></p>
            </div>
            <a class="fpm-add" href="add_product.php"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?= $lang_code==='ar'?'إضافة محصول':'Add crop' ?></a>
        </div>
        <?php if (isset($_SESSION['success_msg'])): ?>
          <div class="alert alert-success" role="status"><?= htmlspecialchars($_SESSION['success_msg'], ENT_QUOTES, 'UTF-8') ?></div>
          <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_msg'])): ?>
          <div class="alert alert-danger" role="alert"><?= htmlspecialchars($_SESSION['error_msg'], ENT_QUOTES, 'UTF-8') ?></div>
          <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?><div class="alert alert-success" role="status"><?= $lang_code==='ar'?'تم حفظ التعديل.':'Changes saved.' ?></div><?php endif; ?>
        <div class="fpm-tools">
           <input id="fpmSearch" class="fpm-search" type="search" autocomplete="off" aria-label="<?= $lang_code==='ar'?'البحث عن محصول':'Search crops' ?>" placeholder="<?= $lang_code==='ar'?'ابحث عن محصول...':'Search crops...' ?>">
           <select id="fpmFilter" class="fpm-select" aria-label="<?= $lang_code==='ar'?'تصفية حسب الحالة':'Filter by status' ?>">
             <option value="all"><?= $lang_code==='ar'?'كل المحاصيل':'All crops' ?></option>
             <option value="active"><?= $lang_code==='ar'?'المعروضة':'Visible' ?></option>
             <option value="hidden"><?= $lang_code==='ar'?'المخفية':'Hidden' ?></option>
           </select>
        </div>
        <div class="fpm-grid" id="fpmGrid">
          <?php while ($p = $products->fetch_assoc()):
            $active = (int)$p['is_active'] === 1;
            $name = htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8');
            $pid = (int)$p['product_id']; ?>
            <article class="fpm-card <?= $active ? '' : 'is-hidden' ?>" data-status="<?= $active?'active':'hidden' ?>" data-name="<?= $name ?>">
              <div class="fpm-card-head"><h2 class="fpm-card-title"><i class="fa-solid fa-seedling text-success" aria-hidden="true"></i> <?= $name ?></h2>
                <span class="fpm-status <?= $active?'active':'hidden' ?>"><?= $active?($lang_code==='ar'?'معروض':'Visible'):($lang_code==='ar'?'مخفي':'Hidden') ?></span>
              </div>
              <div class="fpm-metrics">
                <div class="fpm-metric"><small><?= $lang_code==='ar'?'المخزون':'Stock' ?></small><strong><?= htmlspecialchars((string)$p['quantity'], ENT_QUOTES, 'UTF-8') ?> kg</strong></div>
                <div class="fpm-metric"><small><?= $lang_code==='ar'?'سعر الكيلو':'Per kg' ?></small><strong><?= htmlspecialchars((string)$p['price'], ENT_QUOTES, 'UTF-8') ?> JOD</strong></div>
              </div>
              <div class="fpm-details">
                <span><i class="fa-regular fa-calendar" aria-hidden="true"></i> <?= $lang_code==='ar'?'الحصاد':'Harvest' ?>: <?= htmlspecialchars((string)($p['harvest_date'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
                <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= htmlspecialchars((string)$farm_location, ENT_QUOTES, 'UTF-8') ?></span>
              </div>
              <div class="fpm-actions">
                <?php if ($active): ?>
                  <a class="btn btn-outline-primary btn-sm" href="edit_product.php?id=<?= $pid ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i> <?= $lang_code==='ar'?'تعديل':'Edit' ?></a>
                  <form method="post" action="delete_product.php" onsubmit="return confirm(<?= htmlspecialchars(json_encode($lang_code==='ar'?'إخفاء هذا المحصول من المتجر؟':'Hide this crop from the store?', JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_HEX_TAG), ENT_QUOTES, 'UTF-8') ?>)">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $pid ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i> <?= $lang_code==='ar'?'إخفاء':'Hide' ?></button>
                  </form>
                <?php else: ?><span class="text-muted small"><?= $lang_code==='ar'?'محفوظ بسجل المنتجات':'Retained in product history' ?></span><?php endif; ?>
              </div>
            </article>
          <?php endwhile; ?>
        </div>
        <div id="fpmEmpty" class="fpm-empty" hidden><?= $lang_code==='ar'?'لا توجد محاصيل مطابقة.':'No matching crops.' ?></div>
    </section>
    </div>

</div>

<script src="../js/farmer.js"></script>
<script src="../js/farmer-products-v2.js" defer></script>

</body>
</html>
