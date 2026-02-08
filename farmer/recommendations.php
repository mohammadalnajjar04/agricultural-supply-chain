<?php
session_start();
include "../includes/language.php";
include "../config/db.php";
include "../includes/ai_engine.php";
include "../includes/ai_client.php";

// حماية الوصول
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    header("Location: ../auth/login.php");
    exit;
}

$farmer_id = (int)$_SESSION['user_id'];
$current_page = "ai";

// Farmer info (for market)
$stmtF = $conn->prepare("SELECT name, location FROM farmers WHERE farmer_id = ?");
$stmtF->bind_param("i", $farmer_id);
$stmtF->execute();
$farmer = $stmtF->get_result()->fetch_assoc();
$market = ai_market_from_location($farmer['location'] ?? '');

$today = date('Y-m-d');
$selected_date = $_GET['date'] ?? $today;
// validate YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) { $selected_date = $today; }
// safe check
$tsSel = strtotime($selected_date);
if ($tsSel === false) { $selected_date = $today; $tsSel = strtotime($today); }


// ✅ Farmer products (avoid duplicates: pick latest row per product name)
$stmtP = $conn->prepare("
    SELECT p.product_id, p.name, p.price, p.quantity
    FROM products p
    JOIN (
        SELECT MAX(product_id) AS pid
        FROM products
        WHERE farmer_id = ?
        GROUP BY name
    ) t ON p.product_id = t.pid
    ORDER BY p.product_id DESC
");
$stmtP->bind_param("i", $farmer_id);
$stmtP->execute();
$products = $stmtP->get_result();

$cards = [];
while ($p = $products->fetch_assoc()) {
    $forecast = ai_forecast_market_price($conn, $p['name'], $market, $selected_date);
    // New: ML-based "suggested listing price for today" (works even when no DB daily history exists)
    $suggest = ai_ml_suggest_price(
        (string)($p['name'] ?? ''),
        (string)$market,
        (string)$selected_date,
        max(1, (int)($p['quantity'] ?? 1)),
        isset($p['price']) ? (float)$p['price'] : null
    );

    $cards[] = ['product' => $p, 'forecast' => $forecast, 'suggest' => $suggest];
}


// ===== ML (Flask) Smart Market Recommendations =====
$mlRows = [];
$mlServiceOk = true;

// Use current year/month for recommendation
$yearNow = (int)date('Y', $tsSel);
$monthNow = (int)date('n', $tsSel);

foreach ($cards as $c) {
    $p = $c['product'];
    $pname = (string)($p['name'] ?? '');
    $qtyKg = (int)($p['quantity'] ?? 0);

    // Avoid duplicate names
    $key = mb_strtolower(trim($pname));
    if ($key === '' || isset($mlRows[$key])) continue;

    $resp = ai_ml_recommend($pname, $yearNow, $monthNow, max(1, $qtyKg));
    if (!($resp['ok'] ?? false)) {
        $mlServiceOk = false;
        $mlRows[$key] = [
            'product' => $pname,
            'ok' => false,
            'error' => $resp['error'] ?? 'AI service error'
        ];
        continue;
    }

    $data = $resp['data'];
    $mlRows[$key] = [
        'product' => $pname,
        'ok' => true,
        'best_sell' => $data['best_market_to_sell'] ?? null,
        'best_buy'  => $data['best_market_to_buy'] ?? null,
        'all'       => $data['all_markets'] ?? [],
        'hist_mean' => $data['historical_mean_price_jod_for_month'] ?? null
    ];
}

// helper: fetch ML row by product name
function ml_row_for(array $mlRows, string $productName): ?array {
    $k = mb_strtolower(trim($productName));
    return $mlRows[$k] ?? null;
}

?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= ($lang_code === 'ar') ? "توصيات الذكاء الاصطناعي" : "AI Recommendations"; ?></title>

    <!-- Bootstrap + Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/brand.css?v=3">
    <link rel="stylesheet" href="../css/farmer.css?v=210">
    <link rel="stylesheet" href="../css/ai_pages.css?v=1">
    <?php if ($lang_code === 'ar'): ?>
        <link rel="stylesheet" href="../css/style_ar.css?v=210">
    <?php endif; ?>

    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body data-role="farmer">

<!-- SIDEBAR -->
<?php include "../includes/farmer_sidebar.php"; ?>

<div class="menu-overlay"></div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- TOP BAR -->
    <?php include "../includes/farmer_topbar.php"; ?><br>

    <div class="dashboard-box">

        <!-- PAGE TITLE -->
        <h3 class="mt-3 mb-4 page-main-title">
            <i class="fa-solid fa-chart-line text-success"></i>
            <?= ($lang_code === 'ar') ? "توصيات الذكاء الاصطناعي" : "AI Recommendations" ?>
        </h3>

        <!-- Sub header -->
        
        <!-- Date selector (for committee demo) -->
        <form id="aiDateForm" class="row g-2 align-items-end mb-3" method="get">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1"><?= ($lang_code==='ar')?'تاريخ التوقع (للعرض أمام اللجنة)':'Forecast date (for committee demo)' ?></label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($selected_date) ?>"
                       max="2099-12-31" placeholder="2026-01-26">
                <div class="form-text">
                    <?= ($lang_code==='ar')?'مثال: 2026-01-26 (سيتم التوقع حتى لو كان بعد 10/1/2026)':'Example: 2026-01-26 (prediction works even after 2026-01-10)' ?>
                </div>
            </div>
            <div class="col-md-2">
                <button class="btn btn-dark w-100">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <?= ($lang_code==='ar')?'توقع':'Predict' ?>
                </button>
            </div>
            <div class="col-md-6 text-muted small">
                <?= ($lang_code==='ar')?'يعتمد التوقع على بيانات 2024 و 2025 وحتى 2026-01-10 مع نمط يومي وموسمية.':'Forecast uses 2024–2025 history + up to 2026-01-10 and learns daily/season patterns.' ?>
            </div>
        </form>

<div class="text-muted small mb-3">
            <?= ($lang_code === 'ar') ? "السوق المستخدم للتوقع:" : "Market used for forecast:" ?>
            <span class="badge bg-light text-dark"><?= htmlspecialchars($market) ?></span>
            <span class="mx-1">—</span>
            <?= ($lang_code === 'ar') ? "التاريخ:" : "Date:" ?>
            <span class="badge bg-light text-dark"><?= htmlspecialchars($selected_date) ?></span>
            <span class="badge bg-dark-subtle text-dark ms-2">MA(7) × Seasonality</span>
        </div>

        <?php if (count($cards) === 0): ?>
            <div class="alert alert-warning text-center mt-4">
                <?= ($lang_code === 'ar') ? "لا يوجد منتجات لديك حالياً. أضف منتجاً ثم ارجع هنا." : "You have no products yet. Add a product and come back." ?>
            </div>
        <?php endif; ?>

        <?php
        // ---------- KPI summary ----------
        // Use ML "suggested price" as the primary source (works even without DB daily history)
        $okCards = array_filter($cards, fn($c) => (($c['suggest']['ok'] ?? false) === true));
        $okCount = count($okCards);
        $avgExpected = 0.0; // JOD
        $maxUp = null;   // ['name'=>..., 'pct'=>...]
        $maxDown = null; // ['name'=>..., 'pct'=>...]

        if ($okCount > 0) {
            $sum = 0.0;
            foreach ($okCards as $c) {
                $pname = $c['product']['name'] ?? '';
                $pct = (float)($c['suggest']['data']['pct_change'] ?? 0);
                $sum += (float)($c['suggest']['data']['expected_price_today'] ?? 0);
                if ($pct > 0 && ($maxUp === null || $pct > $maxUp['pct'])) $maxUp = ['name'=>$pname,'pct'=>$pct];
                if ($pct < 0 && ($maxDown === null || $pct < $maxDown['pct'])) $maxDown = ['name'=>$pname,'pct'=>$pct];
            }
            $avgExpected = $sum / $okCount;
        }
        ?>

        <div class="row ai-kpi mb-3">
            <div class="col-md-4 mb-3">
                <div class="product-card shadow-sm p-3 bg-white rounded">
                    <div class="text-muted small"><?= ($lang_code==='ar')?'عدد المنتجات المتوقعة':'Forecasted products' ?></div>
                    <div class="kpi-value"><?= (int)$okCount ?></div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="product-card shadow-sm p-3 bg-white rounded">
                    <div class="text-muted small"><?= ($lang_code==='ar')?'متوسط السعر المتوقع اليوم':'Average expected price today' ?></div>
                    <div class="kpi-value"><?= number_format((float)$avgExpected, 2) ?> <?= ($lang_code==='ar')?'د.أ/كغم':'JOD/kg' ?></div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="product-card shadow-sm p-3 bg-white rounded">
                    <div class="text-muted small"><?= ($lang_code==='ar')?'أكبر تغير اليوم':'Biggest change today' ?></div>
                    <div class="small">
                        <?php if ($maxUp): ?>
                            <div class="text-success"><b>▲</b> <?= htmlspecialchars($maxUp['name']) ?> (<?= number_format((float)$maxUp['pct'],1) ?>%)</div>
                        <?php endif; ?>
                        <?php if ($maxDown): ?>
                            <div class="text-danger"><b>▼</b> <?= htmlspecialchars($maxDown['name']) ?> (<?= number_format((float)$maxDown['pct'],1) ?>%)</div>
                        <?php endif; ?>
                        <?php if (!$maxUp && !$maxDown): ?>
                            <div class="text-muted"><?= ($lang_code==='ar')?'لا يوجد تغير واضح':'No clear change' ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <input id="aiSearch" class="form-control ai-search" placeholder="<?= ($lang_code==='ar')?'ابحث باسم المنتج...':'Search by product name...' ?>">
            </div>
        </div>

        <!-- Cards Grid (same as My Products) -->
        <div class="row">
            <?php foreach ($cards as $c):
                $p = $c['product'];
                $fc = $c['forecast'];
                $sg = $c['suggest'];
                $ok = (($sg['ok'] ?? false) === true);

                $trend = $ok ? ((string)($sg['data']['trend'] ?? 'flat')) : 'flat';
                $trendColor = ($trend === 'up') ? 'success' : (($trend === 'down') ? 'danger' : 'secondary');
                $arrow = ($trend === 'up') ? '▲' : (($trend === 'down') ? '▼' : '■');

                $hash = md5($p['name'] . '|' . $market);
                $chartId = 'chartml_' . $hash;
                $modalId = 'whyModal_' . $hash;

                // Products.price is stored as JOD in DB
                $yourPriceJOD = (float)$p['price'];
                $expectedJOD  = $ok ? (float)($sg['data']['expected_price_today'] ?? 0) : 0.0;
                $yestJOD      = $ok ? (float)($sg['data']['expected_price_yesterday'] ?? 0) : 0.0;
                $suggestJOD   = $ok ? (float)($sg['data']['suggested_listing_price'] ?? 0) : 0.0;
                $pctChange    = $ok ? (float)($sg['data']['pct_change'] ?? 0) : 0.0;

                // --- ML (Flask) recommendation per product ---
                $mlRow = ml_row_for($mlRows, (string)($p['name'] ?? ''));
                $mlOk  = (is_array($mlRow) && (($mlRow['ok'] ?? false) === true));
                $bestSellMarket = $mlOk ? (string)($mlRow['best_sell']['market'] ?? '') : '';
                $bestSellPrice  = $mlOk ? (float)($mlRow['best_sell']['predicted_price_jod'] ?? 0) : 0.0;
                $bestBuyMarket  = $mlOk ? (string)($mlRow['best_buy']['market'] ?? '') : '';
                $bestBuyPrice   = $mlOk ? (float)($mlRow['best_buy']['predicted_price_jod'] ?? 0) : 0.0;

                // Next month check: "wait – price higher"
                $waitAdvice = null;
                $nextPrice = null;
                if ($mlOk && $bestSellMarket !== '') {
                    $ny = $yearNow;
                    $nm = $monthNow + 1;
                    if ($nm > 12) { $nm = 1; $ny++; }
                    $nextResp = ai_ml_predict((string)$p['name'], $bestSellMarket, $ny, $nm, max(1, (int)$p['quantity']));
                    if (($nextResp['ok'] ?? false) && isset($nextResp['data']['predicted_price_jod'])) {
                        $nextPrice = (float)$nextResp['data']['predicted_price_jod'];
                        if ($nextPrice > $bestSellPrice * 1.03) {
                            $waitAdvice = 'wait';
                        } else {
                            $waitAdvice = 'sell_now';
                        }
                    }
                }

                // AI badge: recommended if the suggested listing price beats your current price by at least 5%
                $aiRecommended = ($ok && (bool)($sg['data']['badge_ai_recommended'] ?? false));
            ?>
                <div class="col-md-4 mb-4">
                    <div class="product-card shadow-sm p-3 bg-white rounded h-100" data-name="<?= htmlspecialchars(mb_strtolower($p['name'])) ?>">

                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="product-title m-0">
                                <i class="fa-solid fa-leaf text-success"></i>
                                <?= htmlspecialchars($p['name']); ?>
                            </h5>

                            <div class="d-flex gap-2 align-items-center">
                                <?php if ($aiRecommended): ?>
                                    <span class="badge bg-warning text-dark ai-badge">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                                        <?= ($lang_code==='ar') ? 'AI Recommended' : 'AI Recommended' ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($ok): ?>
                                    <span class="badge bg-light text-dark">
                                        <?= ($lang_code==='ar')?'ثقة:':'Confidence:' ?>
                                        <b><?= ($lang_code==='ar') ? 'عالية' : 'High' ?></b>
                                    </span>
                                <?php endif; ?>
                                <span class="badge bg-<?= $trendColor ?>">
                                    <?= $arrow ?> <?= $ok ? number_format($pctChange, 1) . '%' : '' ?>
                                </span>
                            </div>
                        </div>

                        <div class="mt-2">
                            <p class="mb-1">
                                <strong><?= ($lang_code === 'ar' ? 'سعرك الحالي:' : 'Your price:'); ?></strong>
                                <span class="badge bg-light text-dark">
                                    <?= number_format($yourPriceJOD, 2) ?> <?= ($lang_code === 'ar') ? "د.أ/كغم" : "JOD/kg" ?>
                                </span>
                            </p>

                            <?php if ($mlOk): ?>
                                <div class="ai-ml-box mb-2">
                                    <div class="small text-muted mb-1">
                                        <i class="fa-solid fa-robot"></i>
                                        <?= ($lang_code === 'ar') ? 'توصية ML حسب الأسواق' : 'ML market recommendation' ?>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-success-subtle text-success">
                                            <?= ($lang_code==='ar')?'أفضل بيع:':'Best sell:' ?>
                                            <b><?= htmlspecialchars($bestSellMarket) ?></b>
                                            (<?= number_format($bestSellPrice, 2) ?> <?= ($lang_code==='ar')?'د.أ/كغم':'JOD/kg' ?>)
                                        </span>
                                        <span class="badge bg-primary-subtle text-primary">
                                            <?= ($lang_code==='ar')?'أفضل شراء:':'Best buy:' ?>
                                            <b><?= htmlspecialchars($bestBuyMarket) ?></b>
                                            (<?= number_format($bestBuyPrice, 2) ?> <?= ($lang_code==='ar')?'د.أ/كغم':'JOD/kg' ?>)
                                        </span>
                                    </div>

                                    <?php if ($waitAdvice !== null && $nextPrice !== null): ?>
                                        <div class="mt-2 small">
                                            <?php if ($waitAdvice === 'wait'): ?>
                                                <span class="text-success">
                                                    <i class="fa-solid fa-clock"></i>
                                                    <?= ($lang_code==='ar') ? 'توصية: انتظر شهر – السعر أعلى' : 'Advice: wait a month – higher price' ?>
                                                    (<?= number_format($nextPrice, 2) ?>)
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="fa-solid fa-bolt"></i>
                                                    <?= ($lang_code==='ar') ? 'توصية: البيع الآن مناسب' : 'Advice: selling now is fine' ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($ok): ?>
                                <p class="mb-1">
                                    <strong><?= ($lang_code === 'ar' ? 'السعر المتوقع اليوم:' : 'Expected today:'); ?></strong>
                                    <span class="badge bg-success-subtle text-success">
                                        <?= number_format($expectedJOD, 2) ?> <?= ($lang_code === 'ar') ? "د.أ/كغم" : "JOD/kg" ?>
                                    </span>
                                </p>

                                <p class="mb-1">
                                    <strong><?= ($lang_code === 'ar' ? 'السعر المقترح لعرض منتجك:' : 'Suggested listing price:' ); ?></strong>
                                    <span class="badge bg-warning-subtle text-dark">
                                        <?= number_format($suggestJOD, 2) ?> <?= ($lang_code === 'ar') ? "د.أ/كغم" : "JOD/kg" ?>
                                    </span>
                                </p>

                                <p class="mb-2">
                                    <strong><?= ($lang_code === 'ar' ? 'أمس:' : 'Yesterday:'); ?></strong>
                                    <?= number_format($yestJOD, 2) ?> <?= ($lang_code === 'ar') ? "د.أ/كغم" : "JOD/kg" ?>
                                </p>

                                <?php if (!empty($sg['data']['advice'])): ?>
                                    <div class="mb-2 small">
                                        <?php if (($sg['data']['advice'] ?? '') === 'wait_month'): ?>
                                            <span class="text-success">
                                                <i class="fa-solid fa-clock"></i>
                                                <?= ($lang_code==='ar') ? 'توصية: انتظر شهر – السعر أعلى' : 'Advice: wait a month – higher price' ?>
                                                (<?= number_format((float)($sg['data']['next_month_expected'] ?? 0), 2) ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                <i class="fa-solid fa-bolt"></i>
                                                <?= ($lang_code==='ar') ? 'توصية: البيع الآن مناسب' : 'Advice: selling now is fine' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="small text-muted mb-2">
                                    <strong><?= ($lang_code === 'ar' ? 'الطريقة:' : 'Method:'); ?></strong>
                                    <?= htmlspecialchars((string)($sg['data']['explain']['method'] ?? 'MA(7)')) ?>
                                </div>

                                <!-- ML Chart (predicted next months) -->
                                <?php if ($mlOk && $bestSellMarket !== ''): ?>
                                    <div class="mb-2 ai-chart-wrap">
                                        <canvas id="<?= $chartId ?>"></canvas>
                                    </div>
                                    <script>
                                        window.__mlCharts = window.__mlCharts || [];
                                        window.__mlCharts.push({
                                            product: <?= json_encode($p['name'], JSON_UNESCAPED_UNICODE) ?>,
                                            market: <?= json_encode($bestSellMarket, JSON_UNESCAPED_UNICODE) ?>,
                                            startYear: <?= (int)$yearNow ?>,
                                            startMonth: <?= (int)$monthNow ?>,
                                            months: 6,
                                            quantityKg: <?= max(1, (int)$p['quantity']) ?>,
                                            canvasId: <?= json_encode($chartId) ?>
                                        });
                                    </script>
                                <?php endif; ?>

                                <!-- Button -->
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100"
                                        data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>">
                                    <?= ($lang_code === 'ar') ? "ليش هذا السعر؟" : "Why this price?" ?>
                                </button>

                                <!-- Modal -->
                                <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <?= ($lang_code === 'ar') ? "تفاصيل التوقع" : "Forecast details" ?>
                                                    — <?= htmlspecialchars($p['name']) ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <ul class="mb-0">
                                                    <li><b><?= htmlspecialchars((string)($sg['data']['explain']['method'] ?? 'MA(7)')) ?></b></li>
                                                    <li><?= ($lang_code === 'ar' ? 'المؤشر الموسمي:' : 'Seasonal index:'); ?>
                                                        <b><?= isset($sg['data']['explain']['seasonal_index']) ? round((float)$sg['data']['explain']['seasonal_index'], 3) : '—' ?></b>
                                                    </li>
                                                    <li><?= ($lang_code === 'ar' ? 'السعر المتوقع:' : 'Expected:'); ?>
                                                        <b><?= number_format($expectedJOD, 2) ?></b>
                                                        <?= ($lang_code === 'ar') ? "د.أ/كغم" : "JOD/kg" ?>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">
                                                    <?= ($lang_code === 'ar') ? "إغلاق" : "Close" ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php else: ?>
                                <?php if ($mlOk && $bestSellMarket !== ''): ?>
                                    <div class="mb-2 ai-chart-wrap">
                                        <canvas id="<?= $chartId ?>"></canvas>
                                    </div>
                                    <script>
                                        window.__mlCharts = window.__mlCharts || [];
                                        window.__mlCharts.push({
                                            product: <?= json_encode($p['name'], JSON_UNESCAPED_UNICODE) ?>,
                                            market: <?= json_encode($bestSellMarket, JSON_UNESCAPED_UNICODE) ?>,
                                            startYear: <?= (int)$yearNow ?>,
                                            startMonth: <?= (int)$monthNow ?>,
                                            months: 6,
                                            quantityKg: <?= max(1, (int)$p['quantity']) ?>,
                                            canvasId: <?= json_encode($chartId) ?>
                                        });
                                    </script>
                                <?php else: ?>
                                    <div class="alert alert-info mt-2 mb-0 text-center">
                                        <?= ($lang_code === 'ar') ? "لا توجد بيانات كافية للتوقع." : "Not enough data to forecast yet." ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Explanation -->
        <div class="card mt-2">
            <div class="card-body">
                <h6 class="mb-2"><?= ($lang_code === 'ar') ? "كيف يعمل الذكاء الاصطناعي هنا؟" : "How does the AI work here?" ?></h6>
                <ul class="mb-0 small text-muted">
                    <li><?= ($lang_code === 'ar') ? "نستخدم Dataset أسعار سوق يومية للفترة 2024–2026 (Sample) حسب المنتج والسوق." : "We use a daily market price dataset (2024–2026 sample) per product & market." ?></li>
                    <li><?= ($lang_code === 'ar') ? "نحسب متوسط متحرك لآخر 7 أيام، ثم نعدله بمؤشر موسمي حسب شهر السنة." : "We compute a 7-day moving average and adjust using a monthly seasonal index." ?></li>
                    <li><?= ($lang_code === 'ar') ? "نقارن التوقع بسعر أمس لإظهار الاتجاه (ارتفاع/انخفاض)." : "We compare against yesterday to show trend (up/down)." ?></li>
                    <li><?= ($lang_code === 'ar') ? "كما نستخدم خدمة Python (Flask) لبناء نموذج ML يتوقع الأسعار شهرياً ويقترح أفضل سوق للبيع/الشراء." : "We also use a Python (Flask) ML service to predict monthly prices and recommend the best sell/buy market." ?></li>
                    <li><?= ($lang_code === 'ar') ? "الرسم البياني يعرض توقعات الأشهر القادمة، مع توصية تلقائية (بيع الآن أو انتظر شهر)." : "The chart shows the next months predictions with an automatic advice (sell now or wait a month)." ?></li>
                </ul>
            </div>
        </div>

    </div>
</div>

<!-- Bootstrap JS (needed for modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Charts Render -->
<script>
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
  const search = document.getElementById('aiSearch');
  if (search) {
    search.addEventListener('input', function(){
      const q = (this.value || '').trim().toLowerCase();
      document.querySelectorAll('.product-card[data-name]').forEach(card => {
        const name = (card.getAttribute('data-name') || '').toLowerCase();
        const col = card.closest('.col-md-4');
        if (!col) return;
        col.style.display = name.includes(q) ? '' : 'none';
      });
    });
  }
});
</script>

<script src="../js/farmer.js"></script>
</body>
</html>
