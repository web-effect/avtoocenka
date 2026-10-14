{extends 'parts/section.tpl'}

{block 'section-main'}

    <style>
        @media(min-width: 1200px) {
            .b-advantages__items {
                --columns: {($gallery|count > 5) ? 5 : $gallery|count};
            }
        }
    </style>

    <div class="b-advantages__items | grid">
        {foreach $gallery as $k=>$item}
            <div class="b-advantages__item">
                <div class="b-advantages__item-image">
                   
                    {$svg = $item.image|src|svg}
                    {if $svg}
                        {$svg}
                    {else}
                    
                    
                        <img class="" src="{$item.image|src|phpthumbon:'w=120&f=webp'}" alt="{($item.alt|escape)|default:($item.title|escape)}" width="120" height="120" loading="lazy">
                    {/if}                        
                </div>

                <div class="b-advantages__item-title">
                    <b>{$item.title}</b>
                </div>
                <div class="b-advantages__item-description">{$item.description}</div>
            </div>
        {/foreach}
    </div>
{/block}
