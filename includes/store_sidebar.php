<?php
// Prevent warnings if a page forgets to set the variable
if (!isset($current_page)) {
    $current_page = "";
}
?>

<!-- ========== SIDEBAR ========== -->
<div class="sidebar" id="sidebar">

    <div class="logo">
        <i class="fa-solid fa-store"></i>
        <?= htmlspecialchars(t('role.store','Store')) ?>
    </div>

    <a href="../store/dashboard.php" class="<?= ($current_page === 'dashboard') ? 'active' : '' ?>">
        <i class="fa-solid fa-gauge"></i>
        <?= htmlspecialchars(t('menu.dashboard','Dashboard')) ?>
    </a>

    <a href="../store/available_products.php" class="<?= ($current_page === 'products') ? 'active' : '' ?>">
        <i class="fa-solid fa-shopping-basket"></i>
        <?= htmlspecialchars(t('menu.browse_products','Browse Products')) ?>
    </a>

    <a href="../store/my_orders.php" class="<?= ($current_page === 'orders') ? 'active' : '' ?>">
        <i class="fa-solid fa-list-check"></i>
        <?= htmlspecialchars(t('menu.my_orders','My Orders')) ?>
    </a>

    <a href="../store/recommendations.php" class="<?= ($current_page === 'ai') ? 'active' : '' ?>">
        <i class="fa-solid fa-robot"></i>
        <?= htmlspecialchars(t('menu.ai_recommendations','AI Recommendations')) ?>
    </a>

    <hr>

    <a href="../auth/logout.php">
        <i class="fa-solid fa-right-from-bracket"></i>
        <?= htmlspecialchars(t('menu.logout','Logout')) ?>
    </a>
</div>
