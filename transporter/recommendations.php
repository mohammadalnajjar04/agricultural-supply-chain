<?php
session_start();
include "../includes/language.php";
include "../config/db.php";
include "../includes/ai_engine.php";
include "../includes/ai_client.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'transporter') {
    header("Location: ../auth/login.php");
    exit;
}

$transporter_id = (int)$_SESSION['user_id'];
$current_page = "ai";
$today = date('Y-m-d');

$ai = ai_transporter_recommendations($conn, $transporter_id, $today, 5);
$recs = $ai['recommendations'] ?? [];

// KPIs
$recCount = count($recs);
$nearest = null;
$avgFee = null;
if ($recCount > 0) {
    $sumFee = 0.0; $feeCount = 0;
    foreach ($recs as $r) {
        if ($nearest === null && $r['km_driver_to_farm'] !== null) $nearest = (float)$r['km_driver_to_farm'];
        if ($r['delivery_fee_jd'] !== null) { $sumFee += (float)$r['delivery_fee_jd']; $feeCount++; }
    }
    $avgFee = ($feeCount > 0) ? ($sumFee / $feeCount) : null;
}

// ===== ML Boost (Flask) + Stronger Ranking for Transporter =====
$mlCache = [];
$mlEnabled = true;
$mlError = null;
$yearNow = (int)date('Y');
$monthNow = (int)date('n');

function tp_market_from_location(string $loc): string {
    $l = strtolower($loc);
    if (strpos($l, 'amman') !== false) return 'Amman';
    if (strpos($l, 'irbid') !== false) return 'Irbid';
    if (strpos($l, 'zarqa') !== false) return 'Zarqa';
    if (strpos($l, 'aqaba') !== false) return 'Aqaba';
    // fallback: first word capitalized
    $l = trim(preg_replace('/\s+/', ' ', $l));
    $parts = explode(' ', $l);
    $p = $parts[0] ?? '';
    return $p ? ucfirst($p) : '';
}

// cache ML per product (avoid many calls)
$uniqueProducts = [];
foreach ($recs as $r) {
    $p = (string)($r['product'] ?? '');
    if ($p !== '') $uniqueProducts[$p] = true;
}

foreach (array_keys($uniqueProducts) as $p) {
    $resp = ai_ml_recommend($p, $yearNow, $monthNow, 500);
    if (($resp['ok'] ?? false) === true) {
        $mlCache[$p] = $resp['data'];
    } else {
        $mlEnabled = false;
        $mlError = $resp['error'] ?? null;
    }
}

// Build stronger score for each recommendation
foreach ($recs as &$r) {
    $kmTotal = (float)($r['km_total'] ?? 0);
    $fee = (float)($r['delivery_fee_jd'] ?? 0);

    // Efficiency: higher fee per km is better (normalized 0..1)
    $eff = ($kmTotal > 0.1) ? ($fee / ($kmTotal + 1.0)) : 0.0;
    $effN = min(1.0, $eff / 2.0); // 2 JOD/km is "very good" in this demo scale

    // Urgency: closer transport date => higher priority
    $urg = 0.0;
    if (!empty($r['transport_date'])) {
        $days = (strtotime($r['transport_date']) - strtotime(date('Y-m-d'))) / 86400.0;
        if ($days <= 0) $urg = 1.0;
        else $urg = max(0.0, 1.0 - min(1.0, $days / 7.0));
    }

    // Demand: if destination market matches best sell market -> boost
    $prod = (string)($r['product'] ?? '');
    $destMarket = tp_market_from_location((string)($r['store_location'] ?? ''));
    $demand = 0.35; // baseline
    $bestSell = '';
    $trend = '';
    if ($mlEnabled && $prod !== '' && isset($mlCache[$prod])) {
        $bestSell = (string)($mlCache[$prod]['best_market_to_sell']['market'] ?? '');
        $all = $mlCache[$prod]['all_markets'] ?? [];
        $top2 = [];
        if (is_array($all)) {
            usort($all, fn($a,$b) => ((float)($b['predicted_price_jod']??0)) <=> ((float)($a['predicted_price_jod']??0)));
            $top2 = array_slice($all, 0, 2);
        }
        $inTop2 = false;
        foreach ($top2 as $m) {
            if (strcasecmp((string)($m['market'] ?? ''), $destMarket) === 0) $inTop2 = true;
        }

        if ($destMarket !== '' && $bestSell !== '' && strcasecmp($destMarket, $bestSell) === 0) $demand = 1.0;
        else if ($destMarket !== '' && $inTop2) $demand = 0.65;

        // simple trend hint if available
        $trend = ($bestSell !== '') ? $bestSell : '';
    }

    $score = (0.55 * $effN) + (0.30 * $demand) + (0.15 * $urg);
    $r['ai_score'] = round($score * 100, 1);
    $r['dest_market'] = $destMarket;
    $r['best_sell_market'] = $bestSell;
    $r['urgency'] = round($urg * 100, 0);
}
unset($r);

