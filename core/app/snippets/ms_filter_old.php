<?php
$props = [];

$id = $modx->resource->id;

if ($id == 276) {
    $id = 11;
}


$childIds = $modx->getChildIds($id, 10, ['context' => $modx->context->key]);
$childIds[] = $id;

// цены
$q = $modx->newQuery('msProductData');
$q->select(['MIN(price) as min', 'MAX(price) as max']);
$q->innerJoin('msProduct', 'res', 'res.id = msProductData.id');
$q->where([
    'res.parent:IN' => $childIds,
    "res.published" => 1,
    "res.deleted" => 0,
]);
$q->prepare();
$q->stmt->execute();
$prices = $q->stmt->fetch(\PDO::FETCH_ASSOC);

if(isset($prices, $prices['min'], $prices['max'])) {
    $props['price'] = [
        'label' => 'Цена',
        'type' => 'range',
        'min' => (int)$prices['min'],
        'max' => (int)$prices['max'],
    ];
}

// опции товара
$q = $modx->newQuery('msProductOption');
$q->select([
    'msProductOption.key',
    'msProductOption.value',
    'opt.caption',
    'opt.type',
]);
$q->innerJoin('msProduct', 'res', 'res.id = msProductOption.product_id');
$q->leftJoin('msOption', 'opt', 'opt.key = msProductOption.key');
$q->leftJoin('msCategoryOption', 'cat', 'cat.option_id = opt.id');
$q->where([
    'res.parent:IN' => $childIds,
    "res.published" => 1,
    "res.deleted" => 0,
]);
$q->sortby('rank', 'ASC');
$q->prepare();
$q->stmt->execute();
$q_result2 = $q->stmt->fetchAll(\PDO::FETCH_ASSOC);

foreach ($q_result2 as $key => $item) {
    if ($item['value'] != '') {
        if ($item['key'] == 'size') {
            $item['caption'] = 'Размер';
        }
        if ($item['key'] == 'color') {
            $item['caption'] = 'Цвет';
        }
        $type = $item['type'] == 'numberfield' ? 'range' : 'select';
        $props[$item['key']]['type'] = $type;
        $props[$item['key']]['label'] = $item['caption'];
        $props[$item['key']]['values'][] = $item['value'];
    }
}

// обработка
foreach ($props as $name => &$prop) {
    // только мин и макс
    if ($name != 'price' && $prop['type'] == 'range') {
        $prop['min'] = (int)min($prop['values']);
        $prop['max'] = (int)max($prop['values']);
        unset($prop['values']);
    }
    // только уникальные
    if ($prop['type'] == 'select') {
        $prop['values'] = array_unique($prop['values']);
    }
}


$modx->log(1, print_r($props, 1));

return $props;