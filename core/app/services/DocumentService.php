<?php
namespace App\Services;
use App\Services\UserService;
use App\Services\DictionaryService as Dict;
use Illuminate\Support\Carbon;

class DocumentService {
    private const TECHNICAL_CONDITIONS = [
        'Новое' => 'Новое, не зарегистрированное в ГИБДД АМТС в отличном состоянии, после выполнения предпродажной подготовки, без признаков эксплуатации',
        'Очень хорошее' => 'Практически новое АМТС на гарантийном периоде эксплуатации, с выполненными объемами технического обслуживания и не требующее ремонта или замены каких-либо частей',
        'Хорошее' => 'АМТС на послегарантийном периоде эксплуатации, с выполненными объемами технического обслуживания, не требующее текущего ремонта или замены каких-либо частей',
        'Удовлетворительное' => 'Бывший в эксплуатации объект с выполненными объемами технического обслуживания, требующий текущего ремонта или замены некоторых деталей, имеющий незначительные повреждения лакокрасочного покрытия',
        'Условно пригодное' => 'Бывший в эксплуатации АМТС, в состоянии, пригодном для дальнейшей эксплуатации после выполнения работ текущего ремонта (замены) агрегатов, ремонта (наружной окраски) кузова (кабины)',
        'Неудовлетворительное' => 'Бывшее в эксплуатации АМТС, требующее значительного капитального ремонта или замены номерных агрегатов (двигателя, кузова, рамы), полной окраски кузова',
        'Предельное' => 'Бывшее в эксплуатации АМТС, требующее ремонта в объеме, превышающем экономическую целесообразность его выполнения; отсутствие технической возможности осуществления такового; непригодное к эксплуатации; непригодное к эксплуатации и ремонту',
    ];
    private const VEHICLE_FIELDS = ['brand_name','model_name','generation_name','configuration_name','modification_name','year','engine_type','engine_capacity','gear_type','transmission','steering_wheel','body_number','engine_number','mileage','region','technical_condition','wear','operation_start_date','registration_certificate','registration_certificate_date','registration_number','vin', 'power'];
    private const USER_FIELDS = ['appraiser_type','company_inn_ogrn','company_director_name','company_director_position','company_legal_address','company_phone','company_email','company_website','company_logo','company_registration_certificate','company_director_ecp_certificate_number','company_director_ecp_valid_from','company_director_ecp_valid_to','company_insurer_name','company_insurance_policy_number','company_insurance_contract_date','company_insurance_scan','company_insurance_scan_page2','company_insurance_amount','company_insurance_valid_from','company_insurance_valid_to','appraiser_name','appraiser_sro','appraiser_sro_registry_number','appraiser_sro_certificate_number','appraiser_sro_certificate_date','appraiser_sro_certificate_scan','appraiser_sro_extract_scan','appraiser_ecp_certificate_number','appraiser_ecp_valid_from','appraiser_ecp_valid_to','education_institution_name','diploma_series_number','diploma_registration_number','diploma_scan','qualification_certificate_number','qualification_certificate_scan','qualification_certificate_date','appraisal_experience_start','appraiser_insurer_name','appraiser_insurance_policy_number','appraiser_insurance_contract_date','appraiser_insurance_scan','appraiser_insurance_scan_page2','appraiser_insurance_amount','appraiser_insurance_valid_from','appraiser_insurance_valid_to','employment_contract_number','employment_contract_date','appraiser_inn','appraiser_address','appraiser_phone','appraiser_email','appraiser_website','appraiser_logo'];

    private const USER_FILE_FIELDS = [
        'company_logo',
        'company_registration_certificate',
        'company_insurance_scan',
        'company_insurance_scan_page2',
        'appraiser_sro_certificate_scan',
        'appraiser_sro_extract_scan',
        'diploma_scan',
        'qualification_certificate_scan',
        'appraiser_insurance_scan',
        'appraiser_insurance_scan_page2',
        'appraiser_logo',
    ];

    private const CUSTOMER_FIELDS = ['fullname','birthdate','birth_city','passport_series','passport_number','passport_department_code','passport_issued_at','passport_issued_by','registration_address','email','phone'];
    private const OWNER_FIELDS = ['fullname','birthdate','deathdate','death_certificate_number','death_certificate_issued_at'];

    /**
     * Скачать договор
     */
    public static function generateReport(array $report): void
    {
        ini_set('display_errors', 0);
error_reporting(0);
        setlocale(LC_NUMERIC, 'C'); // Иначе PhpWord вставляет дроби с запятой вместо точки

        global $modx;

        //$templatePath = __DIR__ . '/../doc-templates/report.docx';
        $templatePath = MODX_ASSETS_PATH . 'mgr/files/report.docx';
        if (!file_exists($templatePath)) abortx(500, "Файл $templatePath не найден");

        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);


