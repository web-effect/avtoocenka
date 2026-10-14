<?php
$templates ??= [];

$query = $modx->newQuery('modTemplateVarResource', [
    'tmplvarid' => 97,
    'contentid' => $templates[$modx->resource->template] ?? $modx->resource->id,
]);
$query->select('value');
$blocks =  $modx->getValue($query->prepare());
$blocks = json_decode($blocks, true) ?: [];

foreach ($blocks as &$block) {
    // подтягиваем из другого блока
    if (!empty($block['get_from_other'])) {
        $block_ids = explode('-', $block['get_from_other']);
        
        $query = $modx->newQuery('modTemplateVarResource', [
            'tmplvarid' => 97,
            'contentid' => $block_ids[0],
        ]);
        $query->select('value');
        $table = $modx->getValue($query->prepare());
        $rows = json_decode($table, true);

        if (!$rows) {
            continue;
        }

        foreach ($rows as $row) {
            if ($row['MIGX_id'] == $block_ids[1]) {
                foreach ($row as $key => $value) {
                    if (!empty($value) && empty($block[$key])) {
                        $block[$key] = $value;
                    }
                }
            }
        }
    }
}

return $blocks;