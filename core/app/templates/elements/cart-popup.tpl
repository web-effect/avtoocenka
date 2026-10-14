<div class="cart-popup" x-data="{ cart: undefined }" @ms:cart-update.document="cart = Ms.cart.getStore()" x-bind:class="cart ? 'open' : ''">
    <div class="cart-popup__content">
        <div class="cart-popup__title">Ваша корзина</div>
        <ul class="cart-popup__list">
            <template x-for="item in cart">
                <div class="cart-popup__item | js-ms-item" x-bind:data-id="item.id">                   
                    <a x-bind:href="item.uri" class="cart-popup__item-image">
                        <img x-bind:src="item.image ? item.image : 'app/img/product.png'">
                    </a>
                    <a x-bind:href="item.uri" class="cart-popup__item-name" x-text="item.pagetitle"></a>
                    <div class="cart-popup__item-count">
                        <div class="cart-popup__item-counter" x-on:loading="$el.classList.add('is-loading');">
                            <button x-bind:class="Number(item.count) <= 1 ? 'disabled' : ''" @click.prevent="() => { Ms.cart.set(item.id, Number(item.count) && Number(item.count) - 1); $dispatch('loading')}">{'/app/libs/bi/dash-lg.svg'|svg}</button>
                            <span x-text="Number(item.count)"></span>
                            <button @click.prevent="() => { Ms.cart.set(item.id, Number(item.count) + 1); $dispatch('loading') }">{'/app/libs/bi/plus-lg.svg'|svg}</button>                        
                        </div>
                        <div class="cart-popup__item-price" x-text="`${ numFormat(item.price) } ₽/шт.`"></div>
                    </div>                   
                    <div class="cart-popup__item-cost" x-text="`${ numFormat(item.price * Number(item.count))}  ₽`"></div>
                    <div class="cart-popup__item-remove" @click="Ms.cart.set(item.id, 0)">{'assets/mgr/images/main/cart.svg'|svg}</div>
                </div>
            </template>  
        </ul>      
    </div>
    <div class="cart-popup__footer">
        <div class="cart-popup__cost" 
        x-data="{ priceCost: 0 }"
        x-init="$watch('cart', () => { if (cart) { priceCost = 0; for (k in cart) { priceCost = priceCost + cart[k]['price'] * Number(cart[k]['count'])} }});"
        x-html="`Итого: <b>${ numFormat(priceCost) } ₽</b>`"></div> 
        <a href="/checkout" class="button cart-popup__btn">Перейти в корзину</a>
    </div>  
</div>