<?php
session_start();
include "../includes/language.php";
include "../config/db.php";
include "../includes/ai_engine.php";
include "../includes/ai_client.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'store') {
    header("Location: ../auth/login.php");
    exit;
}

$store_id = (int)$_SESSION['user_id'];
$current_page = "ai";
$today = date('Y-m-d');

// For demo (committee day) you can choose any date
$pred_date = trim((string)($_GET['date'] ?? $today));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $pred_date)) { $pred_date = $today; }


// Choose product to analyze
$product_name = trim((string)($_GET['product'] ?? ''));

// Build product dropdown from available products
$resNames = $conn->query("SELECT DISTINCT name FROM products ORDER BY name ASC");

$ai = null;
if ($product_name !== '') {
    $ai = ai_store_best_offer($conn, $store_id, $product_name, $today);
}


// ===== ML (Flask) Market Recommendation (for Store) =====
$mlDailyBuy = null;
$mlDailySell = null;
$mlDailyError = null;
$mlRec = null;
$mlError = null;
$mlAdvice = null;
$mlNextPrice = null;
$mlBadge = false;
$mlChartId = null;
if ($product_name !== '') {
    $yearNow = (int)date('Y');
    $monthNow = (int)date('n');
    $resp = ai_ml_recommend($product_name, $yearNow, $monthNow, 500);
    if (($resp['ok'] ?? false) === true) {
        $mlRec = $resp['data'];// Daily prediction for selected date (stronger demo)
$buyMarket  = (string)($mlRec['best_market_to_buy']['market'] ?? '');
$sellMarket = (string)($mlRec['best_market_to_sell']['market'] ?? '');
if ($buyMarket !== '') {
    $d = ai_ml_predict_daily($product_name, $buyMarket, $pred_date, 500);
    if (($d['ok'] ?? false) === true) $mlDailyBuy = $d['data'];
    else $mlDailyError = $d['error'] ?? null;
}
if ($sellMarket !== '') {
    $d2 = ai_ml_predict_daily($product_name, $sellMarket, $pred_date, 500);
    if (($d2['ok'] ?? false) === true) $mlDailySell = $d2['data'];
    else $mlDailyError = $mlDailyError ?: ($d2['error'] ?? null);
}


        // Advice for store (buy-side): wait if next month is noticeably cheaper
        $bestBuyMarket = (string)($mlRec['best_market_to_buy']['market'] ?? '');
        $bestBuyPrice  = (float)($mlRec['best_market_to_buy']['predicted_price_jod'] ?? 0);
        if ($bestBuyMarket !== '' && $bestBuyPrice > 0) {
            $ny = $yearNow;
            $nm = $monthNow + 1;
            if ($nm > 12) { $nm = 1; $ny++; }
            $nextResp = ai_ml_predict($product_name, $bestBuyMarket, $ny, $nm, 500);
            if (($nextResp['ok'] ?? false) && isset($nextResp['data']['predicted_price_jod'])) {
                $mlNextPrice = (float)$nextResp['data']['predicted_price_jod'];
                $mlAdvice = ($mlNextPrice < $bestBuyPrice * 0.97) ? 'wait' : 'buy_now';
            }
        }

        // Badge: good deal if below historical mean by at least 3%
        $hist = $mlRec['historical_mean_price_jod_for_month'] ?? null;
        if ($hist !== null && $bestBuyPrice > 0) {
            $mlBadge = ($bestBuyPrice <= ((float)$hist) * 0.97);
        }

        $mlChartId = 'ml_store_chart_' . md5($product_name);
    } else {
        $mlError = $resp['error'] ?? 'AI service error';
    }
}

?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8" />
    <title><?= ($lang_code === 'ar') ? "توصيات الذكاء الاصطناعي" : "AI Recommendations" ?></title>

    <!-- Bootstrap + Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS (match dashboard look) -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/brand.css?v=3">
    <link rel="stylesheet" href="../css/farmer.css?v=200">
    <link rel="stylesheet" href="../css/ai_pages.css?v=1">
    <?php if ($lang_code === 'ar'): ?>
        <link rel="stylesheet" href="../css/style_ar.css?v=200">
    <?php endif; ?>

    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body data-role="store">

<!-- ============ SIDEBAR ============ -->
<?php include "../includes/store_sidebar.php"; ?>

<div class="menu-overlay"></div>

