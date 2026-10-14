{extends 'dashboard.tpl'}
{block 'header'}
{/block}
{block 'page_content'}
    {'AuthCheck'|snippet nocache}

    <div class="reports" id="reports">
        {nocache}
            {ajax key="reports"}
                {* {$items_all = '@FILE query'|snippet:[
                    'limit' => 0,
                    'select' => ['id', 'car_name', 'car_year', 'plate_number', 'car_vin', 'report_date', 'client_name', 'report_summary', 'report_status', 'user_id', 'vehicle', 'step'],
                    'sortby' => ['publishedon', 'DESC'],
                    'where' => [
                        'parent' => 323,
                        'user_id' => $modx->user->id
                    ]
                ]}
                
                {if $smarty.post['all']}
                    {$items = $items_all}
                {else}
                    {$items = '@FILE query'|snippet:[
                        'limit' => 5,
                        'select' => ['id', 'car_name', 'car_year', 'plate_number', 'car_vin', 'report_date', 'client_name', 'report_summary', 'report_status', 'user_id', 'vehicle', 'step'],
                        'sortby' => ['publishedon', 'DESC'],
                        'where' => [
                            'parent' => 323,
                            'user_id' => $modx->user->id
                        ]
                    ]}
                {/if} *}

                {$result = \App\Services\ReportService::getList(['limit' => ($smarty.post['all']) ? 0 : 5])}
            
                <div class="reports__table">
                    <table>
                        <thead>
                            <tr>
                                <th class="reports__head-id">#</th>
                                <th class="reports__head-auto">Авто</th>
                                {* <th class="reports__head-number">гос.номер</th> *}
                                <th class="reports__head-vin">vin</th>
                                <th class="reports__head-date">дата</th>
                                <th class="reports__head-name">ФИО заказчика</th>
                                <th class="reports__head-result">Итог</th>
                                <th class="reports__head-status">Статус</th>
                                <th class="reports__head-action">Действие</th>
                            </tr>
                        </thead>
                        <tbody>                        
                            {foreach $result.items as $item}
                                {* {$item_step = '@FILE get_report_step'|snippet:['report_id' => $item.id]} *}
                                {* {$vehicle = json_decode($item.vehicle, true)} *}
                                <tr>
                                    <td class="reports__table-id">
                                        {$item@iteration}
                                    </td>
                                    <td class="reports__auto">
                                        <div class="reports__car-name">{$item.vehicle.brand_name} {$item.vehicle.model_name}</div>
                                        <div class="reports__year">{$item.vehicle.year}</div>
                                    </td>
                                    {* <td class="reports__number">
                                        {$item.plate_number}
                                    </td> *}
                                    <td class="reports__vin">
                                        {$item.vehicle.vin}
                                    </td>
                                    <td class="reports__date">
                                        {$item.evaluation_date|date_format:"%d-%m-%Y"}
                                    </td>
                                    <td class="reports__name">
                                        {$item.customer.fullname}
                                    </td>
                                    <td class="reports__result">
                                        {if $item.step >= 2}
                                            {$item.final_price|price_format} ₽
                                        {else}
                                            —
                                        {/if}
                                    </td>
                                    <td class="reports__status">
                                        <div class="reports__status-wrapper">
                                            {if $item.step == 5}
                                                <div class="reports__status-value">Готов</div>
                                            {else}    
                                                <div class="reports__status-draft">Черновик</div>
                                            {/if}
                                            {if $item.sent_emails}
                                                <div class="reports__status-send">{'/app/img/icons/sent.svg'|svg} Отправлено</div>
                                            {/if}
                                        </div>
                                    </td>
                                    <td class="reports__action">
                                        {if $item.step == 5}
                                            <a href="/dashboard/reports/{$item.id}" class="reports__documents">Документы</a>
                                        {else}
                                            <a href="/dashboard/reports/{$item.id}" class="reports__continue">Продолжить</a>
                                        {/if}
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                <div class="reports__bottom">
                    <div class="reports__shown">Показано {$result.items|count} из {$result.total}</div>
                    
                    {if $result.total > 5 && empty($smarty.post['all'])}
                        <button 
                            class="reports__all"
                            hx-post="ajax/reports"
                            hx-vals='{
                                "all": true
                            }'
                            hx-target="#reports"
                            hx-swap="innerHTML"
                        >
                            Показать все
                        </button>
                    {/if}
                </div>
            {/ajax}
        {/nocache}
    </div>   
{/block}
{block 'blocks'}{/block}