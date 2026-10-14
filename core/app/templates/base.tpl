<!doctype html>
<html lang="{'cultureKey'|config}">

<head>
    {include "parts/00-head"}
</head>

<body class="body _template_{'template'|resource} _id_{'id'|resource}">
    {* Избранное (список из кук) *}
    {$favorites = $smarty.cookies.favorites nocache}
    
    {block 'header'}
        <div class="main__header">
            <div class="main__header-container container">
                <a href="/" class="main__logo">
                    <div class="main__image">
                        <img src="{1|field:'logo'|phpthumbon:'w=500&f=webp'}" alt="{1|field:'site_name'}" width="230">
                    </div>
                    <div class="main__header-content">
                        <div class="main__sitename">АвтоОценка</div>
                        <div class="main__header-descriptor">{1|field:'descriptor'}</div>
                    </div>
                </a>
                {guest nocache}
                    <a class="button" href="/auth/login">
                        {'/app/img/icons/login.svg'|svg}
                        <span>Войти в кабинет</span>
                    </a>
                {/guest}
                {auth nocache}
                    <a class="button" href="/dashboard">
                        {'/app/img/icons/profile.svg'|svg}
                        <span>Личный кабинет</span>
                    </a>
                {/auth}
            </div>
        </div>
    {/block}

    {block 'main'}
        {* основная часть шаблона (напр. товара) *}
    {/block}

    {block 'blocks' nocache}
        {$blocks = '@FILE blocks'|snippet:[]:0}


        {* если нет блоков, выводим content *}
        {if !count($blocks) && 'content'|resource}
            {if 'id'|resource != 16}
                <section class="content__container container">
                    
                    <div class="content__wrapper content">
                        <h1 class="pagetitle">{'pagetitle'|resource|default:$pagetitle}</h1>
                        <div class="content__inner">
                            {if 'parent'|resource == 260}
                                {'content'|resource|parse}
                            {else}
                                {'content'|resource|modx}
                            {/if}
                        </div>
                    </div>
                </section>
            {/if}
        {/if}
        

        {foreach $blocks as $block}
            {if !$block.off}
                {$scss[] = "blocks/{$block.tpl}"|replace:'.tpl':''}
                {if $block.tpl}
                    {$tpl = "parts/{$block.tpl}"}
                {else}
                    {$tpl = "parts/section.tpl"}
                {/if}
                {include $tpl}
            {/if}
        {/foreach}
        
        
    {/block}
    


    {include "parts/20-footer"}
    {include "elements/modals"}
    
    {include "elements/enable-cookies"}

    {if $smarty.session['modx.user.contextTokens'].mgr nocache}
        <a target="_blank" href="/backend/?a=resource/update&id={'id'|resource}" id="quick-edit">РЕД</a>
    {/if}

    {if $modx->user->sudo}
        {modx}
        <div class="dev-info" style="padding: 1rem; background: #222; color: #eee; font-size: 0.85rem">
            [^q^] - Количество запросов<br>
            [^t^] - Общее время<br>
            [^m^] - Использование памяти
        </div>
        {/modx}
    {/if}

    {'@FILE scss'|snippet:['scss' => $scss]}
    {'@FILE css_js'|snippet nocache}

    
    {* скрипты / счетчики *}
    {if $smarty.get.noscripts != 1}
        {123|field:'content'}
    {/if}
    {*include "elements/micro"*}
</body>

</html>

