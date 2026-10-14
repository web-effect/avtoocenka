<?php
namespace App\Controllers;
use App\Services\UserService;
use App\Services\AutoApiService;
use App\Services\ReportService;
use App\Services\DocumentService;

class ReportApiController extends \Zoomx\Controllers\Controller
{
    public function decodeVin($id)
    {
        $report = ReportService::getOrFail($id, true);

        $vin = trim($_POST['vin'] ?? '');
        if (!$vin) return abortx(400, 'Не передан vin');

        
        $vehicle = $report->vehicle;

        if ($vehicle['vin_decoded'] === true) {
            return abortx(400, 'Vin уже был декодирован');
        }

        $vehicle = []; // сброс данных
        $vehicle['vin_decoded'] = false;
        $vehicle['vin_decoded_fields'] = [];

        $data = AutoApiService::decodeVin($vin);
        // log

        // для теста
        // if ($vin == '11111111111111111') {
        //     $data = null;
        // }

        if ($data) {
            $vehicle = $data;
            $vehicle['vin_decoded'] = true;

            foreach ($data as $key => $value) {
                if ($value) {
                    $vehicle['vin_decoded_fields'][] = $key;
                }
            }
        }

        $vehicle['vin'] = $vin;
        $report->vehicle = $vehicle;
        $report->save();

        return jsonx([
            'report' => ReportService::getOrFail($id),
        ]);
    }

    public function change($id)
    {
        // @todo проверять field и value
        $report = ReportService::getOrFail($id, true);

        $field = $_POST['field'] ?? '';
        $value = $_POST['value'] ?? '';
        if (is_string($value)) {
            $value = trim($value);
        }

        if (strpos($field, "vehicle_") !== false) {
            $field = str_replace("vehicle_", '', $field);
            $vehicle = $report->vehicle;
            $level = str_replace('vehicle_', '', $field);

            $levels = [
                'brand',
                'model',
                'generation',
                'configuration',
                'modification',
            ];

            $levelIndex = array_search($level, $levels);

            $protectedFields = [
                'vin_decoded',
            ];

            if ($levelIndex !== false) {
                $allowed = array_slice($levels, 0, $levelIndex + 1);

                foreach (array_keys($vehicle) as $key) {
                    $keep = false;

                    if (in_array($key, $protectedFields)) {
                        $keep = true;
                    } else {
                        foreach ($allowed as $prefix) {
                            if (strpos($key, $prefix . '_') === 0) {
                                $keep = true;
                                break;
                            }
                        }
                    }

                    if (!$keep) {
                        unset($vehicle[$key]);
                    }
                }
            }

            //$vehicle['vin_decoded'] = false;


            // Модификация
            if ($field === 'modification' && is_array($value)) {
                $vehicle['modification_id'] = $value['id'] ?? null;
                $vehicle['modification_name'] = $value['tech_params']['human_name'] ?? null;

                foreach ($value['tech_params'] ?? [] as $key => $val) {
                    $vehicle[$key] = $val;
                }

                $vehicle['year_from'] = $value['tech_params']['year_start'] ?? null;
                $vehicle['year_to'] = $value['tech_params']['year_stop'] ?? null;

                $vehicle['engine_capacity'] = round(($value['tech_params']['displacement'] ?? 0) / 1000, 1);

            } elseif (is_array($value)) {
                // Марка / модель / поколение / прочие обычные поля
                foreach ($value as $key => $val) {
                    $vehicle["{$field}_{$key}"] = trim($val);
                }
            } else {
                $vehicle[$field] = $value;
            }

            // сбрасываем износ
            if ($field === 'technical_condition') {
                $vehicle['wear'] = '';
            }

            $report->vehicle = $vehicle;

            // подгружаем данные из api
            // $vehicleApiData = AutoApiService::getVehicleData([
            //     'brand' => $vehicle['brand_id'] ?? null,
            //     'model' => $vehicle['model_id'] ?? null
            // ]);
        }

        foreach (['customer', 'owner'] as $i) {
            if (strpos($field, "{$i}_") !== false) {
                $data = $report->{$i};
                $data[str_replace("{$i}_", '', $field)] = $value;
                $report->{$i} = $data;
            }
        }

        if (in_array($field, [
            'discount', 'evaluation_price', 'file_downloaded',
            'report_date', 'report_number',
            'agreement_date', 'agreement_number'
        ])) {
            //$report->setTVValue($field, $value);
            $report->{$field} = $value;
        }

        $report->save();

        return jsonx([
            'report' => ReportService::getOrFail($id),
            //'vehicle_api_data' => $vehicleApiData
        ]);
    }
    
