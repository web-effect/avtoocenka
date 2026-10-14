<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Файл:    modifier.plural.php
 * Тип:     modifier
 * Имя:     plural
 * Назначение: Склоняет слово в зависимости от кол-ва
 * -------------------------------------------------------------
 */
function smarty_modifier_plural($number, $single, $few, $many)
{
    $number = abs((int)$number);

    $n1 = $number % 10;
    $n2 = $number % 100;

    if ($n1 == 1 && $n2 != 11) {
        return $single;
    }

    if ($n1 >= 2 && $n1 <= 4 && !($n2 >= 12 && $n2 <= 14)) {
        return $few;
    }

    return $many;
}