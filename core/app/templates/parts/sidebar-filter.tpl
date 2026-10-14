<div class="catalog-filter-wrapper">
    <button class="catalog-filter-expand | button is-outlined is-fullwidth | js-catalog-filter-expand">Фильтр</button>
    
    <form class="catalog-filter | js-catalog-filter">
        {nocache}
            {$data = '@FILE ms_filter'|snippet:[]}
            
            {foreach $data as $key => $item}
                <section class="filter-section">
                    <h3 class="filter-section-title h6">{$item.label}</h3>
                    {if $item.type == 'select'}
                        <div class="filter-checkboxes-list">
                            {foreach $item.values as $val}
                                {$checked = (in_array($val, explode(',', $smarty.get["filter_{$key}"]))) ? 'checked' : ''}
                                <label class="checkbox">
                                    <input name="filter_{$key}[]" value="{$val}" type="checkbox" {$checked}>
                                    <span class="checkbox-check"></span>
                                    <span>{$val}</span>
                                </label>
                            {/foreach}

                        </div>
                    {/if}
                    
                    {if $item.type == 'range'}
                        <div class="filter-ranger">
                            <div class="filter-ranger-inputs">
                                <label>
                                    <input type="number" class="input"
                                        placeholder="{$item.min}"
                                        name="min_{$key}"
                                        min="{$item.min}" max="{$item.max}"
                                        value='{$smarty.get["min_{$key}"]}'
                                    >
                                </label>
                                <label>
                                    <input type="number" class="input"
                                        placeholder="{$item.max}"
                                        name="max_{$key}"
                                        min="{$item.min}" max="{$item.max}"
                                        value='{$smarty.get["max_{$key}"]}'
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