// Sort by AI score (desc)
usort($recs, function($a, $b){
    return ((float)($b['ai_score'] ?? 0)) <=> ((float)($a['ai_score'] ?? 0));
});

// Prepare chart data (top 6)
$chartLabels = [];
$chartScores = [];
foreach (array_slice($recs, 0, 6) as $r) {
    $chartLabels[] = ' #' . ($r['request_id'] ?? '') . ' - ' . ($r['product'] ?? '');
    $chartScores[] = (float)($r['ai_score'] ?? 0);
}

?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>" dir="<?= ($lang_code === 'ar') ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8" />
    <title><?= ($lang_code === 'ar') ? "توصيات الذكاء الاصطناعي" : "AI Recommendations" ?></title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
</head>
<body data-role="transporter">

<!-- SIDEBAR -->
<?php include "../includes/transporter_sidebar.php"; ?>

<div class="menu-overlay"></div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <?php include "../includes/transporter_topbar.php"; ?><br>

    <div class="dashboard-box">

        <h3 class="mt-3 mb-4 page-main-title">
            <i class="fa-solid fa-route text-success"></i>
            <?= ($lang_code === 'ar') ? "توصيات الذكاء الاصطناعي (الناقل)" : "AI Recommendations (Transporter)" ?>
        </h3>

        <div class="ai-card-subtitle mb-3">
            <?= ($lang_code === 'ar')
                ? "نقوم بترتيب الطلبات حسب أقرب مزرعة لموقعك (pickup) ثم نعرض أجرة توصيل تقديرية."
                : "We rank requests by the closest farm to your location and show an estimated delivery fee."; ?>
        </div>

        <?php if (($ai['ok'] ?? false) !== true): ?>
            <div class="alert alert-danger text-center">
                <?= ($lang_code === 'ar') ? "تعذر توليد التوصيات." : "Failed to generate recommendations." ?>
            </div>
        <?php else: ?>

            <div class="text-muted small mb-3">
                <?= ($lang_code === 'ar') ? "موقعك:" : "Your location:" ?>
                <span class="badge bg-light text-dark"><?= htmlspecialchars($ai['transporter']['location'] ?? '') ?></span>
                <span class="mx-1">—</span>
                <?= ($lang_code === 'ar') ? "التاريخ:" : "Date:" ?>
                <span class="badge bg-light text-dark"><?= htmlspecialchars($today) ?></span>
                <span class="badge bg-dark-subtle text-dark ms-2"><?= ($lang_code === 'ar') ? "ترتيب حسب المسافة" : "Distance ranking" ?></span>
            </div>

            <div class="row ai-kpi mb-3">
                <div class="col-md-4 mb-3">
                    <div class="product-card shadow-sm p-3 bg-white rounded">
                        <div class="text-muted small"><?= ($lang_code==='ar') ? 'طلبات مقترحة' : 'Recommended requests' ?></div>
                        <div class="kpi-value"><?= (int)$recCount ?></div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="product-card shadow-sm p-3 bg-white rounded">
                        <div class="text-muted small"><?= ($lang_code==='ar') ? 'أقرب مزرعة (كم)' : 'Nearest pickup (km)' ?></div>
                        <div class="kpi-value"><?= ($nearest !== null) ? number_format((float)$nearest, 1) : '—' ?></div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="product-card shadow-sm p-3 bg-white rounded">
                        <div class="text-muted small"><?= ($lang_code==='ar') ? 'متوسط الأجرة' : 'Average fee' ?></div>
                        <div class="kpi-value"><?= ($avgFee !== null) ? number_format((float)$avgFee, 2) : '—' ?> <?= ($lang_code==='ar') ? 'د.أ' : 'JOD' ?></div>
                    </div>
                </div>
            </div>

