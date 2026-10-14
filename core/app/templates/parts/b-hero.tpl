{extends 'parts/section.tpl'}

{block 'section-main'}

    {* Параметры swiper *}
    {$swiper_options = "{
        loop: true,
        slidesPerView: 1,
        spaceBetween: 10,
    }"}
    
    <div class="b-hero__slider swiper pager arrow" data-svg="{'app/libs/bi/arrow-right.svg'|svg|escape}"
        data-options='{$swiper_options|escape}'>
        {foreach $gallery as $k=>$item}
            <div class="swiper-slide">
                <div class="b-hero__content">
                    <div class="b-hero__title | font-title h2">{$item.title}</div>
                    <div class="b-hero__text">{$item.description}</div>
                    {if $item.alt_text}
                        <div>
                            <a href="{$item.alt_text|default:1|url}" class="button">Подробнее</a>
                        </div>
                    {/if}
                </div>

                <div class="b-hero__img">
                    
                    <picture>
                        <source srcset="{$item.image|src|phpthumbon:'w=1600&h=400&zc=1&f=webp'}" type="image/webp"
                            media="(min-width: 576px)">
                        <source srcset="{$item.image|src|phpthumbon:'w=575&h=350&zc=1&f=webp'}" type="image/webp"
                            media="(min-width: 0px)">
                        <img src="{$item.image|src}" alt="{$item.alt|default:($alt|cat:' '|cat:($k+1))}">
                    </picture>
                </div>
            </div>
        {/foreach}
    </div>
{/block}
