<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Файл:    modifier.src.php
 * Тип:     modifier
 * Имя:     src
 * Назначение:
 * -------------------------------------------------------------
 */

function smarty_modifier_src($path)
{
    if (stripos($path, 'assets/mgr/') === false) {
        $path =  "/assets/mgr/{$path}";
    }

    return $path;
}