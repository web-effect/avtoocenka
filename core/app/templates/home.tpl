{extends 'base.tpl'}
{block 'header'}{/block}
{block 'main'}
    <div class="main">
        <div class="main__bg">
            <img src="{1|field:'main_bg'|src|phpthumbon:'f=webp'}" alt="">
        </div>
        <div class="main__header">
            <div class="main__header-container container">
                <a href="/" class="main__logo">
                    <div class="main__image">
                        <img src="{1|field:'logo'|phpthumbon:'w=500&f=webp'}" alt="{1|field:'site_name'}" width="230">
                    </div>
                    <div class="main__header-content">
                        <div class="main__sitename">АвтоОценка</div>
                        <div class="main__header-descriptor">{1|field:'descriptor'}</div>
                    </div>
                </a>
                {guest nocache}
                    <a class="button" href="/auth/login">
                        {'/app/img/icons/login.svg'|svg}
                        <span>Войти в кабинет</span>
                    </a>
                {/guest}
                {auth nocache}
                    <a class="button" href="/dashboard">
                        {'/app/img/icons/profile.svg'|svg}
                        <span>Личный кабинет</span>
                    </a>
                {/auth}
            </div>
        </div>
        <div class="main__hero">
            <div class="main__hero-container container">
                <div class="main__hero-content">
                    <div class="main__hero-wrapper">
                        <div class="main__hero-title">{1|field:'main_title'}</div>
                        <div class="main__hero-description">{1|field:'main_description'}</div>
                    </div>
                    <div class="main__hero-text">{1|field:'main_content'}</div>
                    <a href="/auth/login" class="main__hero-button">Начать расчёт {'/app/img/icons/arrow-right-light.svg'|svg}</a>
                </div>
                <div class="main__hero-image">
                    <img src="{1|field:'main_image'|src}" alt="">
                </div>
            </div>
        </div>
    </div>
{/block}