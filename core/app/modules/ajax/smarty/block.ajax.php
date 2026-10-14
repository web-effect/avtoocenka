<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     block.ajax.php
 * Type:     block
 * Name:     ajax
 * Purpose:  ajax block
 * -------------------------------------------------------------
 */

function smarty_block_ajax($params, $content, Smarty_Internal_Template $template, &$repeat)
{
    global $modx;

    if (!$repeat) {
        $res_id = $modx->resource->id;
        $params['params']['ajax_key'] = $params['key'];
        $content = str_replace(['{literal}', '{/literal}'], ['', ''], $content);

        $to_cache = [
            'params' => $params['params'],
            'tpl' => $content,
        ];

        $modx->cacheManager->set($params['key'], $to_cache, 0, [xPDO::OPT_CACHE_KEY => 'ajax']);

        $output = parserx()->parse($content, $params['params']);

        $modx->regClientHTMLBlock("<script>
            document.body.addEventListener('htmx:beforeRequest', (e) => {
                if (e.detail.pathInfo.path.includes('ajax/')) {
                    e.detail.requestConfig.parameters.resource = {$res_id};
                    e.detail.xhr.responseType = 'json';
                }
                e.detail.target.classList.add('htmx-swapping');
            });
            document.body.addEventListener('htmx:beforeSwap', (e) => {
                if (e.detail.pathInfo.path.includes('ajax/')) {
                    e.detail.serverResponse = e.detail.serverResponse.data.html;
                }
            });
        </script>");

        return $output;
    }
}