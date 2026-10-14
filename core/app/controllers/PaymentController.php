<?php
namespace App\Controllers;
use Zoomx\Controllers;
use App\Services\UserService;
use App\Services\PurchaseService;
use App\Models\Payment;

class PaymentController extends \Zoomx\Controllers\Controller
{
    private $accountId = '...';
    private $secretKey = '...';

    public function create()
    {
        zoomx()->autoloadResource(false);
        UserService::requireAccess();

        $packageId = (int)($_GET['package_id'] ?? 0);
        if (!$packageId) abortx(400, 'Не передан id пакета');

        $package = $this->modx->getObject('modResource', $packageId);
        if (!$package) abortx(400, 'Пакет не найден');

        $count = (int)$package->pagetitle;
        $price = (int)$package->longtitle;
        if (!$count || !$price) abortx(400, 'Неверный пакет');

        if (UserService::isAdmin() && (int)($_GET['manual'] ?? 0) === 1) {
            UserService::changeAvailableReports($count);
            return "Пакет активирован ({$count} отчётов)";
        }

        // Создаём заказ
        $order = $this->modx->newObject('msOrder');
        $order->fromArray([
            'user_id' => UserService::id(),
            'weight' => $count,
            'cost' => $price,
            'status' => 1,
            'createdon' => time()
        ]);
        $order->save();

        // Данные покупателя
        $profile = UserService::getProfile();
        $customer = [
            'full_name' => $profile['fullname'] ?? '',
            'email' => $profile['email'] ?? '',
            'phone' => $profile['phone'] ?? ''
        ];
            
        // Товары в заказе
        $items = [
            [
                'description' => "Пакет на {$count} отчетов",
                'amount' => [
                    'value' => $price,
                    'currency' => 'RUB',
                ],
                'quantity' => 1,
                'vat_code' => 1,
                'payment_subject' => 'service',
                'payment_mode' => 'full_payment',
                //'tax_system_code' => 1,
                //'measure' => 'piece',
                //'settlements' => 'cashless',
            ],
        ];

        // Данные для отправки в YooKassa
        $data = [
            'amount' => [
                'value' => $price,
                'currency' => 'RUB',
            ],
            'confirmation' => [
                'type' => 'redirect',
                'locale' => 'ru_RU',
                'return_url' => $this->modx->getOption('site_url') . "dashboard/pricing",
            ],
            'capture' => true,
            'description' => "Пакет на {$count} отчетов",
            'metadata' => [
                'orderNumber' => $order->id,
            ],
            'receipt' => [
                'customer' => $customer,
                'items' => $items,
            ],
        ];

        /* YooKassa */
        $keys = $this->modx->getOption('yookassa.keys', null, '', true);
        $keys = explode(',', $keys);
        $config = [
            'shop_id' => $keys[0] ?? '',
            'secret_code' => $keys[1] ?? '',
            'test' => 1
        ];
        $data['test'] = $config['test'];

        $client = new \YooKassa\Client();
        $client->setAuth($config['shop_id'], $config['secret_code']);

        try {
            $idempotenceKey = uniqid('', true);
            $response = $client->createPayment(
                $data,
                $idempotenceKey
            );

            // Получение ссылки для оплаты
            $link = $response->getConfirmation()->getConfirmationUrl();
            header('Location: ' . $link);
            exit;

        } catch (\Exception $e) {
            $this->modx->log(1, print_r('Ошибка при создании платежа: ' . $e->getMessage(), 1));
            abortx(500, $e->getMessage());
        }
        
    }


    // взять с турлайна
    public function callback()
    {
        zoomx()->autoloadResource(false);
        global $modx;

        $response = $_POST;
        //$response = json_decode($response, true);
        $modx->log(1, 'Колбек ЮКассы: ' . print_r($response ?? '-', true));

        if ($response) {
            //$modx->log(1, 'Колбек ЮКассы получен: ' . print_r($response, true));
        } else {
            return ['success' => false, 'message' => 'Invalid request'];
        }

        $orderId = (int)$response['object']['metadata']['orderNumber'];

        $orderStatus = $response['event'];
        $orderSum = (int)($response['object']['amount']['value'] ?? 0);

        $order = $modx->getObject('msOrder', $orderId);
        if (!$order) {
            $modx->log(1, "Заказ $orderId не найден");
            return ['success' => false];
        }

        switch ($orderStatus) {
            // Платеж успешен
            case 'payment.succeeded':
                if ((int)$order->cost == $orderSum && $order->status == 1) {
                    $reportsCount = (int)($order->weight ?? 0);
                    if ($reportsCount) {
                        $order->set('status', 2);
                        $order->set('updatedon', time());
                        $order->save();

                        UserService::changeAvailableReports($reportsCount, (int)$order->user_id);
                        $this->sendEmail($order);
                        
                        http_response_code(200);
                        echo('OK');
                        return;
                        
                    } else {
                        $modx->log(1, "$orderStatus - В заказе $orderId не указано кол-во отчётов");
                    }

                } else {
                    $modx->log(1, "Заказ $orderId: неверный статус или не совпадает сумма");
                }

            default:
                $modx->log(1, "Неизвестный статус: $orderStatus - заказ $orderId");
                break;
        }

        return ['success' => false];
    }

    public function sendEmail(object $order): void
    {
        global $modx;

        $message = "
            Пакет оплачен <br>
            Кол-во отчётов: {$order->weight} <br>
            Стоимость: {$order->cost} ₽ <br>
            ID пользователя: {$order->user_id}
        ";

        $emails = str_replace(' ', '', $modx->getOption('ms2_email_manager'));
        $emails = explode(',', $emails);

        $modx->getService('mail', 'mail.modPHPMailer');
        $modx->mail->set(\modMail::MAIL_BODY, $message);
        $modx->mail->set(\modMail::MAIL_FROM, $modx->getOption('emailsender'));
        $modx->mail->set(\modMail::MAIL_FROM_NAME, $modx->getOption('site_name'));
        $modx->mail->set(\modMail::MAIL_SUBJECT, 'Пакет оплачен');
        foreach ($emails as $to) {
            $modx->mail->address('to', $to);
        }
        $modx->mail->setHTML(true);

        if (!$modx->mail->send()) {
            $modx->log(1, 'Ошибка отправки письма: ' . print_r($modx->mail->mailer->ErrorInfo, true));
        }
    }
    
}