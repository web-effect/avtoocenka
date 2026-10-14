<?php
$start = microtime(true); // Запоминаем стартовое время
$catalogId = [$modx->resource->get('id')];



//параметры из url
$props = [];
$props1 = [];
    
foreach ($_REQUEST as $key => $val) {
    if (preg_match('/sort|page|min_|max_|filter_/i', $key)) {
        $props1[$key] = $val;
        if (preg_match('/filter_/i', $key) && gettype($val) == 'string') {
            
            preg_match_all('/\d+(,\d+)?\s*[^,]+/', $val, $matches1);
            
            if(count($matches1[0]) > 0){
                $props1[$key] = $matches1[0];
            } 
            else {
                $props1[$key] = explode(',', $val);
            }
    	}
    }
}


$filters = $props1;

$result = [];

foreach ($filters as $key => $values) {
    if($key != 'page' && $key != 'sort' && $key != 'min_price' & $key != 'max_price'){
        $option = str_replace('filter_', '', $key);

        $otherFilters = array_diff_key($filters, [$key => $values]);
        $formatted = [];
    
        foreach ($otherFilters as $otherKey => $otherValues) {
            if($otherKey != 'page' && $otherKey != 'sort' && $otherKey != 'min_price' & $otherKey != 'max_price'){
                $shortKey = str_replace('filter_', '', $otherKey);
                $formatted[$shortKey] = $otherValues;
            }
        }
    
        $result[$option] = $formatted;
    }
}


// Фильтрация по цене
$props_price_where = [];
foreach ($props1 as $key => $val) {
    if (preg_match('/min_|max_/i', $key)) {
        $keys = explode('_', $key);
        $c = $keys[0] == 'min' ? '>=' : '<=';
        if ($keys[1] == 'price') {
            $props_price_where[] = "product_data.price {$c} " . (int)$val;
        }
    }
}



// Расчет всех опций без фильтров
if(count($result) <= 0){
    $q = $modx->newQuery('msProductOption');
    
    $q->select([
        'msProductOption.product_id',
        'msProductOption.key',
        'msProductOption.value',
        'opt.caption',
        'res.parent AS product_parent_id'
    ]);
    
    $q->leftJoin('msOption', 'opt', 'opt.key = msProductOption.key');
    $q->leftJoin('modResource', 'res', 'res.id = msProductOption.product_id');
    $q->leftJoin('msProductData', 'product_data', 'product_data.id = msProductOption.product_id');
    
    $q->where([
        'res.parent:IN' => $catalogId
    ]); 
    $q->where($props_price_where);
    $q->prepare();
    
    if ($q->stmt->execute()) {
        if ($rows = $q->stmt->fetchAll(PDO::FETCH_ASSOC)) {
            $unique = [];
            $counts1 = [];
            
            // цены
            $q = $modx->newQuery('msProductData');
            $q->select(['MIN(price) as min', 'MAX(price) as max']);
            $q->innerJoin('msProduct', 'res', 'res.id = msProductData.id');
            $where = [
                "res.parent:IN" => $catalogId,
            ];
            $q->where($where);
            $q->prepare();
            $q->stmt->execute();
            $prices = $q->stmt->fetch(\PDO::FETCH_ASSOC);
            
            if(isset($prices, $prices['min'], $prices['max'])) {
                $counts1['price'] = [
                    'label' => 'Цена',
                    'type' => 'range',
                    'min' => (int)$prices['min'],
                    'max' => (int)$prices['max'],
                ];
            }
            
            foreach ($rows as $item) {
                $uniqKey = $item['key'] . '||' . $item['value'] . '||' . $item['product_id'];
            
                if (!isset($unique[$uniqKey])) {
                    $unique[$uniqKey] = true;
            
                    // Подсчёт по key и value
                    if (!isset($counts1[$item['key']])) {
                        $counts1[$item['key']] = [];
                        if ($item['key'] == 'size') {
                            $counts1[$item['key']]['label'] = 'Размер';
                        }
                        elseif ($item['key'] == 'color') {
                            $counts1[$item['key']]['label'] = 'Цвет';
                        }else{
                            
                            $counts1[$item['key']]['label'] = $item['caption'];
                        }
                        $type = $item['type'] == 'numberfield' ? 'range' : 'select';
                        $counts1[$item['key']]['type'] = $type;
                        $counts1[$item['key']]['values'] = [];
                    }
                    if (!isset($counts1[$item['key']]['values'][$item['value']])) {
                        $counts1[$item['key']]['values'][$item['value']] = 0;
                    }
                    $counts1[$item['key']]['values'][$item['value']]++;
                }
            }
            
            foreach ($counts1 as &$filter) {
                if (isset($filter['values']) && is_array($filter['values'])) {
                    arsort($filter['values']);
                }
            }
            unset($filter);
            
            return $counts1;
        }
    } else {
        $modx->log(modX::LOG_LEVEL_ERROR, "[mSearch2] Error on get filter params.\nQuery: " . $q->toSQL() . "\nResponse: " . print_r($q->stmt->errorInfo(), 1));
    }
}




