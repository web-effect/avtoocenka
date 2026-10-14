{extends 'parts/section.tpl'}

{block 'section-main'}
    {* @todo видео, адаптив *}
    
    <div class="b-text-media__items {($text_media__invert) ? 'is-inverted' : ''}">
        <div class="b-text-media__text | content">{$content}</div>

        {if $image}
         <a href="{$image|src|phpthumbon:'&f=webp'}" data-fancybox="" class="b-text-media__item b-text-media__image">
             <picture>
                 <source srcset="{$image|src|phpthumbon:'w=800&f=webp'}" type="image/webp">
                 <img src="{$image|src|phpthumbon:'w=800&f=webp'}" alt="{$alt|default:$title}" loading="lazy">
             </picture>
         </a>
        {/if}
    </div>
{/block}