        $data = self::prepareData($report);

        // echo '<pre>';
        // print_r($data);
        // echo '</pre>';
        // exit;

        // Картинки в конце
        $filesDir = rtrim(MODX_CORE_PATH, '/') . '/uploads/appraisers/' . UserService::id() . '/';

        foreach (self::USER_FILE_FIELDS as $field) {
            $placeholder = 'user_' . $field;
            $fileExists  = false;
            $path        = null;
                    
            if (!empty($data[$placeholder])) {
                $rawPath = $filesDir . $data[$placeholder];
                // Получаем чистый абсолютный путь без ../
                $path = realpath($rawPath);

                if ($path && is_file($path)) {
                    $fileExists = true;
                }
            }

            if ($fileExists) {
                // Защита от PDF и других не-картинок
                $mimeType = mime_content_type($path);
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

                if (in_array($mimeType, $allowedMimes, true)) {
                    try {
                        $templateProcessor->setImageValue($placeholder, [
                            'path'  => $path,
                            'width' => '17cm', // A4 21 см, поля по 2 см
                            'height' => '',
                            'ratio' => true
                        ]);
                    } catch (\Throwable $e) {
                        // Если PHPWord не смог вставить картинку — удаляем плейсхолдер
                        $templateProcessor->setValue($placeholder, '');
                    }
                } else {
                    // Файл есть, но это не картинка (например, PDF) — удаляем плейсхолдер
                    $templateProcessor->setValue($placeholder, '');
                }
            } else {
                $templateProcessor->setValue($placeholder, '');
            }
        }

        if (!empty($data['offers'])) {
            $count = count($data['offers']);
            
            // Поля, которые должны стать кликабельными ссылками
            // $linkFields = ['offer_url'];
            $linkFields = []; // ссылки отключены, из-за них документ не открывается в word
            
            // Клонируем блок 'offer_block' нужное количество раз.
            $templateProcessor->cloneBlock('offer_block', $count, true, true);

            foreach ($data['offers'] as $i => $offer) {
                $blockNumber = $i + 1; // Нумерация блоков начинается с 1
                
                // --- Подставляем картинку ---
                if (!empty($offer['offer_image'])) {
                    $localImage = self::downloadImage($offer['offer_image']);

                    if ($localImage) {
                        $templateProcessor->setImageValue('block_offer_image#' . $blockNumber, [
                            'path'  => $localImage,
                            'width' => '4.20cm',
                            'height' => '',
                            'ratio' => true
                        ]);
                        @unlink($localImage);
                    }
                }

                // --- Подставляем остальные поля ---
                foreach ($offer as $key => $value) {
                    // Картинку уже обработали выше — пропускаем
                    if ($key === 'offer_image') {
                        continue;
                    }

                    // Плейсхолдеры, которые нужно заменить
                    $placeholder1 = 'block_' . $key . '#' . $blockNumber;
                    $placeholder2 = $key . '_' . $blockNumber;
                    // ${offer_url_N} не выводится во второй таблице, поэтому используем ${offer_url_N_2}
                    $placeholder3 = $key . '_' . $blockNumber . '_2';
                    $stringValue  = (string)($value ?? '');

                    // Если это поле-ссылка и там валидный URL — вставляем как гиперссылку
                    if (in_array($key, $linkFields, true) 
                        && !empty($value) 
                        && filter_var($value, FILTER_VALIDATE_URL)
                    ) {
                        $linkRun = new \PhpOffice\PhpWord\Element\TextRun();
                        $linkRun->addLink($value, $stringValue, [
                            'color'     => '0563C1',
                            'underline' => 'single',
                        ]);

                        $templateProcessor->setComplexValue($placeholder1, $linkRun);
                        $templateProcessor->setComplexValue($placeholder2, $linkRun);
                        $templateProcessor->setComplexValue($placeholder3, $linkRun);

                    } else {
                        // Обычный текст
                        $templateProcessor->setValue($placeholder1, $stringValue);
                        $templateProcessor->setValue($placeholder2, $stringValue);
                        $templateProcessor->setValue($placeholder3, $stringValue);
                    }
                }
            }
        }

