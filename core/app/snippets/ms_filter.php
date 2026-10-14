<?php
$props = [];

$id = $modx->resource->id;

if ($id == 276) {
    $id = 11;
    $favorites = isset($_COOKIE['favorites']) ? $_COOKIE['favorites'] : '';
    $favorites = explode(',', $favorites);
}

$childIds = $modx->getChildIds($id, 10, ['context' => $modx->context->key]);
$childIds[] = $id;

// $modx->log(1, print_r($id, 1));

// цены
$q = $modx->newQuery('msProductData');
$q->select(['MIN(price) as min', 'MAX(price) as max']);
$q->innerJoin('msProduct', 'res', 'res.id = msProductData.id');
$where = [
    "res.published" => 1,
    "res.deleted" => 0,
    "res.parent:IN" => $childIds,
];
if ($modx->resource->id == 276) {
    $where['msProductData.id:IN'] = $favorites;
}
$q->where($where);
$q->prepare();
$q->stmt->execute();
$prices = $q->stmt->fetch(\PDO::FETCH_ASSOC);


$modx->log(1, print_r($where, 1));

if(isset($prices, $prices['min'], $prices['max'])) {
    $props['price'] = [
        'label' => 'Цена',
        'type' => 'range',
        'min' => (int)$prices['min'],
        'max' => (int)$prices['max'],
    ];
}

//опции товара
$q_result2 = [];

$filters = array();

foreach ($props as $name => &$prop) {
    $filters[$name] = $prop;
}

$fields = $modx->getFields('msProductData');
$q = $modx->newQuery('msProductOption');
if (array_key_exists('sku_id', $fields)) {
    $q->innerJoin('msProductData', 'Data', 'Data.sku_id = msProductOption.product_id');
    $q->leftJoin('msOption', 'opt', 'opt.key = msProductOption.key');
    $q->where(array('Data.id:IN' => $childIds, 'opt.key:NOT IN' => ['artikul-proizvoditelya']));
    $q->select('Data.id as product_id, msProductOption.key, msProductOption.value, opt.caption');
} else {
    $q->select([
        'msProductOption.key',
        'msProductOption.value',
        'opt.caption',
        'opt.type',
    ]);
    $q->select('msProductOption.product_id, msProductOption.key, msProductOption.value, opt.caption, opt.type');
    $q->leftJoin('msOption', 'opt', 'opt.key = msProductOption.key');
    if ($modx->resource->id == 276) {
        $q->where(array('msProductOption.product_id:IN' => $favorites));
    }
}

$tstart = microtime(true);
if ($q->prepare() && $q->stmt->execute()) {
    $modx->queryTime += microtime(true) - $tstart;
    $modx->executedQueries++;
    
    if ($rows = $q->stmt->fetchAll(PDO::FETCH_ASSOC)) {
        $ids_flip = array_flip($childIds);
        
        foreach ($rows as $row) {
            if (!isset($ids_flip[$row['product_id']])) {
                continue;
            }

            $value = str_replace('"', '"', trim($row['value']));
            $key = $row['key'];
            $caption = $row['caption'];

            // Обработка формата "key==value"
            if (strpos($value, '==')) {
                list($key, $value) = explode('==', $value);
                $key = preg_replace('/\s+/', '_', $key);
            }

            // Запись уникальных значений в фильтры
            if (!isset($filters[$key]) && !empty($value)) {
                //$modx->log(1, print_r('New KEY = '.$key, 1));
                $type = $type == 'numberfield' ? 'range' : 'select';
                $filters[$key]['type'] = $type;
                $filters[$key]['label'] = $caption;
                
                if ($key == 'size') {
                    $filters[$key]['label'] = 'Размер';
                }
                if ($key == 'color') {
                    $filters[$key]['label'] = 'Цвет';
                }
            }

            if (!in_array($value, $filters[$key]['values'], true) && !empty($value)) {
                $filters[$key]['values'][] = $value;
            }
        }
    }
} else {
    $modx->log(modX::LOG_LEVEL_ERROR, "[mSearch2] Error on get filter params.\nQuery: " . $q->toSQL() . "\nResponse: " . print_r($q->stmt->errorInfo(), 1));
}

// Фильтруем массив, оставляя только элементы с непустым "values"
// $filteredData = array_filter($filters, function ($item, $key) {
//     return $key === 'price' || (isset($item['values']) && !empty($item['values']));
// }, ARRAY_FILTER_USE_BOTH);

//$modx->log(1, print_r($filters, 1));

return $filters;