<!-- ============ MAIN CONTENT ============ -->
<div class="main-content">

    <?php include "../includes/store_topbar.php"; ?><br>

    <div class="dashboard-box">

        <h3 class="mt-3 mb-4 page-main-title">
            <i class="fa-solid fa-wand-magic-sparkles text-success"></i>
            <?= ($lang_code === 'ar') ? "توصيات الذكاء الاصطناعي (المتجر)" : "AI Recommendations (Store)" ?>
        </h3>

        <div class="ai-card-subtitle mb-3">
            <?= ($lang_code === 'ar')
                ? "اختر منتجاً لرؤية السعر المتوقع اليوم + أرخص مزارع يعرض المنتج + مقارنة بسيطة." 
                : "Pick a product to see today's expected market price + the cheapest farmer offer + a simple comparison."; ?>
        </div>


        <!-- ML Market Insight -->
        <?php if ($product_name !== ''): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="m-0">
                            <i class="fa-solid fa-robot text-primary"></i>
                            <?= ($lang_code === 'ar') ? "تحليل سوق ذكي (ML)" : "Smart Market Insight (ML)" ?>
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($mlBadge): ?>
                                <span class="badge bg-warning text-dark ai-badge">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    <?= ($lang_code==='ar') ? 'AI Deal' : 'AI Deal' ?>
                                </span>
                            <?php endif; ?>
                            <span class="badge bg-primary-subtle text-primary">Flask AI</span>
                        </div>
                    </div>

                    <?php if ($mlRec): ?>
                        <div class="mt-2">
                            <div class="mb-1">
                                <?= ($lang_code === 'ar') ? "أفضل سوق للشراء:" : "Best market to buy:" ?>
                                <span class="badge bg-danger-subtle text-danger">
                                    <?= htmlspecialchars($mlRec['best_market_to_buy']['market'] ?? '-') ?>
                                </span>
                                <span class="ms-2">
                                    <?= number_format((float)($mlRec['best_market_to_buy']['predicted_price_jod'] ?? 0), 2) ?>
                                    <?= ($lang_code==='ar') ? "د.أ/كغم" : "JOD/kg" ?>
                                </span>
                            </div><?php if ($mlDailyBuy && isset($mlDailyBuy['predicted_price_jod'])): ?>
    <div class="text-muted small ms-1">
        <i class="fa-solid fa-calendar-day"></i>
        <?= ($lang_code==='ar') ? "سعر متوقع بتاريخ " : "Predicted on " ?>
        <b><?= htmlspecialchars($pred_date) ?></b>:
        <b><?= number_format((float)$mlDailyBuy['predicted_price_jod'], 2) ?></b>
        <?= ($lang_code==='ar') ? "د.أ/كغم" : "JOD/kg" ?>
    </div>
<?php endif; ?>


                            <div class="mb-1">
                                <?= ($lang_code === 'ar') ? "أفضل سوق للبيع (للمقارنة):" : "Best market to sell (for comparison):" ?>
                                <span class="badge bg-success-subtle text-success">
                                    <?= htmlspecialchars($mlRec['best_market_to_sell']['market'] ?? '-') ?>
                                </span>
                                <span class="ms-2">
                                    <?= number_format((float)($mlRec['best_market_to_sell']['predicted_price_jod'] ?? 0), 2) ?>
                                    <?= ($lang_code==='ar') ? "د.أ/كغم" : "JOD/kg" ?>
                                </span>
                            </div><?php if ($mlDailySell && isset($mlDailySell['predicted_price_jod'])): ?>
    <div class="text-muted small ms-1">
        <i class="fa-solid fa-calendar-day"></i>
        <?= ($lang_code==='ar') ? "سعر متوقع بتاريخ " : "Predicted on " ?>
        <b><?= htmlspecialchars($pred_date) ?></b>:
        <b><?= number_format((float)$mlDailySell['predicted_price_jod'], 2) ?></b>
        <?= ($lang_code==='ar') ? "د.أ/كغم" : "JOD/kg" ?>
    </div>