<div class="row mb-3">
    <div class="col-12">
        <div class="product-card shadow-sm p-3 bg-white rounded">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h5 class="mb-0"><?= ($lang_code === 'ar') ? "قوة التوصيات (AI Score)" : "Recommendation Strength (AI Score)" ?></h5>
                    <div class="text-muted small">
                        <?= ($lang_code === 'ar') ? "كلما ارتفعت النسبة، كانت الفرصة أفضل (حسب المسافة/الأجرة/الطلب/الوقت)." : "Higher score means better opportunity (distance/fee/demand/time)." ?>
                    </div>
                </div>
                <?php if (!empty($mlError)): ?>
                    <span class="badge bg-warning-subtle text-warning">
                        <?= ($lang_code==='ar') ? "وضع تجريبي بدون Flask" : "Demo mode (Flask unavailable)" ?>
                    </span>
                <?php endif; ?>
            </div>
            <div style="height:220px;">
                <canvas id="tpScoreChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('tpScoreChart');
    if(!ctx) return;
    const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
    const scores = <?= json_encode($chartScores, JSON_UNESCAPED_UNICODE) ?>;

    const grad = ctx.getContext('2d').createLinearGradient(0, 0, 0, 220);
    grad.addColorStop(0, 'rgba(59, 130, 246, 0.35)');
    grad.addColorStop(1, 'rgba(59, 130, 246, 0.05)');

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: <?= json_encode(($lang_code==='ar') ? 'AI Score %' : 'AI Score %') ?>,
          data: scores,
          backgroundColor: grad,
          borderWidth: 1,
          borderRadius: 10
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (c) => (Number(c.parsed.y).toFixed(1) + '%')
            }
          }
        },
        scales: {
          x: { grid: { display:false } },
          y: {
            beginAtZero: true,
            suggestedMax: 100,
            ticks: { callback: (v) => v + '%' }
          }
        }
      }
    });
  });
