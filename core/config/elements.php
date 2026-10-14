<?php
require_once MODX_CORE_PATH . 'app/modules/ajax/elements.php';
require_once MODX_CORE_PATH . "app/modules/eloquent/vendor/autoload.php";
require_once MODX_CORE_PATH . 'app/vendor/vendor/autoload.php';

$elementService->addSnippetPath(MODX_CORE_PATH . 'app/snippets/');

zoomx()->getLoader()->addPsr4('App\\Plugins\\', MODX_CORE_PATH . 'app/plugins/');
zoomx()->getLoader()->addPsr4('App\\Controllers\\', MODX_CORE_PATH . 'app/controllers/');
zoomx()->getLoader()->addPsr4('App\\Services\\', MODX_CORE_PATH . 'app/services/');

parserx()->loadFilter('pre', 'ajax');

$elementService->registerPlugins([
    App\Plugins\App::class,
]);