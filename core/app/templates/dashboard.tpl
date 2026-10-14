{extends 'base.tpl'}

{block 'main'}
{nocache}
    {$profile = \App\Services\UserService::getProfile()}
    {$availableReports = \App\Services\UserService::getAvailableReports()}

    {* Словарь *}
    {$Dict = '\\App\\Services\\DictionaryService'}
    <script>
        window.Dict = {
            engineTypes: {$Dict::ENGINE_TYPES|json_encode},
            gearTypes: {$Dict::GEAR_TYPES|json_encode},
            transmissions: {$Dict::TRANSMISSIONS|json_encode},
            steeringWheels: {$Dict::STEERING_WHEELS|json_encode},
            regions: {$Dict::REGIONS|json_encode},
        };
    </script>
    
    

    <div class="block">
        <div class="mobile__header">
            <div class="mobile__header-container container">
                <a href="/" class="mobile__logo">
                    <div class="mobile__image">
                        <img src="{1|field:'logo'|phpthumbon:'w=500&f=webp'}" alt="{1|field:'site_name'}" width="230">
                    </div>
                    <div class="mobile__header-content">
                        <div class="mobile__sitename">АвтоОценка</div>
                        <div class="mobile__header-descriptor">{1|field:'descriptor'}</div>
                    </div>
                </a>
                <div class="mobile__header-wrapper">
                    <a class="mobile__reports" href="{319|url}">
                        {'/app/img/icons/replenishment.svg'|svg}
                        <div class="mobile__reports-content">14</div>
                        <div class="mobile__reports-text">
                            Доступно:
                            <span>
                                {$availableReports}
                                {$availableReports|plural:'отчёт':'отчёта':'отчётов'}
                            </span>
                        </div>
                    </a>
                    <button class="mobile__menu" id="menuToggle">
                        {'/app/img/icons/menu.svg'|svg}
                    </button>
                </div>
            </div>
        </div>
        <div class="overlay"></div>
        <div class="header-mobile">
            <div class="header-mobile__container container">
                <a href="/" class="header-mobile__logo mobile__logo">
                    <div class="header-mobile__logo-image mobile__image">
                        <img src="{1|field:'logo'|phpthumbon:'w=500&f=webp'}" alt="{1|field:'site_name'}" width="230">
                    </div>
                    <div class="header-mobile__header-content mobile__header-content">
                        <div class="header-mobile__sitename mobile__sitename">АвтоОценка</div>
                        <div class="header-mobile__header-descriptor mobile__header-descriptor">{1|field:'descriptor'}</div>
                    </div>
                </a>
                <div class="header-mobile__column">
                    <a class="header-mobile__button {if 'id'|resource == 321}active{/if}" href="dashboard/reports/new">{'/app/img/icons/add.svg'|svg} Новая оценка</a>
                    <a class="header-mobile__button {if 'id'|resource == 317}active{/if}" href="{317|url}">{'/app/img/icons/reports.svg'|svg} Отчёты</a>
                    <a class="header-mobile__button {if 'id'|resource == 318}active{/if}" href="{318|url}">{'/app/img/icons/profile.svg'|svg} Оценщик</a>
                </div>
                <div class="header-mobile__column">
                    <a class="header-mobile__button {if 'id'|resource == 319}active{/if}" href="{319|url}">{'/app/img/icons/replenishment.svg'|svg} Пополнение</a>
                    <a class="header-mobile__button {if 'id'|resource == 320}active{/if}" href="{320|url}">{'/app/img/icons/info.svg'|svg} Инструкция</a>
                    <a class="header-mobile__button {if 'id'|resource == 239}active{/if}" href="/profile">{'/app/img/icons/profile.svg'|svg} Профиль</a>
                    <a class="header-mobile__button" href="/auth/logout">{'/app/img/icons/exit.svg'|svg} Выйти</a>
                </div>
                <div class="header-mobile__column">
                    {$phones = explode('||', 16|field:'contacts.phone')}
                    {strip}
                        {foreach $phones as $phone}
                            <a class="header-mobile__contact" target="_blank" href="tel:{$phone|regex_replace:'/[ \(\)\-]/':''}">
                                {$phone}
                            </a>
                        {/foreach}
                    {/strip}
                    
                    {$emails = explode('||', 16|field:'contacts.email')}
                    {strip}
                        {foreach $emails as $email}
                            <a class="header-mobile__contact" target="_blank" href="mailto:{$email}">
                                {$email}
                            </a>
                        {/foreach}
                    {/strip}
                    
                    <div class="header-mobile__socials">
                        {foreach json_decode(16|field:'contacts.soc', true) as $item}
                            {if $item.on}
                                <a class="header-mobile__soc" href="{$item.link}" target="_blank" rel="nofollow noopener">
                                    {$item.icon|source|svg}
                                </a>
                            {/if}
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
        <div class="block__container container">
            <div class="block__inner">
                <div class="block__header">
                    <a href="/" class="block__logo">
                        <div class="block__image">
                            <img src="{1|field:'logo'|phpthumbon:'w=500&f=webp'}" alt="{1|field:'site_name'}" width="230">
                        </div>
                        <div class="block__header-content">
                            <div class="block__sitename">АвтоОценка</div>
                            <div class="block__descriptor">{1|field:'descriptor'}</div>
                        </div>
                    </a>
                    <h1 class="block__pagetitle">{'pagetitle'|resource}</h1>
                    <div class="block__header-wrapper">
                        <a class="block__reports" href="{319|url}">
                            {'/app/img/icons/replenishment.svg'|svg}
                            <div class="block__reports-text">
                                Доступно:
                                <span>
                                    {$availableReports}
                                    {$availableReports|plural:'отчёт':'отчёта':'отчётов'}
                                </span>
                            </div>
                        </a>
                        <a href="/auth/logout" class="block__exit">
                            <span>Выйти</span>
                            {'/app/img/icons/exit.svg'|svg}
                        </a>
                    </div>
                    {if 'id'|resource == 317}
                        <a href="dashboard/reports/new" class="block__report">Новая оценка {'/app/img/icons/add.svg'|svg}</a>
                    {/if}
                </div>
                <div class="block__main">
                    <div class="block__sidebar">   
                        <a href="dashboard/reports/new" class="block__grade {if 'id'|resource == 321}active{/if}">{'/app/img/icons/add.svg'|svg} Новая оценка</a>
                        <div class="block__column">
                            <a class="block__button {if 'id'|resource == 317}active{/if}" href="{317|url}">{'/app/img/icons/reports.svg'|svg} Отчёты</a>
                            <a class="block__button {if 'id'|resource == 318}active{/if}" href="{318|url}">{'/app/img/icons/profile.svg'|svg} Оценщик</a>
                        </div>
                        <div class="block__column">
                            <a class="block__button {if 'id'|resource == 319}active{/if}" href="{319|url}">{'/app/img/icons/replenishment.svg'|svg} Пополнение</a>
                            <a class="block__button {if 'id'|resource == 320}active{/if}" href="{320|url}">{'/app/img/icons/info.svg'|svg} Инструкция</a>
                            <a class="block__button {if 'id'|resource == 239}active{/if}" href="/dashboard/profile">{'/app/img/icons/profile.svg'|svg} Профиль</a>
                        </div>
                    </div>
                    <div class="block__content">
                        {block 'page_content'}{/block}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="menu">
        <div class="menu__container container">
            <a class="menu__button" href="dashboard/reports/new">{'/app/img/icons/add.svg'|svg} Оценка</a>
            <a class="menu__button" href="{317|url}">{'/app/img/icons/reports.svg'|svg} Отчёты</a>
            <a class="menu__button" href="{318|url}">{'/app/img/icons/profile.svg'|svg} Оценщик</a>
            <a class="menu__button" href="{319|url}">{'/app/img/icons/replenishment.svg'|svg} Пополнение</a>
            <a class="menu__button" href="{320|url}">{'/app/img/icons/info.svg'|svg} Инструкция</a>
        </div>
    </div>
    
    <script>
        // Mobile menu
        $(document).ready(function () {
            let isOpen = false;
            
            const overlay = $('.overlay');
            const menu = $('.header-mobile');
            const button = $('#menuToggle');
            const body = $('body');
            
            function openMenu() {
                menu.stop(true, true).animate({
                    left: 0,
                    opacity: 1
                }, 200);
                
                button.addClass('is-open');
                body.addClass('modal-open');
                overlay.addClass('active');
                isOpen = true;
            }
            
            function closeMenu() {
                menu.stop(true, true).animate({
                    left: '-100%',
                    opacity: 0
                }, 200);
                
                button.removeClass('is-open');
                body.removeClass('modal-open');
                overlay.removeClass('active');
                isOpen = false;
            }
            
            button.on('click', function () {
                isOpen ? closeMenu() : openMenu();
            });
            
            $('.header-mobile__link').on('click', function () {
                if (isOpen) {
                    closeMenu();
                }
            });
            
            $(window).on('resize', function () {
                if (window.innerWidth > 767 && isOpen) {
                    closeMenu();
                }
            });
            
            
            $(document).on('click', function (e) {
                if (!isOpen) return;
                
                const target = $(e.target);
                
                // Клик внутри меню
                const insideMenu = target.closest('.header-mobile').length;
                
                // Клик по кнопке открытия
                const insideButton = target.closest('#menuToggle').length;
                
                if (!insideMenu && !insideButton) {
                    closeMenu();
                }
            });
        });
    </script>
{/nocache}
{/block}