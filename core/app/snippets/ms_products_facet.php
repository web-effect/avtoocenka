<?php
$categoryId = $modx->resource->get('id');

$limitBase = 3; // сколько показываем по умолчанию на одной странице
$page = max(1, (int)($_REQUEST['page'] ?? 1)); // текущая страница
$extraLoad = max(0, (int)($_REQUEST['loadmore'] ?? 0)); // сколько раз нажата кнопка "ещё"

// пример: если page=2 и loadmore=1 → limit = 12 + (1 * 12) = 24
$limit = $limitBase + ($extraLoad * $limitBase);
$offset = ($page - 1) * $limitBase;

//параметры из url
$props = [];

foreach ($_REQUEST as $key => $val) {
    if (preg_match('/sort|page|min_|max_|filter_/i', $key)) {
        $props[$key] = $val;
        if (preg_match('/filter_/i', $key) && gettype($val) == 'string') {
            
            preg_match_all('/\d+(,\d+)?\s*[^,]+/', $val, $matches1);
            
            if(count($matches1[0]) > 0){
                $props[$key] = $matches1[0];
            } 
            else {
                $props[$key] = explode(',', $val);
            }
    	}
    }
}

// Массивы для join и where
$joins = [];
$where = [];
    
foreach ($props as $filterKey => $values) {
    // Пропускаем пустые значения
    if (empty($values)) {
        continue;
    }

    // Извлекаем "strana" из "filter_strana"
    $optionKey = str_replace('filter_', '', $filterKey);
    
    if($optionKey != 'page' && $optionKey != 'sort' && $optionKey !== 'min_price' & $optionKey !== 'max_price'){
        
        // alias для join, например "optStrana"
        $alias = 'opt' . ucfirst($optionKey);
    
        // LEFT JOIN
        $joins[] = [
            'class' => 'msProductOption',
            'alias' => $alias,
            'on' => "$alias.product_id = msProduct.id AND $alias.key = '$optionKey'"
        ];
    
        // WHERE условие
        $where["{$alias}.value:IN"] = $values;
    }
}

$props_price_join = [];
$props_price_where = [];

foreach ($props as $key => $val) {
    if ($key == 'sort') {
        $s = explode('_', $val);
        if (isset($s[0])) {
            if (in_array($s[0], ['price'])) {
                $sortby = "product_data.price";
            }
            if(in_array($s[0], ['pagetitle'])){
                $sortby = 'msProduct.pagetitle';
            }
            if (isset($s[1]) && $s[1] == 'desc') {
                $sortdir = 'DESC';
            }else{
                $sortdir = 'ASC';
            }
        }
    }
    
    if (preg_match('/min_|max_/i', $key)) {
        $keys = explode('_', $key);
        $c = $keys[0] == 'min' ? '>=' : '<=';
        if ($keys[1] == 'price') {
            $props_price_where[] = "product_data.price {$c} " . (int)$val;
        }
    }
}

$modx->log(1, print_r($props_price_where, 1));

// Создаём запрос
$query = $modx->newQuery('msProduct');
$query->leftJoin('msProductData', 'product_data', 'product_data.id = msProduct.id');
foreach ($joins as $join) {
    $query->leftJoin($join['class'], $join['alias'], $join['on']);
}

$query->where([
    'parent' => $categoryId
]);

$query->where($where);
$query->where($props_price_where);
$query->select([
    'msProduct.id',
    'msProduct.pagetitle',
    'msProduct.uri',
    'product_data.price',
    'product_data.old_price',
    'product_data.new',
    'product_data.popular',
    'product_data.favorite',
]);
$sortby = isset($sortby) ? $sortby : 'msProduct.menuindex';
$sortdir = isset($sortdir) ? $sortdir : 'ASC';
if(isset($sortby) && !empty($sortby) && isset($sortdir) && !empty($sortdir)){
    $query->sortby($sortby, $sortdir);
}
$query->limit($limit, $offset);



// Генерация пагинации
function renderPagination(int $totalPages, int $currentPage): string {
    global $modx;
    
    if ($totalPages <= 1) return '';

    $html = '<ul class="pagination-list">';
    $pages = [];

    // Первые 2 страницы
    for ($i = 1; $i <= 2 && $i <= $totalPages; $i++) {
        $pages[] = $i;
    }

    // Текущая страница и по 1 слева и справа
    for ($i = $currentPage - 1; $i <= $currentPage + 1; $i++) {
        if ($i > 2 && $i < $totalPages - 1) {
            $pages[] = $i;
        }
    }

    // Последние 2 страницы
    for ($i = $totalPages - 1; $i <= $totalPages; $i++) {
        if ($i > 2) {
            $pages[] = $i;
        }
    }

    // Удаляем дубликаты и сортируем
    $pages = array_unique($pages);
    sort($pages);

    // Выводим страницы с ...
    $last = 0;
    
    $alias = $modx->resource->get('alias');
    
    foreach ($pages as $page) {
        if ($page - $last > 1) {
            $html .= '<li class="pagination-list-skip">...</li>';
        }

        if ($page == $currentPage) {
            $html .= '<li class="pagination-list-active">' . $page . '</li>';
        } else {
            $html .= '<li><a class="" href="catalog/' . $alias . '" data-page="' . $page . '">' . $page . '</a></li>';
        }

        $last = $page;
    }

    $html .= '</ul>';
    return $html;
}



// Подсчет количество товарных предложений
$countQuery = $modx->newQuery('msProduct');
$countQuery->leftJoin('msProductData', 'product_data', 'product_data.id = msProduct.id');
foreach ($joins as $join) {
    $countQuery->leftJoin($join['class'], $join['alias'], $join['on']);
}

$countQuery->where([
    'msProduct.parent:IN' => [$categoryId]
]);
$countQuery->where($where);
$countQuery->where($props_price_where);
$countQuery->select('COUNT(msProduct.id)');
$countQuery->prepare();
$countQuery->stmt->execute();
$total = $countQuery->stmt->fetchColumn();

$totalinpage = $page * $limitBase;
$totalloadmore = $extraLoad * $limitBase;

$is_loadmore = true;

if($total <= ($totalinpage + $totalloadmore)){
    $is_loadmore = false;
}


// Готовим и выполняем
if ($query->prepare() && $query->stmt->execute()) {
    $products = $query->stmt->fetchAll(PDO::FETCH_ASSOC);
    return [
        'page' => $page,
        'loadmore' => $extraLoad,
        'is_loadmore' => $is_loadmore,
        'total_products' => $total, 
        'total_pages' => ceil($total / $limitBase), 
        'pagination' => renderPagination(ceil($total / $limitBase), $page),
        'items' => $products
    ];
} else {
    echo "Ошибка выполнения запроса.";
}