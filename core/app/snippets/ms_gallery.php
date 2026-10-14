<?php
$q = $modx->newQuery('msProductFile');
$q->where([
    'product_id' => $id ?? $modx->resource->id,
    'parent' => 0,
]);
$q->select(['url', 'description','name']);
$q->sortby('rank', 'ASC');
$q->prepare();
$q->stmt->execute();
$q_result = $q->stmt->fetchAll(\PDO::FETCH_ASSOC);

if (!$q_result) {
    $q_result[] = [
        
        'url' => 'assets/components/minishop2/img/web/ms2_big@2x.png'
    ];
}

return $q_result;