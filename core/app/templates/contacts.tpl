{extends 'base.tpl'}
{block 'main'}
    {$scss[] = 'pages/contacts' nocache}
    <main class="p-contacts | container">

        <div class="columns">
            <div class="contacts__wrapper">
                <div>
                    {'pdoCrumbs'|snippet:[ 
                        'showHome' => 1,
                        'tplWrapper' => 
                            '@INLINE <ul itemscope="" itemtype="http://schema.org/BreadcrumbList" class="breadcrumb">{$output}</ul>
                        ',
                        'tpl' =>
                        '@INLINE <li><span itemscope="" itemprop="itemListElement" itemtype="http://schema.org/ListItem">
                            <a title="{$menutitle}" itemprop="item" href="{$link}"><span itemprop="name">{$menutitle}</span>
                                <meta itemprop="position" content="{$idx}">
                            </a></span></li>',
                        'tplCurrent' =>
                        '@INLINE <li><span itemscope="" itemprop="itemListElement" itemtype="http://schema.org/ListItem">
                            <span itemprop="name">{$menutitle}</span>
                            <meta itemprop="position" content="{$idx}">
                        </span></li>'
                    ]}
            
                    {if !$no_title}
                        <h1 class="pagetitle">{'pagetitle'|resource|default:$pagetitle}</h1>
                    {/if}
                </div>
                 <div class="content">
                    {'content'|resource}
                </div>
            </div>
           
    
            <form action="" class="form-box is-fullwidth | js-form" hx-post="ajax/form_contacts">
                <div class="form-box-title h4">Написать нам</div>
                {ajax key='form_contacts'}
                    {$form = '@FILE form'|snippet:[
                        'form_title' => 'Написать нам',
                        'form_ya_target' => 'contacts'
                    ]}
                    {if $form.success}
						{$form.successMessage}
                    {else}
                        {include 'components/form-field' name='fullname' label='Ваше имя' attrs='required'}
                        {include 'components/form-field' name='email' label='Ваш email' type='email' attrs='required'}
                        {include 'components/form-field' name='phone' label='Ваш телефон' type='tel' attrs='required'}
                        {include 'components/form-field' name='message' label='Сообщение' type='textarea'}
                        {$form.validation_error_message}
                        <div class="form-privacy">
                        <input type="checkbox" required class="form-privacy-ref-input">
                            Даю согласие на обработку персональных данных на условиях и для целей, определенных в указанной форме сбора персональных данных, а также подтверждаю ознакомление с <a class="form-privacy-ref" rel="noopener noreferrer nofollow" href="/privacy-policy" target="_blank" tabindex="0">текстом политики в отношении обработки персональных данных</a>.
                        </div>
                        <div class="form-box-footer _center">
                            <button class="button is-fullwidth"><span>Отправить</span></button>
                        </div>
                    {/if}
                {/ajax}
            </form>
            
        </div>

    </main>

    <div class="section">
        <div class="container">
            <div class="ya-map" data-map='{16|field:'contacts.map'}' data-map-zoom="{16|field:'contacts.map_zoom'}">
                {if 16|field:'company_ref'}
                    <a class="company_ref" target="_blank" href="{16|field:'company_ref'}">
                		<ymaps class="" title="Мы на Яндекс Картах">
                			<ymaps class="ymaps__container">
                				<ymaps class="ymaps__pin">{'/app/img/yametka.svg'|svg}</ymaps>
                				<ymaps class="ymaps__text-container">
                					<ymaps class="ymaps__text">Мы на Яндекс Картах</ymaps>
                				</ymaps>
                			</ymaps>
                		</ymaps>
                	</a>
        	    {/if}
            </div>
        </div>
    </div>
{/block}
