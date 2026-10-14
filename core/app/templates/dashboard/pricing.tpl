{extends 'dashboard.tpl'}
{block 'header'}
{/block}
{block 'page_content'}
    {* {'AuthCheck'|snippet nocache} *}
    {$items = '@FILE query'|snippet:[
        'select' => ['id', 'pagetitle', 'longtitle'],
        'where' => [
            'parent' => 364
        ]
    ]:0}

    <div class="replenshiment">
        <div class="replenshiment__top">
            <div class="replenshiment__total">
                <div class="replenshiment__total-title">Текущий баланс</div>
                <div class="replenshiment__total-balance">{$profile.available_reports}</div>
            </div>
            <div class="replenshiment__total-description">
                Нажмите на пакет,<br>чтобы оплатить и пополнить баланс
            </div>
        </div>

        <ul class="replenshiment__items">
            {foreach $items as $item}
                {$package = intval($item.pagetitle)}
                {$price = intval($item.longtitle)}
                <li class="replenshiment__item">
                    <div class="replenshiment__reports">
                        <div class="replenshiment__reports-count">{$package}</div>
                        <div class="replenshiment__reports-title">
                            {$package|plural:'отчёт':'отчёта':'отчётов'}
                        </div>
                    </div>
                    <div class="replenshiment__bottom">
                        <div class="replenshiment__cost">{$price|price_format} ₽</div>
                        <div class="replenshiment__price">
                            <span>{($price / $package)|price_format} ₽</span>
                            за отчёт    
                        </div>
                    </div>
                    <a class="replenshiment__pay button" href="/api/payments?package_id={$item.id}">Оплатить</a>
                </li>
            {/foreach}
        </ul>

        <div class="replenshiment__description">
            {'introtext'|resource}
        </div>
    </div>
{/block}
{block 'blocks'}{/block}