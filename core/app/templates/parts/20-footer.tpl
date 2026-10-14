<footer class="footer">
    <div class="footer__container container">
        <div class="footer__left">
            <a href="/" class="footer__logo">
                <div class="footer__image">
                    <img src="{1|field:'logo'|phpthumbon:'w=500&f=webp'}" alt="{1|field:'site_name'}" width="230">
                </div>
                <div class="footer__logo-content">
                    <div class="footer__sitename">АвтоОценка</div>
                    <div class="footer__descriptor">{1|field:'descriptor'}</div>
                </div>
            </a>
            
            <div class="footer__bottom">
                <div class="footer__privacy">{1|field:'copyright'|replace:'YEAR':date('Y')}</div>
                <a class="footer__developer" href="{1|field:'site_dev'}" target="_blank">
                    <span>Создание сайтов:</span>
                    {'/app/img/icons/effect.svg'|svg}
                </a>
            </div>
        </div>
        
        <div class="footer__right">
            <div class="footer__menu">
                <div class="footer__title">Документы</div>
                <div class="footer__column">
                    <a href="{313|url}" class="footer__link">Пользовательское соглашение</a>
                    <a href="{9|url}" class="footer__link">Политика персональных данных</a>
                    <a href="{314|url}" class="footer__link">Публичная оферта</a>
                    <a href="{305|url}" class="footer__link">Реквизиты</a>
                </div>
            </div>
            <div class="footer__contacts">
                <div class="footer__title">Контакты</div>
                <div class="footer__column">
                    {$phones = explode('||', 16|field:'contacts.phone')}
                    {strip}
                        {foreach $phones as $phone}
                            <a class="footer__contact" target="_blank" href="tel:{$phone|regex_replace:'/[ \(\)\-]/':''}">
                                {$phone}
                            </a>
                        {/foreach}
                    {/strip}
                    
                    {$emails = explode('||', 16|field:'contacts.email')}
                    {strip}
                        {foreach $emails as $email}
                            <a class="footer__contact" target="_blank" href="mailto:{$email}">
                                {$email}
                            </a>
                        {/foreach}
                    {/strip}
                    
                    <div class="footer__socials">
                        {foreach json_decode(16|field:'contacts.soc', true) as $item}
                            {if $item.on}
                                <a class="footer__soc" href="{$item.link}" target="_blank" rel="nofollow noopener">
                                    {$item.icon|source|svg}
                                </a>
                            {/if}
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer__bottom footer__bottom-mobile">
            <div class="footer__privacy">{1|field:'copyright'|replace:'YEAR':date('Y')}</div>
            <a class="footer__developer" href="{1|field:'site_dev'}" target="_blank">
                <span>Создание сайтов:</span>
                {'/app/img/icons/effect.svg'|svg}
            </a>
        </div>
    </div>
</footer>