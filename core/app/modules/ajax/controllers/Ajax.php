<?php
namespace Ajax\Controllers;
use Zoomx\Controllers;

class Ajax extends \Zoomx\Controllers\Controller
{

    public function index($key)
    {
        $data = $this->modx->cacheManager->get($key, [\xPDO::OPT_CACHE_KEY => 'ajax']);

        if (!empty($data) && !empty($_POST['resource']) && is_numeric($_POST['resource'])) {
            $params = array_merge($data['params'], $_POST);
            $params['is_ajax'] = 1;
            $params['all_params'] = $params; // для дебага, вывод всех доступных параметров

            $this->modx->resource = $this->modx->getObject('modResource', $_POST['resource']);
            $_REQUEST['q'] = $this->modx->resource->uri;

            return ['html' => parserx()->parse($data['tpl'], $params)];
        }

        return ['html' => "Ajax error"];
    }

}