</script>

            <div class="row mb-3">
                <div class="col-md-6">
                    <input id="aiSearch" class="form-control ai-search" placeholder="<?= ($lang_code==='ar')?'ابحث (منتج/مزارع/متجر)...':'Search (product/farmer/store)...' ?>">
                </div>
            </div>

            <?php if ($recCount === 0): ?>
                <div class="alert alert-info text-center">
                    <?= ($lang_code === 'ar') ? "لا توجد طلبات نقل متاحة حالياً." : "No transport requests available right now." ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($recs as $r): ?>
                        <?php $searchKey = mb_strtolower(($r['product'] ?? '').' '.($r['farmer'] ?? '').' '.($r['store'] ?? '')); ?>
                        <div class="col-md-4 mb-4 rec-col">
                            <div class="product-card shadow-sm p-3 bg-white rounded h-100" data-name="<?= htmlspecialchars($searchKey) ?>">

                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="text-muted small"><?= ($lang_code === 'ar') ? "طلب" : "Request" ?> #<?= (int)($r['request_id'] ?? 0) ?></div>
                                        <h5 class="product-title m-0">
                                            <i class="fa-solid fa-box text-success"></i>
                                            <?= htmlspecialchars($r['product'] ?? '') ?>
                                        </h5>
                                        <div class="ai-card-subtitle mt-1">
                                            <?= ($lang_code === 'ar') ? "الكمية:" : "Quantity:" ?> <b><?= (int)($r['quantity'] ?? 0) ?></b> kg
                                        </div>
                                    </div>
                                    <span class="badge bg-success-subtle text-success">
                                        <?= ($lang_code === 'ar') ? "مقترح" : "Recommended" ?>
                                    </span>
                                    <span class="badge bg-dark-subtle text-dark ms-2">
                                        <?= ($lang_code === 'ar') ? "قوة" : "Score" ?>:
                                        <b><?= htmlspecialchars((string)($r['ai_score'] ?? 0)) ?>%</b>
                                    </span>
                                </div>

                                <hr>

                                <div class="small">
                                    <div class="mb-2">
                                        <div class="text-muted"><?= ($lang_code === 'ar') ? "المزرعة" : "Farm" ?></div>
                                        <div><b><?= htmlspecialchars($r['farmer'] ?? '') ?></b></div>
                                        <div class="text-muted small"><?= htmlspecialchars($r['farm_location'] ?? '') ?></div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="text-muted"><?= ($lang_code === 'ar') ? "المتجر" : "Store" ?></div>
                                        <div><b><?= htmlspecialchars($r['store'] ?? '') ?></b></div>
                                        <div class="text-muted small"><?= htmlspecialchars($r['store_location'] ?? '') ?></div>
                                    <?php if (!empty($r['best_sell_market'])): ?>
                                        <div class="text-muted small mt-1">
                                            <i class="fa-solid fa-chart-line"></i>
                                            <?= ($lang_code==='ar') ? 'أفضل طلب حالياً في سوق: ' : 'Best demand now in: ' ?>
                                            <b><?= htmlspecialchars((string)$r['best_sell_market']) ?></b>
                                            <?php if (!empty($r['dest_market'])): ?>
                                                <span class="ms-1">(<?= ($lang_code==='ar') ? 'وجهتك:' : 'Your dest:' ?> <?= htmlspecialchars((string)$r['dest_market']) ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge bg-light text-dark">
                                        <?= ($lang_code === 'ar') ? "كم إلى المزرعة" : "Km to farm" ?>:
                                        <b><?= $r['km_driver_to_farm'] !== null ? htmlspecialchars((string)$r['km_driver_to_farm']) : '-' ?></b>
                                    </span>
                                    <span class="badge bg-light text-dark">
                                        <?= ($lang_code === 'ar') ? "كم إجمالي" : "Total km" ?>:
                                        <b><?= $r['km_total'] !== null ? htmlspecialchars((string)$r['km_total']) : '-' ?></b>
                                    </span>
                                    <span class="badge bg-primary-subtle text-primary">
                                        <?= ($lang_code === 'ar') ? "أجرة" : "Fee" ?>:
                                        <b><?= $r['delivery_fee_jd'] !== null ? number_format((float)$r['delivery_fee_jd'], 2) : '-' ?></b>
                                        <?= ($lang_code === 'ar') ? "د.أ" : "JOD" ?>
                                    </span>
                                </div>

                                <div class="mt-3 d-grid">
                                    <a class="btn btn-success" href="accept_request.php?id=<?= (int)($r['request_id'] ?? 0) ?>">
                                        <i class="fa fa-check"></i> <?= ($lang_code === 'ar') ? "اقبل الطلب" : "Accept Request" ?>
                                    </a>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="alert alert-info mt-2 mb-0">
                    <?= ($lang_code === 'ar')
                        ? "ملاحظة: توحيد كتابة المواقع (عربي/إنجليزي) يزيد دقة حساب المسافة."
                        : "Note: Distance accuracy improves when locations are standardized (Arabic/English)."; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('aiSearch')?.addEventListener('input', function(){
  const q = (this.value || '').trim().toLowerCase();
  document.querySelectorAll('.product-card[data-name]').forEach(card => {
    const name = (card.getAttribute('data-name') || '').toLowerCase();
    const col = card.closest('.rec-col');
    if (!col) return;
    col.style.display = name.includes(q) ? '' : 'none';
  });
});
</script>

</body>
</html>
