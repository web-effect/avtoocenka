{$menu = 'pdoMenu'|snippet:[
    'parents' => 0,
    'resources' => '-1',
    'level' => 2,
    'levelClass' => 'navbar__menu-lvl-',
    'parentClass' => 'navbar__menu-parent',
    'innerClass' => 'navbar__menu-submenu',
    'tplOuter' => '@INLINE {$wrapper}',
    'tplInner' => '@INLINE <ul {$classes}>{$wrapper}</ul>',
    'tplParentRow' => '@INLINE
        <li {$classes}>
            <span class="navbar__menu-title | spoiler">
                <a class="navbar__menu-link" href="{$link}" {$attributes}>{$menutitle}</a>
                <span class="navbar__menu-icon | spoiler-title">{"/app/libs/bi/chevron-down.svg"|svg}</span> 
            </span>
            {$wrapper}
        </li>',
    'tpl' => '@INLINE
        <li {$classes}>
            <a class="navbar__menu-link" href="{$link}" {$attributes}>{$menutitle}</a>
            {$wrapper}
        </li>'
]}

<div class="navbar">
    <div class="navbar__row | container">
        <button class="navbar__burger" data-src="#navbar__mobile" data-fancybox-class="navbar__mobile" data-fancybox>
            {'/app/libs/bi/list.svg'|svg}
        </button>

        <a href="/" class="navbar__logo">
            <img src="{1|field:'logo'}" alt="" width="224" height="44">
        </a>

        <ul class="navbar__menu">
            {$menu}
        </ul>

        {if 'modules_favorites'|config}
            <a class="navbar__favorites {($favorites)?'':'_empty' nocache}" href="/favorites">
                {'/app/libs/bi/heart.svg'|svg}
                {'/app/libs/bi/heart-fill.svg'|svg}
            </a>
        {/if}
        
        {if 'modules_profile'|config}
            <a class="navbar__profile" href="/auth/login">
                {guest nocache}
                {'/app/img/person.svg'|svg}
                {* <span>Войти в ЛК</span> *}
                {/guest}
                {auth nocache}
                {'/app/img/person-check.svg'|svg}
                {* <span>Личный<br>кабинет</span> *}
                {/auth}
            </a>
        {/if}
        
        {if 'modules_shop'|config && 'id'|resource != 277}
            <div class="navbar__cart-wrap ">
                <a class="navbar__cart" href="/checkout">
                    <div class="navbar__cart-icon">
                        {'/app/libs/bi/cart.svg'|svg}
                        {'msMiniCart'|snippet:['tpl' => '@INLINE <span class="navbar__cart-count | ms2_total_count">{$total_count}</span>'] nocache}
                    </div>
                </a>
                
                {* Всплывающая корзина *}
                {include 'elements/cart-popup'}
            </div>
        {/if}
    </div>
</div>


<div class="navbar__mobile-inner" id="navbar__mobile" hidden>
    <a href="/" class="navbar__mobile-logo">
        111
        <img src="{1|field:'logo'|phpthumbon:'f=webp'}" alt="" width="128" height="34">
        222
    </a>

    <ul class="navbar__mobile-menu">
        {$menu}
    </ul>

    {*
    <div class="navbar__mobile-contacts">
        <div>{$phones}</div>
        <div class="navbar__mobile-soc">{$soc}</div>
    </div>
    *}
</div>