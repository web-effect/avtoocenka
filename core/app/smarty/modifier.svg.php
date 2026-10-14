<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Файл:    modifier.svg.php
 * Тип:     modifier
 * Имя:     svg
 * Назначение:
 * -------------------------------------------------------------
 */

function smarty_modifier_svg($input)
{
    $svg = @file_get_contents(MODX_BASE_PATH . $input);
    if (!$svg) {
        return "";
    }
    $is_svg = preg_match("/.*?<svg\s/uis", $svg);
    if (!$is_svg) {
        return "";
    }

    $svg = preg_replace("/.*?<svg\s/uis", "<svg ", $svg);
    $svg_tag = [];
    preg_match("/<svg(.*?)>/uis", $svg, $svg_tag);
    $_svg_tag = $svg_tag[1];

    $idprefix = "svg_" . dechex(random_int(0, 999999)) . "__";
    $proptector = "|@@@" . dechex(random_int(0, 999999)) . "@@@|";

    $svg = str_replace("<svg" . $svg_tag[1], $proptector, $svg);
    $svg = preg_replace(
        '/id=[\'"](.*?)[\'"]/uis',
        'id="' . $idprefix . '$1"',
        $svg
    );
    $svg = preg_replace("/\(#(.*?)([;\s])/uis", "(#" . $idprefix . '$1$2', $svg);
    $svg = str_replace($proptector, "<svg" . $_svg_tag, $svg);
            
    return $svg;
}