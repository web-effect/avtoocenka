{extends 'base.tpl'}

{block 'main'}
	<main class="p-search | main container">
        {'SimpleSearch'|snippet:[
            'pagingSeparator' => '',
            'pageLimit' => 2,
            'customPackages'=>'msProductData:article:minishop2:{core_path}/components/minishop2/model/:msProductData.id = modResource.id',
            'containerTpl' => '@INLINE
                <p class="simplesearch-results">{$resultInfo}</p>
                <div class="simplesearch-results-list content">
                    {$results}
                </div>
                <div class="simplesearch-paging content">
                    
                    <span class="simplesearch-result-pages">[[%simplesearch.result_pages? &namespace=`simplesearch` &topic=`default`]]</span>
                
                    <ul class="pagination-list">{$paging}</ul>
                    
                </div>
            ',
            'tpl' => '@INLINE
                <p class="simplesearch-result">
                    <div class="h6"><b>{$idx}. <a href="{$link}" title="{$longtitle}">{$pagetitle}</a></b></div>
                    <div class="extract">
                        <p>{$extract}</p>
                    </div>
                </p>
            ',
            'currentPageTpl' => '@INLINE
                <li class="simplesearch-page simplesearch-current-page pagination-list-active">{$text}</li>
            ',
            'pageTpl' => '@INLINE
                <li class="simplesearch-page">
                    <a href="{$link}">{$text}</a>
                </li>
            '
        ] nocache}
	</main>
{/block}