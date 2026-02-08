<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// لغة الموقع
$lang_code = $_SESSION['lang'] ?? 'en';

// اسم الصفحة الحالية
$current_page = $current_page ?? "dashboard";

// تعريف عناوين المتجر
$titles = [
    "dashboard"       => ["لوحة تحكم المتجر", "Store Dashboard", "fa-gauge"],
    "products"        => ["المنتجات المتاحة", "Available Products", "fa-store"],
    "orders"          => ["طلباتي", "My Orders", "fa-list-check"],
    "ai"              => ["توصيات الذكاء الاصطناعي", "AI Recommendations", "fa-robot"],
];

// إذا الصفحة غير معرفة → استخدم dashboard
$pageKey = isset($titles[$current_page]) ? $current_page : 'dashboard';
$title = ($lang_code === "ar") ? $titles[$pageKey][0] : $titles[$pageKey][1];
$icon  = $titles[$pageKey][2];
?>

<div class="top-bar">
    <div class="top-left">
        <span class="menu-btn d-lg-none"><i class="fa-solid fa-bars"></i></span>

        <span class="brand">
            <i class="fa-solid fa-store"></i>
            <?= ($lang_code === "ar") ? "المتجر" : "Store"; ?>
        </span>
    </div>

    <div class="top-center">
        <h2 class="page-title">
            <i class="fa-solid <?= $icon ?>"></i> <?= $title ?>
        </h2>
    </div>

    <div class="top-right">
        <div class="lang-switch">
            <a href="<?= htmlspecialchars(url_with_lang('en')) ?>">English</a> |
            <a href="<?= htmlspecialchars(url_with_lang('ar')) ?>">العربية</a>
        </div>
    </div>
</div>
