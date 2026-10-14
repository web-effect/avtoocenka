<?php
/**
 * компилируем только если залогинен как админ и файлы изменились или если нет времени изменения в кэше
 */
use \ScssPhp\ScssPhp\Compiler;
use \ScssPhp\ScssPhp\Exception;
require_once MODX_CORE_PATH . 'app/vendor/vendor/autoload.php';



function compile_scss($name, $path)
{
    global $modx;
    try {
        $compiler = new Compiler();
        $compiler->setOutputStyle(\ScssPhp\ScssPhp\OutputStyle::COMPRESSED);
        $compiler->setSourceMap(Compiler::SOURCE_MAP_FILE);
        $compiler->setSourceMapOptions([
            'sourceMapURL' => "./$name.map",
            'sourceMapFilename' => "$name.css",
            'sourceMapBasepath' => $_SERVER['DOCUMENT_ROOT'],
            'sourceRoot' => '/',
        ]);
        if (is_array($path)) {
            $compile = "";
            foreach ($path as $i) {
                if (file_exists(MODX_BASE_PATH . "app/scss/$i.scss")) {
                    $compile .= "@import 'app/scss/$i';";
                }
            }
        } else {
            $compile = "@import 'app/scss/$path'";
        }

        $result = $compiler->compileString($compile);
        file_put_contents(MODX_ASSETS_PATH . "_cache/compiled/$name.map", $result->getSourceMap());
        file_put_contents(MODX_ASSETS_PATH . "_cache/compiled/$name.css", $result->getCss());
        return true;
    } catch (\Exception $e) {
        $modx->regClientHTMLBlock('
            <div style="position: fixed; top: 0; right: 0; left: 0; z-index: 9999; padding: 1rem; background: DarkRed; color: white;">
                Ошибка SCSS: ' . $e->getMessage() . '
            </div>
        ');
        return false;
    }
}


function is_changed($name = 'scss_time')
{
    if (empty($_SERVER['HTTPS'])) {
        // если не https, то не кэшируется
        return true;
    }
    global $modx;
    $scss_changed = false;
    $scss_time = $modx->cacheManager->get($name) ?? 0;
    if (!$scss_time) {
        $scss_changed = true;
    } elseif ($_SESSION['modx.user.contextTokens']['mgr']) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(MODX_BASE_PATH . 'app/scss'));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getMTime() > $scss_time) {
                $scss_changed = true;
                break;
            }
        }
    }
    return $scss_changed;
}


if (!is_dir(MODX_ASSETS_PATH . '_cache/compiled')) {
    mkdir(MODX_ASSETS_PATH . '_cache/compiled', 0777, true);
}

$scss = $scss ?? [];
$scss = array_unique($scss);
$page_hash = md5(json_encode($scss));

$scss_time = $modx->cacheManager->get('scss_time') ?? 0;
$scss_time_page = $modx->cacheManager->get('scss_time_page_' . $page_hash) ?? 0;

// index
if (is_changed('scss_time')) {
    $success = false;
    $success = compile_scss('index', "index");
    if ($success) {
        $scss_time = time();
        $modx->cacheManager->set('scss_time', $scss_time, 0);
    }
}

// page
if (is_changed('scss_time_page_' . $page_hash) || !file_exists(MODX_ASSETS_PATH . "_cache/compiled/page-{$page_hash}.css")) {
    $success = false;
    $success = compile_scss("page-{$page_hash}", $scss ?? []);

    if ($success) {
        $scss_time_page = time();
        $modx->cacheManager->set('scss_time_page_' . $page_hash , $scss_time_page, 0);
    }
}

$modx->sjscripts[] = "<link rel='stylesheet' href='/assets/_cache/compiled/index.css?v=$scss_time'>";
$modx->sjscripts[] = "<link rel='stylesheet' href='/assets/_cache/compiled/page-{$page_hash}.css?v=$scss_time_page'>";

return null;