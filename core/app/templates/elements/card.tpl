<a class="card" href="{$item.uri}">
    <div class="card__image">
        {*<picture>
            <source srcset="{$item.image|src|phpthumbon:'w=300&h=225&zc=T&f=webp'}" type="image/webp">
            <img src="{$item.image|src}" alt="{$item.alt|default:$item.pagetitle}" width="300" height="225" loading="lazy">
        </picture>*}
        {$alt = $item.alt|default:$item.pagetitle}
        {include 'elements/image' phpthumbon="w=300&h=225&f=webp" h=225  image=$item.image|src hasbg=true alt=$alt}  
    </div>
    <div class="card__body">
        <div class="card__title">{$item.pagetitle}</div>
    </div>
</a>
