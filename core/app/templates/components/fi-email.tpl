{$fields = [
	'Имя' => $fullname|default:('fullname'|user),
	'Телефон' => $phone|default:('phone'|user),
	'Email' => $email|default:('email'|user),
	'Город' => $city|default:('city'|user),
	'Комментарий' => $comment,
	'Сообщение' => $message
]}
{$page = $modx->makeUrl('id'|resource, '', '', 0)}
{$css_cell = 'style="border-bottom-width: 1px; border-bottom-color: #ccc; border-bottom-style: solid;"'}


<!doctype html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
	<div style="height:100%; padding: 32px 64px; border-radius: 12px; background: #f6f8fa; text-align: center; color: #24292f;">
		<div style="max-width: 450px; margin: 0 auto;">
			<h2>
				{if $emailSubject}
					{$emailSubject}
				{else}
					[[+emailSubject]]
				{/if}
			</h2>
			<table style="width: 100%; margin-bottom: 24px; text-align: left;" cellpadding="8">
				{foreach $fields as $key => $field}
					{if $field}
						<tr>
							<td {$css_cell}>{$key}:  </td>
							<td {$css_cell}>{$field}</td>
						</tr>
					{/if}
				{/foreach}
				<tr>
					<td {$css_cell}>Со страницы:</td>
					<td {$css_cell}><a href="{$page}">{'pagetitle'|resource}</a></td>
				</tr>
			</table>
			<p><small><em>Пользователь дал согласие на обработку персональных данных</em></small></p>
			<p><small>{'site_url'|config}</small></p>
		</div>
	</div>
</body>
</html>