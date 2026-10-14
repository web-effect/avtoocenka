
{if 'alias'|resource != 'ya-search'}
    {nocache}
        {if $smarty.post.sort}
          {$sort = $smarty.post.sort}
        {elseif $smarty.get.sort}
          {$sort = $smarty.get.sort}
        {else}
          {$sort = ''}
        {/if}
        <form action="" class="p-catalog-sort | js-catalog-filter-faced">
            <div class="p-catalog-sort__title | js-catalog-filter-expand">Сортировка {"/app/libs/bi/chevron-down.svg"|svg}</div>
            <div class="p-catalog-sort__toggle-wrapper">
                <div class="p-catalog-sort__toggle">
                    <label class="checkbox">
                        <input id="sort-default"  name="sort" type="radio" value="" {(!$sort) ? 'checked' : ''}>
                        <span class="checkbox-check"></span>
                        <span>По умолчанию</span>
                    </label>
                    <label class="checkbox">
                        <input id="sort-price" name="sort" type="radio" value="price" {($sort === 'price') ? 'checked' : ''}>
                        <span class="checkbox-check"></span>
                        <span>По возрастанию цены</span>
                    </label>
                    <label class="checkbox">
                        <input id="sort-price-desc" name="sort" type="radio" value="price_desc" {($sort === 'price_desc') ? 'checked' : ''}>
                        <span class="checkbox-check"></span>
                        <span>По убыванию цены</span>
                    </label>
                    <label class="checkbox">
                        <input id="sort-abc" name="sort" type="radio" value="pagetitle" {($sort === 'pagetitle') ? 'checked' : ''}>
                        <span class="checkbox-check"></span>
                        <span>По алфавиту А-Я</span>
                    </label>
                    <label class="checkbox">
                        <input id="sort-abc-desc" name="sort" type="radio" value="pagetitle_desc" {($sort === 'pagetitle_desc') ? 'checked' : ''}>
                        <span class="checkbox-check"></span>
                        <span>По алфавиту Я-А</span>
                    </label>
                </div>
            </div>
        </form>
    {/nocache}
{/if}


{* Старый вариант *}
{*
{if 'alias'|resource != 'ya-search'}
    {nocache}
        {$sort = $smarty.get.sort|default:''}
        <form action="" class="p-catalog-sort | js-catalog-filter">
            <div class="p-catalog-sort-title">Сортировка:</div>
            <div>
                <input id="sort-default"  name="sort" type="radio" value="" {(!$sort) ? 'checked' : ''}>
                <label for="sort-default">по умолчанию</label>
            </div>
            <div class="p-catalog-sort-toggle">
                <input id="sort-price-desc" name="sort" type="radio" value="price_desc" 
                    {($sort === 'price_desc') ? 'checked' : ''}>
                <input id="sort-price" name="sort" type="radio" value="price"
                    {($sort === 'price') ? 'checked' : ''}>
                <label for="sort-price">по цене{'/app/libs/bi/sort-down.svg'|svg}</label>
                <label for="sort-price-desc">по цене{'/app/libs/bi/sort-down-alt.svg'|svg}</label>
            </div>
            <div class="p-catalog-sort-toggle">
                <input id="sort-abc-desc" name="sort" type="radio" value="pagetitle_desc"
                    {($sort === 'pagetitle_desc') ? 'checked' : ''}>
                <input id="sort-abc" name="sort" type="radio" value="pagetitle"
                    {($sort === 'pagetitle') ? 'checked' : ''}>
                <label for="sort-abc">по алфавиту{'/app/libs/bi/sort-alpha-down.svg'|svg}</label>
                <label for="sort-abc-desc">по алфавиту{'/app/libs/bi/sort-alpha-down-alt.svg'|svg}</label>
            </div>
        </form>
    {/nocache}
{/if}
*}