<?php endif; ?>


                            <?php if (($mlRec['historical_mean_price_jod_for_month'] ?? null) !== null): ?>
                                <div class="text-muted small">
                                    <?= ($lang_code === 'ar') ? "متوسط تاريخي لهذا الشهر:" : "Historical mean for this month:" ?>
                                    <?= number_format((float)$mlRec['historical_mean_price_jod_for_month'], 2) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($mlAdvice !== null && $mlNextPrice !== null): ?>
                                <div class="mt-2 small">
                                    <?php if ($mlAdvice === 'wait'): ?>
                                        <span class="text-success">
                                            <i class="fa-solid fa-clock"></i>
                                            <?= ($lang_code==='ar') ? 'توصية: انتظر شهر – السعر أقل (فرصة شراء أفضل)' : 'Advice: wait a month – cheaper price' ?>
                                            (<?= number_format($mlNextPrice, 2) ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <i class="fa-solid fa-bolt"></i>
                                            <?= ($lang_code==='ar') ? 'توصية: الشراء الآن مناسب' : 'Advice: buying now is fine' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($mlChartId && ($mlRec['best_market_to_buy']['market'] ?? '') !== ''): ?>
                                <div class="mt-3 ai-chart-wrap" style="height:140px;">
                                    <canvas id="<?= htmlspecialchars($mlChartId) ?>"></canvas>
                                </div>
                                <script>
                                  window.__mlCharts = window.__mlCharts || [];
                                  window.__mlCharts.push({
                                    product: <?= json_encode($product_name, JSON_UNESCAPED_UNICODE) ?>,
                                    market: <?= json_encode((string)($mlRec['best_market_to_buy']['market'] ?? ''), JSON_UNESCAPED_UNICODE) ?>,
                                    startYear: <?= (int)$yearNow ?>,
                                    startMonth: <?= (int)$monthNow ?>,
                                    months: 6,
                                    quantityKg: 500,
                                    canvasId: <?= json_encode($mlChartId) ?>
                                  });
                                </script>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3 mb-0">
                            <?= ($lang_code === 'ar')
                                ? "خدمة الذكاء الاصطناعي (Flask) غير متاحة الآن. شغّل ai_service على المنفذ 5000."
                                : "Flask AI service is unavailable. Run ai_service on port 5000."; ?>
                            <?php if ($mlError): ?>
                                <div class="text-muted small mt-1"><?= htmlspecialchars($mlError) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>


        <!-- Filter -->
        <form class="row g-2 align-items-end mb-4" method="get">
            <div class="col-md-6 col-lg-4">
                <label class="form-label"><?= ($lang_code === 'ar') ? "المنتج" : "Product" ?></label>
                <select class="form-select" name="product" required>
                    <option value=""><?= ($lang_code === 'ar') ? "اختر..." : "Choose..." ?></option>
                    <?php while ($n = $resNames->fetch_assoc()):
                        $val = $n['name'];
                    ?>
                        <option value="<?= htmlspecialchars($val) ?>" <?= ($product_name === $val) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($val) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
    <label class="form-label"><?= ($lang_code === 'ar') ? "تاريخ التوقع" : "Prediction Date" ?></label>
    <input type="date" class="form-control" name="date"
           value="<?= htmlspecialchars($pred_date) ?>"
           min="2024-01-01" max="2030-12-31"
           placeholder="<?= ($lang_code === 'ar') ? "مثال: 2026-01-26" : "e.g., 2026-01-26" ?>">
    <div class="form-text">
        <?= ($lang_code === 'ar') ? "اختر يوم المناقشة (مثل 26/1/2026) لعرض التوقع." : "Pick the committee day (e.g., 2026-01-26) to demo the prediction." ?>
    </div>
</div>
<div class="col-md-3 col-lg-2">
                <button class="btn btn-primary w-100">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <?= ($lang_code === 'ar') ? "اعرض" : "Show" ?>
                </button>
            </div>
        </form>

        <?php if ($ai && ($ai['ok'] ?? false) === true): ?>
            <?php
                $fc = $ai['forecast'] ?? [];
                $best = $ai['best_offer'] ?? null;

                $expectedJOD = (($fc['ok'] ?? false) === true) ? ((float)($fc['expected'] ?? 0))/100 : null;
                $yestJOD = (($fc['ok'] ?? false) === true) ? ((float)($fc['yesterday'] ?? 0))/100 : null;
                $bestPrice = $best ? (float)($best['price_jd'] ?? 0) : null;
                $savings = ($expectedJOD !== null && $bestPrice !== null) ? ($expectedJOD - $bestPrice) : null;
                $offersCount = count($ai['offers'] ?? []);

                $chartId = 'store_chart_' . md5($product_name . '|' . ($ai['market'] ?? ''));
            ?>

            <!-- KPI -->
            <div class="row ai-kpi mb-3">
                <div class="col-md-4 mb-3">
                    <div class="product-card shadow-sm p-3 bg-white rounded">
                        <div class="text-muted small"><?= ($lang_code==='ar') ? 'السعر المتوقع اليوم' : 'Expected price today' ?></div>
                        <div class="kpi-value">
                            <?= ($expectedJOD !== null) ? number_format($expectedJOD, 2) : '—' ?>
                            <?= ($lang_code==='ar') ? 'د.أ/كغم' : 'JOD/kg' ?>
                        </div>
                        <?php if ($yestJOD !== null): ?>
                            <div class="ai-card-subtitle mt-1">
                                <?= ($lang_code==='ar') ? 'أمس:' : 'Yesterday:' ?>
                                <b><?= number_format($yestJOD, 2) ?></b>
                                <?= ($lang_code==='ar') ? 'د.أ/كغم' : 'JOD/kg' ?>
                                · <span class="text-muted"><?= ($lang_code==='ar') ? 'التغير:' : 'Change:' ?></span>
                                <b><?= htmlspecialchars((string)($fc['pct'] ?? '0')) ?>%</b>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="product-card shadow-sm p-3 bg-white rounded">
                        <div class="text-muted small"><?= ($lang_code==='ar') ? 'أرخص عرض' : 'Cheapest offer' ?></div>
                        <div class="kpi-value">
                            <?= ($bestPrice !== null) ? number_format($bestPrice, 2) : '—' ?>
                            <?= ($lang_code==='ar') ? 'د.أ/كغم' : 'JOD/kg' ?>
                        </div>
                        <div class="ai-card-subtitle mt-1">
                            <?= ($best ? htmlspecialchars($best['farmer_name'] ?? '') : (($lang_code==='ar') ? 'لا يوجد عروض' : 'No offers')) ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="product-card shadow-sm p-3 bg-white rounded">
                        <div class="text-muted small"><?= ($lang_code==='ar') ? 'فرق السعر (توفير)' : 'Potential savings' ?></div>
                        <div class="kpi-value">
                            <?= ($savings !== null) ? number_format($savings, 2) : '—' ?>
                            <?= ($lang_code==='ar') ? 'د.أ/كغم' : 'JOD/kg' ?>
                        </div>
                        <div class="ai-card-subtitle mt-1"><?= ($lang_code==='ar') ? "عدد العروض: {$offersCount}" : "Offers: {$offersCount}" ?></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="product-card shadow-sm p-3 bg-white rounded h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="product-title m-0">
                                <i class="fa-solid fa-chart-line text-success"></i>
                                <?= ($lang_code==='ar') ? 'منحنى سعر السوق (آخر 14 يوم)' : 'Market trend (last 14 days)' ?>
                            </h5>
                            <span class="badge bg-light text-dark">
                                <?= htmlspecialchars($ai['market'] ?? '') ?> · <?= htmlspecialchars($today) ?>
                            </span>
                        </div>
                        <div class="ai-card-subtitle mt-1">
                            <?= ($lang_code==='ar') ? 'مصدر: Dataset (Sample 2024–2026)' : 'Source: Sample dataset (2024–2026)' ?>
                        </div>
                        <div class="mt-2 ai-chart-wrap">
                            <canvas id="<?= $chartId ?>"></canvas>
                        </div>
                        <?php if (($fc['ok'] ?? false) === true): ?>
                            <div class="ai-card-subtitle mt-2">
                                <?= ($lang_code==='ar') ? 'ثقة التوقع:' : 'Forecast confidence:' ?>
                                <b><?= htmlspecialchars($fc['confidence'] ?? '—') ?></b>
                                <?php if (!empty($fc['data_points'])): ?>
                                    · <?= ($lang_code==='ar') ? 'بيانات:' : 'Data points:' ?> <b><?= (int)$fc['data_points'] ?></b>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="product-card shadow-sm p-3 bg-white rounded h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="product-title m-0">
                                <i class="fa-solid fa-tags text-success"></i>
                                <?= ($lang_code==='ar') ? 'أفضل توصية شراء' : 'Best buying recommendation' ?>
                            </h5>
                            <?php if ($best): ?>
                                <span class="badge bg-success-subtle text-success"><?= ($lang_code==='ar') ? 'مقترح' : 'Recommended' ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($best): ?>
                            <div class="mt-2">
                                <div class="text-muted small"><?= ($lang_code==='ar') ? 'المزارع' : 'Farmer' ?></div>
                                <div style="font-size:20px;font-weight:800;">
                                    <?= htmlspecialchars($best['farmer_name'] ?? '') ?>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="text-muted"><?= ($lang_code==='ar') ? 'السعر:' : 'Price:' ?></span>
                                <b><?= number_format((float)($best['price_jd'] ?? 0), 2) ?></b> <?= ($lang_code==='ar') ? 'د.أ/كغم' : 'JOD/kg' ?>
                                <span class="text-muted"> · <?= ($lang_code==='ar') ? 'الكمية:' : 'Qty:' ?></span>
                                <b><?= (int)($best['qty'] ?? 0) ?></b> kg
                            </div>

                            <div class="alert alert-success mt-3 mb-0">
                                <?= ($lang_code==='ar')
                                    ? "التوصية: اشترِ من <strong>".htmlspecialchars($best['farmer_name'] ?? '')."</strong> لأنه الأرخص (<strong>".number_format((float)($best['price_jd'] ?? 0),2)."</strong> د.أ/كغم) مع توفر كمية <strong>".(int)($best['qty'] ?? 0)."</strong> كغم."
                                    : "Recommendation: Buy from <strong>".htmlspecialchars($best['farmer_name'] ?? '')."</strong> because it's the lowest price (<strong>".number_format((float)($best['price_jd'] ?? 0),2)."</strong> JOD/kg) with <strong>".(int)($best['qty'] ?? 0)."</strong> kg available.";
                                ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0 mt-2">
                                <?= ($lang_code === 'ar') ? "لا يوجد عروض متاحة حالياً." : "No offers available right now." ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Offers table -->
            <div class="product-card shadow-sm p-3 bg-white rounded">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="product-title m-0">
                        <i class="fa-solid fa-list text-success"></i>
                        <?= ($lang_code === 'ar') ? "أفضل 5 عروض" : "Top 5 offers" ?>
                    </h5>
                    <input id="offerSearch" class="form-control ai-search" placeholder="<?= ($lang_code==='ar')?'ابحث باسم المزارع...':'Search farmer name...' ?>">
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-sm align-middle" id="offersTable">
                        <thead>
                        <tr>
                            <th><?= ($lang_code === 'ar') ? "المزارع" : "Farmer" ?></th>
                            <th><?= ($lang_code === 'ar') ? "السعر (د.أ/كغم)" : "Price (JOD/kg)" ?></th>
                            <th><?= ($lang_code === 'ar') ? "الكمية" : "Qty" ?></th>
                            <th><?= ($lang_code === 'ar') ? "إجراء" : "Action" ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach (($ai['offers'] ?? []) as $o):
                            $isBest = ($best && (int)$o['product_id'] === (int)($best['product_id'] ?? 0));
                        ?>
                            <tr class="offer-row" data-name="<?= htmlspecialchars(mb_strtolower($o['farmer_name'] ?? '')) ?>" <?= $isBest ? 'style="background:rgba(25,135,84,0.08);"' : '' ?>>
                                <td>
                                    <?= $isBest ? '<span class="badge bg-success-subtle text-success me-2">'.(($lang_code==='ar')?'الأرخص':'Best').'</span>' : '' ?>
                                    <?= htmlspecialchars($o['farmer_name'] ?? '') ?>
                                </td>
                                <td><b><?= number_format((float)($o['price_jd'] ?? 0), 2) ?></b></td>
                                <td><?= (int)($o['qty'] ?? 0) ?> kg</td>
                                <td>
                                    <a class="btn btn-sm btn-primary" href="place_order.php?product_id=<?= (int)($o['product_id'] ?? 0) ?>">
                                        <i class="fa-solid fa-cart-shopping"></i>
                                        <?= ($lang_code === 'ar') ? "اطلب" : "Order" ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="ai-card-subtitle">
                    <?= ($lang_code === 'ar') ? "الترتيب: الأرخص أولاً ثم الأعلى كمية." : "Sorted by: lowest price first then higher quantity." ?>
                </div>
            </div>

        <?php elseif ($product_name !== ''): ?>
            <div class="alert alert-danger">
                <?= ($lang_code === 'ar') ? "حدث خطأ أثناء توليد التوصية." : "Failed to generate recommendation." ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ML Charts (predicted next months)
async async function renderMLChart(item){
  const url = `../api/ai/ml_forecast_series.php?product=${encodeURIComponent(item.product)}`
            + `&market=${encodeURIComponent(item.market)}`
            + `&start_year=${encodeURIComponent(item.startYear)}`
            + `&start_month=${encodeURIComponent(item.startMonth)}`
            + `&months=${encodeURIComponent(item.months || 6)}`
            + `&quantity_kg=${encodeURIComponent(item.quantityKg || 500)}`;

  try{
    const res = await fetch(url, { cache: 'no-store' });
    const data = await res.json();
    if(!data.ok || !Array.isArray(data.series) || data.series.length < 2) return;

    const labels = data.series.map(x => x.label ?? x.date ?? '');
    const prices = data.series.map(x => Number(x.predicted_price_jod ?? x.price_jod ?? 0));

    const canvas = document.getElementById(item.canvasId);
    if(!canvas) return;

    // Destroy previous chart if any
    canvas.__chart && canvas.__chart.destroy && canvas.__chart.destroy();

    const ctx = canvas.getContext('2d');
    const h = canvas.parentElement?.clientHeight || 140;
    const gradient = ctx.createLinearGradient(0, 0, 0, h);
    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.28)');
    gradient.addColorStop(1, 'rgba(16, 185, 129, 0.02)');

    const chart = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: (document.documentElement.getAttribute('lang') === 'ar')
            ? 'توقع السعر (د.أ/كغم)'
            : 'Predicted price (JOD/kg)',
          data: prices,
          borderWidth: 2,
          tension: 0.35,
          fill: true,
          backgroundColor: gradient,
          pointRadius: 2,
          pointHoverRadius: 5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            intersect: false,
            mode: 'index',
            callbacks: {
              label: (ctx) => {
                const v = Number(ctx.parsed.y ?? 0);
                const isAr = (document.documentElement.getAttribute('lang') === 'ar');
                return (isAr ? 'السعر: ' : 'Price: ') + v.toFixed(2) + ' JOD/kg';
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { maxTicksLimit: 6 }
          },
          y: {
            beginAtZero: false,
            suggestedMin: 0.15,
            suggestedMax: 0.70,
            ticks: {
              callback: (v) => Number(v).toFixed(2)
            }
          }
        }
      }
    });

    canvas.__chart = chart;
  }catch(e){
    // silent (demo-safe)
  }
}

