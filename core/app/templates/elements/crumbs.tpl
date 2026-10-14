{if 'template'|resource != 2 && 'template'|resource != 17}
    <div class="container">
        {'pdoCrumbs'|snippet:[ 
                'showHome' => 1,
                'tplWrapper' => 
                    '@INLINE <ul itemscope="" itemtype="http://schema.org/BreadcrumbList" class="breadcrumb">{$output}</ul>
    ',
        'tpl' =>
        '@INLINE <li><span itemscope="" itemprop="itemListElement" itemtype="http://schema.org/ListItem">
            <a title="{$menutitle}" itemprop="item" href="{$link}"><span itemprop="name">{$menutitle}</span>
                <meta itemprop="position" content="{$idx}">
            </a></span></li>',
        'tplCurrent' =>
        '@INLINE <li><span itemscope="" itemprop="itemListElement" itemtype="http://schema.org/ListItem">
            <span itemprop="name">{$menutitle}</span>
            <meta itemprop="position" content="{$idx}">
        </span></li>'
        ]}

        {if !$no_title}
            <h1 class="pagetitle">{'pagetitle'|resource|default:$pagetitle}</h1>
        {/if}
    </div>
{/if}
