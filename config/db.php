<?php
$host = "localhost";
$user = "root";
$pass = "Mohammad@281204";  // ضع كلمة مرور MySQL الخاصة بك
$dbname = "agri_supply_chain";

// إنشاء الاتصال
$conn = new mysqli($host, $user, $pass, $dbname);

// فحص الاتصال
if ($conn->connect_error) {
    die("❌ Database Connection Failed: " . $conn->connect_error);
}

// دعم كامل للغة العربية والإنجليزية
$conn->set_charset("utf8mb4");
?>
