<?php
session_start();
include "../includes/language.php";
include "../config/db.php";
// NOTE: Delivery pricing is calculated when the farmer approves the order.

// حماية المتجر
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "store") {
    header("Location: ../auth/login.php");
    exit;
}

$store_id = $_SESSION['user_id'];

if (!isset($_GET['product_id'])) {
    header("Location: available_products.php");
    exit;
}

$product_id = intval($_GET['product_id']);

// جلب بيانات المنتج
$stmt = $conn->prepare("
    SELECT p.product_id, p.name, p.price, p.quantity, f.name AS farmer_name, f.location
    FROM products p
    JOIN farmers f ON p.farmer_id = f.farmer_id
    WHERE p.product_id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result  = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

// عند تأكيد الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    if ($quantity <= 0) {
        $error_msg = ($lang_code === 'ar') ? "يرجى إدخال كمية صحيحة" : "Please enter a valid quantity";
    } elseif ($quantity > (int)$product['quantity']) {
        $error_msg = ($lang_code === 'ar') ? "الكمية المطلوبة غير متوفرة" : "Requested quantity is not available";
    } else {

        // total price = unit price * quantity
        $total_price = (float)$product['price'] * $quantity;

        try {
            $conn->begin_transaction();

            // Create order ONLY. Stock deduction + transport request happen after farmer approval.
            $stmtOrder = $conn->prepare("
                INSERT INTO orders (store_id, product_id, quantity, order_date, total_price, status)
                VALUES (?, ?, ?, CURDATE(), ?, 'pending')
            ");
            $stmtOrder->bind_param("iiid", $store_id, $product_id, $quantity, $total_price);

            if (!$stmtOrder->execute()) {
                $conn->rollback();
                $error_msg = ($lang_code === 'ar') ? "حدث خطأ أثناء إنشاء الطلب" : "Error placing order";
            } else {
                $conn->commit();
                header("Location: my_orders.php?msg=placed");
                exit;
            }
        } catch (Throwable $e) {
            // In case begin_transaction or other DB errors
            if ($conn->errno) {
                $conn->rollback();
            }
            $error_msg = ($lang_code === 'ar') ? "خطأ غير متوقع" : "Unexpected error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= ($lang_code === 'ar') ? "تأكيد الطلب" : "Place Order"; ?></title>

    <!-- Bootstrap + Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/brand.css?v=2">
<link rel="stylesheet" href="../css/brand.css?v=2">
<link rel="stylesheet" href="../css/brand.css?v=2">
<link rel="stylesheet" href="../css/farmer.css">
    <?php if ($lang_code === 'ar'): ?>
        <link rel="stylesheet" href="../css/style_ar.css">
    <?php endif; ?>
</head>

<body data-role="store">

<!-- SIDEBAR -->
<?php include "../includes/store_sidebar.php"; ?>

<div class="main-content">

    <!-- TOP BAR -->
    <?php include "../includes/store_topbar.php"; ?>
    <br>

    <div class="dashboard-box">

    <h3 class="mb-4">
        <i class="fa-solid fa-cart-plus"></i>
        <?= ($lang_code === 'ar') ? "تأكيد الطلب" : "Confirm Order"; ?>
    </h3>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="product-card">

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger"><?= $error_msg; ?></div>
                <?php endif; ?>

                <h5 class="product-title">
                    <i class="fa-solid fa-leaf"></i>
                    <?= htmlspecialchars($product['name']); ?>
                </h5>

                <p><strong><?= ($lang_code === 'ar') ? "المزارع:" : "Farmer:"; ?></strong>
                    <?= htmlspecialchars($product['farmer_name']); ?></p>

                <p><strong><?= ($lang_code === 'ar') ? "موقع المزرعة:" : "Farm Location:"; ?></strong>
                    <?= htmlspecialchars($product['location']); ?></p>

                <p><strong><?= ($lang_code === 'ar') ? "السعر:" : "Price:"; ?></strong>
                    <?= $product['price']; ?> JOD</p>

                <?php if ((int)$product['quantity'] <= 0): ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <?= ($lang_code === 'ar') ? "هذا المنتج غير متوفر حاليًا" : "This product is currently out of stock"; ?>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <a href="available_products.php" class="btn btn-outline-secondary">
                            <?= ($lang_code === 'ar') ? "رجوع" : "Back"; ?>
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" class="mt-3">
                        <div class="mb-3">
                            <label class="form-label">
                                <?= ($lang_code === 'ar') ? "الكمية المطلوبة" : "Quantity"; ?>
                            </label>
                            <input
                                type="number"
                                name="quantity"
                                class="form-control"
                                min="1"
                                max="<?= (int)$product['quantity']; ?>"
                                value="1"
                                required
                            />
                            <small class="text-muted">
                                <?= ($lang_code === 'ar') ? "المتوفر" : "Available"; ?>: <?= (int)$product['quantity']; ?>
                            </small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fa-solid fa-check"></i>
                                <?= ($lang_code === 'ar') ? "تأكيد الطلب" : "Confirm Order"; ?>
                            </button>

                            <a href="available_products.php" class="btn btn-outline-secondary">
                                <?= ($lang_code === 'ar') ? "رجوع" : "Back"; ?>
                            </a>
                        </div>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
    </div>

</div>

</body>
</html>