document.addEventListener('DOMContentLoaded', () => {
  if(Array.isArray(window.__mlCharts)){
    window.__mlCharts.forEach(renderMLChart);
  }
});

// Offer filter
document.getElementById('offerSearch')?.addEventListener('input', function(){
  const q = (this.value || '').trim().toLowerCase();
  document.querySelectorAll('#offersTable .offer-row').forEach(row => {
    const name = (row.getAttribute('data-name') || '').toLowerCase();
    row.style.display = name.includes(q) ? '' : 'none';
  });
});

// Chart
<?php if ($ai && ($ai['ok'] ?? false) === true && $product_name !== ''): ?>
(async function(){
  try{
    const url = `../api/ai/price_series.php?product=${encodeURIComponent(<?= json_encode($product_name, JSON_UNESCAPED_UNICODE) ?>)}&market=${encodeURIComponent(<?= json_encode($ai['market'] ?? '') ?>)}&date=${encodeURIComponent(<?= json_encode($today) ?>)}`;
    const res = await fetch(url);
    const data = await res.json();
    if(!data.ok || !data.series || data.series.length < 2) return;

    const labels = data.series.map(x => x.date);
    const prices = data.series.map(x => Number(x.price_qirsh_per_kg) / 100);
    const canvas = document.getElementById(<?= json_encode($chartId) ?>);
    if(!canvas) return;

    new Chart(canvas, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: <?= json_encode(($lang_code==='ar') ? 'سعر السوق (د.أ/كغم)' : 'Market price (JOD/kg)') ?>,
          data: prices,
          tension: 0.25,
          fill: false
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { maxTicksLimit: 6 } }
        }
      }
    });
  }catch(e){
    // silent
  }
})();
<?php endif; ?>
</script>

</body>
</html>
