<?php
namespace App\Plugins;

class App extends \Zoomx\Elements\Plugin
{

    public $disabled = false;
    public static $events = [
        'OnUserSave' => 0,
        //'OnWebPageInit' => 0,
    ];
    
    public function OnUserSave($scriptProperties = [])
    {   
        // добавляем 1 отчёт новым пользователям
        if ($scriptProperties['mode'] == 'new') {
            $user = $scriptProperties['user'];
            $profile = $user->getOne('Profile');
            $profile->set('fax', 1);
            $profile->save();
            //$this->modx->log(1, $scriptProperties['mode']);
        }
    }

    public function OnWebPageInit($scriptProperties = [])
    {   
        if (!$this->modx->user->id) {
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetime = 604800; // 7 дней
        $expires = time() + $lifetime;
        $sessionId = session_id();
        // $now = time();

        // Продлеваем cookie PHP-сессии
        setcookie(
            session_name(),
            $sessionId,
            [
                'expires' => $expires,
                'path' => '/',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        // Возможно это не нужно, надо тестировать с настройками php
        // "Обманываем" PHP, чтобы он принудительно сохранил сессию
        // Записываем в сессию текущий timestamp.
        $_SESSION['modx_session_keepalive'] = time();

        // Обновляем время активности MODX-сессии
        // $sessionTable = $this->modx->getTableName('modSession');
        // $this->modx->exec(
        //     "UPDATE {$sessionTable}
        //     SET access = {$now}
        //     WHERE id = " . $this->modx->quote($sessionId)
        // );
    }

}


