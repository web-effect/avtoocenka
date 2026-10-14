<?php
$keys = $modx->getOption('shop_ya_search_keys') ?? '';
$keys = explode(',', $keys);

$params = [
    'apikey' => $keys[1] ?? '',
    'searchid' => $keys[0] ?? '',
    'text' => $_REQUEST['text'] ?? '',
    //'available' => 'false',
    'per_page' => 96,
    'page' => 0,
];

$url = 'https://catalogapi.site.yandex.net/v1.0' . '?' . http_build_query($params);
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$out = curl_exec($curl) ?: '';
curl_close($curl);
$array = json_decode($out, true);

foreach ($array['documents'] as $k => &$i) {
    $res = $modx->getObject('msProduct', $i['id']);
    $i = $res->toArray();
}

return $array;