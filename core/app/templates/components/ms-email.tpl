{$style = [
    'logo' => 'display:block;margin: auto;',
    'a' => 'color:#348eda;',
    'p' => 'font-family: Arial;color: #666666;font-size: 12px;',
    'h' => 'font-family: Arial; color: #111111; font-weight: 200; line-height: 1.2em; margin: 40px auto; width: 90%;',
    'h1' => 'font-size: 36px;',
    'h2' => 'font-size: 28px;',
    'h3' => 'font-size: 22px;',
    'th' => 'font-family: Arial;text-align: left;color: #111111;',
    'td' => 'font-family: Arial;text-align: left;color: #111111;'
]}
{$site_url = ('site_url'|config)|regex_replace:'#/$#':''}
{$assets_url = 'assets_url'|config}
{$app_url = '/app/'}

<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{1|field:'site_name'}</title>
</head>

<body style="margin:0;padding:0;background:#f6f6f6;">
    <div style="height:100%;padding-top:20px;padding-bottom:20px;background:#f6f6f6;">
        {block 'logo'}
            {$logo = 1|field:'logo'}
            {if $logo}
                <a href="{$site_url}">
                    <img style="{$style.logo}" src="{$site_url}/assets/mgr/{$logo}" alt="{$site_url}" width="auto"
                        height="40" />
                </a>
            {/if}
        {/block}
        <!-- body -->
        <table class="body-wrap" style="padding:0 20px 20px 20px;width: 100%;background:#f6f6f6;margin-top:10px;">
            <tr>
                <td></td>
                <td class="container" style="border:1px solid #f0f0f0;background:#ffffff;width:800px;margin:auto;">
                    <div class="content">
                        <table style="width:100%;">
                            <tr>
                                <td>
                                    <h3 style="{$style.h}{$style.h3}">
                                        {block 'title'}
                                            {if $order.status == 1}
                                                Вы сделали заказ #{$num} на сайте {1|field:'site_name'}
                                            {elseif $order.status == 2}
                                                Вы оплатили заказ #{$num}
                                            {elseif $order.status == 3}
                                                Ваш заказ #{$num} был отправлен
                                            {elseif $order.status == 4}
                                                Ваш заказ #{$num} был отменён
                                            {else}
                                                Изменился статус вашего заказа #{$num}<br>
                                                Новый статус: {$order.status}
                                            {/if}
                                        {/block}
                                    </h3>

                                    {block 'comment'}
                                        {if $order.comment}
                                            <div style="margin: 0 auto 40px auto; width: 90%;">
                                                <strong>Комментарий:</strong> {$order.comment}
                                            </div>
                                        {/if}
                                    {/block}

                                    {block 'products'}
                                        <table style="width:90%;margin:auto;">
                                            <thead>
                                                <tr>
                                                    <th>&nbsp;</th>
                                                    <th style="{$style.th}">{'ms2_cart_title'|lexicon}</th>
                                                    <th style="{$style.th}">{'ms2_cart_count'|lexicon}</th>
                                                    {* <th style="{$style.th}">{'ms2_cart_weight'|lexicon}</th> *}
                                                    <th style="{$style.th}">{'ms2_cart_cost'|lexicon}</th>
                                                </tr>
                                            </thead>
                                            {foreach $products as $product}
                                                <tr>
                                                    <td style="{$style.th}">
                                                        {if $product.thumb}
                                                            <img src="{$site_url}{$product.thumb}" alt="{$product.pagetitle}"
                                                                title="{$product.pagetitle}" width="120" height="90"/>
                                                        {else}
                                                            <img src="{$site_url}{$app_url}img/product.png"
                                                                alt="{$product.pagetitle}" title="{$product.pagetitle}" width="120"
                                                                height="90" style="object-fit:cover;" />
                                                            {* <img src="{$site_url}{$assets_url}components/minishop2/img/web/ms2_small@2x.png"
                                                                alt="{$product.pagetitle}" title="{$product.pagetitle}" width="120"
                                                                height="90" /> *}
                                                        {/if}
                                                    </td>
                                                    <td style="{$style.th}">
                                                        {if $product.id}
                                                            <a href="{$product.id|url:['scheme' => 'full']}" style="{$style.a}">
                                                                {$product.name}
                                                            </a>
                                                        {else}
                                                            {$product.name}
                                                        {/if}
                                                        {if $product.options}
                                                            <div class="small">
                                                                {$product.options|join:'; '}
                                                            </div>
                                                        {/if}
                                                    </td>
                                                    <td style="{$style.th}">{$product.count} {'ms2_frontend_count_unit'|lexicon}
                                                    </td>
                                                    {* <td style="{$style.th}">{$product.weight} {'ms2_frontend_weight_unit'|lexicon}</td> *}
                                                    <td style="{$style.th}">{$product.price} {'ms2_frontend_currency'|lexicon}
                                                    </td>
                                                </tr>
                                            {/foreach}
                                            <tfoot>
                                                <tr>
                                                    <th colspan="2"></th>
                                                    <th style="{$style.th}">
                                                        {$total.cart_count} {'ms2_frontend_count_unit'|lexicon}
                                                    </th>
                                                    {* <th style="{$style.th}">
                                                {$total.cart_weight} {'ms2_frontend_weight_unit'|lexicon}
                                            </th> *}
                                                    <th style="{$style.th}">
                                                        {$total.cart_cost} {'ms2_frontend_currency'|lexicon}
                                                    </th>
                                                </tr>
                                            </tfoot>
                                        </table>

                                        <h3 style="{$style.h}{$style.h3} font-size: 16px;">
                                            {'ms2_frontend_order_cost'|lexicon}:
                                            {if $total.delivery_cost}
                                                {$total.cart_cost} {'ms2_frontend_currency'|lexicon} + {$total.delivery_cost}
                                                {'ms2_frontend_currency'|lexicon} =
                                            {/if}
                                            <strong>{$total.cost}</strong> {'ms2_frontend_currency'|lexicon}
                                        </h3>

                                        {if $payment_link && $order.status == 1}
                                            <p style="margin-left:20px;{$style.p}">
                                                Если вы случайно прервали процедуру оплаты, вы всегда можете <a
                                                    href="{$payment_link}" style="color:#348eda;">продолжить её по этой
                                                    ссылке</a>.
                                            </p>
                                        {/if}
                                    {/block}
                                </td>
                            </tr>
                        </table>
                    </div>
                    <!-- /content -->
                </td>
                <td></td>
            </tr>

            <tr>
                <td></td>
                <td style="height: 32px"></td>
                <td></td>
            </tr>

            <tr>
                <td></td>
                <!-- контакты -->
                <td class="container" style="border:1px solid #f0f0f0;background:#ffffff;width:800px;margin:auto;">
                    <table style="width:90%; margin:auto; padding:32px 0;">
                        <thead></thead>
                        <tbody>
                            <tr>
                                <td>Способ доставки:</td>
                                <td>{$delivery.name}</td>
                            </tr>
                            <tr>
                                <td>Способ оплаты:</td>
                                <td>{$payment.name}</td>
                            </tr>
                            {* <tr>
                                <td>Email:</td>
                                <td>{$user.email}</td>
                            </tr> *}
                            

                            {foreach $address as $key => $value}
                                {if $value && !in_array($key, ['id','order_id','user_id','createdon','updatedon','city', 'street', 'building', 'room', 'index'])}
                                    <tr>
                                        <td>{("ms2_frontend_{$key}"|lexicon)|default:$key}:</td>
                                        <td>{$value}</td>
                                    </tr>
                                {/if}
                                
                            {/foreach}
                            
                            {if $delivery.id != 1}
                                {if $address.region}
                                    {$region = $address.region}
                                {/if}
                                {if $address.city}
                                    {$city = 'г.'|cat:$address.city}
                                {/if}
                                {if $address.street}
                                    {$street = 'улица '|cat:$address.street}
                                {/if}
                                {if $address.building}
                                    {$building = 'дом '|cat:$address.building}
                                {/if}
                                {if $address.room}
                                    {$room = 'квартира '|cat:$address.room}
                                {/if}
                                {if $address.index}
                                    {$index = $address.index}
                                {/if}
                                {$address_one = $street|cat:', '|cat:$building|cat:', '|cat:$room|cat:', '|cat:$city|cat:', '|cat:$region|cat:', '|cat:$index}
                                
                                <tr>
                                    <td>{("ms2_frontend_address"|lexicon)}:</td>
                                    <td>{$address_one}</td>
                                </tr>
                            {/if}
                        </tbody>
                    </table>
                </td>
                <td></td>
            </tr>

        </table>
        <!-- /body -->
        <!-- footer -->
        <table style="clear:both !important;width: 100%;">
            <tr>
                <td></td>
                <td class="container">
                    <!-- content -->
                    <div class="content">
                        <table style="width:100%;text-align: center;">
                            <tr>
                                <td align="center">
                                    <p style="{$style.p}">
                                        {block 'footer'}
                                            <a href="{$site_url}" style="color: #999999;">
                                                {1|field:'site_name'}
                                            </a>
                                        {/block}
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <!-- /content -->
                </td>
                <td></td>
            </tr>
        </table>
        <!-- /footer -->
    </div>
</body>

</html>