// Если выбранны фильтры
$result_filters = [];

// Цены
$q = $modx->newQuery('msProductData');
$q->select(['MIN(price) as min', 'MAX(price) as max']);
$q->innerJoin('msProduct', 'res', 'res.id = msProductData.id');

foreach($filters as $key_filter => $val_filter){
    $option = str_replace('filter_', '', $key_filter);
    $q->leftJoin('msProductOption', $option, "$option.product_id = msProductData.id AND $option.key = '$option'");
}
$where = [
    "res.parent:IN" => $catalogId,
];
$q->where($where);
    
foreach($filters as $key_filter => $val_filter){
    $option = str_replace('filter_', '', $key_filter);
    
    if($option !== 'page' && $option !== 'sort' && $option !== 'min_price' & $option !== 'max_price'){
        $q->where([
            "$option.value:IN" => $val_filter
        ]);
    }
}

$q->prepare();
$q->stmt->execute();
$prices = $q->stmt->fetch(\PDO::FETCH_ASSOC);

if(isset($prices, $prices['min'], $prices['max'])) {
    $result_filters['price'] = [
        'label' => 'Цена',
        'type' => 'range',
        'min' => (int)$prices['min'],
        'max' => (int)$prices['max'],
    ];
}










// Рассчет кол-во опций по фильтрам
foreach($result as $key => $filter){
    $q = $modx->newQuery('msProductOption');

    $q->select([
        'msProductOption.product_id',
        'msProductOption.key',
        'msProductOption.value',
        'opt.caption',
        'res.parent AS product_parent_id'
    ]);
    
    $q->leftJoin('msOption', 'opt', 'opt.key = msProductOption.key');
    $q->leftJoin('modResource', 'res', 'res.id = msProductOption.product_id');
    $q->leftJoin('msProductData', 'product_data', 'product_data.id = msProductOption.product_id');
    
    foreach($filter as $item_key => $item_filter){
         $q->leftJoin('msProductOption', $item_key, "$item_key.product_id = msProductOption.product_id AND $item_key.key = '$item_key'");
    }
    
    $q->where([
        'res.parent:IN' => $catalogId,
        'msProductOption.key:IN' => [$key]
    ]); 
    
    foreach($filter as $item_key => $item_filter){
         $q->where([
            "$item_key.value:IN" => $item_filter
        ]);  
    }
    
    $q->where($props_price_where);
    
    $q->prepare();
    
    if ($q->stmt->execute()) {
        if ($rows = $q->stmt->fetchAll(PDO::FETCH_ASSOC)) {
            $unique = []; // Для уникальных комбинаций (key, value, product_parent_id)
            $counts1 = []; // Для подсчёта по key => value
            foreach ($rows as $item) {
                $uniqKey = $item['key'] . '||' . $item['value'] . '||' . $item['product_id'];
            
                if (!isset($unique[$uniqKey])) {
                    $unique[$uniqKey] = true;
            
                    // Подсчёт по key и value
                    if (!isset($counts1[$item['key']])) {
                        $counts1[$item['key']] = [];
                        if ($item['key'] == 'size') {
                            $counts1[$item['key']]['label'] = 'Размер';
                        }
                        elseif ($item['key'] == 'color') {
                            $counts1[$item['key']]['label'] = 'Цвет';
                        }else{
                            
                            $counts1[$item['key']]['label'] = $item['caption'];
                        }
                        $type = $item['type'] == 'numberfield' ? 'range' : 'select';
                        $counts1[$item['key']]['type'] = $type;
                        $counts1[$item['key']]['values'] = [];
                    }
                    if (!isset($counts1[$item['key']]['values'][$item['value']])) {
                        $counts1[$item['key']]['values'][$item['value']] = 0;
                    }
                    $counts1[$item['key']]['values'][$item['value']]++;
                }
            }
            
            $result_filters[$key] = reset($counts1);
        }
    } else {
        $modx->log(modX::LOG_LEVEL_ERROR, "[mSearch2] Error on get filter params.\nQuery: " . $q->toSQL() . "\nResponse: " . print_r($q->stmt->errorInfo(), 1));
    }
}










