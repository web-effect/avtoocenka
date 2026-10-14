<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Файл:       modifier.ms_in_cart.php
 * Тип:        modifier
 * Имя:        ms_in_cart
 * Назначение: получение кол-ва товара в корзине по id
 * -------------------------------------------------------------
 */

function smarty_modifier_ms_in_cart($id)
{
    global $modx;
    $ms2 = $modx->getService('minishop2');
    $ms2->initialize('web');
    $cart = $ms2->cart->get();

    $count = 0;
    foreach ($cart as $item) {
        if ($item['id'] == $id) {
            $count = $item['count'];
            break;
        }
    }

    return $count;
}