<?php
if (!$modx->user->id) {
    return [];
}

$q = $modx->newQuery('msOrder');
$q->select([
    $modx->getSelectColumns('msOrder', 'msOrder'),
    'msOrderStatus.name as status_name',
    'msOrderStatus.color as status_color',
]);
$q->leftJoin('msOrderStatus', 'msOrderStatus', 'msOrderStatus.id = msOrder.status');
$q->where([
    'user_id' => $modx->user->id
]);
$q->sortby('id', 'DESC');
$q->prepare();
$q->stmt->execute();
$q_result = $q->stmt->fetchAll(\PDO::FETCH_ASSOC);

$orders = [];

foreach ($q_result as $row) {
    $orders[$row['id']] = $row;
}

$q = $modx->newQuery('msOrderProduct');
$q->select([
    'msOrder.user_id',
    'msProduct.uri',
    $modx->getSelectColumns('msOrderProduct', 'msOrderProduct') ,
]);
$q->where([
    'msOrder.user_id' => $modx->user->id
]);
$q->leftJoin('msOrder', 'msOrder', 'msOrder.id = msOrderProduct.order_id');
$q->leftJoin('msProduct', 'msProduct', 'msProduct.id = msOrderProduct.product_id');
$q->prepare();
$q->stmt->execute();
$q_result = $q->stmt->fetchAll(\PDO::FETCH_ASSOC);

foreach ($q_result as $row) {
    $orders[$row['order_id']]['products'][] = $row;
}

return $orders;