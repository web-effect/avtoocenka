<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Файл:       modifier.price_format.php
 * Тип:        modifier
 * Имя:        price_format
 * Назначение: форматирование цены
 * -------------------------------------------------------------
 */

function smarty_modifier_price_format($num)
{
    return str_replace(',00', '', number_format((float)$num, 2, ',', ' '));
}