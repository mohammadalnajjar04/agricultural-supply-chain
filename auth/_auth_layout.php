<?php
/**
 * Shared Auth Layout
 * Expected variables:
 * - $page_title (string)
 * - $data_role (string)  farmer|transporter|store|admin|generic
 * - $nav_right_html (string) optional
 * - $hero_pill_html (string)
 * - $hero_title (string)
 * - $hero_sub (string)
 * - $hero_extra_html (string) optional
 * - $right_html (string) (main card)
 */

if (!isset($page_title)) $page_title = t('app.name');
if (!isset($data_role)) $data_role = 'generic';
if (!isset($nav_right_html)) $nav_right_html = '';
if (!isset($hero_pill_html)) $hero_pill_html = '';
if (!isset($hero_title)) $hero_title = '';
if (!isset($hero_sub)) $hero_sub = '';
if (!isset($hero_extra_html)) $hero_extra_html = '';
if (!isset($right_html)) $right_html = '';

?>
<!DOCTYPE html>
<html lang="<?= $is_ar ? 'ar' : 'en' ?>" dir="<?= $is_ar ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/brand.css?v=4">
  <?php if ($is_ar): ?>
    <link rel="stylesheet" href="../css/style_ar.css?v=3">
  <?php endif; ?>
</head>
<body class="stripe-bg stripe-grid" data-role="<?= htmlspecialchars($data_role) ?>">

<nav class="navbar navbar-expand-lg brand-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand brand-badge" href="../index.php">
      <span class="brand-dot"></span>
      <span><?= htmlspecialchars(t('app.name','Agri Supply Chain')) ?></span>
    </a>
    <div class="d-flex align-items-center gap-2">
      <a class="btn btn-sm btn-soft" href="../index.php"><i class="fa-solid fa-house"></i> <?= htmlspecialchars(t('nav.home','Home')) ?></a>
      <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(url_with_lang($is_ar ? 'en' : 'ar')) ?>">
        <i class="fa-solid fa-language"></i> <?= $is_ar ? t('nav.english','English') : t('nav.arabic','العربية') ?>
      </a>
      <?= $nav_right_html ?>
    </div>
  </div>
</nav>

<main class="auth-wrap">
  <div class="auth-grid">
    <section class="hero hero-max reveal">
      <div class="hero-orb" style="top:-120px; left:-140px"></div>
      <div class="hero-orb orb-2" style="top:-160px; right:-180px"></div>
      <div class="hero-inner">
        <?= $hero_pill_html ?>
        <h1 class="hero-title mb-3"><?= htmlspecialchars($hero_title) ?></h1>
        <p class="hero-sub mb-4"><?= htmlspecialchars($hero_sub) ?></p>
        <?= $hero_extra_html ?>
      </div>
    </section>

    <aside class="cardx reveal">
      <div class="cardx-body">
        <?= $right_html ?>
      </div>
    </aside>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/brand.js?v=4"></script>
</body>
</html>
