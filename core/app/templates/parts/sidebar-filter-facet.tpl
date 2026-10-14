<div class="catalog-filter-wrapper">
    <button class="catalog-filter-expand | button is-outlined is-fullwidth | js-catalog-filter-expand">Фильтр</button>
    
    <form class="catalog-filter | js-catalog-filter-faced">
        {nocache}
            {$data = '@FILE ms_filter_facet'|snippet:[]}
            
            
            
            {foreach $data as $key => $item}
                <section class="filter-section">
                    <h3 class="filter-section-title h6">{$item.label}</h3>
                    {if $item.type == 'select'}
                        <div class="filter-checkboxes-list">
                            {foreach $item.values as $val => $counts}
                                {if $val != ''}
                                {$checked = (in_array($val, explode(',', $smarty.get["filter_{$key}"]))) ? 'checked' : ''}
                                {$checked = ''}
                                {$props = []}
                                {if $smarty.post["filter_"|cat:$key]}
                                    {$props = $smarty.post["filter_"|cat:$key]}
                                {elseif $smarty.get["filter_{$key}"]}
                                    {$regex = preg_match_all('/\d+(,\d+)?\s*[^,]+/', $smarty.get["filter_{$key}"], $matches1)}
                                    
                                    {if count($matches1[0]) > 0}
                                        {$props = $matches1[0]}
                                    {else}
                                        {$props = explode(',', $smarty.get["filter_{$key}"])}
                                    {/if}
                                {else}
                                    {$checked = ''}
                                {/if}
                                {$checked = (in_array($val, $props)) ? 'checked' : ''}
                                <label class="checkbox">
                                    <input name="filter_{$key}[]" value="{$val}" type="checkbox" {$checked}>
                                    <span class="checkbox-check"></span>
                                    <span style="flex-grow: 1">{$val}</span>
                                    <span>{$counts}</span>
                                </label>
                                {/if}
                            {/foreach}
                        </div>
                    {/if}
                    
                    {if $item.type == 'range'}
                        <div class="filter-ranger">
                            <div class="filter-ranger-inputs">
                                {if $smarty.get["min_{$key}"]}
                                    {$value_min = $smarty.get["min_{$key}"]}
                                {elseif $smarty.post["min_{$key}"]}
                                    {$value_min = $smarty.post["min_{$key}"]}
                                {else}
                                    {$value_min = $item.min}
                                {/if}
                                
                                {if $smarty.get["max_{$key}"]}
                                    {$value_max = $smarty.get["max_{$key}"]}
                                {elseif $smarty.post["max_{$key}"]}
                                    {$value_max = $smarty.post["max_{$key}"]}
                                {else}
                                    {$value_max = $item.max}
                                {/if}
                                
                                <label>
                                    <input type="number" class="input"
                                        placeholder="{$item.min}"
                                        name="min_{$key}"
                                        min="{$item.min}" max="{$item.max}"
                                        value='{$value_min}'
                                    >
                                </label>
                                <label>
                                    <input type="number" class="input"
                                        placeholder="{$item.max}"
                                        name="max_{$key}"
                                        min="{$item.min}" max="{$item.max}"
                                        value='{$value_max}'
                                    >
                                </label>
                            </div>
                            <div class="js-catalog-filter-slider"></div>
                        </div>
                    {/if}
                </section>
            {/foreach}
        {/nocache}

        <div class="filter-section">
            <button class="button _gray" type="reset">Сбросить</button>
        </div>

    </form>
</div>