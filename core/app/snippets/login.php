<?php
$out = [];

if (!isset($_POST['username'])) {
    return $out;
}

$response = $modx->runProcessor('/security/login', [
    'username' => $_POST['username'],
    'password' => $_POST['password'] ?? '',
    'rememberme' => 1,
    'login_context' => $modx->context->key ?? 'web',
]);

if ($response->isError()) {
    $out['error'] = $response->getMessage();
} else {
    $out['success'] = 1;
    header('HX-Redirect: /dashboard');
}

return $out;