<div class="top-bar">

    <div class="top-left">
        <span class="menu-btn d-lg-none"><i class="fa-solid fa-bars"></i></span>

        <span class="brand">
            <i class="fa-solid fa-seedling"></i>
            <?=($lang_code==="ar"?"المزارع":"Farmer");?>
        </span>
    </div>

    <div class="top-center">
        <h2 class="page-title">
            <?php
            $titles = [
                "dashboard"        => ["لوحة تحكم المزارع","Farmer Dashboard","fa-gauge"],
                "add_product"      => ["إضافة منتج","Add Product","fa-plus"],
                "my_products"      => ["منتجاتي","My Products","fa-boxes-stacked"],
                "transport_new"    => ["طلب نقل","Create Transport Request","fa-truck-arrow-right"],
                "transport_list"   => ["طلبات النقل","Transport Requests","fa-list-check"],
                "ai"               => ["توصيات الذكاء الاصطناعي","AI Recommendations","fa-robot"],
            ];

            // Safe fallback to avoid warnings if a page forgets to set $current_page
            $pageKey = isset($titles[$current_page]) ? $current_page : 'dashboard';
            $icon = $titles[$pageKey][2];
            $title = ($lang_code==="ar") ? $titles[$pageKey][0] : $titles[$pageKey][1];

            echo "<i class='fa-solid $icon'></i> $title";
            ?>
        </h2>
    </div>

    <div class="top-right">

        <div class="lang-switch">
            <a href="<?= htmlspecialchars(url_with_lang('en')) ?>">English</a> |
            <a href="<?= htmlspecialchars(url_with_lang('ar')) ?>">العربية</a>
        </div>

    </div>

</div>
