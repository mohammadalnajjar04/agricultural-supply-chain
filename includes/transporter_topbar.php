<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lang_code = $_SESSION['lang'] ?? 'en';
$current_page = $current_page ?? "dashboard";

// تعريف عناوين وصفحات الناقل + الأيقونات
$titles = [
    "dashboard"    => ["لوحة تحكم الناقل", "Transporter Dashboard", "fa-gauge"],
    "available"    => ["الطلبات المتاحة", "Available Requests", "fa-list-ul"],
    "my_requests"  => ["طلباتي", "My Requests", "fa-truck-ramp-box"],
    "vehicle"      => ["معلومات المركبة", "Vehicle Info", "fa-car-side"],
    "ai"           => ["توصيات الذكاء الاصطناعي", "AI Recommendations", "fa-robot"],
];

// فحص الصفحة — إذا غير موجودة: dashboard
if (!isset($titles[$current_page])) {
    $current_page = "dashboard";
}

// استخراج قيم الصفحة الحالية
$page_title_ar   = $titles[$current_page][0];
$page_title_en   = $titles[$current_page][1];
$page_icon       = $titles[$current_page][2];

// اختيار اللغة
$display_title = ($lang_code === 'ar') ? $page_title_ar : $page_title_en;
?>
 
<div class="top-bar">

    <div class="top-left">

        <!-- زر القائمة للموبايل -->
        <span class="menu-btn d-lg-none">
            <i class="fa-solid fa-bars"></i>
        </span>

        <!-- شعار الصفحة حسب الصفحة الحالية -->
        <span class="brand">
            <i class="fa-solid <?= $page_icon ?>"></i>
            <?= ($lang_code === 'ar') ? "الناقل" : "Transporter"; ?>
        </span>
    </div>

    <div class="top-center">
        <h2 class="page-title">
            <i class="fa-solid <?= $page_icon ?>"></i>
            <?= $display_title ?>
        </h2>
    </div>

    <div class="top-right">
        <div class="lang-switch">
            <a href="<?= htmlspecialchars(url_with_lang('en')) ?>">English</a> |
            <a href="<?= htmlspecialchars(url_with_lang('ar')) ?>">العربية</a>
        </div>
    </div>

</div>
