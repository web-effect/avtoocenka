{* уберите :0 (кэш) у сниппета если использется аякс *}

{extends 'parts/section.tpl'}

{block 'section-main'}
    {$products = 'msProducts'|snippet:[
        'limit' => 12,
        'parents' => null,
        'where' => ['(popular = 1 OR new = 1 OR favorite = 1) AND deleted = 0'],
        'return' => 'JSON'
    ]:0}
    

    <div class="grid">
        {foreach json_decode($products, true) as $item}
            {include 'elements/card-product'}
        {/foreach}
    </div>
{/block}
