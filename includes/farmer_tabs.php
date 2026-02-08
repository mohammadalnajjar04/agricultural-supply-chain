<div class="top-tabs">

    <a href="dashboard.php" class="tab-link <?=($current_page==='dashboard'?'active':'');?>">
        <i class="fa-solid fa-gauge"></i>
        <?=($lang_code==="ar"?"لوحة التحكم":"Dashboard");?>
    </a>

    <a href="add_product.php" class="tab-link <?=($current_page==='add_product'?'active':'');?>">
        <i class="fa-solid fa-plus"></i>
        <?=($lang_code==="ar"?"إضافة منتج":"Add Product");?>
    </a>

    <a href="my_products.php" class="tab-link <?=($current_page==='my_products'?'active':'');?>">
        <i class="fa-solid fa-boxes-stacked"></i>
        <?=($lang_code==="ar"?"منتجاتي":"My Products");?>
    </a>

    <a href="transport_request.php" class="tab-link <?=($current_page==='transport_new'?'active':'');?>">
        <i class="fa-solid fa-truck-arrow-right"></i>
        <?=($lang_code==="ar"?"طلب نقل":"Transport Request");?>
    </a>

    <a href="transport_requests.php" class="tab-link <?=($current_page==='transport_list'?'active':'');?>">
        <i class="fa-solid fa-list-check"></i>
        <?=($lang_code==="ar"?"طلبات النقل":"Transport Requests");?>
    </a>

</div>
