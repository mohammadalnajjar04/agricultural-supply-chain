<?php
session_start();

// حذف جميع بيانات الجلسة
session_unset();

// إنهاء الجلسة تمامًا
session_destroy();

// إعادة التوجيه لصفحة تسجيل الدخول
header("Location: login.php");
exit;
?>
