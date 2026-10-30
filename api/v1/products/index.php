<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/_bootstrap.php';
api_require_method('GET');
api_require_role(['store']);

$limitRaw = $_GET['limit'] ?? '20';
$pageRaw = $_GET['page'] ?? '1';
if (!is_string($limitRaw) || !ctype_digit($limitRaw) || !is_string($pageRaw) || !ctype_digit($pageRaw)) {
    api_response(422, ['success' => false, 'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'page and limit must be positive integers']]);
}
$limit = filter_var($limitRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
$page = filter_var($pageRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
if ($limit === false || $page === false) {
    api_response(422, ['success' => false, 'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'page must be 1..100000 and limit 1..100']]);
}

try {
    require_once dirname(__DIR__, 3) . '/config/db.php';
    require_once dirname(__DIR__, 3) . '/app/Services/ProductCatalog.php';
    $catalog = new ProductCatalog($conn);
    $rows = $catalog->forStore($limit, ($page - 1) * $limit);
    api_response(200, ['success' => true, 'data' => $rows, 'pagination' => ['page' => $page, 'limit' => $limit, 'returned' => count($rows)]]);
} catch (Throwable $e) {
    error_log('Product catalog API failure: ' . $e->getMessage());
    api_response(500, ['success' => false, 'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Unable to load products']]);
}
