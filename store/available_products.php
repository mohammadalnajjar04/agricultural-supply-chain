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
$current_page = "products"; // لتفعيل التبويب الصحيح في الـ Sidebar

// جلب المنتجات من المزارعين
$stmt = $conn->prepare("
    SELECT 
        p.product_id,
        p.name AS product_name,
        p.quantity,
        p.price,
        p.harvest_date,
        f.name AS farmer_name,
        f.location,
        COALESCE(AVG(o.rating), 0) AS avg_rating,
        SUM(CASE WHEN o.rating IS NULL THEN 0 ELSE 1 END) AS rating_count
    FROM products p
    JOIN farmers f ON p.farmer_id = f.farmer_id
    LEFT JOIN orders o ON o.product_id = p.product_id AND o.rating IS NOT NULL
    GROUP BY p.product_id, p.name, p.quantity, p.price, p.harvest_date, f.name, f.location
    ORDER BY p.product_id DESC
");
$stmt->execute();
$products = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= ($lang_code === 'ar') ? "المنتجات المتاحة" : "Available Products"; ?></title>

    <!-- Bootstrap + Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS -->
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

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- TOP BAR -->
    <?php include "../includes/store_topbar.php"; ?>
    <br>


    <div class="dashboard-box">

    <!-- PAGE TITLE -->
    <h3 class="mb-4">
        <i class="fa-solid fa-store"></i>
        <?= ($lang_code === 'ar') ? "المنتجات المتاحة" : "Available Products"; ?>
    </h3>

    <!-- PRODUCT CARDS -->
    <div class="row g-4">

        <?php if ($products->num_rows > 0): ?>

            <?php while($row = $products->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="product-card">

                        <!-- PRODUCT TITLE -->
                        <h5 class="product-title">
                            <i class="fa-solid fa-leaf"></i>
                            <?= htmlspecialchars($row['product_name']); ?>
                        </h5>

                        <!-- FARMER -->
                        <p>
                            <strong><?= ($lang_code === 'ar') ? "المزارع:" : "Farmer:"; ?></strong>
                            <?= htmlspecialchars($row['farmer_name']); ?>
                        </p>

                        
                        <!-- RATING -->
                        <p class="mb-1">
                            <strong><?= ($lang_code === 'ar') ? "التقييم:" : "Rating:"; ?></strong>
                            <?php
                              $avg = (float)$row['avg_rating'];
                              $cnt = (int)$row['rating_count'];
                              $stars = (int)round($avg);
                              if ($cnt <= 0) {
                                  echo ($lang_code === 'ar') ? "لا يوجد تقييم" : "No ratings";
                              } else {
                                  for ($i=1; $i<=5; $i++) {
                                      echo '<i class="fa-solid fa-star '.($i <= $stars ? 'text-warning' : 'text-muted').'"></i>';
                                  }
                                  echo ' <span class="text-muted small">(' . number_format($avg, 1) . '/5 · ' . $cnt . ')</span>';
                              }
                            ?>
                        </p>
<!-- QUANTITY -->
                        <p>
                            <strong><?= ($lang_code === 'ar') ? "الكمية:" : "Quantity:"; ?></strong>
                            <?= $row['quantity']; ?>
                        </p>

                        <!-- PRICE -->
                        <p>
                            <strong><?= ($lang_code === 'ar') ? "السعر:" : "Price:"; ?></strong>
                            <?= number_format((float)$row['price'], 2) ?> JOD/kg
                        </p>

                        <!-- HARVEST -->
                        <p>
                            <strong><?= ($lang_code === 'ar') ? "تاريخ الحصاد:" : "Harvest Date:"; ?></strong>
                            <?= $row['harvest_date']; ?>
                        </p>

                        <!-- LOCATION -->
                        <p>
                            <strong><?= ($lang_code === 'ar') ? "موقع المزرعة:" : "Farm Location:"; ?></strong>
                            <?= $row['location']; ?>
                        </p>

                        <!-- BUTTON -->
                        <?php if ((int)$row['quantity'] <= 0): ?>
                            <button class="btn btn-secondary mt-2 w-100" disabled>
                                <i class="fa-solid fa-circle-xmark"></i>
                                <?= ($lang_code === 'ar') ? "غير متوفر" : "Out of stock"; ?>
                            </button>
                        <?php else: ?>
                            <a href="place_order.php?product_id=<?= $row['product_id']; ?>"
                               class="btn btn-success mt-2 w-100">
                                <i class="fa fa-shopping-cart"></i>
                                <?= ($lang_code === 'ar') ? "طلب" : "Order"; ?>
                            </a>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endwhile; ?>

        <?php else: ?>

            <div class="col-12">
                <div class="alert alert-info text-center">
                    <?= ($lang_code === 'ar')
                        ? "لا توجد منتجات متاحة حالياً."
                        : "No products available at the moment."; ?>
                </div>
            </div>

        <?php endif; ?>

    </div>
    </div>

</div>

</body>
</html>
