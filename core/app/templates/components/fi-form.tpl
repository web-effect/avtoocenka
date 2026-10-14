{assign 'site_name' 1|field:'site_name' scope="parent"}

{$default = [
    'hooks' => 'email,FormItSaveForm',
    'successMessage' => '
        <div class="message is-success">Спасибо!<br>Наши специалисты свяжутся с вами в ближайшее время.</div>
    ',
    'validate' => 'fullname:required',
    'emailTpl' => 'fi-email.tpl',
	'emailSubject' => "Сообщение с сайта «{$site_name}»",
'emailTo' => 1|field:'mail_to',
'emailFrom' => 1|field:'mail_from',
'emailFromName' => $site_name,
'emailReplyTo' => $email,
'emailReplyToName' => $fullname
]}

{$params = array_merge($default, $params|default:[])}

{if $is_ajax}
    {'FormIt'|snippet:$params nocache}
{/if}

{capture assign="privacy"}
    <div class="form-box-privacy">
        Отправляя данные через форму, вы соглашаетесь с
        <a class="" href="{9|url}" target="_blank">Политикой обработки персональных данных</a>
    </div>
{/capture}
{assign 'privacy' $privacy scope="parent"}

{capture assign="errors"}
    {if 'fi.validation_error'|ph}
        <div class="message is-danger">
            Не удалось пройти валидацию формы
        </div>
    {/if}
{/capture}
{assign 'errors' $errors scope="parent"}