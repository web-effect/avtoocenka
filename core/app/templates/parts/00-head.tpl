{$favicon = 1|field:'favicon'}





{if 'longtitle'|resource}
    {$title='longtitle'|resource}
{else}
    {$title= 'pagetitle'|resource|cat:' | Компания '|cat:(1|field:'site_name')}
    {if (1|field:'meta_city')}
        {$title = $title|cat:' '|cat:(1|field:'meta_city')}
    {/if}
{/if}

{if 'description'|resource}
    {$description='description'|resource}
{else}
    {$description=strip_tags('content'|resource)|truncate:'100'|cat:' | Компания '|cat:(1|field:'site_name')}
    {if (1|field:'meta_city')}
        {$description = $description|cat:' '|cat:(1|field:'meta_city')}
    {/if}
    {if !('content'|resource)}
        {$description = 'Компания '|cat:(1|field:'site_name')}
        {if (1|field:'meta_city')}
            {$description = $description|cat:' '|cat:(1|field:'meta_city')}
        {/if}
    {/if}
    {$description = $description|cat:' | '|cat:('pagetitle'|resource)}
{/if}







<base href="{'site_url'|config}">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">

{*favicons*}
<link rel="apple-touch-icon" sizes="180x180" href="{$favicon|phpthumbon:'w=180&h=180&zc=1'}">
<link rel="icon" type="image/png" sizes="32x32" href="{$favicon|phpthumbon:'w=32&h=32&zc=1'}">
<link rel="icon" type="image/png" sizes="16x16" href="{$favicon|phpthumbon:'w=16&h=16&zc=1'}">

{*seo*}
<title>{$title|escape}</title>
<meta name="description" content="{$description|escape}">

{$canonical =  'id'|resource|url}
{if $canonical == '/'}
    {$canonical= substr('site_url'|config, 0, -1)}
{else}
    {$canonical='id'|resource|url:['scheme'=>'full']}
{/if}
<link rel="canonical" href="{$canonical}">

<meta property="og:type" content="website">
<meta property="og:title" content="{$title|escape}">
<meta property="og:description" content="{$description|escape}">
<meta property="og:url" content="{'id'|resource|url:['scheme'=>'full']}">
<meta property="og:image" content="{'site_url'|config}{'image'|resource|default:$favicon}">
{'ss_meta.keywords'|ph}

{*libs*}

{'/app/js/libraries/jscookie.js'|js}
{'/app/js/libraries/htmx.js'|js}
{'/app/js/libraries/fancybox.js'|js}
{'/app/js/libraries/maska.js'|js}
{'/app/js/libraries/alpine.mask.js'|js}
{'/app/js/libraries/air-datepicker.js'|js}
{'/assets/components/minishop2/js/web/vanilajs/lib/izitoast/iziToast.min.js'|js}

{'/app/js/libraries/fancybox.css'|css}
{'/app/js/libraries/air-datepicker.css'|css}
{'/assets/components/minishop2/css/web/lib/izitoast/iziToast.min.css'|css}

{*
подключить через |js
*}
<script src="/app/js/libraries/jquery-3.6.4.js"></script>


{*js*}
{'/app/js/main.js'|js}
{'/app/js/forms.js'|js}
{'/app/js/maps.js'|js}
{'/app/js/report.js'|js}
{'/app/js/libraries/alpinejs.js'|js}

{*Vision для работы нужно раскомментировать Jquery выше*}
{*
'/app/js/libraries/vision/bvi-init.js'|js
*}
{*
'/app/js/libraries/vision/bvi.min.js'|js
*}
{*
'/app/js/libraries/vision/bvi.min.css'|css
*}


{*fonts*}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=PT+Sans+Caption:wght@400;700&display=swap" rel="stylesheet">

{* ресурс метатеги *}
{4|field:'content'}