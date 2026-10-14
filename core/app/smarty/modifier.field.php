<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Файл:    modifier.field.php
 * Тип:     modifier
 * Имя:     field
 * Назначение: Получить поле или tv ресурса
 * -------------------------------------------------------------
 * @todo zoomx->getobject
 */

function smarty_modifier_field($id, $field)
{
    global $modx;
    if (!$id) return '';
    $pdo = $modx->getService('pdoTools');

    if (!$resource = $pdo->getStore($id, 'resource')) {
        $resource = $modx->getObject('modResource', ['id' => intval($id)]);
        $pdo->setStore($id, $resource, 'resource');
    }

    if (!empty($resource)) {
        if (isset($resource->{$field})) {
            return $resource->{$field};
        }
        $tv = $resource->getTVValue($field);
        if (isset($tv)) {
            return $tv;
        }
    }

    return '';
}