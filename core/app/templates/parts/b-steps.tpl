{extends 'parts/section.tpl'}

{block 'section-main'}
    <style>
        @media (min-width: 992px) {
            .b-steps__item {
                width: calc(100% / {$gallery|count});
            }
        }
    </style>
    
    <div class="b-steps__items">
        {foreach $gallery as $k=>$item}
            {if $k < 4}
                <div class="b-steps__item">
                    <div class="b-steps__item-point"></div>
                    <div class="b-steps__item-title">
                        <b>{$item.alt} {$item@index+1}</b>
                        <span>{$item.title}</span>
                    </div>
                </div>            
            {/if}
        {/foreach}
    </div>
{/block}