    public function changeStep($id)
    {
        $report = ReportService::getOrFail($id, true);

        $step = (int)($_POST['step'] ?? 0);
        if ($step <= 0 || $step > 5) abortx(400, 'Неверный параметр step'); 

        // Если возврат назад — не сохраняем
        if ($step <= $report->step) {
            $report = ReportService::getOrFail($id);
            return jsonx([
                'report' => $report,
            ]);
        } 
        
        // На 2 шаге получаем объявления
        if ($step == 2) {
            if (empty($report->vehicle['vin'])) {
                return abortx(400, 'Не введён VIN');
            }

            $requiredFields = ['brand_name', 'model_name', 'region', 'year'];
            $emptyFields = [];

            foreach ($requiredFields as $key) {
                if (empty($report->vehicle[$key])) {
                    $emptyFields[] = $key;
                }
            }

            if ($emptyFields) {
                return abortx(400, 'Не заполнены поля: ' . implode(', ', $emptyFields));
            }
            
            // получаем похожие объявления
            if (empty($report->api_offers)) {
                $data = AutoApiService::getOffers($report->vehicle);

                if (!$data['offers']) {
                    $report->step = 1;
                    $report->save();
                    return abortx(502, 'Извините, похожие объявления не найдены'); // или 400
                }
                
                // выбираем для показа первые 5
                foreach ($data['offers'] as $key => &$offer) {
                    $offer['selected'] = $key < 5 ? $key + 1 : null;
                }
                unset($offer);

                $report->api_offers = $data['offers'];
            }
        }

        $report->step = $step;
        $report->save();

        $report = ReportService::getOrFail($id);
        return jsonx([
            'report' => $report,
        ]);
    }

    public function replaceOffer($id)
    {
        $report = ReportService::getOrFail($id, true);
        $offers = $report->api_offers;

        $offerId = $_POST['offer_id'] ?? null;

        if (!$offerId) {
            return abortx(400, 'Не передан offer_id');
        }

        // Находим позицию заменяемого объявления
        $position = null;

        foreach ($offers as $offer) {
            if ($offer['id'] == $offerId && !empty($offer['selected'])) {
                $position = $offer['selected'];
                break;
            }
        }

        if ($position === null) {
            return abortx(400, 'Объявление не выбрано');
        }

        // Ищем объявления, которые сейчас не отображаются
        $available = [];

        foreach ($offers as $offer) {
            if (empty($offer['selected']) && $offer['id'] != $offerId) {
                $available[] = $offer;
            }
        }

        if (!$available) {
            return abortx(400, 'Нет других объявлений для замены');
        }

        // Выбираем случайное
        $newOffer = $available[array_rand($available)];

        // Переносим позицию со старого объявления на новое
        foreach ($offers as &$offer) {
            if ($offer['id'] == $offerId) {
                $offer['selected'] = null;
            }

            if ($offer['id'] == $newOffer['id']) {
                $offer['selected'] = (int)$position;
            }
        }
        unset($offer);

        $report->api_offers = $offers;
        $report->save();

        $report = ReportService::getOrFail($id);

        return jsonx([
            'report' => $report,
        ]);
    }

    public function downloadFile($id)
    {
        zoomx()->autoloadResource(false);
        $report = ReportService::getOrFail($id);

        //@todo проверять step
        //if (!$order['data_confirmed'] || $order['paid'] <= 0) abortx(400, 'Договор ещё не сформирован');

        DocumentService::generateReport($report);
        exit;
    }

    public function uploadFile($id)
    {
        zoomx()->autoloadResource(false);
        $report = ReportService::getOrFail($id, true);

        if (
            empty($_FILES['file']) ||
            $_FILES['file']['error'] !== UPLOAD_ERR_OK
        ) {
            return abortx(400, 'Файл не загружен');
        }

        $targetDir = MODX_CORE_PATH . 'uploads/reports/';

        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true)) {
            return abortx(500, 'Не удалось создать директорию');
        }

        $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['pdf', 'doc', 'docx'])) {
            return abortx(400, 'Недопустимый формат файла');
        }

        $filename = "report-{$report->id}.{$extension}";
        $targetFile = $targetDir . $filename;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            return abortx(500, 'Не удалось сохранить файл');
        }

        $report->file = $filename;
        $report->save();

        return jsonx([
            'report' => $report->toArray(),
        ]);
    }

    public function sendEmail(int $id)
    {
        //zoomx()->autoloadResource(false);
        $report = ReportService::getOrFail($id, true);
        
        $emails = $_POST['emails'] ?? [];
        if (!$emails || !is_array($emails)) abortx(400, 'Получатели не выбраны');

        $file = $report->file;
        if (!$file) abortx(400, 'Файл ещё не загружен');
        
        $this->modx->getService('mail', 'mail.modPHPMailer');
        
        foreach ($emails as $email) {
            $this->modx->mail->set(\modMail::MAIL_BODY, 'Отчёт об оценке');
            $this->modx->mail->set(\modMail::MAIL_FROM, $this->modx->getOption('emailsender'));
            $this->modx->mail->set(\modMail::MAIL_FROM_NAME, $this->modx->getOption('site_name'));
            $this->modx->mail->address('to', $email);
            $this->modx->mail->attach(MODX_CORE_PATH . 'uploads/reports/' . $file, 'Отчёт об оценке №' . $report->id);
            $this->modx->mail->set(\modMail::MAIL_SUBJECT, 'Отчёт об оценке №' . $report->id);
            
            if (!$this->modx->mail->send()) {
                $this->modx->log(1, 'Ошибка отправки письма: ' . $this->modx->mail->mailer->ErrorInfo);
                abortx(500, 'Ошибка отправки письма: ' . $this->modx->mail->mailer->ErrorInfo);
            }
            
            $this->modx->mail->reset();
        }

        $report->sent_emails = array_unique(array_merge($report->sent_emails ?? [], $emails));
        $report->save();
        
        return jsonx([
            'report' => $report->toArray(),
        ]);
    }
    

}