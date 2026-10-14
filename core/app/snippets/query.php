<?php
/**
 * 
 */

if (empty($where) || empty($select)) {
    return 'Пустой where или select';
}

$class = $class ?? 'modResource';
$leftJoin = [];

if ($class == 'modResource') {
    $sortby = $sortby ?? ['menuindex', 'ASC'];
    $where = array_merge(['deleted' => 0], $where);

    foreach ($select as &$field) {
        if (stripos($modx->getSelectColumns('modResource'), "`$field`") === false) {
            $leftJoin[] = ['modTemplateVar', "tv_$field", "`tv_$field`.`name` = '$field'"];
            $leftJoin[] = [
                'modTemplateVarResource',
                "tvr_$field",
                "`tvr_$field`.`tmplvarid` = `tv_$field`.`id` AND `tvr_$field`.`contentid` = `modResource`.`id`",
            ];
            $field = "`tvr_$field`.`value` as `$field`";
        } else {
            $field = "`modResource`.`$field` as `$field`";
        }
        unset($field);
    }

    foreach ($where as $key => $item) {
        unset($where[$key]);
        $field = explode(':', $key);
        if (stripos($modx->getSelectColumns('modResource'), "`{$field[0]}`") === false) {
            $key = "`tvr_{$field[0]}`.`value`" . (isset($field[1]) ? ":{$field[1]}" : '');
        } else {
            $key = "`modResource`.`{$field[0]}`" . (isset($field[1]) ? ":{$field[1]}" : '');
        }
        $where[$key] = $item;
    }
} else {
    $sortby = $sortby ?? ['id', 'ASC'];
}

$q = $modx->newQuery($class);
$q->limit($limit ?? 0);
$q->select($select);
$q->sortby(...$sortby);
$q->where($where);
if ($leftJoin) {
    foreach ($leftJoin as $join) {
        $q->leftJoin(...$join);
    }
}
$q->prepare();
$q->stmt->execute();
$q_result = $q->stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

return $q_result;