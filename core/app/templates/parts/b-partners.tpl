{extends 'parts/section.tpl'}

{block 'section-main'}
    <div class="b-partners__slider | swiper arrow grid pager" data-is-grid>
        {foreach $block.gallery as $item}
            <div class="swiper-slide b-partners__item">
                <img src="{$item.image|src|phpthumbon:'w=200&f=webp'}" alt="{($item.alt|escape)|default:($item.title|escape)}" loading="lazy">
            </div>
        {/foreach}
    </div>
{/block}
