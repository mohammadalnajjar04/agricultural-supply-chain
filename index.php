<?php
session_start();
include "includes/language.php";

$is_ar = ($lang_code === 'ar');
$title = $is_ar ? "منصة إدارة سلسلة التوريد الزراعية في الأردن" : "Agriculture Supply Chain Platform in Jordan";
$subtitle = $is_ar
    ? "منصة تربط المزارعين والناقلين والمتاجر لإدارة المنتجات والطلبات والنقل ضمن تدفق واضح وموثوق." 
    : "A trusted platform connecting Farmers, Transporters, and Stores to manage products, orders, and transport in a clear workflow.";

$cta_login = $is_ar ? "تسجيل الدخول" : "Sign in";
$cta_register = $is_ar ? "إنشاء حساب" : "Sign up";
?>
<!DOCTYPE html>
<html lang="<?= $is_ar ? 'ar' : 'en' ?>" dir="<?= $is_ar ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($title) ?></title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/brand.css?v=3">
  <?php if ($is_ar): ?>
    <link rel="stylesheet" href="css/style_ar.css?v=2">
  <?php endif; ?>
</head>
<body class="stripe-bg stripe-grid">

<nav class="navbar navbar-expand-lg brand-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand brand-badge" href="index.php">
      <span class="brand-dot"></span>
      <span>Agri Supply Chain</span>
    </a>

    <div class="d-flex align-items-center gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="?lang=<?= $is_ar ? 'en' : 'ar' ?>">
        <i class="fa-solid fa-language"></i> <?= $is_ar ? "English" : "العربية" ?>
      </a>
      <a class="btn btn-sm btn-soft" href="auth/login.php">
        <i class="fa-solid fa-right-to-bracket"></i> <?= $cta_login ?>
      </a>
      <a class="btn btn-sm btn-brand" href="auth/register.php">
        <i class="fa-solid fa-user-plus"></i> <?= $cta_register ?>
      </a>
    </div>
  </div>
</nav>

