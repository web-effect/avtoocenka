{extends 'parts/section.tpl'}

{block 'section-main'}
    <div class="b-faq__items">
        {foreach $gallery as $item}
            <div class="b-faq__item | spoiler | js-spoiler">
                <div class="b-faq__item-title | spoiler-title" role="button">{$item.title}</div>
                <div class="b-faq__item-content | spoiler-content content">{$item.content}</div>
            </div>
        {/foreach}
    </div>
{/block}
