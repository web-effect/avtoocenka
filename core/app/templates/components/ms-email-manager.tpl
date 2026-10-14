{extends 'components/ms-email.tpl'}
{block 'title'}
    {if $order.status == 1}
        У вас новый заказ #{$num} на сайте {1|field:'site_name'}
    {elseif $order.status == 2}
        Заказ #{$num} был оплачен
    {elseif $order.status == 4}
        Заказ #{$num} был отменён
    {else}
        Изменился статус заказа #{$num}<br>
        Новый статус: {$order.status}
    {/if}
{/block}