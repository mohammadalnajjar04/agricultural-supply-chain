<?php
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
$stmt = $conn->prepare("SELECT * FROM products WHERE farmer_id = ? ORDER BY product_id DESC");
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

    <!-- PAGE TITLE -->
    <h3 class="mt-3 mb-4 page-main-title">
        <i class="fa-solid fa-boxes-stacked text-success"></i>
        <?= ($lang_code==='ar'?'منتجاتي':'My Products'); ?>
    </h3>

    <!-- Products Grid -->
    <div class="row">

        <?php if ($products->num_rows > 0): ?>
            <?php while ($p = $products->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">

                    <div class="product-card shadow-sm p-3 bg-white rounded">

                        <h5 class="product-title">
                            <i class="fa-solid fa-leaf text-success"></i>
                            <?= htmlspecialchars($p['name']); ?>
                        </h5>

                        <p><strong><?=($lang_code=='ar'?'الكمية:':'Quantity:');?></strong> <?= $p['quantity']; ?></p>
                        <p><strong><?=($lang_code=='ar'?'السعر:':'Price:');?></strong> <?= $p['price']; ?> JOD</p>
                        <p><strong><?=($lang_code=='ar'?'تاريخ الحصاد:':'Harvest Date:');?></strong> <?= $p['harvest_date']; ?></p>

                        <p><strong><?=($lang_code=='ar'?'موقع المزرعة:':'Farm Location:');?></strong>
                            <span class="badge bg-light text-dark"><?= htmlspecialchars($farm_location); ?></span>
                        </p>

                        <div class="d-flex justify-content-between mt-3">

                            <a href="edit_product.php?id=<?= $p['product_id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-pen"></i>
                                <?=($lang_code=='ar'?'تعديل':'Edit');?>
                            </a>

                            <a href="delete_product.php?id=<?= $p['product_id']; ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('<?=($lang_code=='ar'?'هل أنت متأكد أنك تريد الحذف؟':'Are you sure to delete?');?>')">
                                <i class="fa-solid fa-trash"></i>
                                <?=($lang_code=='ar'?'حذف':'Delete');?>
                            </a>

                        </div>

                    </div>

                </div>
            <?php endwhile; ?>

        <?php else: ?>
            <div class="alert alert-warning text-center mt-4">
                <?=($lang_code=='ar'?'لا توجد منتجات حتى الآن.':'No products added yet.');?>
            </div>
        <?php endif; ?>

    </div>
    </div>

</div>

<script src="../js/farmer.js"></script>

</body>
</html>
