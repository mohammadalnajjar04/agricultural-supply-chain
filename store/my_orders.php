<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "store") {
    header("Location: ../auth/login.php");
    exit;
}

$store_id     = $_SESSION['user_id'];
$current_page = "orders"; // مهم عشان التبويب يتفعل في الـ sidebar/topbar

// جلب الطلبات
$stmt = $conn->prepare("
    SELECT 
        o.order_id,
        o.order_date,
        o.total_price,
        o.quantity,
        o.status,
        o.delivery_fee,
        o.rating,
        p.name AS product_name,
        f.name AS farmer_name,
        f.location
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN farmers f ON p.farmer_id = f.farmer_id
    WHERE o.store_id = ?
    ORDER BY o.order_id DESC
");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$orders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= ($lang_code === 'ar') ? "طلباتي" : "My Orders"; ?></title>

    <!-- Bootstrap + Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/brand.css?v=2">
<link rel="stylesheet" href="../css/farmer.css?v=200">
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
        <i class="fa-solid fa-list-check"></i>
        <?= ($lang_code === 'ar') ? "طلباتي" : "My Orders"; ?>
    </h3>

    <!-- ===== ORDER CARDS ===== -->
    <div class="row g-4">

        <?php if ($orders->num_rows > 0): ?>
            <?php while($row = $orders->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="product-card">

                        <h5 class="product-title">
                            <i class="fa-solid fa-bag-shopping"></i>
                            <?= htmlspecialchars($row['product_name']); ?>
                        </h5>

                        <p><strong><?= ($lang_code === 'ar') ? "رقم الطلب:" : "Order ID:"; ?></strong>
                            <?= $row['order_id']; ?></p>

                        <p><strong><?= ($lang_code === 'ar') ? "المزارع:" : "Farmer:"; ?></strong>
                            <?= htmlspecialchars($row['farmer_name']); ?></p>

                        <p><strong><?= ($lang_code === 'ar') ? "موقع المزرعة:" : "Farm Location:"; ?></strong>
                            <?= htmlspecialchars($row['location']); ?></p>

                        <p><strong><?= ($lang_code === 'ar') ? "الكمية:" : "Quantity:"; ?></strong>
                            <?= (int)$row['quantity']; ?></p>

                        <p><strong><?= ($lang_code === 'ar') ? "السعر الكلي:" : "Total Price:"; ?></strong>
                            <?= number_format((float)$row['total_price'], 2); ?> JOD</p>

                        <p><strong><?= ($lang_code === 'ar') ? "أجرة التوصيل (تدفعها أنت):" : "Delivery Fee (you pay):"; ?></strong>
                            <?= number_format((float)($row['delivery_fee'] ?? 0), 2); ?> JOD</p>

                        <p><strong><?= ($lang_code === 'ar') ? "الإجمالي مع التوصيل:" : "Grand Total:"; ?></strong>
                            <?= number_format(((float)$row['total_price'] + (float)($row['delivery_fee'] ?? 0)), 2); ?> JOD</p>

                        <p><strong><?= ($lang_code === 'ar') ? "الحالة:" : "Status:"; ?></strong>
                            <?php
                            $sttxt = strtolower(trim((string)$row['status']));
                            if ($lang_code === 'ar') {
                                $map = [
                                    'pending' => 'قيد الانتظار',
                                    'approved' => 'تمت الموافقة',
                                    'rejected' => 'مرفوض',
                                    'delivered' => 'تم التسليم',
                                    'completed' => 'مكتمل'
                                ];
                                echo $map[$sttxt] ?? $row['status'];
                            } else {
                                echo ucfirst($row['status']);
                            }
                        ?></p>

                        <p><strong><?= ($lang_code === 'ar') ? "تاريخ الطلب:" : "Order Date:"; ?></strong>
                            <?= $row['order_date']; ?></p>

                        <p><strong><?= ($lang_code === 'ar') ? "التقييم:" : "Rating:"; ?></strong>
                            <?php if (!is_null($row['rating'])): ?>
                                <span class="badge bg-success"><?= (int)$row['rating']; ?>/5</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">-</span>
                            <?php endif; ?>
                        </p>

                        <?php
                            $st = strtolower(trim($row['status']));
                            $can_rate = (is_null($row['rating']) && in_array($st, ['delivered','completed'], true));
                        ?>

                        <?php if ($can_rate): ?>
                            <a class="btn btn-outline-primary w-100 mt-2" href="rate_order.php?order_id=<?= (int)$row['order_id']; ?>">
                                <i class="fa-solid fa-star"></i>
                                <?= ($lang_code === 'ar') ? "قيّم الطلب" : "Rate Order"; ?>
                            </a>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <?= ($lang_code === 'ar') ? "لا يوجد أي طلبات حتى الآن." : "No orders yet."; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
    </div>

</div>

</body>
</html>
