<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../auth/login.php");
    exit;
}

$farmer_id    = $_SESSION['user_id'];
$current_page = "transport_new"; // ⭐ مهم للـ Topbar + Tabs

// ---------------- جلب منتجات المزارع ----------------
$stmt = $conn->prepare("SELECT product_id, name FROM products WHERE farmer_id = ?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$products = $stmt->get_result();

// ---------------- جلب المتاجر ----------------
$stores = $conn->query("SELECT store_id, name FROM stores");

// ---------------- معالجة إرسال طلب النقل ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_id     = $_POST['product_id'];
    $store_id       = $_POST['store_id'];
    $quantity       = $_POST['quantity'];
    $transport_date = $_POST['transport_date'];
    $notes          = $_POST['notes'];
    $status         = "pending";

    $insert = $conn->prepare("
        INSERT INTO transport_requests 
        (farmer_id, product_id, quantity, store_id, transport_date, notes, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param(
        "iiiisss",
        $farmer_id,
        $product_id,
        $quantity,
        $store_id,
        $transport_date,
        $notes,
        $status
    );

    if ($insert->execute()) {
        header("Location: transport_requests.php?success=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= ($lang_code === 'ar') ? 'ar' : 'en' ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= ($lang_code==='ar'?'إنشاء طلب نقل':'Create Transport Request') ?></title>

    <!-- Bootstrap + Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/style.css?v=200">
    <link rel="stylesheet" href="../css/brand.css?v=2">
<link rel="stylesheet" href="../css/farmer.css?v=210">
    <?php if ($lang_code === 'ar'): ?>
        <link rel="stylesheet" href="../css/style_ar.css?v=210">
    <?php endif; ?>
</head>

<body data-role="farmer">

<!-- ============ SIDEBAR (نفس الصفحات الأخرى) ============ -->
<?php include "../includes/farmer_sidebar.php"; ?>

<div class="menu-overlay"></div>

<!-- ============ MAIN CONTENT ============ -->
<div class="main-content">

    <!-- TopBar الموحد -->
    <?php include "../includes/farmer_topbar.php"; ?></br>


    <!-- صندوق الفورم -->
    <div class="dashboard-box">

    <!-- عنوان الصفحة -->
    <h3 class="mb-3 page-main-title">
        <i class="fa-solid fa-truck-arrow-right text-success"></i>
        <?= ($lang_code==='ar'?'إنشاء طلب نقل':'Create Transport Request') ?>
    </h3>

    

        <form method="POST">

            <!-- Select Product -->
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <?= ($lang_code==='ar'?'اختر المنتج':'Select Product') ?>
                </label>
                <select name="product_id" class="form-control" required>
                    <option disabled selected>-- <?= ($lang_code==='ar'?'اختر منتج':'Select Product') ?> --</option>
                    <?php while($p = $products->fetch_assoc()): ?>
                        <option value="<?= $p['product_id'] ?>"><?= htmlspecialchars($p['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Select Store -->
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <?= ($lang_code==='ar'?'اختر المتجر':'Select Store') ?>
                </label>
                <select name="store_id" class="form-control" required>
                    <option disabled selected>-- <?= ($lang_code==='ar'?'اختر متجر':'Select Store') ?> --</option>
                    <?php while($s = $stores->fetch_assoc()): ?>
                        <option value="<?= $s['store_id'] ?>"><?= htmlspecialchars($s['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Quantity -->
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <?= ($lang_code==='ar'?'الكمية':'Quantity') ?>
                </label>
                <input type="number" name="quantity" class="form-control" required>
            </div>

            <!-- Transport Date -->
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <?= ($lang_code==='ar'?'تاريخ النقل':'Transport Date') ?>
                </label>
                <input type="date" name="transport_date" class="form-control" required>
            </div>

            <!-- Notes -->
            <div class="mb-3">
                <label class="form-label fw-bold">
                    <?= ($lang_code==='ar'?'ملاحظات':'Notes') ?>
                </label>
                <textarea name="notes" class="form-control" rows="3"></textarea>
            </div>

            <!-- Buttons -->
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-paper-plane"></i>
                    <?= ($lang_code==='ar'?'إرسال الطلب':'Submit Request') ?>
                </button>
            </div>

        </form>

    </div>

</div>

<script src="../js/farmer.js"></script>
</body>
</html>