<main class="container py-4 py-md-5">
  <!-- HERO -->
  <section class="hero hero-max reveal">
    <!-- animated orbs (Stripe-ish) -->
    <div class="hero-orb" style="top:-120px; left:-140px"></div>
    <div class="hero-orb orb-2" style="top:-160px; right:-180px"></div>
    <div class="hero-orb orb-3" style="bottom:-180px; right:10%"></div>
    <div class="hero-inner">
      <div class="row align-items-center g-4">
        <div class="col-lg-7">
          <span class="pill mb-3">
            <i class="fa-solid fa-location-dot"></i>
            <?= $is_ar ? "الأردن" : "Jordan" ?>
            <span class="opacity-50">•</span>
            <?= $is_ar ? "موثوقية وتدفق واضح" : "Trusted & structured workflow" ?>
          </span>

          <div class="section-kicker mb-2"><?= $is_ar ? "منصة موثوقة" : "Trusted platform" ?></div>
          <h1 class="hero-title mb-3"><?= htmlspecialchars($title) ?></h1>
          <p class="hero-sub mb-4"><?= htmlspecialchars($subtitle) ?></p>

          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-brand" href="auth/login.php">
              <i class="fa-solid fa-right-to-bracket"></i> <?= $cta_login ?>
            </a>
            <a class="btn btn-soft" href="#roles">
              <i class="fa-solid fa-diagram-project"></i> <?= $is_ar ? "ابدأ حسب دورك" : "Start by role" ?>
            </a>
            <a class="btn btn-soft" href="#how">
              <i class="fa-solid fa-circle-play"></i> <?= $is_ar ? "كيف تعمل؟" : "How it works" ?>
            </a>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-3">
            <span class="pill"><i class="fa-solid fa-shield-halved"></i> <?= $is_ar ? "صلاحيات منفصلة" : "Separated permissions" ?></span>
            <span class="pill"><i class="fa-solid fa-box"></i> <?= $is_ar ? "خصم مخزون تلقائي" : "Automatic stock deduction" ?></span>
            <span class="pill"><i class="fa-solid fa-star"></i> <?= $is_ar ? "تقييم بعد التسليم" : "Rating after delivery" ?></span>
            <span class="pill" style="background:rgba(30,99,216,.10); color:#1e63d8"><i class="fa-solid fa-robot"></i> <?= $is_ar ? "توصيات ذكية" : "Smart recommendations" ?></span>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="cardx reveal">
            <div class="cardx-body">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-bold"><i class="fa-solid fa-seedling me-2"></i><?= $is_ar ? "نظرة سريعة" : "Quick overview" ?></div>
                <span class="pill" style="background:rgba(30,99,216,.10); color:#1e63d8"><i class="fa-solid fa-robot"></i> AI</span>
              </div>
              <ul class="mb-0">
                <li class="mb-2"><?= $is_ar ? "المزارع يضيف المنتجات ويطلب نقل." : "Farmers add products and request transport." ?></li>
                <li class="mb-2"><?= $is_ar ? "المتجر يطلب بالكمية ويخصم المخزون." : "Stores order with quantity and stock is deducted." ?></li>
                <li class="mb-2"><?= $is_ar ? "الناقل يقبل الطلب ويحدّث حالة التسليم." : "Transporters accept requests and update delivery status." ?></li>
                <li class="mb-0"><?= $is_ar ? "تقييم الطلب بعد التسليم لتحسين الثقة." : "Rating after delivery to improve trust." ?></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- STATS + VALUE -->
  <section class="py-4 py-md-5">
    <div class="row g-4 align-items-center">
      <div class="col-lg-6">
        <div class="section-kicker mb-2"><?= $is_ar ? "مصمم للتسليم" : "Built for real workflows" ?></div>
        <div class="section-title mb-2"><?= $is_ar ? "تدفق عمل واضح + ثقة + توصيات" : "Clear workflow + trust + recommendations" ?></div>
        <p class="section-sub mb-0"><?= $is_ar ? "كل دور يملك صلاحياته ولوحة تحكمه — مع تحقق حسابات (Pending/Approved) لمنع التزوير." : "Each role has separated permissions and its own dashboard — with account verification (Pending/Approved) to prevent fraud." ?></p>
      </div>
      <div class="col-lg-6">
        <div class="stat-grid">
          <div class="cardx stat reveal"><div class="cardx-body">
            <div class="k"><i class="fa-solid fa-shield-halved me-2"></i><?= $is_ar ? "موثوق" : "Trusted" ?></div>
            <div class="l"><?= $is_ar ? "حسابات موثقة" : "Verified accounts" ?></div>
          </div></div>
          <div class="cardx stat reveal"><div class="cardx-body">
            <div class="k"><i class="fa-solid fa-box me-2"></i><?= $is_ar ? "مخزون" : "Stock" ?></div>
            <div class="l"><?= $is_ar ? "خصم تلقائي" : "Auto deduction" ?></div>
          </div></div>
          <div class="cardx stat reveal"><div class="cardx-body">
            <div class="k"><i class="fa-solid fa-truck-fast me-2"></i><?= $is_ar ? "تتبع" : "Tracking" ?></div>
            <div class="l"><?= $is_ar ? "حالات توصيل" : "Delivery status" ?></div>
          </div></div>
          <div class="cardx stat reveal"><div class="cardx-body">
            <div class="k"><i class="fa-solid fa-robot me-2"></i><?= $is_ar ? "ذكاء اصطناعي" : "AI" ?></div>
            <div class="l"><?= $is_ar ? "توصيات وتحليلات" : "Insights & recommendations" ?></div>
          </div></div>
        </div>
      </div>
    </div>

    <div class="feature-grid mt-4">
      <div class="cardx feature reveal"><div class="cardx-body">
        <div class="icon mb-3"><i class="fa-solid fa-user-lock"></i></div>
        <h5 class="mb-1"><?= $is_ar ? "صلاحيات منفصلة" : "Separated permissions" ?></h5>
        <p><?= $is_ar ? "كل مستخدم يدخل على لوحة تحكم مخصصة لدوره." : "Each role signs in to its own dedicated dashboard." ?></p>
      </div></div>
      <div class="cardx feature reveal"><div class="cardx-body">
        <div class="icon mb-3"><i class="fa-solid fa-file-circle-check"></i></div>
        <h5 class="mb-1"><?= $is_ar ? "تسجيل موثوق" : "Trusted signup" ?></h5>
        <p><?= $is_ar ? "رفع إثبات + مراجعة المشرف (Pending/Approved)." : "Upload proof + admin review (Pending/Approved)." ?></p>
      </div></div>
      <div class="cardx feature reveal"><div class="cardx-body">
        <div class="icon mb-3"><i class="fa-solid fa-chart-line"></i></div>
        <h5 class="mb-1"><?= $is_ar ? "قرارات أفضل" : "Better decisions" ?></h5>
        <p><?= $is_ar ? "توصيات (AI) لتحسين التسعير والطلب والمسارات." : "AI recommendations for pricing, demand and routing." ?></p>
      </div></div>
    </div>
  </section>

  <!-- STATS (for committee) -->
  <section class="py-4 py-md-5">
    <div class="cardx reveal">
      <div class="cardx-body">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
          <div>
            <div class="section-kicker mb-1"><?= $is_ar ? "لماذا هذا النظام؟" : "Why this system" ?></div>
            <div class="section-title mb-1"><?= $is_ar ? "تدفق واضح + ثقة + قرارات أذكى" : "Clear workflow + Trust + Smarter decisions" ?></div>
            <div class="section-sub"><?= $is_ar ? "مصمم لعرضٍ احترافي أمام لجنة التقييم: منطق أعمال واضح، صلاحيات منفصلة، وتحقق من الحسابات." : "Built for an evaluation-ready demo: clear business logic, separated roles, and verified onboarding." ?></div>
          </div>
          <div class="d-flex gap-2">
            <a class="btn btn-soft" href="auth/login.php?role=admin"><i class="fa-solid fa-shield-halved"></i> <?= $is_ar ? "لوحة المشرف" : "Admin Panel" ?></a>
          </div>
        </div>

        <div class="stat-grid mt-4">
          <div class="stat">
            <div class="k">3</div>
            <div class="l"><?= $is_ar ? "أدوار منفصلة" : "Separate roles" ?></div>
          </div>
          <div class="stat">
            <div class="k">4</div>
            <div class="l"><?= $is_ar ? "حالات نقل" : "Transport states" ?></div>
          </div>
          <div class="stat">
            <div class="k">1</div>
            <div class="l"><?= $is_ar ? "تقييم بعد التسليم" : "Post-delivery rating" ?></div>
          </div>
          <div class="stat">
            <div class="k">AI</div>
            <div class="l"><?= $is_ar ? "توصيات ذكية" : "Smart recommendations" ?></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ROLES -->
  <section id="roles" class="py-4 py-md-5">
    <div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h2 class="fw-bold mb-1"><?= $is_ar ? "اختر دورك" : "Choose your role" ?></h2>
        <div class="text-muted"><?= $is_ar ? "كل دور يملك لوحة تحكم وصلاحيات مختلفة" : "Each role has its own dashboard and permissions" ?></div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-4">
        <div class="cardx reveal h-100">
          <div class="cardx-body">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="fa-solid fa-tractor fs-4" style="color:#1f7a4a"></i>
              <h5 class="mb-0 fw-bold"><?= $is_ar ? "مزارع" : "Farmer" ?></h5>
            </div>
            <p class="text-muted mb-3"><?= $is_ar ? "إضافة المنتجات وتحديد السعر/الكمية وإنشاء طلبات نقل." : "Add products, set price/quantity, and create transport requests." ?></p>
            <div class="d-grid gap-2">
              <a class="btn btn-brand" href="auth/login.php?role=farmer"><i class="fa-solid fa-right-to-bracket"></i> <?= $is_ar ? "دخول كمزارع" : "Login as Farmer" ?></a>
              <a class="btn btn-soft" href="auth/register_farmer.php"><i class="fa-solid fa-user-plus"></i> <?= $is_ar ? "تسجيل مزارع" : "Register Farmer" ?></a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="cardx reveal h-100">
          <div class="cardx-body">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="fa-solid fa-truck fs-4" style="color:#1e63d8"></i>
              <h5 class="mb-0 fw-bold"><?= $is_ar ? "ناقل" : "Transporter" ?></h5>
            </div>
            <p class="text-muted mb-3"><?= $is_ar ? "استعراض الطلبات وقبولها وتحديث حالة التوصيل." : "Browse requests, accept jobs, and update delivery status." ?></p>
            <div class="d-grid gap-2">
              <a class="btn btn-brand" href="auth/login.php?role=transporter" style="--brand:#1e63d8; --brand-2:#0e46a8"><i class="fa-solid fa-right-to-bracket"></i> <?= $is_ar ? "دخول كناقل" : "Login as Transporter" ?></a>
              <a class="btn btn-soft" href="auth/register_transporter.php"><i class="fa-solid fa-user-plus"></i> <?= $is_ar ? "تسجيل ناقل" : "Register Transporter" ?></a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="cardx reveal h-100">
          <div class="cardx-body">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="fa-solid fa-store fs-4" style="color:#c28a00"></i>
              <h5 class="mb-0 fw-bold"><?= $is_ar ? "متجر" : "Store" ?></h5>
            </div>
            <p class="text-muted mb-3"><?= $is_ar ? "تصفح المنتجات وطلبها بالكمية والتقييم بعد التسليم." : "Browse products, order with quantity, and rate after delivery." ?></p>
            <div class="d-grid gap-2">
              <a class="btn btn-brand" href="auth/login.php?role=store" style="--brand:#f4b400; --brand-2:#c28a00; color:#111"><i class="fa-solid fa-right-to-bracket"></i> <?= $is_ar ? "دخول كمتجر" : "Login as Store" ?></a>
              <a class="btn btn-soft" href="auth/register_store.php"><i class="fa-solid fa-user-plus"></i> <?= $is_ar ? "تسجيل متجر" : "Register Store" ?></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section id="how" class="py-4 py-md-5">
    <div class="row g-3">
      <div class="col-md-4">
        <div class="cardx reveal h-100">
          <div class="cardx-body">
            <div class="fw-bold mb-2"><i class="fa-solid fa-1 me-2"></i><?= $is_ar ? "إضافة منتج" : "Add a product" ?></div>
            <div class="text-muted"><?= $is_ar ? "المزارع يضيف المنتج (الكمية، السعر، تاريخ الحصاد)." : "Farmer adds product (quantity, price, harvest date)." ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="cardx reveal h-100">
          <div class="cardx-body">
            <div class="fw-bold mb-2"><i class="fa-solid fa-2 me-2"></i><?= $is_ar ? "طلب شراء" : "Place an order" ?></div>
            <div class="text-muted"><?= $is_ar ? "المتجر يختار الكمية ويُخصم المخزون تلقائيًا." : "Store selects quantity and stock is deducted automatically." ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="cardx reveal h-100">
          <div class="cardx-body">
            <div class="fw-bold mb-2"><i class="fa-solid fa-3 me-2"></i><?= $is_ar ? "نقل وتسليم" : "Transport & delivery" ?></div>
            <div class="text-muted"><?= $is_ar ? "الناقل يحدّث الحالة حتى التسليم، ثم تقييم لتعزيز الثقة." : "Transporter updates status to delivered, then rating strengthens trust." ?></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TRUST & VERIFICATION -->
  <section class="py-4 py-md-5">
    <div class="row g-3">
      <div class="col-lg-6">
        <div class="hero reveal">
          <div class="hero-inner">
            <div class="section-kicker mb-2"><?= $is_ar ? "الموثوقية" : "Trust" ?></div>
            <div class="section-title mb-2"><?= $is_ar ? "تسجيل موثوق بدون تزوير" : "Verified sign up (no fake accounts)" ?></div>
            <p class="section-sub mb-4"><?= $is_ar ? "الحسابات الجديدة تبدأ بحالة (قيد المراجعة) حتى يعتمدها المشرف بناءً على المعلومات/الوثائق." : "New accounts start as (Pending) until an Admin approves them based on submitted details/documents." ?></p>
            <div class="d-flex flex-wrap gap-2">
              <span class="badge-soft"><i class="fa-solid fa-clock"></i> <?= $is_ar ? "Pending" : "Pending" ?></span>
              <span class="badge-soft"><i class="fa-solid fa-circle-check"></i> <?= $is_ar ? "Approved" : "Approved" ?></span>
              <span class="badge-soft"><i class="fa-solid fa-circle-xmark"></i> <?= $is_ar ? "Rejected" : "Rejected" ?></span>
            </div>
            <div class="d-flex gap-2 mt-4">
              <a class="btn btn-brand" href="auth/register.php"><i class="fa-solid fa-user-plus"></i> <?= $cta_register ?></a>
              <a class="btn btn-soft" href="auth/login.php?role=admin"><i class="fa-solid fa-shield-halved"></i> <?= $is_ar ? "عرض لوحة المشرف" : "View Admin dashboard" ?></a>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="cardx reveal h-100">
          <div class="cardx-body">
            <div class="section-kicker mb-2"><?= $is_ar ? "الذكاء الاصطناعي" : "AI" ?></div>
            <div class="section-title mb-2"><?= $is_ar ? "توصيات ذكية بالذكاء الاصطناعي" : "AI-powered recommendations" ?></div>
            <p class="section-sub mb-4"><?= $is_ar ? "النظام يستخدم نماذج توقع بسيطة مبنية على Dataset حقيقي (Sample) ويتم عرض النتائج داخل لوحة المستخدم." : "The system uses dataset-based forecasting and shows the insights directly in user dashboards." ?></p>
            <div class="table-clean">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th><?= $is_ar ? "المستخدم" : "User" ?></th>
                    <th><?= $is_ar ? "نوع التوصية" : "Recommendation" ?></th>
                    <th><?= $is_ar ? "مثال" : "Example" ?></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><i class="fa-solid fa-tractor me-2"></i>Farmer</td>
                    <td><?= $is_ar ? "تسعير" : "Pricing" ?></td>
                    <td class="text-muted"><?= $is_ar ? "سعر مقترح حسب العرض/الطلب" : "Suggested price based on demand" ?></td>
                  </tr>
                  <tr>
                    <td><i class="fa-solid fa-truck me-2"></i>Transporter</td>
                    <td><?= $is_ar ? "فرص نقل" : "Jobs" ?></td>
                    <td class="text-muted"><?= $is_ar ? "طلبات مناسبة لمسارك" : "Requests matching your route" ?></td>
                  </tr>
                  <tr>
                    <td><i class="fa-solid fa-store me-2"></i>Store</td>
                    <td><?= $is_ar ? "تنبؤ" : "Forecast" ?></td>
                    <td class="text-muted"><?= $is_ar ? "منتجات مطلوبة قريبًا" : "Likely high-demand products" ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="d-flex gap-2 mt-3">
              <a class="btn btn-soft" href="auth/login.php"><i class="fa-solid fa-right-to-bracket"></i> <?= $cta_login ?></a>
              <a class="btn btn-soft" href="ai_service/"><i class="fa-brands fa-python"></i> <?= $is_ar ? "ملفات خدمة AI" : "AI service files" ?></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer class="text-center py-4">
    <div class="text-muted">© 2026 — Supply Chain Management Agriculture in Jordan</div>
    <div class="small-link mt-2">
      <a href="auth/login.php?role=admin"><?= $is_ar ? "دخول المشرف" : "Admin sign in" ?></a>
      <span class="opacity-50">•</span>
      <a href="README.md"><?= $is_ar ? "دليل التشغيل" : "Setup guide" ?></a>
    </div>
  </footer>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/brand.js?v=3"></script>
</body>
</html>
