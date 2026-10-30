<?php
session_start();
include "../includes/language.php";
include "../config/db.php";
require_once "../includes/security.php";
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
    WHERE p.product_id = ? AND p.is_active = 1 AND p.quantity > 0
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result  = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

// Create a pending order. Approval deducts stock, not order submission.
// Product availability is rechecked inside the transaction to avoid stale data.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    if ($quantity === false || $quantity === null) {
        $error_msg = ($lang_code === 'ar') ? 'يرجى إدخال كمية صحيحة' : 'Please enter a valid quantity';
    } else {
        $inTransaction = false;
        try {
            $conn->begin_transaction();
            $inTransaction = true;
            // Lock product so availability and price come from the database,
            // not from the stale page load or client input.
            $lock = $conn->prepare('SELECT name, quantity, price FROM products WHERE product_id = ? AND is_active = 1 FOR UPDATE');
            $lock->bind_param('i', $product_id);
            $lock->execute();
            $current = $lock->get_result()->fetch_assoc();
            if (!$current || (int)$current['quantity'] < $quantity) {
                $conn->rollback();
                $inTransaction = false;
                $error_msg = ($lang_code === 'ar') ? 'الكمية المطلوبة غير متوفرة حالياً' : 'Requested quantity is not currently available';
            } else {
                $total_price = round((float)$current['price'] * $quantity, 2);
                $stmtOrder = $conn->prepare("INSERT INTO orders (store_id, product_id, quantity, order_date, total_price, status, product_name_at_order, unit_price_at_order) VALUES (?, ?, ?, CURDATE(), ?, 'pending', ?, ?)");
                $unitPrice = (string)$current['price'];
                $productName = (string)$current['name'];
                $stmtOrder->bind_param('iiidsd', $store_id, $product_id, $quantity, $total_price, $productName, $unitPrice);
                $stmtOrder->execute();
                $conn->commit();
                $inTransaction = false;
                header('Location: my_orders.php?msg=placed');
                exit;
            }
        } catch (Throwable $e) {
            if ($inTransaction) { $conn->rollback(); }
            error_log('Order placement failed: ' . $e->getMessage());
            $error_msg = ($lang_code === 'ar') ? 'حدث خطأ أثناء إنشاء الطلب' : 'Could not create order';
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
                        <?= csrf_field(); ?>
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
