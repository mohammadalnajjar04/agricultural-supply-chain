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
$current_page = "transport_list"; // ⭐ لتمييز التاب الصحيح

// جلب طلبات النقل
$stmt = $conn->prepare("
    SELECT 
        tr.request_id,
        tr.quantity,
        tr.status,
        tr.request_date,
        p.name AS product_name,
        s.name AS store_name,
        t.name AS transporter_name
    FROM transport_requests tr
    JOIN products p      ON tr.product_id = p.product_id
    JOIN stores s        ON tr.store_id   = s.store_id
    LEFT JOIN transporters t ON tr.transporter_id = t.transporter_id
    WHERE tr.farmer_id = ?
    ORDER BY tr.request_id DESC
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$requests = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="<?=($lang_code==='ar'?'ar':'en')?>">
<head>
    <meta charset="UTF-8">
    <title><?=($lang_code=='ar'?'طلبات النقل الخاصة بي':'My Transport Requests')?></title>

    <!-- CSS + Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/brand.css?v=2">
<link rel="stylesheet" href="../css/farmer.css?v=250">

    <?php if($lang_code=='ar'): ?>
        <link rel="stylesheet" href="../css/style_ar.css?v=250">
    <?php endif; ?>
</head>

<body data-role="farmer">

<!-- ⭐ SIDEBAR -->
<?php include "../includes/farmer_sidebar.php"; ?>

<!-- ⭐ MAIN CONTENT -->
<div class="main-content">

    <!-- ⭐ Top Bar -->
    <?php include "../includes/farmer_topbar.php"; ?></br>

    <!-- ⭐ صندوق المحتوى -->
    <div class="dashboard-box">

    <!-- ⭐ Page Title -->
    <h3 class="page-main-title mt-3 mb-3">
        <i class="fa-solid fa-list-check text-success"></i>
        <?=($lang_code=='ar'?'طلبات النقل الخاصة بي':'My Transport Requests')?>
    </h3>

    <!-- ⭐ زر إنشاء طلب جديد -->
    <div class="mb-3">
        <a href="transport_request.php" class="btn btn-success">
            <i class="fa-solid fa-plus"></i>
            <?=($lang_code=='ar'?'طلب نقل جديد':'Create New Request')?>
        </a>
    </div>

    

        <p class="text-muted">
            <?=($lang_code=='ar'
                ? 'هنا يمكنك متابعة جميع طلبات النقل وحالة كل طلب.'
                : 'Here you can track all your transport requests and their current status.'
            )?>
        </p>

        <div class="row">

            <?php if($requests->num_rows > 0): ?>
                <?php while($r = $requests->fetch_assoc()): ?>

                    <?php
                    // الحالة (تصميم الشارة)
                    switch ($r['status']) {
                        case 'pending':
                            $badgeClass = "bg-warning text-dark";
                            $statusText = ($lang_code=='ar'?'قيد الانتظار':'Pending');
                            break;
                        case 'accepted':
                            $badgeClass = "bg-primary";
                            $statusText = ($lang_code=='ar'?'تم القبول':'Accepted');
                            break;
                        case 'completed':
                            $badgeClass = "bg-success";
                            $statusText = ($lang_code=='ar'?'مكتمل':'Completed');
                            break;
                        default:
                            $badgeClass = "bg-secondary";
                            $statusText = $r['status'];
                    }

                    // الناقل
                    $transporterName = $r['transporter_name'] 
                        ? $r['transporter_name']
                        : ($lang_code=='ar'?'لم يتم تعيين ناقل بعد':'No transporter assigned yet');
                    ?>

                    <div class="col-md-4 mb-4">
                        <div class="farmer-card-item shadow-sm rounded p-3">

                            <div class="d-flex justify-content-between mb-2">
                                <h5 class="product-title mb-0">
                                    <i class="fa-solid fa-truck text-success"></i>
                                    <?= htmlspecialchars($r['product_name']) ?>
                                </h5>

                                <span class="badge <?=$badgeClass?>"><?=$statusText?></span>
                            </div>

                            <p><strong><?=($lang_code=='ar'?'المتجر:':'Store:')?></strong> <?=$r['store_name']?></p>
                            <p><strong><?=($lang_code=='ar'?'الناقل:':'Transporter:')?></strong> <?=$transporterName?></p>
                            <p><strong><?=($lang_code=='ar'?'الكمية:':'Quantity:')?></strong> <?=$r['quantity']?></p>
                            <p><strong><?=($lang_code=='ar'?'تاريخ الطلب:':'Request Date:')?></strong> <?=$r['request_date']?></p>

                            <div class="divider my-2"></div>

                            <small class="text-muted">
                                <?=($lang_code=='ar'?'رقم الطلب: ':'Request ID: ')?>#<?=$r['request_id']?>
                            </small>

                        </div>
                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        <?=($lang_code=='ar'?'لا توجد طلبات نقل.':'No transport requests found.')?>
                    </div>
                </div>

            <?php endif; ?>

        </div>

    </div>
</div>

<script src="../js/farmer.js"></script>
</body>
</html>
