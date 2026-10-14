<?php
function download_lib($input)
{
    global $modx;
    $path_esc = preg_replace("/http(s?):\/\/|[^0-9a-z\/\.]/", "", $input);
    $pathinfo = pathinfo($path_esc);
    $dir = MODX_ASSETS_PATH . '_cache/libs/' . $pathinfo['dirname'];
    $file = $dir . '/' . $pathinfo['basename'];
    if (!file_exists($file)) {
        $modx->log(1, 'css_js: загрузка  ' . $file);
        $content = file_get_contents($input);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($file, $content);
    }
    return '/assets/_cache/libs/' . $path_esc;
}


function get_script_path($item)
{
    preg_match_all('/<link[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $item, $link);
    preg_match_all('/<script[^>]+src=([\'"])(?<src>.+?)\1[^>]*>/i', $item, $script);
    return $link['href'][0] ?? $script['src'][0] ?? '';
}


/**
 * качаем библиотеки с cdn
 */
foreach (['sjscripts', 'jscripts'] as $scripts) {
    foreach ($modx->$scripts as $key => $i) {
        $path = get_script_path($i);
        if (stripos($path, '://') !== false) {
            $path = download_lib($path);
            if ($scripts == 'sjscripts') {
                $modx->$scripts[$key] = "<link rel='stylesheet' href='$path'>";
            } else {
                $modx->$scripts[$key] = "<script src='$path'></script>";
            }
        }
    }
}


/**
 * склеиваем библиотеки если очищен кэш
 */
$css_js_time = $modx->cacheManager->get('css_js_time') ?? 0;
if (!$css_js_time) {
    $css = $js = '';
    foreach (['sjscripts'/*, 'jscripts'*/] as $scripts) {
        foreach ($modx->$scripts as $key => $i) {
            if (preg_match('/\/libs\//i', get_script_path($i))) {
                $file = file_get_contents(MODX_BASE_PATH . get_script_path($i)) . "\n";
                // удаляем ссылки на source map
                $file = preg_replace('~(\/[\*|\/]# sourceMappingURL=)(.*.map ?\*?\/?)~', '', $file);
                if ($scripts == 'sjscripts') {
                    $css .= $file;
                } else {
                    $js .= $file;
                }
            }
        }
    }
    file_put_contents(MODX_ASSETS_PATH . '_cache/libs.css', $css);
    //file_put_contents(MODX_ASSETS_PATH . '_cache/libs.js', $js);
    $css_js_time = time();
    $modx->cacheManager->set('css_js_time', $css_js_time, 0);
}

// удаляем подключенные
foreach (['sjscripts'/*, 'jscripts'*/] as $scripts) {
    foreach ($modx->$scripts as $key => $i) {
        if (preg_match('/\/libs\//i', get_script_path($i))) {
            unset($modx->$scripts[$key]);
        }
    }
}

// подключаем склееные
if (file_exists(MODX_ASSETS_PATH . '_cache/libs.css')) {
    array_unshift($modx->sjscripts, "<link rel='stylesheet' href='/assets/_cache/libs.css?t=$css_js_time'>");
}


// добавляем время к скриптам чтоб сбросить кэш
foreach (['jscripts'] as $scripts) {
    foreach ($modx->$scripts as $key => $i) {
        if (stripos($i, '?') === false) {
            $modx->$scripts[$key] = preg_replace(
                "/\.js(['\"])\s*><\/script>/i",
                ".js?t={$css_js_time}$1></script>",
                $i
            );
        }
    }
}

// if (file_exists(MODX_ASSETS_PATH . '_cache/libs.js')) {
//     array_unshift($modx->jscripts, "<script src='/assets/_cache/libs.js?v=$css_js_time'></script>");
// }

// move minishop script to bottom
// $ms_script = '';
// foreach ($modx->jscripts as $key => $i) {
//     $path = get_script_path($i);
//     if (!empty($path) && stripos($path, 'minishop2/js/web/default.js') !== false) {
//         $ms_script = $i;
//         unset($modx->jscripts[$key]);
//         break;
//     }
// }
// if (!empty($ms_script)) {
//     $modx->jscripts[] = $ms_script;
// }

return null;