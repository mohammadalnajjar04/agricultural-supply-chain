<?php
session_start();
include "../includes/language.php";

$title = t('auth.choose_type_title','Create account');
$sub   = t('auth.choose_type_sub','Choose your account type to continue. New accounts are reviewed for trust.');

// Shared layout vars
$page_title = $title;
$data_role = 'generic';
$nav_right_html =
  '<a class="btn btn-sm btn-soft" href="login.php"><i class="fa-solid fa-right-to-bracket"></i> ' . htmlspecialchars(t('nav.sign_in','Sign in')) . '</a>';

// Hero (left)
$hero_pill_html = '<span class="pill mb-3"><i class="fa-solid fa-shield-halved"></i> ' . htmlspecialchars(t('role.trusted_onboarding','Trusted onboarding')) . '</span>';
$hero_title = $title;
$hero_sub   = $sub;

ob_start();
?>
<div class="row g-3">
  <div class="col-md-4">
    <div class="cardx h-100">
      <div class="cardx-body">
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="fa-solid fa-tractor fs-4" style="color: var(--brand)"></i>
          <div class="fw-bold"><?= htmlspecialchars(t('role.farmer','Farmer')) ?></div>
        </div>
        <div class="text-muted mb-3"><?= $is_ar ? 'أضف منتجاتك وتواصل مع المتاجر والناقلين.' : 'List products and connect with stores and transporters.' ?></div>
        <a class="btn btn-brand w-100" href="register_farmer.php">
          <i class="fa-solid fa-user-plus"></i> <?= $is_ar ? 'تسجيل مزارع' : 'Register Farmer' ?>
        </a>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="cardx h-100">
      <div class="cardx-body">
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="fa-solid fa-truck fs-4" style="color:#1e63d8"></i>
          <div class="fw-bold"><?= htmlspecialchars(t('role.transporter','Transporter')) ?></div>
        </div>
        <div class="text-muted mb-3"><?= $is_ar ? 'اقبل طلبات النقل وحدّث حالة التوصيل.' : 'Accept transport jobs and update delivery status.' ?></div>
        <a class="btn btn-brand w-100" style="--brand:#1e63d8; --brand-2:#0e46a8" href="register_transporter.php">
          <i class="fa-solid fa-user-plus"></i> <?= $is_ar ? 'تسجيل ناقل' : 'Register Transporter' ?>
        </a>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="cardx h-100">
      <div class="cardx-body">
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="fa-solid fa-store fs-4" style="color:#c28a00"></i>
          <div class="fw-bold"><?= htmlspecialchars(t('role.store','Store')) ?></div>
        </div>
        <div class="text-muted mb-3"><?= $is_ar ? 'اطلب من المزارعين وقيّم بعد التسليم.' : 'Order from farmers and rate after delivery.' ?></div>
        <a class="btn btn-brand w-100" style="--brand:#f4b400; --brand-2:#c28a00; color:#111" href="register_store.php">
          <i class="fa-solid fa-user-plus"></i> <?= $is_ar ? 'تسجيل متجر' : 'Register Store' ?>
        </a>
      </div>
    </div>
  </div>
</div>
<?php
$hero_extra_html = ob_get_clean();

// Right side content
ob_start();
?>
<h5 class="fw-bold mb-2"><?= htmlspecialchars(t('auth.after_signup_title','What happens after you sign up?')) ?></h5>
<ol class="text-muted mb-0">
  <li class="mb-2"><?= htmlspecialchars(t('auth.after_signup_1','Fill your details and upload a simple proof document.')) ?></li>
  <li class="mb-2"><?= htmlspecialchars(t('auth.after_signup_2','Your account becomes (Pending Review).')) ?></li>
  <li><?= htmlspecialchars(t('auth.after_signup_3','Once approved, you can sign in and use the system.')) ?></li>
</ol>

<hr>
<div class="small-link">
  <?= htmlspecialchars(t('auth.have_account','Already have an account?')) ?>
  <a href="login.php"><?= htmlspecialchars(t('nav.sign_in','Sign in')) ?></a>
</div>
<?php
$right_html = ob_get_clean();

require __DIR__ . '/_auth_layout.php';
exit;
?>
