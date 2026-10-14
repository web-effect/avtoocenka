{extends 'parts/section.tpl'}

{block 'section-main'}
    {$items = '@FILE query'|snippet:[
        'limit' => 4,
        'select' => ['content', 'pagetitle', 'uri', 'image'],
        'where' => ['parent' => 14]
    ]:0}

    <div class="b-reviews__items | swiper grid arrow pager">
        {foreach $items as $item}
            <div class="b-reviews__item swiper-slide">
                <div class="b-reviews__title"><b>{$item.pagetitle}</b></div>
                <div class="b-reviews__text content">{$item.introtext|default:$item.content}</div>
            </div>
        {/foreach}
    </div>
{/block}
