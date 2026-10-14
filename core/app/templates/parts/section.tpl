{* задаём переменные (распаковываем из массивов) *}
{$block.gallery = json_decode($block.gallery, true)}
{foreach $block as $key => $val} 
    {assign $key $val}
{/foreach}
{foreach json_decode($block.params, true) as $i} 
    {assign $i.key $i.value}
{/foreach}

{* классы блока*}
{$classes = [
    'section',
    $block.tpl|replace:'.tpl':''|default:'b-text',
    "_id_{'id'|resource}-{$MIGX_id}",
    $block.bg_class
]}         

{* фоновая картинка*}
{if $image_bg} 
    <style>
        .{$block.tpl|replace:'.tpl':''|default:'b-text'}.{"_id_{'id'|resource}-{$MIGX_id}"} {
            background-image: url('{$image_bg|src|phpthumbon:"f=webp"}')
        }
    </style>
{/if}



{block 'section'}
    <section class="{implode(' ', $classes)}" id="section_id_{'id'|resource}-{$MIGX_id}">
        <div class="container">
            {block 'section-header'}
                {if $block.has_title}
                    <div class="section__header">
                        <h2 class="section__title | h1 font-title">{$title}</h2>
                        <div class="section__introtext">{$introtext}</div>
                    </div>
                {/if}
            {/block}

            {block 'section-main'}
                <div class="content">{$content|modx}</div>
            {/block}
        </div>
    </section>
{/block}