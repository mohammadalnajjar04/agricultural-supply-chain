<?php
// Prevent warnings if a page forgets to set the variable
if (!isset($current_page)) {
    $current_page = "";
}
?>

<!-- ========== TRANSPORTER SIDEBAR ========== -->
<div class="sidebar" id="sidebar">

    <div class="logo">
        <i class="fa-solid fa-truck"></i>
        <?= htmlspecialchars(t('role.transporter','Transporter')) ?>
    </div>

    <a href="../transporter/dashboard.php" class="<?= ($current_page === 'dashboard') ? 'active' : '' ?>">
        <i class="fa-solid fa-gauge"></i>
        <?= htmlspecialchars(t('menu.dashboard','Dashboard')) ?>
    </a>

    <a href="../transporter/available_requests.php" class="<?= ($current_page === 'available') ? 'active' : '' ?>">
        <i class="fa-solid fa-list-ul"></i>
        <?= htmlspecialchars(t('menu.available_requests','Available Requests')) ?>
    </a>

    <a href="../transporter/my_requests.php" class="<?= ($current_page === 'my_requests') ? 'active' : '' ?>">
        <i class="fa-solid fa-truck-ramp-box"></i>
        <?= htmlspecialchars(t('menu.my_requests','My Requests')) ?>
    </a>

    <a href="../transporter/recommendations.php" class="<?= ($current_page === 'ai') ? 'active' : '' ?>">
        <i class="fa-solid fa-robot"></i>
        <?= htmlspecialchars(t('menu.ai_recommendations','AI Recommendations')) ?>
    </a>

    <hr>

    <a href="../auth/logout.php">
        <i class="fa-solid fa-right-from-bracket"></i>
        <?= htmlspecialchars(t('menu.logout','Logout')) ?>
    </a>
</div>
