<?php
$q = $modx->newQuery('msProductOption');
$q->where([
    'product_id' => $id ?? $modx->resource->id,
]);
$q->select(['key','value']);
$q->prepare();
$q->stmt->execute();
$q_result = $q->stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

$out = [];
foreach ($q_result as $item) {
    $out[$item['key']] = $item;
}

return $out;