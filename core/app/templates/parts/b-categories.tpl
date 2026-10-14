{extends 'parts/section.tpl'}

{block 'section-main'}
    {$items = '@FILE query'|snippet:[
        'limit' => $limit|default:12,
        'select' => ['pagetitle', 'image', 'uri','alt'],
        'where' => ['parent' => $parent|default:('id'|resource)]
    ]:0}

    <div class="grid">
        {foreach $items as $item}
            {include 'elements/card'}
        {/foreach}
    </div>
{/block}