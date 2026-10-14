<header class="header">
    <div class="container">
        <div class="header-grid">

            <div class="header-logo">
                <a href="/" class="header-logo">
                    <img src="{1|field:'logo'|phpthumbon:'w=500&f=webp'}" alt="{1|field:'site_name'}" width="230">
                </a>
                {if 1|field:'descriptor'}
                    <span class="content">{1|field:'descriptor'}</span>
                {/if}
            </div>


            {if 'modules_shop_ya_search'|config}
                {$ya_search_keys = explode(',', 'shop_ya_search_keys'|config)}
                <script type="text/javascript">
                    window.ya_site_path = { serp: "//site.yandex.ru/search/site/catalog/" };
                </script>
                <div class="header-search">
                    <div class="ya-site-form ya-site-form_inited_no"
                        data-bem="{ &quot;action&quot;:&quot;{'site_url'|config}ya-search&quot;,&quot;arrow&quot;:false,&quot;bg&quot;:&quot;transparent&quot;,&quot;language&quot;:&quot;ru&quot;,&quot;logo&quot;:&quot;rb&quot;,&quot;publicname&quot;:&quot;&quot;,&quot;suggest&quot;:true,&quot;target&quot;:&quot;_self&quot;,&quot;tld&quot;:&quot;ru&quot;,&quot;type&quot;:2,&quot;usebigdictionary&quot;:false,&quot;searchid&quot;:{$ya_search_keys[0]},&quot;input_placeholder&quot;:&quot;Поиск по каталогу&quot;,&quot;is_catalog&quot;:true }">
                        <form action="//site.yandex.ru/search/site/catalog/" method="get" target="_self"
                            accept-charset="utf-8">
                            <input type="hidden" name="searchid" value="{$ya_search_keys[0]}" />
                            <input type="hidden" name="l10n" value="ru" /><input type="hidden" name="reqenc"
                                value="utf-8" />
                            <input type="search" name="text" value="{$smarty.get.text nocache}" />
                            {* <input type="submit" value="Найти"/> *}
                        </form>
                    </div>
                    <button
                        onclick="document.querySelector('.ya-site-form__submit').click()">{'/app/libs/bi/search.svg'|svg}</button>
                </div>
                <script type="text/javascript">
                    (function(w, d, c) {
                        var s = d.createElement('script'),
                            h = d.getElementsByTagName('script')[0],
                            e = d.documentElement;
                        if ((' ' + e.className + ' ').indexOf(' ya-page_js_yes ') === -1) {
                            e
                                .className += ' ya-page_js_yes';
                        }
                        s.type = 'text/javascript';
                        s.async = true;
                        s.charset = 'utf-8';
                        s.src = (d.location.protocol === 'https:' ? 'https:' : 'http:') +
                            '//site.yandex.net/v2.0/js/all.js';
                        h.parentNode.insertBefore(s, h);
                        (w[c] || (w[c] = [])).push(function() { Ya.Site.Form.init() })
                    })(window, document,
                        'yandex_site_callbacks');
                </script>
            {else}
                {* костыль, иначе не отправляется из-за скрипта минишопа *}
                <form class="header-search" action="search" x-data @submit="$el.submit()">
                    <input class="input" name="search" type="search" value="{$smarty.get.search nocache}"
                        placeholder="Поиск по сайту">
                    <button>{'/app/img/search.svg'|svg}</button>
                </form>
            {/if}

            <div class="header-soc">{include 'elements/contacts' type='soc'}</div>

            <div>
                <div class="header-contacts">
                    {'/app/libs/bi/telephone.svg'|svg}
                    <div class="header-phone">{include 'elements/contacts' type='phone'}</div>
                </div>

                <div class="header-contacts">
                    {'/app/libs/bi/envelope.svg'|svg}
                    <div class="header-email">{include 'elements/contacts' type='email'}</div>
                </div>


                <div class="header-contacts">
                    {'/app/libs/bi/geo-alt.svg'|svg}
                    <div class="header-addr">{include 'elements/contacts' type='addr'}</div>
                </div>

            </div>

            <div>
                <a href="#modal_callback" class="button" data-fancybox>Заказать звонок</a>
            </div>

        </div>
    </div>
</header>