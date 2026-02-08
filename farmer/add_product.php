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
$current_page = "add_product"; // ⭐ لتفعيل التاب المناسب

// ---------------- جلب بيانات المزارع للحصول على موقع المزرعة ----------------
$stmtF = $conn->prepare("SELECT name, location FROM farmers WHERE farmer_id = ?");
$stmtF->bind_param("i", $farmer_id);
$stmtF->execute();
$farmer        = $stmtF->get_result()->fetch_assoc();
$farm_location = $farmer['location'];   // سيتم استخدامه لكل المنتجات الجديدة

// ---------------- معالجة إضافة المنتج ----------------
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name         = trim($_POST['name']);
    $quantity     = trim($_POST['quantity']);
    $price        = trim($_POST['price']);
    $harvest_date = trim($_POST['harvest_date']);
    // الموقع يأتي من الحقل المخفي وليس من المستخدم
    $location     = $farm_location;

    if (!empty($name) && !empty($quantity) && !empty($price)) {

        $stmt = $conn->prepare("
            INSERT INTO products (name, quantity, price, harvest_date, location, farmer_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param("sidssi",
            $name,
            $quantity,
            $price,
            $harvest_date,
            $location,
            $farmer_id
        );

        if ($stmt->execute()) {
            $message = ($lang_code === 'ar')
                ? "<div class='alert alert-success mb-3'>✔️ تمت إضافة المنتج بنجاح</div>"
                : "<div class='alert alert-success mb-3'>✔️ Product added successfully</div>";
        } else {
            $message = ($lang_code === 'ar')
                ? "<div class='alert alert-danger mb-3'>✖ حدث خطأ أثناء إضافة المنتج</div>"
                : "<div class='alert alert-danger mb-3'>✖ Failed to add product</div>";
        }

    } else {
        $message = ($lang_code === 'ar')
            ? "<div class='alert alert-warning mb-3'>⚠ الرجاء تعبئة جميع الحقول المطلوبة.</div>"
            : "<div class='alert alert-warning mb-3'>⚠ Please fill in all required fields.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="<?= ($lang_code === 'ar') ? 'ar' : 'en' ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= ($lang_code === 'ar') ? "إضافة منتج" : "Add Product" ?></title>

    <!-- Bootstrap + Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/style.css?v=2">
    <link rel="stylesheet" href="../css/brand.css?v=2">
    <link rel="stylesheet" href="../css/farmer.css?v=210">

    <?php if ($lang_code === 'ar'): ?>
        <link rel="stylesheet" href="../css/style_ar.css?v=210">
    <?php endif; ?>
</head>

<body data-role="farmer">

<!-- ============ SIDEBAR ============ -->
<?php include "../includes/farmer_sidebar.php"; ?>

<div class="menu-overlay"></div>

<!-- ============ MAIN CONTENT ============ -->
<div class="main-content">

    <!-- Top Bar الموحد -->
    <?php include "../includes/farmer_topbar.php"; ?></br>

    <!-- صندوق الفورم -->
    <div class="dashboard-box">

        <h3 class="mb-3 page-main-title">
            <i class="fa-solid fa-plus text-success"></i>
            <?= ($lang_code === 'ar') ? "إضافة منتج جديد" : "Add New Product" ?>
        </h3>

        <?= $message ?>

        <form method="POST">

            <!-- Product Name -->
            <div class="mb-3">
                <label class="form-label">
                    <b><?=($lang_code==="ar"?"اسم المنتج":"Product Name")?></b>
                </label>
                <input type="text" name="name" class="form-control" required placeholder="<?= ($lang_code==='ar')?'مثال: طماطم':'Example: Tomato' ?>">
                <div class="form-text"><?= ($lang_code==='ar')?'اختر الاسم بدقة لأن الذكاء الاصطناعي يستخدمه للتوقع.':'Use an exact name; AI uses it for forecasting.' ?></div>
            </div>

            <!-- Quantity -->
            <div class="mb-3">
                <label class="form-label">
                    <b><?=($lang_code==="ar"?"الكمية":"Quantity")?></b>
                </label>
                <input type="number" name="quantity" class="form-control" required placeholder="<?= ($lang_code==='ar')?'مثال: 500':'Example: 500' ?>">
                <div class="form-text"><?= ($lang_code==='ar')?'بالكيلوغرام (Kg).':'In kilograms (Kg).'; ?></div>
            </div>

            <!-- Price -->
            <div class="mb-3">
                <label class="form-label">
                    <b><?=($lang_code==="ar"?"السعر":"Price")?></b>
                </label>
                <input type="number" step="0.01" name="price" class="form-control" required placeholder="<?= ($lang_code==='ar')?'مثال: 0.45':'Example: 0.45' ?>" min="0.15" max="0.70">
                <div class="form-text"><?= ($lang_code==='ar')?'تلميح: اجعل السعر بين 0.15 و 0.70 د.أ/كغم. يمكنك مقارنة السعر مع توصية الذكاء الاصطناعي في صفحة التوصيات.':'Hint: keep price between 0.15 and 0.70 JOD/kg. Compare it with AI suggestions in Recommendations.' ?></div>
            </div>

            <!-- Harvest Date -->
            <div class="mb-3">
                <label class="form-label">
                    <b><?=($lang_code==="ar"?"تاريخ الحصاد":"Harvest Date")?></b>
                </label>
                <input type="date" name="harvest_date" class="form-control" placeholder="YYYY-MM-DD">
                <div class="form-text"><?= ($lang_code==='ar')?'اختياري.':'Optional.' ?></div>
            </div>

            <!-- Farm Location (ثابت من حساب المزارع) -->
            <div class="mb-1">
                <label class="form-label">
                    <b><?=($lang_code==="ar"?"موقع المزرعة":"Farm Location")?></b>
                </label>

                <!-- حقل للعرض فقط -->
                <input type="text" class="form-control" value="<?= htmlspecialchars($farm_location); ?>" disabled>

                <!-- حقل مخفي يُرسل مع الفورم -->
                <input type="hidden" name="location" value="<?= htmlspecialchars($farm_location); ?>">
            </div>

            <small class="text-muted">
                <?= ($lang_code==="ar"
                    ? "📍 يتم استخدام موقع مزرعتك المسجل في حسابك لجميع المنتجات تلقائيًا."
                    : "📍 Farm location is automatically taken from your account for all products."); ?>
            </small>

            <!-- Buttons -->
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-success">
                    <i class="fa fa-check"></i>
                    <?=($lang_code==="ar"?"إضافة المنتج":"Add Product")?>
                </button>

            </div>

        </form>

    </div>
</div>

<!-- JS -->
<script src="../js/farmer.js"></script>

</body>
</html>
