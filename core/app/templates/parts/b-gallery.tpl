{extends 'parts/section.tpl'}

{block 'section-main'}
    <div class="b-gallery__slider | swiper grid arrow pager">
        {foreach $gallery as $item}
            <div class="swiper-slide">
                <a class="b-gallery__image" href="{$item.image|src|phpthumbon}" data-fancybox="b-gallery" data-caption="{$item.title|escape}">
                    {*<picture>
                        <source srcset="{$item.image|src|phpthumbon:'w=306&h=230&zc=T&f=webp'}" type="image/webp">
                        <img src="{$item.image|src}" alt="{($item.alt|escape)|default:($item.title|escape)}" width="306" height="230" loading="lazy">
                    </picture>*}
                    {include 'elements/image' phpthumbon="w=306&h=230&f=webp" h=230  image=$item.image|src hasbg=true alt=$item.name|escape}  
                </a>
            </div>
        {/foreach}
    </div>
{/block}
