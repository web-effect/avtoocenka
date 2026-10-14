{extends 'dashboard.tpl'}
{block 'header'}
{/block}
{block 'page_content'}
    {'AuthCheck'|snippet nocache}

    {$video = 'id'|resource|field:'instruction_video'}
    {$timeline = 'id'|resource|field:'instruction_timeline'}
    {$description_title = 'id'|resource|field:'instruction_title'}
    {$description_time = 'id'|resource|field:'instruction_time'}
    {$description = 'id'|resource|field:'instruction_description'}
    {$faq = 'id'|resource|field:'instruction_faq'}
    {$buttons = 'id'|resource|field:'instruction_buttons'}

    <div class="instructions">
        {* <div class="instruction__block">
            <div class="instruction__video-wrapper">
                <div class="instruction__video">
                    <video controls preload="metadata">
                        <source src="{$video|src}">
                    </video>
                    <button class="instruction__preview">
                        <div class="instruction__preview-container">
                            <div class="instruction__preview-button">
                                {'/app/img/icons/play.svg'|svg}
                            </div>
                            <div class="instruction__preview-content">
                                <div class="instruction__preview-title">Как работать с АвтоОценкой</div>
                                <div class="instruction__preview-description">Нажмите, чтобы посмотреть</div>
                            </div>
                        </div>
                    </button>
                </div>
                <div class="instruction__video-description">
                    <div class="instruction__video-header">
                        <div class="instruction__video-description-title">{$description_title}</div>
                        {if $description_time}
                            <div class="instruction__video-time">{$description_time}</div>
                        {/if}
                    </div>
                    <div class="instruction__video-description-text">
                        {$description}
                    </div>
                </div>
            </div>
            <div class="instruction__video-content">
                <div class="instruction__video-title">Содержание</div>
                <div class="instruction__video-timeline-wrapper">
                    <div class="instruction__video-timeline">
                        {if $timeline}
                            {$timeline = json_decode($timeline, true)}
                            
                            {foreach $timeline as $item}
                                <div class="instruction__video-item">
                                    <span>{$item.title}</span>
                                    {$item.description}
                                </div>
                            {/foreach}
                        {/if}
                    </div>
                </div>
            </div>
        </div>
         *}
        <div class="instruction__faq">
            <div class="instruction__faq-title">Частые вопросы</div>
            <div class="instruction__faq-items">
                {if $faq}
                    {$faq = json_decode($faq, true)}
                    
                    {foreach $faq as $elem}
                        <div class="instruction__faq-item">
                            <button class="instruction__faq-header">
                                <div class="instruction__faq-item-title">{$elem.title}</div>
                                {'/app/img/icons/arrow-down-dark.svg'|svg}
                            </button>
                            <div class="instruction__faq-content">
                                <div class="instruction__faq-content-inner">
                                    {$elem.content}
                                </div>
                            </div>
                        </div>
                    {/foreach}
                {/if}
            </div>
        </div>
        
        <div class="instruction__bottom">
            <div class="instruction__bottom-left">
                <div class="instruction__bottom-title">Нужна помощь?</div>
                <div class="instruction__bottom-description">Ответим в течение 4 рабочих часов</div>
            </div>
            <div class="instruction__bottom-buttons">
                {if $buttons}
                    {$buttons = json_decode($buttons, true)}
                    
                    {if $buttons[0]}
                        <a class="instruction__bottom-button-v1" href="{$buttons[0]['description']}" target="_blank">{$buttons[0]['title']} {'/app/img/icons/arrow-right-light.svg'|svg}</a> 
                    {/if}
                    {if $buttons[1]}
                        <a class="instruction__bottom-button-v2" href="#modal-help" data-fancybox>
                            {$buttons[1]['title']}
                        </a>
                        {* <a class="instruction__bottom-button-v2" href="{$buttons[1]['description']}" target="_blank">{$buttons[1]['title']}</a> *}
                    {/if}
                {/if}
            </div>
        </div>
    </div>   

    <script>
    $('.instruction__preview').on('click', function () {
        const $video = $(this).siblings('video');
        
        $video.prop('controls', true);
        $video[0].play();
        
        $(this).fadeOut(100);
    });
    
    $(document).on('click', '.instruction__faq-header', function () {
        const $item = $(this).closest('.instruction__faq-item');
        const $content = $item.find('.instruction__faq-content');
        
        $item.toggleClass('active');
        $content.stop(true, true).slideToggle(100);
    });
    </script>
{/block}
{block 'blocks'}{/block}