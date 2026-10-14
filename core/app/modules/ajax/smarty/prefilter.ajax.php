<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     prefilter.ajax.php
 * Type:     prefilter
 * Name:     ajax
 * Purpose:  Не кэшировать и не парсить содержимое блока ajax
 * -------------------------------------------------------------
 */

function smarty_prefilter_ajax($source, Smarty_Internal_Template $template)
{
    return preg_replace(
        ['~(\{ajax)(.*\})~', '~(\{\/ajax\})~'],
        ['{nocache}$1$2{literal}', "{/literal}$1{/nocache}"],
        $source
    );
}