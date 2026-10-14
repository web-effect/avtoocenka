<?php

if (empty($_POST) || !empty($_POST['no_action'])) return [];

$out = [];
$site_name = $modx->getOption('site_name', null, 'Сайт');




$form_title = $form_title ?? 'Форма';
$ya_target = $form_ya_target ?? 'form';
$ga_target = $form_ga_target ?? $ya_target;
$form_privacy_text = $form_privacy_text ?? "
    <div class='form-privacy'>
        <a class='form-privacy-ref' rel='noopener noreferrer nofollow' href='/privacy-policy' target='_blank'><img src='app/img/politika.svg'></a>
    </div>
";

$out['privacy_text'] = $form_privacy_text;
$out['metrika'] = "
    <script>Forms.metrika('{$ya_target}')</script>
    <script>Forms.ga('{$ga_target}')</script>
";


// Экранируем $site_name для использования в заголовках письма
//$emailFromName = '"' . str_replace('"', '\"',  addslashes($site_name)) . '"'; // Дополнительно экранируем двойные кавычки
$emailFromName = mb_encode_mimeheader($site_name, 'UTF-8', 'Q');


$default = [
    'hooks' => 'email,FormItSaveForm',
    'successMessage' => "
        <div class='message is-success'>
            Спасибо!<br>Наши специалисты свяжутся с вами в ближайшее время
        </div>
        {$out['metrika']}
    ",
    'validate' => 'fullname:required',
    'validationErrorMessage' => '
        <div class="message is-danger">Произошла ошибка проверки формы. Пожалуйста, проверьте введенные значения</div>
    ',
    'emailTpl' => 'fi-email.tpl',
	'emailSubject' => "Заявка с формы «{$form_title}» на сайте «{$site_name}»",
	'emailTo' => $modx->getOption('formit.user_email', null, 'effect.emails@gmail.com'),
	'emailFrom' => $modx->getOption('emailsender', null, 'noreply@domain.com'),
	'emailFromName' => $emailFromName,
	'emailReplyTo' => $_POST['email'] ?? '',
	'emailReplyToName' => $_POST['fullname'] ?? '',
];
// $default['customValidators'] = 'recaptcha.v3';
// $default['validate'] = 'fullname:required,g-recaptcha-response:recaptcha.v3';

$params = array_merge($default, $scriptProperties);


// только при аякс-запросе
if (!empty($_SERVER['HTTP_HX_REQUEST'])) {
    $modx->getService('lexicon','modLexicon');
    $modx->lexicon->load('ru:formit:default'); 
    $formit = $modx->runSnippet('Formit', $params);
}

foreach ($modx->placeholders as $key => $value) {
    if (stripos($key, 'fi.') !== false) {
        $out[str_replace(['fi.', '.'], ['', '_'], $key)] = $value;
    }
}


return $out;