        // Подставляем переменные
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $templateProcessor->setValue($key, $value);
            }
        }


        //hired_block / private_block
        // $type = 'hired'; // или 'private'
        // $blockCount = 5; // сколько пар блоков в документе

        // for ($i = 1; $i <= $blockCount; $i++) {
        //         $keepBlock = ($type === 'hired' ? 'hired_block_' : 'private_block_') . $i;
        //     $removeBlock = ($type === 'hired' ? 'private_block_' : 'hired_block_') . $i;

        //     $templateProcessor->cloneBlock($removeBlock, 0, true);
        // }

        // $templateProcessor->cloneBlock('block_moto', 1, true);
        // //$templateProcessor->deleteBlock('block_vehicle');
        // $templateProcessor->cloneBlock('block_vehicle', 0, true, true);



        self::sendAsDownload('docx', "Отчёт об оценке № {$data['report_number']}.docx");
        $templateProcessor->saveAs('php://output');
        exit;
    }

    public static function prepareData(array $report): array
    {
        // $dicts = [ENGINE_TYPES, GEAR_TYPES];
        // $tronkDict = [''];
        $discount = $report['discount'] ?? 0;

        $output = [
            'id' => $report['id'],
            'evaluation_date' => $report['evaluation_date'] ?? '—',
            'report_date' => $report['updated_at'] ?? '—',
            'report_number' => $report['report_number'] ?? '—',
            'agreement_date' => $report['agreement_date'] ?? '—',
            'agreement_number' => $report['agreement_number'] ?? '—',
            'wear' => number_format(($report['wear'] ?? 0), 1, ',', ''),

            // Цены
            'price' => self::formatNumber($report['price'] ?? 0),
            'price_words' => self::priceToWords($report['price'] ?? 0),
            'price_min' =>  self::formatNumber($report['prices']['min'] ?? 0),
            'price_max' =>  self::formatNumber($report['prices']['max'] ?? 0),
            'final_price' => self::formatNumber($report['final_price'] ?? 0),
            'final_price_words' => self::priceToWords($report['final_price'] ?? 0),
            'discount' => $discount,


            'offers' => []
        ];

        // Автомобиль
        // foreach ($report['vehicle'] ?? [] as $key => $item) {
        //     $output["vehicle_{$key}"] = $item;
        // }

        foreach (self::VEHICLE_FIELDS as $field) {
            $output["vehicle_{$field}"] = $report['vehicle'][$field] ?? '—';
        }

        // Возраст автомобиля
        $output['vehicle_age'] = '—';
        if (!empty($report['vehicle']['operation_start_date']) && !empty($report['evaluation_date'])) {
            $startDate = new \DateTime($report['vehicle']['operation_start_date']);
            $evaluationDate = new \DateTime($report['evaluation_date']);
            $vehicleAge = $startDate->diff($evaluationDate);
            $output['vehicle_age'] = self::formatAge($vehicleAge);
        }

        // Техническое состояние
        $output['vehicle_technical_condition_details'] = self::TECHNICAL_CONDITIONS[$output['vehicle_technical_condition']] ?? '—';

        // Значения из справочника
        $output['vehicle_engine_type'] = Dict::ENGINE_TYPES[$output['vehicle_engine_type']]['name'] ?? '—';
        $output['vehicle_gear_type'] = Dict::GEAR_TYPES[$output['vehicle_gear_type']]['name'] ?? '—';
        $output['vehicle_steering_wheel'] = Dict::STEERING_WHEELS[$output['vehicle_steering_wheel']]['name'] ?? '—';
        $output['vehicle_transmission'] = Dict::TRANSMISSIONS[$output['vehicle_transmission']]['name'] ?? '—';

        foreach (self::CUSTOMER_FIELDS as $field) {
            $output["customer_{$field}"] = $report['customer'][$field] ?? '—';
        }

        foreach (self::OWNER_FIELDS as $field) {
            $output["owner_{$field}"] = $report['owner'][$field] ?? '—';
        }

        // Пользователь
        $user = UserService::getProfile($report['user_id']);

        foreach (self::USER_FIELDS as $field) {
            $output["user_{$field}"] = $user[$field] ?? '—';
        }

        // Стаж работы (год начала) 
        $output['user_appraisal_experience_start'] = !empty($output['user_appraisal_experience_start'])
            ? substr($output['user_appraisal_experience_start'], 0, 4)
            : '—';

        $output['user_appraiser_insurance_amount'] = (int)($output['user_appraiser_insurance_amount'] ?? 0);
        $output['user_appraiser_insurance_amount_words'] = !empty($output['user_appraiser_insurance_amount'])
            ? self::priceToWords($output['user_appraiser_insurance_amount'])
            : '—';
        
        // Форматируем все даты
        foreach ($output as $key => $item) {
            if (
                strpos($key, '_date') !== false ||
                strpos($key, '_valid_to') !== false ||
                strpos($key, '_valid_from') !== false ||
                strpos($key, '_issued_at') !== false ||
                strpos($key, '_birthdate') !== false ||
                $key === 'owner_deathdate'
            ) {
                $output[$key] = self::formatDate($item, 'dd.MM.yyyy');
            }
        }

        // Объявления
        $n = 0;
        foreach ($report['api_offers'] ?? [] as $key => $item) {
            if (empty($item['selected'])) continue;
            $n++;

            $price = (int)$item['price'] ?? 0;
            $adj = round($price * $discount / 100); // корректировка
            $adjPrice = $price - $adj; // цена после корректировки
            
            $output["offers"][] = [
                'offer_number' => $n,
                'offer_image' => $item['images'][0] ?? '',
                'offer_url' => $item['url'] ?? '—',

                'offer_title' => $item['title'] ?? '—',
                'offer_city' => $item['city'] ?? '—',
                'offer_region' => $item['region'] ?? '—',
                'offer_year' => $item['year'] ?? '—',
                'offer_mileage' => $item['mileage'] ?? '—',

                'offer_power' => $item['power'] ?? '—',
                'offer_engine_capacity' => $item['engine_capacity'] ?? '—',
                'offer_body_type' => $item['body_type'] ?? '—',

                'offer_transmission' => $item['transmission'] ?? '—',
                'offer_engine_type' => $item['engine_type'] ?? '—',
                'offer_gear_type' => $item['gear_type'] ?? '—',

                'offer_price' => self::formatNumber($price),
                'offer_adj' => self::formatNumber($adj),
                'offer_adj_price' => self::formatNumber($adjPrice),

            ];
        }

        return $output;
    }

    private static function downloadImage(string $url): ?string
    {
        global $modx;
        
        $tmpFile = MODX_CORE_PATH . 'cache/img_' . uniqid() . '.tmp';

        //$modx->log(1, "[PHPWord] tmpFile: $tmpFile");

        $ch = curl_init('https:' . $url);
        $fp = fopen($tmpFile, 'wb');
        
        if ($fp === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        //$modx->log(1, 'https:' . $url);

        // Если не скачалось
        if ($httpCode !== 200 || !filesize($tmpFile)) {
            $modx->log(1, "[PHPWord] Не скачалось (HTTP $httpCode): $url");
            @unlink($tmpFile);
            return null;
        }

        $info = @getimagesize($tmpFile);
        if ($info === false) {
            $modx->log(1, "[PHPWord] Файл не картинка: $url");
            @unlink($tmpFile);
            return null;
        }

        // Загружаем картинку в память в зависимости от формата
        $src = null;
        if ($info['mime'] === 'image/jpeg') $src = @imagecreatefromjpeg($tmpFile);
        elseif ($info['mime'] === 'image/png') $src = @imagecreatefrompng($tmpFile);
        elseif ($info['mime'] === 'image/gif') $src = @imagecreatefromgif($tmpFile);
        elseif ($info['mime'] === 'image/webp' && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($tmpFile);

        if (!$src) {
            $modx->log(1, "[PHPWord] Не удалось прочитать формат: " . $info['mime'] . " для URL: $url");
            @unlink($tmpFile);
            return null;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);
        $maxWidth = 500; // Максимальная ширина в пикселях

        $newW = $origW;
        $newH = $origH;

        // Пропорциональный ресайз, если картинка огромная
        if ($origW > $maxWidth) {
            $newW = $maxWidth;
            $newH = (int)($origH * ($maxWidth / $origW));
        }

        // Создаем новый холст и ресайзим
        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        // Сохраняем как оптимизированный JPEG (убивает EXIF и делает файл легким)
        $finalPath = MODX_CORE_PATH . 'cache/img_opt_' . uniqid() . '.jpg';
        imagejpeg($dst, $finalPath, 85); // 85% качества — идеальный баланс
        
        // Освобождаем память
        imagedestroy($src);
        imagedestroy($dst);
        @unlink($tmpFile); // Удаляем оригинал

        //$modx->log(1, "[PHPWord] Успешно оптимизировано ($origW->$newW px): $url");
        
        return $finalPath;
    }


    private static function numberToWord($n, $words) {
        return ($words[($n=($n=$n%100)>19?($n%10):$n)==1?0 : (($n>1&&$n<=4)?1:2)]);
    }

    
    // self::formatDate('2026-06-05', 'd MMMM yyyy'); // 5 июня 2026
    // self::formatDate('2026-06-05', 'dd.MM.yyyy'); // 05.06.2026
    private static function formatDate(string $date, string $format): string
    {
        $time = strtotime($date);
        $formatter = new \IntlDateFormatter(
            'ru_RU',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            'Europe/Moscow',
            \IntlDateFormatter::GREGORIAN,
            $format,
        );
        return $formatter->format($time);
    }

    private static function formatAge(\DateInterval $age): string
    {
        $result = [];

        if ($age->y > 0) {
            $result[] = $age->y . ' ' . self::plural($age->y, 'год', 'года', 'лет');
        }

        if ($age->m > 0) {
            $result[] = $age->m . ' ' . self::plural($age->m, 'месяц', 'месяца', 'месяцев');
        }

        return $result ? implode(' ', $result) : '0 месяцев';
    }

    private static function plural(int $number, string $one, string $few, string $many): string
    {
        $n = $number % 100;

        if ($n >= 11 && $n <= 19) {
            return $many;
        }

        switch ($number % 10) {
            case 1:
                return $one;

            case 2:
            case 3:
            case 4:
                return $few;

            default:
                return $many;
        }
    }

    /**
     * Сумма прописью
     * https://www.manhunter.ru/webmaster/1165_summa_propisyu_na_php.html
     */
    public static function priceToWords(int $n, bool $valute = false): string
    {
        $words=array(
            900=>'девятьсот',
            800=>'восемьсот',
            700=>'семьсот',
            600=>'шестьсот',
            500=>'пятьсот',
            400=>'четыреста',
            300=>'триста',
            200=>'двести',
            100=>'сто',
            90=>'девяносто',
            80=>'восемьдесят',
            70=>'семьдесят',
            60=>'шестьдесят',
            50=>'пятьдесят',
            40=>'сорок',
            30=>'тридцать',
            20=>'двадцать',
            19=>'девятнадцать',
            18=>'восемнадцать',
            17=>'семнадцать',
            16=>'шестнадцать',
            15=>'пятнадцать',
            14=>'четырнадцать',
            13=>'тринадцать',
            12=>'двенадцать',
            11=>'одиннадцать',
            10=>'десять',
            9=>'девять',
            8=>'восемь',
            7=>'семь',
            6=>'шесть',
            5=>'пять',
            4=>'четыре',
            3=>'три',
            2=>'два',
            1=>'один',
        );
    
        $level=array(
            4=>array('миллиард', 'миллиарда', 'миллиардов'),
            3=>array('миллион', 'миллиона', 'миллионов'),
            2=>array('тысяча', 'тысячи', 'тысяч'),
        );
    
        list($rub,$kop)=explode('.',number_format($n,2));
        $parts=explode(',',$rub);
    
        for($str='', $l=count($parts), $i=0; $i<count($parts); $i++, $l--) {
            if (intval($num=$parts[$i])) {
                foreach($words as $key=>$value) {
                    if ($num>=$key) {
                        // Fix для одной тысячи
                        if ($l==2 && $key==1) {
                            $value='одна';
                        }
                        // Fix для двух тысяч
                        if ($l==2 && $key==2) {
                            $value='две';
                        }
                        $str.=($str!=''?' ':'').$value;
                        $num-=$key;
                    }
                }
                if (isset($level[$l])) {
                    $str.=' '.self::numberToWord($parts[$i],$level[$l]);
                }
            }
        }
    
        if ($valute && intval($rub=str_replace(',','',$rub))) {
            $str.=' '.self::numberToWord($rub,array('рубль', 'рубля', 'рублей'));
        }
    
        // $str.=($str!=''?' ':'').$kop;
        // $str.=' '.self::numberToWord($kop,array('копейка', 'копейки', 'копеек'));
    
        return trim(mb_strtoupper(mb_substr($str,0,1,'utf-8'),'utf-8').
            mb_substr($str,1,mb_strlen($str,'utf-8'),'utf-8'));
    }

    private static function formatNumber(float $number, string $symbol = ''): string
    {
        // если %, пробел не нужен
        $symbol = $symbol == '%' ? $symbol : " {$symbol}";
        return str_replace(',00', '', number_format($number, 2, ',', ' ')) . ($number && $symbol ? "$symbol" : '');
    }

    private static function sendAsDownload(string $format, string $filename): void
    {
        // Очищаем буфер перед генерацией, чтобы убрать лишний вывод/пробелы
        if (ob_get_length()) {
            ob_end_clean();
        }
        header("Content-Description: File Transfer");
        //header('Content-Disposition: attachment; filename="' . $filename . '"');

        header(
            'Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($filename)
        );

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
    }
}