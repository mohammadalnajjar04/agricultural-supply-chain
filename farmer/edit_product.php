<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../auth/login.php");
    exit;
}

$farmer_id = $_SESSION['user_id'];
$current_page = "my_products"; // 🔹 لتفعيل تاب "My Products"

// التحقق من وجود ID المنتج
if (!isset($_GET['id'])) {
    header("Location: my_products.php");
    exit;
}
$product_id = intval($_GET['id']);

// جلب بيانات المزارع (للحصول على موقع المزرعة الثابت)
$stmtFarmer = $conn->prepare("SELECT name, email, location FROM farmers WHERE farmer_id = ?");
$stmtFarmer->bind_param("i", $farmer_id);
$stmtFarmer->execute();
$farmer = $stmtFarmer->get_result()->fetch_assoc();
$farm_location = $farmer['location']; // 🔹 نفس الموقع لجميع المنتجات

// جلب بيانات المنتج
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ? AND farmer_id = ?");
$stmt->bind_param("ii", $product_id, $farmer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // منتج غير موجود أو لا يخص هذا المزارع
    die(($lang_code === 'ar') ? "المنتج غير موجود أو ليس لديك صلاحية لتعديله." : "Product not found or permission denied.");
}

$product = $result->fetch_assoc();

// عند حفظ التعديلات
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name         = trim($_POST['name']);
    $quantity     = intval($_POST['quantity']);
    $price        = floatval($_POST['price']);
    $harvest_date = $_POST['harvest_date'];

    if (!empty($name) && $quantity > 0 && $price >= 0) {

        // 🔒 نثبت موقع المزرعة من حساب المزارع (لا نأخذه من الفورم)
        $update = $conn->prepare("
            UPDATE products 
            SET name = ?, quantity = ?, price = ?, harvest_date = ?, location = ?
            WHERE product_id = ? AND farmer_id = ?
        ");

        $update->bind_param(
            "sidssii",
            $name,
            $quantity,
            $price,
            $harvest_date,
            $farm_location, // نفس location من جدول farmers
            $product_id,
            $farmer_id
        );

        if ($update->execute()) {
            header("Location: my_products.php?updated=1");
            exit;
        } else {
            $message = ($lang_code === 'ar')
                ? "<div class='alert alert-danger'>حدث خطأ أثناء حفظ التعديلات.</div>"
                : "<div class='alert alert-danger'>Failed to save changes.</div>";
        }

    } else {
        $message = ($lang_code === 'ar')
            ? "<div class='alert alert-warning'>الرجاء تعبئة الحقول الأساسية بشكل صحيح.</div>"
            : "<div class='alert alert-warning'>Please fill the required fields correctly.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="<?= ($lang_code === 'ar') ? 'ar' : 'en' ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="UTF-8">
    <title><?= ($lang_code === 'ar') ? "تعديل المنتج" : "Edit Product"; ?></title>

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

    <!-- TOP BAR -->
    <?php include "../includes/farmer_topbar.php"; ?></br>


    <!-- FORM CARD -->
    <div class="dashboard-box">

    <!-- PAGE TITLE -->
    <h3 class="mt-3 mb-4 page-main-title">
        <i class="fa-solid fa-pen text-success"></i>
        <?= ($lang_code === 'ar') ? "تعديل المنتج" : "Edit Product"; ?>
    </h3>

    

        <?= $message; ?>

        <form method="POST">

            <!-- Product Name -->
            <div class="mb-3">
                <label class="form-label"><b><?= ($lang_code === 'ar') ? "اسم المنتج" : "Product Name"; ?></b></label>
                <input type="text" name="name" class="form-control" placeholder="<?= ($lang_code==='ar')?'مثال: طماطم':'Example: Tomato' ?>"
                       value="<?= htmlspecialchars($product['name']); ?>" required>
                <div class="form-text"><?= ($lang_code==='ar')?'الذكاء الاصطناعي يعتمد على اسم المنتج للتوقع.':'AI uses the product name for forecasting.' ?></div>
            </div>

            <!-- Quantity -->
            <div class="mb-3">
                <label class="form-label"><b><?= ($lang_code === 'ar') ? "الكمية" : "Quantity"; ?></b></label>
                <input type="number" name="quantity" class="form-control" placeholder="<?= ($lang_code==='ar')?'مثال: 500':'Example: 500' ?>"
                       value="<?= (int)$product['quantity']; ?>" min="1" required>
            </div>

            <!-- Price -->
            <div class="mb-3">
                <label class="form-label"><b><?= ($lang_code === 'ar') ? "السعر" : "Price"; ?></b></label>
                <input type="number" step="0.01" name="price" class="form-control" placeholder="<?= ($lang_code==='ar')?'مثال: 0.45':'Example: 0.45' ?>"
                       value="<?= htmlspecialchars($product['price']); ?>" min="0.15" max="0.70" required>
                <div class="form-text"><?= ($lang_code==='ar')?'تلميح: بين 0.15 و 0.70 د.أ/كغم.':'Hint: between 0.15 and 0.70 JOD/kg.' ?></div>
            </div>

            <!-- Harvest Date -->
            <div class="mb-3">
                <label class="form-label"><b><?= ($lang_code === 'ar') ? "تاريخ الحصاد" : "Harvest Date"; ?></b></label>
                <input type="date" name="harvest_date" class="form-control"
                       value="<?= htmlspecialchars($product['harvest_date']); ?>">
            </div>

            <!-- Farm Location (Read-only from farmer account) -->
            <div class="mb-3">
                <label class="form-label"><b><?= ($lang_code === 'ar') ? "موقع المزرعة" : "Farm Location"; ?></b></label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($farm_location); ?>" readonly>

                <small class="text-muted">
                    <?= ($lang_code === 'ar')
                        ? "يتم تحديد موقع المزرعة تلقائيًا من بيانات حسابك ولا يمكن تعديله من هنا."
                        : "The farm location is automatically taken from your account and cannot be edited here."; ?>
                </small>
            </div>

            <!-- Buttons -->
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-save"></i>
                    <?= ($lang_code === 'ar') ? "حفظ التعديلات" : "Save Changes"; ?>
                </button>
            </div>

        </form>

    </div>

</div>

<!-- JS -->
<script src="../js/farmer.js"></script>

</body>
</html>
