<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

// حماية الصفحة — فقط للمزارع
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "farmer") {
    header("Location: ../auth/login.php");
    exit;
}

// يجب أن يحتوي الرابط على ID المنتج
if (!isset($_GET['id'])) {
    header("Location: my_products.php");
    exit;
}

$product_id = intval($_GET['id']);
$farmer_id = $_SESSION['user_id'];

// حذف المنتج مع التحقق أنه يخص نفس المزارع
$stmt = $conn->prepare("DELETE FROM products WHERE product_id = ? AND farmer_id = ?");
$stmt->bind_param("ii", $product_id, $farmer_id);

if ($stmt->execute()) {
    // نجاح الحذف
    $_SESSION['success_msg'] = ($lang_code === 'ar') 
        ? "تم حذف المنتج بنجاح"
        : "Product deleted successfully";
} else {
    // فشل الحذف
    $_SESSION['error_msg'] = ($lang_code === 'ar') 
        ? "فشل حذف المنتج"
        : "Failed to delete product";
}

// إعادة التوجيه
header("Location: my_products.php");
exit;
?>
