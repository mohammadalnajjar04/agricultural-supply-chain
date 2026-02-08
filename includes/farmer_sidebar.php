<?php
// Prevent warnings if a page forgets to set the variable
if (!isset($current_page)) {
    $current_page = "";
}
?>

<!-- ========== SIDEBAR ========== -->
<div class="sidebar" id="sidebar">

    <div class="logo">
        <i class="fa-solid fa-seedling"></i>
        <?= htmlspecialchars(t('role.farmer','Farmer')) ?>
    </div>

    <a href="../farmer/dashboard.php" class="<?= ($current_page === 'dashboard') ? 'active' : '' ?>">
        <i class="fa-solid fa-gauge"></i>
        <?= htmlspecialchars(t('menu.dashboard','Dashboard')) ?>
    </a>

    <a href="../farmer/add_product.php" class="<?= ($current_page === 'add_product') ? 'active' : '' ?>">
        <i class="fa-solid fa-plus"></i>
        <?= htmlspecialchars(t('menu.add_product','Add Product')) ?>
    </a>

    <a href="../farmer/my_products.php" class="<?= ($current_page === 'my_products') ? 'active' : '' ?>">
        <i class="fa-solid fa-boxes-stacked"></i>
        <?= htmlspecialchars(t('menu.my_products','My Products')) ?>
    </a>

    <a href="../farmer/store_orders.php" class="<?= ($current_page === 'store_orders') ? 'active' : '' ?>">
        <i class="fa-solid fa-store"></i>
        <?= htmlspecialchars(t('menu.store_orders','Store Orders')) ?>
    </a>

    <a href="../farmer/transport_requests.php" class="<?= ($current_page === 'transport_list') ? 'active' : '' ?>">
        <i class="fa-solid fa-list-check"></i>
        <?= htmlspecialchars(t('menu.transport_requests','Transport Requests')) ?>
    </a>

    <a href="../farmer/recommendations.php" class="<?= ($current_page === 'ai') ? 'active' : '' ?>">
        <i class="fa-solid fa-robot"></i>
        <?= htmlspecialchars(t('menu.ai_recommendations','AI Recommendations')) ?>
    </a>

    <hr>

    <a href="../auth/logout.php">
        <i class="fa-solid fa-right-from-bracket"></i>
        <?= htmlspecialchars(t('menu.logout','Logout')) ?>
    </a>
</div>
