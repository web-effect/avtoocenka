{$gallery = '@FILE ms_gallery'|snippet:['id' => $item.id]}
{$in_cart = $item.id|ms_in_cart}

<div class="prod-card _hoverable {($in_cart) ? 'is-added' : ''} | ms-item js-ms-item" data-id="{$item.id}"
x-data="{ product: {} }" x-init="product.count = {$in_cart}" @ms:cart-update.document="product = Ms.cart.getProduct({$item.id});">
    <a class="prod-card__link" href="{$item.uri}"></a>

    <div class="prod-card__badges prod-badges">
        {if $item.favorite == 1}
            <div class="prod-badges__item _action">Акция</div>
        {/if}
        {if $item.new == 1}
            <div class="prod-badges__item _new">Новинка</div>
        {/if}
        {if $item.popular == 1}
            <div class="prod-badges__item _hit">Хит</div>
        {/if}
    </div>

    {* В избранное *}
    {include 'elements/like' id=$item.id nocache}
     {$swiper_options = "{
        autoHeight: true,
        
    }"}

    <a class="prod-card__gallery swiper hover pager" href="{$item.uri}" data-options='{$swiper_options|escape}'>
        {foreach $gallery as $k=>$img}
        
            {if $k < 3}
            
                <div class="swiper-slide">
                    {*<img src="{$img.url|phpthumbon:"w=306&h=230&zc=1&f=webp"}" width="306" height="230" alt='{($img.name|escape)|default:($item.pagetitle|escape)}'>*}
                     {include 'elements/image' phpthumbon="w=306&h=230&f=webp" h='230'  image=$img.url hasbg=true alt=$alt}  
                     
                </div>               
            {/if}
        {/foreach}
    </a>

    
    <div class="prod-card__body">
        <div class="prod-card__title" href="{$item.uri}">
            {$item.pagetitle}
        </div>

        <div class="prod-card__price">
            {if $item.old_price && $item.price < $item.old_price}
                <span class="prod-card__price-old prod-price-old">{$item.old_price|price_format} ₽</span>
            {/if}
            <span class="prod-card__price-val">{$item.price|price_format} ₽</span>
        </div>
    </div>

    <div class="prod-card__footer">
        {if 'modules_shop'|config}
            <div class="prod-form">

                <button class="prod-card__add | hide-if-added button _light _fullwidth" @click="() => { Ms.cart.set({$item.id}, 1)};">
                    <span>В корзину</span>
                </button>

                <div class="prod-card__qty | prod-qty show-if-added" x-on:loading="$el.classList.add('is-loading');">
                    <button class="button _light" @click.prevent="() => { Ms.cart.set({$item.id}, Number(product.count) - 1), $dispatch('loading')}">{'/app/libs/bi/dash-lg.svg'|svg}</button>
                    <span x-text="product ? product.count : 0"></span>
                    <button class="button _light" @click.prevent="() => { Ms.cart.set({$item.id}, Number(product.count) + 1), $dispatch('loading')};">{'/app/libs/bi/plus-lg.svg'|svg}</button>
                </div>        
            </div>
        {/if}
    </div>
</div>
