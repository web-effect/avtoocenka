{extends 'parts/section.tpl'}

{block 'section-main'}
    {$items = '@FILE query'|snippet:[
        'limit' => 0,
        'select' => ['content', 'introtext', 'pagetitle', 'publishedon', 'uri', 'image','alt'],
        'sortby' => ['publishedon', 'DESC'],
        'where' => ['parent' => 13]
    ]:0}

    <div class="b-news__items | swiper grid arrow pager">
        {foreach $items as $item}
            <a class="b-news__item | swiper-slide" href="{$item.uri}">
                <div class="b-news__image">
                    {*<picture>
                        <source srcset="{$item.image|src|phpthumbon:'w=536&h=402&zc=T&f=webp'}" type="image/webp">
                        <img src="{$item.image|src}" width="536" height="402" loading="lazy" alt="{$item.alt|default:$item.pagetitle}">
                    </picture>*}
                    {include 'elements/image' phpthumbon="f=webp" h=228  image=$item.image|src hasbg=true alt=$item.alt|default:$item.pagetitle}  
                </div>
                <div class="b-news__body">
                    <div class="b-news__date">
                        {'/app/libs/bi/calendar-date.svg'|svg}
                        {$item.publishedon|fuzzyDate:'d F Y'}
                    </div>
                    <div class="b-news__title"><b>{$item.pagetitle}</b></div>
                    <div class="b-news__text">{$item.introtext|default:($item.content|strip_tags)|truncate:100}</div>
                </div>
            </a>
        {/foreach}
    </div>
{/block}