// Получение всех опций с учетов выбранных фильтров без включения выбранных в результат
$q = $modx->newQuery('msProductOption');

$q->select([
    'msProductOption.product_id',
    'msProductOption.key',
    'msProductOption.value',
    'opt.caption',
    'res.parent AS product_parent_id'
]);

$q->leftJoin('msOption', 'opt', 'opt.key = msProductOption.key');
$q->leftJoin('modResource', 'res', 'res.id = msProductOption.product_id');

foreach($filters as $key_filter => $val_filter){
    $option = str_replace('filter_', '', $key_filter);
    $q->leftJoin('msProductOption', $option, "$option.product_id = msProductOption.product_id AND $option.key = '$option'");
}

$q->where([
    'res.parent:IN' => $catalogId
]);  

// Не включать выбранные опции в результат
$keys = array_filter(array_map(function($key) {
    return str_replace('filter_', '', $key);
}, array_filter(array_keys($filters), function($key) {
    return $key !== 'page' && $key !== 'sort';
})));

$keys[] = 'artikul';

$q->where([
    "msProductOption.key:NOT IN" => $keys
]);

foreach($filters as $key_filter => $val_filter){
    $option = str_replace('filter_', '', $key_filter);
    
    if($option !== 'page' && $option !== 'sort' && $option !== 'min_price' & $option !== 'max_price'){
        $q->where([
            "$option.value:IN" => $val_filter
        ]);
    }
}

// Фильтрация по цене
$q->where($props_price_where);
$q->prepare();

if ($q->stmt->execute()) {
    if ($rows = $q->stmt->fetchAll(PDO::FETCH_ASSOC)) {
        $unique = [];
        $counts1 = [];
        foreach ($rows as $item) {
            $uniqKey = $item['key'] . '||' . $item['value'] . '||' . $item['product_id'];
        
            if (!isset($unique[$uniqKey])) {
                $unique[$uniqKey] = true;
        
                // Подсчёт по key и value
                if (!isset($counts1[$item['key']])) {
                    $counts1[$item['key']] = [];
                    if ($item['key'] == 'size') {
                        $counts1[$item['key']]['label'] = 'Размер';
                    }
                    elseif ($item['key'] == 'color') {
                        $counts1[$item['key']]['label'] = 'Цвет';
                    }else{
                        
                        $counts1[$item['key']]['label'] = $item['caption'];
                    }
                    $type = $item['type'] == 'numberfield' ? 'range' : 'select';
                    $counts1[$item['key']]['type'] = $type;
                    $counts1[$item['key']]['values'] = [];
                }
                if (!isset($counts1[$item['key']]['values'][$item['value']])) {
                    $counts1[$item['key']]['values'][$item['value']] = 0;
                }
                $counts1[$item['key']]['values'][$item['value']]++;
            }
        }
        
        $result_filters += $counts1;
    }
} else {
    $modx->log(modX::LOG_LEVEL_ERROR, "[mSearch2] Error on get filter params.\nQuery: " . $q->toSQL() . "\nResponse: " . print_r($q->stmt->errorInfo(), 1));
}

foreach ($result_filters as &$filter) {
    if (isset($filter['values']) && is_array($filter['values'])) {
        arsort($filter['values']);
    }
}
unset($filter);

return $result_filters;