<?php
namespace App\Services;
use App\Services\DictionaryService as Dict;

class AutoApiService
{
    // vin для теста XTA219110EY125108
    private const TRONK_API_KEY = 'd5350cff-9cfe-44c7-a5a2-a8ae3a7e7e06';
    private const AUTO_RU_API_KEY = 'cheb0153-33e76efcc615deec53fda63a9e0a7479a612a99d';
    private const AUTO_PARSER_API_KEY = 'Q33GmBC7JSz6scbqHPYZ';

    public static function getVehicleData(array $filters = [])
    {
        // global $modx;
        // $modx->log(1, print_r($_SERVER, 1)); 
        
        $params = [
            $filters['brand'] ?? null,
            $filters['model'] ?? null,
            $filters['generation'] ?? null,
            $filters['configuration'] ?? null,
        ];
        $params = urlencode(implode('#', array_filter($params)));
        
        $url = 'https://apiauto.ru/1.0/search/cars/breadcrumbs';
        if ($params) {
            $url .= "?bc_lookup={$params}";
        }

        //return $params;


        //print_r($url);

        $headers = [
            'x-authorization: ' . self::AUTO_RU_API_KEY,
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);

        $result = [
            'brands' => [],
            'models' => [],
            'generations' => [],
            'configurations' => [],
            'modifications' => [],
        ];

        if ($response === false) {
            curl_close($ch);
            return $result;
        }

        curl_close($ch);

        $data = json_decode($response, true);

        if (!is_array($data)) {
            return abortx(502, 'Ошибка API Auto.ru: не получены данные');
        }

        if (($data['status'] ?? '') == 'ERROR') {
            return abortx(502, 'Ошибка API Auto.ru: ' . $data['detailed_error'] ?? $data['error'] ?? '');
        }


        foreach ($data['breadcrumbs'] ?? [] as $breadcrumb) {
            switch ($breadcrumb['meta_level'] ?? '') {
                case 'MARK_LEVEL':
                    $result['brands'] = $breadcrumb['entities'] ?? [];
                    break;

                case 'MODEL_LEVEL':
                    $result['models'] = $breadcrumb['entities'] ?? [];
                    break;
                
                case 'GENERATION_LEVEL':
                    $result['generations'] = $breadcrumb['entities'] ?? [];

                    foreach ($result['generations'] as &$i) {
                        if (empty($i['name'])) {
                            $i['name'] = ($i['super_gen']['year_from'] ?? '') . ' — ' . ($i['super_gen']['year_to'] ?? '');
                        }
                    }
                    unset($i);

                    break;

                case 'CONFIGURATION_LEVEL':
                    $result['configurations'] = $breadcrumb['entities'] ?? [];
                    break;
                
                case 'TECH_PARAM_LEVEL':
                    $result['modifications'] = $breadcrumb['entities'] ?? [];
                    break;
            }
        }

        //$techParams = array_column($data['entities'] ?? [], 'tech_params');

        if (!$result['brands']) {
            return abortx(502, 'Ошибка API Auto.ru: не получены марки');
        }

        $result['debug'] = [
            'url' => $url,
            'data' => $data,
            'filters' => $filters
        ];

        return $result;
    }

    public static function decodeVin(string $vin): ?array
    {
        $url = "https://data.tronk.info/vindecode2.ashx";
        $requestParams = [
            "key" => self::TRONK_API_KEY,
            "vin" => $vin
        ];
        $getParams = http_build_query($requestParams);
        $response = file_get_contents($url."?".$getParams);

        global $modx;
        $modx->log(1, print_r($response, 1));
        
        if (empty($response)) {
          abortx(502, 'Не пришёл ответ от api');
        }
          
        $result = json_decode($response, true);

        // if (!empty($result['error'])) {
        //   abortx(502, $result['error_msg'] ?? 'Неизвестная ошибка api');
        // }

        // global $modx;
        // $modx->log(1, print_r($response, true));

        if (empty($result['result'])) {
          return [];
        }

        $result = self::normalizeVehicle($result);


        return $result;

        //print_r($result);
    }

    public static function normalizeVehicle(array $response): array
    {
        $data = $response['result'] ?? [];

        $vehicle = [
            'brand_id' => $data['mark_info']['code'] ?? null,
            'brand_name' => $data['mark_info']['en_name'] ?? null,

            'model_id' => $data['model_info']['code'] ?? null,
            'model_name' => $data['model_info']['en_name'] ?? null,

            'generation_id' => $data['super_gen']['code'] ?? null, // не приходит в api
            'generation_name' => $data['super_gen']['name'] ?? null,
            'year_from' => $data['super_gen']['year_from'] ?? null,
            'year_to' => $data['super_gen']['year_to'] ?? null,

            'configuration_id' => null, // не приходит в api
            'configuration_name' => $data['human_name'] ?? null,
            
            'modification_id' => null, // не приходит в api
            'modification_name' => $data['tech_param']['human_name'] ?? null,
            
            'year' => $data['year'] ?? null,
            'steering_wheel' => $data['steering_wheel'] ?? null,
            'engine_capacity' => round(($data['tech_param']['displacement'] ?? 0) / 1000, 1),
        ];
        
        foreach (['gear_type', 'engine_type', 'transmission', 'power'] as $field) {
            $vehicle[$field] = $data['tech_param'][$field] ?? null;
        }

        return $vehicle;
    }
    
    // @todo обработка ошибок при запросе
    public static function getOffers(array $vehicle): array
    {
        $url = "https://autopulseglobal.auto-parser.ru/api/v1/search/offers";
        
        $year = (int)$vehicle['year'];

        $request_params = array(
            "api_key" => self::AUTO_PARSER_API_KEY,
            "mark" =>  $vehicle['brand_name'],
            "model" => $vehicle['model_name'],
            'region' => $vehicle['region'],
            'year_from' => $year - 1,
            'year_to' => $year + 1,
            'classified' => 'autoru',
            'page' => 1,
            'sort_order' => 'desc'
        );
        
        if (!empty($vehicle['engine_type'])) {
            $request_params['prep_engine_type'] = Dict::ENGINE_TYPES[$vehicle['engine_type']]['auto_parser_name'] ?? '';
        }

        if (!empty($vehicle['transmission'])) {
            $request_params['prep_transmission'] = Dict::TRANSMISSIONS[$vehicle['transmission']]['auto_parser_name'] ?? '';
        }

        if (!empty($vehicle['gear_type'])) {
            $request_params['prep_drive_type'] = Dict::GEAR_TYPES[$vehicle['gear_type']]['auto_parser_name'] ?? '';
        }

        if (!empty($vehicle['configuration_name'])) {
            $request_params['prep_body_type'] = Dict::BODY_TYPES[$vehicle['configuration_name']]['auto_parser_name'] ?? '';
        }

        // prep_body_type - тип кузова
        // prep_engine_type - тип двигателя
        // prep_transmission - коробка передач
        // prep_drive_type - привод

        $get_params = http_build_query($request_params);
        $response = file_get_contents($url . "?" . $get_params);
        $data = json_decode($response ?? '', true);

        if (empty($data)) {
            return [
                'error' => true,
                'offers' => []
            ];
        }
                
        $offers = $data['result'] ?? [];
        
        // если меньше 5 — ищем без учёта региона
        if (count($offers) < 5) {
            unset($request_params['region']);
            $get_params = http_build_query($request_params);
            $response = file_get_contents($url . "?" . $get_params);

            // $get_params = http_build_query([
            //     "api_key" => self::AUTO_PARSER_API_KEY,
            //     "mark" =>  $vehicle['brand_name'],
            //     "model" => $vehicle['model_name'],
            //     'classified' => 'autoru',
            //     'page' => 1
            // ]);
            // $response = file_get_contents($url . "?" . $get_params);

            $data = json_decode($response ?? '', true);

            // Сливаем результаты
            $offers = array_merge($offers, $data['result'] ?? []);
        }
        
        if (empty($offers)) {
            return [
                'error' => true,
                'offers' => []
            ];
        }

        $result = [
            'error' => null,
            'offers' => []
        ];

        // Используем inner_id как ключ. Все дубликаты перезапишутся
        $uniqueItems = [];

        foreach ($offers as $item) {
            // фильтр по поколению
            if (!empty($vehicle['generation_name']) && !empty($item['generation'])) {
                if ($vehicle['generation_name'] != $item['generation']) {
                    continue;
                }
            }

            if (!empty($item['inner_id'])) {
                $uniqueItems[$item['inner_id']] = $item;
            }
        }

        // Формируем финальный массив уже из чистых данных
        foreach ($uniqueItems as $item) {
            $offer = [];
            $offer['id'] = $item['inner_id'];
            $offer['price'] = (int)($item['price_rub'] ?? 0);

            $offer['title'] = $item['title'];
            $offer['url'] = $item['url'];
            $offer['mark'] = $item['mark'];
            $offer['model'] = $item['model'];
            $offer['generation'] = $item['generation'] ?? '';
            $offer['year'] = $item['year'] ?? '';
            $offer['region'] = $item['region'] ?? '';
            $offer['city'] = $item['city'] ?? '';
            $offer['body_type'] = $item['prep_body_type'] ?? '';
            $offer['engine_type'] = $item['prep_engine_type'] ?? '';
            $offer['engine_capacity'] = $item['displacement'] ?? '';
            $offer['power'] = $item['horse_power'] ?? '';
            $offer['transmission'] = $item['prep_transmission'] ?? '';
            $offer['gear_type'] = $item['prep_drive_type'] ?? '';
            $offer['mileage'] = $item['km_age'] ?? 0; 
            $offer['created'] = $item['offer_created'] ?? ''; 
            $offer['images'] = array_column($item['images_list'] ?? [], 'small');

            $offer['selected'] = null; 

            $result['offers'][] = $offer;
        }

        return $result;
    }

            // $response = '
        //     {"start":"17.08.2026 10:20:18","end":"17.08.2026 10:20:18","vin":"XTA219110EY125108","error":false,
        //     "result":{
        //     "vin":"XTA219110EY125108",
        //     "year":2014,"mileage":146604,"pts_owners_count":2,"doors_count":null,
        //     "human_name":"Лифтбек",
        //     "complectation":{"name":null,"available_options":null},
        //     "equipment":{},
        //     "mark_info":{"code":"VAZ","en_name":"Lada (ВАЗ)","ru_name":"Лада"},
        //     "model_info":{"code":"GRANTA","en_name":"Granta","ru_name":"Гранта"},
        //     "steering_wheel":"LEFT",
        //     "super_gen":{"name":"I","ru_name":"1","year_from":2011,"year_to":2018,"price_segment":"ECONOMY","purpose_group":"FAMILY"},
        //     "tech_param":{
        //         "gear_type":"FORWARD_CONTROL","engine_type":"GASOLINE","transmission":"MECHANICAL","displacement":1596,"power":87,"power_kvt":64,"human_name":"1.6 MT (87 л.с.)"
        //     },
        //     "vendor_detail":{"vendor":null,"market":null,"assembly_country":null}}}
        // ';

        // global $modx;
        // $modx->log(1, print_r($response, 1));


        // [2026-10-07 12:18:23] (ERROR @ /home/n/nn52rf/67-pro20260514/core/app/services/AutoApiService.php : 132) {"start":"07.10.2026 12:18:22","end":"07.10.2026 12:18:23","vin":"X7LHSRH8N46366294","error":false,"decode":{"Reports":[{"BasicParams":"RENAULT Duster 1.6 16V 105","PowerParams":"1.6{l} 104.69{PS} Euro 4","EcoParams":null,"Data":{"Vin":"X7LHSRH8N46366294","IsSpecial":false,"BrandID":"X000457","ModelID":"Z020010","Brand":"RENAULT","Model":"Duster","BodyName":"X79","Generation":null,"Segment":{"VinformerID":"J10","VinformerEN":"Mini SUV","VinformerRU":"Субкомпактный кроссовер","American":"Mini SUV","British":"Mini 4?4","Australian":"Small SUV","Russian":"-","European":"sport utility cars (including off-road vehicles)","EuropeanID":"J","EuroNCAP":"Small off-road 4x4","EUEPA":"Small sport utility vehicle"},"StartYear":"03.2010","ProduceDate":null,"ModelYear":null,"FinishYear":"01.2018","Market":null,"OriginalBodyName":null,"Body":"WGN","Doors":5,"Gear":"AUT","Drive":null,"Trim":null,"Modification":"1.6 16V 105","FuelType":["PO"],"EngineSeries":"K4M 690","EngineVolume":{"L":1.6,"Ccm":1598,"Ci":97.5},"EnginePower":{"KW":77,"PS":104.69,"Hp":103.26,"Range":{"Min":5750,"Max":null}},"EngineTorque":{"Nm":148,"Kgm":15.09,"Ftlb":109.16,"Range":{"Min":3750,"Max":null}},"EngineEmission":"Euro 4","EngineDescription":null,"Lineup":"L4","Camshafts":"DOHC","Valves":16,"ValveTiming":null,"doubleercooler":null,"Aspiration":"N","Compression":9.8,"SupplySystem":"MPI","EngineFuelrate":"7.1 - 8.0","MeasureStandard":"DIN","CountryOfOrigin":"Russia","AssemblyCountry":null,"AssemblyRegion":null,"AssemblyCity":null,"AssemblyPlantName":null,"ReportPosition":0,"CheckDigit":{"Result":"absent"}}}],"BrandsAndModels":[{"BrandName":"RENAULT","BrandID":"X000457","Models":[{"Name":"Duster","Id":"Z020010"}]},{"BrandName":"МОСКВИЧ","BrandID":"X000039","Models":null}],"Manufacturers":[{"Id":"X000457","Brand":"RENAULT","IsSpecial":false,"ManufacturerName":"ОАО Автофрамос","CountryOfOrigin":"Russia","Address":"109147 г. Москва г. Москва ул. Воронцовская, д. 35","Phone":"(495) 775-48-48","Web":"renault.ru","RegisterDate":"01.01.1998","RegionCode":"77"},{"Id":"X000039","Brand":"МОСКВИЧ","IsSpecial":false,"ManufacturerName":"АО Московский автомобильный завод Москвич","CountryOfOrigin":"Russia","Address":"109316 г. Москва г. Москва Волгоградский проспект, д. 42, к. 36","Phone":"+7 (495) 775-40-00","Web":"moskvich-auto.ru","RegisterDate":"23.11.2022","RegionCode":"77"}],"Vin":{"Income":"X7LHSRH8N46366294","Normal":"X7LHSRH8N46366294"}}}


    // public static function getOffers(array $vehicle): array
    // {
    //     // $url = "https://data.tronk.info/offer.ashx";
    //     // $request_params = array(
    //     //     "key" => self::TRONK_API_KEY,
    //     //     "command" => "list",
    //     //     "startyear" => 2014,
    //     //     "endyear" => 2014,
    //     //     // "pricestart" => 1,
    //     //     // "priceend" => 9999,
    //     //     // "distancestart" => 146604,
    //     //     // "distanceend" => 146604,
    //     //     "marka" =>  ".VAZ.",
    //     //     "model" => ".GRANTA.",
    //     //     //"source" => ".2.",
    //     //     //"regionid" => 71,
    //     //     //"listregionid" => ".REG1.REG2.",
    //     //     // "countcar" => 5,
    //     //     // "countphone" => 5
    //     // );

    //     // $get_params = http_build_query($request_params);
    //     // $response = file_get_contents($url."?".$get_params);
        
    //     // return $response;

    //     $response = self::OFFERS;
    //     $data = json_decode($response, true);
    //     $error = !empty($data['error'])
    //         ? ($data['error_msg'] ?? $data['error'])
    //         : null;

    //     $result = [
    //         'error' => $error,
    //         'offers' => []
    //     ];

    //     if ($error) {
    //         return $result;
    //     }

    //     $offers = $data['result']['ListOffer'] ?? [];
    //     $ids = []; // Для 2 запроса

    //     foreach ($offers as &$offer) {
    //         $ids[] = $offer['ID'];

    //         if (isset($offer['Descr'])) {
    //             unset($offer['Descr']);
    //         }

    //         $offer['selected'] = false;
    //     }
    //     unset($offer);
        
    //     // Второй запрос для получения картинок и url объявлений
    //     // $url = "https://data.tronk.info/offer.ashx";
    //     // $request_params = array(
    //     //     "key" => self::TRONK_API_KEY,
    //     //     "command" => "data",
    //     //     "listid" => implode(',', $ids),
    //     // );

    //     // $get_params = http_build_query($request_params);
    //     // $response = file_get_contents($url."?".$get_params);
    //     $response = self::OFFERS2;
    //     $data = json_decode($response, true);
    //     $error = !empty($data['error'])
    //         ? ($data['error_msg'] ?? $data['error'])
    //         : null;

    //     if ($error) {
    //         $result['error'] = $error;
    //         return $result;
    //     }

    //     // Собираем данные второго запроса по UID
    //     $contacts = [];

    //     foreach ($data['result']['ListContact'] ?? [] as $contact) {
    //         $contacts[$contact['UID']] = [
    //             'url' => $contact['Url'] ?? null,
    //             'images' => !empty($contact['Images'])
    //                 ? explode(',', $contact['Images'])
    //                 : [],
    //         ];
    //     }

    //     // Добавляем URL и картинки к объявлениям
    //     foreach ($offers as &$offer) {
    //         $id = $offer['ID'];
    //         $offer['price'] = $offer['Price'] ?? 0;
    //         $offer['url'] = $contacts[$id]['url'] ?? null;
    //         $offer['images'] = $contacts[$id]['images'] ?? [];
    //     }
    //     unset($offer);

    //     $result['offers'] = $offers;

    //     return $result;
    // }
                        
    // public static function getPrices(array $vehicle)
    // {
    //     $url = "https://data.tronk.info/avgcarprice.ashx";
    //     $requestParams = [
    //         "key" => self::TRONK_API_KEY,
    //         "method" => "getprice",

    //         "marka" => "VAZ",
    //         "model" => "GRANTA",
    //         "gen" => "I",
    //         "year" => 2014,

    //         "regionid" => 71, // киров

    //         "volume" => 1.6,
    //         "horse" => 87,
    //         //"kpp" => 1,
    //     ];

    //     // $url = "https://data.tronk.info/carprices.ashx";
    //     // $requestParams = array(
    //     //     "key" => self::TRONK_API_KEY,
    //     //     "year" => 2014,
    //     //     "marka" => "VAZ",
    //     //     "model" => "GRANTA",
    //     //     "method" => "prices"
    //     // );


    //     // $getParams = http_build_query($requestParams);
    //     // $response = file_get_contents($url."?".$getParams);
        
    //     $response = self::PRICES;
    //     $data = json_decode($response, true);

    //     $result = [
    //         'error' => !empty($data['error']) ? ($data['error_msg'] ?? $data['error']) : null,
    //         'prices' => []
    //     ];

    //     $prices = array_column($data['result']['offers'] ?? [], 'price');

    //     $result['prices'] = [
    //         'avg' => isset($data['result']['average'])
    //             ? (int)$data['result']['average']
    //             : null,
    //         'min' => $prices ? (int)min($prices) : null,
    //         'max' => $prices ? (int)max($prices) : null,
    //     ];

    //     return $result;

    //     // Тип двигателя автомобиля:
    //     // 1 - бензиновый
    //     // 2 - дизельный
    //     // 3 - гибридный
    //     // 4 - электродвигатель
    //     // 5 - газовый

    //     // Тип привода:
    //     // 1 - передний
    //     // 2 - задний
    //     // 3 - полный
    // }

    // public static function getSimilarByVin(string $vin)
    // {
    //     // Входные данные
    //     $url = "https://data.tronk.info/offerbyvin.ashx";
    //     $requestParams = [
    //         "key" => self::TRONK_API_KEY,
    //         "command" => "list",
    //         "startyear" => 2014,
    //         "endyear" => 2014,
    //         // "pricestart" => 1,
    //         // "priceend" => 9999,
    //         "distancestart" => 146604,
    //         "distanceend" => 146604,
    //         "marka" =>  ".VAZ.",
    //         "source" => ".2.",
    //         "regionid" => 71, // киров
    //         "pagesize" => 300,
    //         //"listregionid" => ".REG1.REG2.", // например .1.2.
    //         // "countcar" => 5,
    //         // "countphone" => 5
    //     ];
    //     $getParams = http_build_query($requestParams);
    //     $response = file_get_contents($url."?".$getParams);
    //     return $response;

    //     // Преобразование ответа
    //     $result = json_decode($response);
    // }


    private const OFFERS = '
        {
  "result": [
    {
      "inner_id": "1132305133-61290ab5",
      "title": "Lada (ВАЗ) Granta I Рестайлинг",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1132305133-61290ab5/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I Рестайлинг",
      "complectation": "Comfort Light",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "106",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "компания",
      "year": 2025,
      "price_rub": 938000,
      "km_age": 10000,
      "vin": "XTA**************",
      "owners_count": "1",
      "description": "Без ДТП, зеленая Автотека, 1 владелец, электронный ПТС, кондиционер, оригинальный пробег.\n\nЭтот современный седан идеально подходит для ежедневных поездок по городу и уверенных выездов на трассу, сочетая в себе практичность и доступность обслуживания. Динамичный двигатель мощностью 106 л.с. в паре с механической трансмиссией обеспечивает отличную топливную эффективность и необходимый запас мощности.\n\nАвтомобиль находится в превосходном состоянии, фактически соответствующем новому. Минимальный пробег в 9 361 км подтвержден документально, двигатель только прошел период обкатки. Кузов без повреждений, салон чистый и ухоженный, в нем сохранена атмосфера нового авто. Все узлы и агрегаты работают в штатном режиме, не требуя никаких вложений.\n\nЮридическая чистота автомобиля полностью проверена: отсутствуют залоги, штрафы или какие-либо ограничения на регистрацию. История эксплуатации прозрачна и подтверждена «зеленой» Автотекой. Автомобиль своевременно прошел регламентное ТО-1 у официального дилера на пробеге 9 361 км, что гарантирует техническую исправность всех систем.\n\nПриглашаем вас лично оценить состояние этой LADA Granta. Вы можете осмотреть автомобиль в светлом боксе и пройти тест-драйв, чтобы убедиться в его отличных ходовых качествах. Свяжитесь с нами, чтобы согласовать удобное время визита.\n\n🌟 Автосалон Гусар предлагает три эксклюзивных подарка\n1️⃣ УСТРАНЕНИЕ НЕДОСТАТКОВ АВТОМОБИЛЯ перед продажей.\n2️⃣ РАСШИРЕННАЯ ГАРАНТИЯ (действие акции до 31.10.2026)\n3️⃣ БЕСПЛАТНАЯ ЗАМЕНА МАСЛА ПРИ ПОКУПКЕ АВТОМОБИЛЯ\n\n🌟 УСЛУГИ\n✅ АВТОКРЕДИТ .\n✅ ОБМЕН Вашего автомобиля с пробегом на наш на выгодных условиях. (с доплатой в любую сторону).\n✅ ВЫКУП авто по рыночной цене (деньги сразу).\n✅ ИСТОРИЯ автомобиля БЕСПЛАТНО!\n✅ ЮРИДИЧЕСКАЯ ЧИСТОТА автомобиля гарантируется.\n\n#cme633-hjvvKI_",
      "offer_created": "2026-04-23T14:01:44.935+03:00",
      "added_at": "2026-09-08",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/14440393/b53f44810eb70b3e7e429676075e8c8e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/14440393/b53f44810eb70b3e7e429676075e8c8e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17841911/6781e9c5d675f781baaf4ab2073b6751/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17841911/6781e9c5d675f781baaf4ab2073b6751/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16966486/d5f9731bf0aa1d85fd8206c1257fc300/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16966486/d5f9731bf0aa1d85fd8206c1257fc300/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17640555/514426a714dd97dd87a241a4762760ef/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17640555/514426a714dd97dd87a241a4762760ef/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17707957/d7092ec2a6683f2cccb94740c8f86bb9/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17707957/d7092ec2a6683f2cccb94740c8f86bb9/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20761562/9c0fc647892bf082c0dda994a2649020/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20761562/9c0fc647892bf082c0dda994a2649020/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17720009/ff2208eac42b93f1e82eceb92b1eb844/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17720009/ff2208eac42b93f1e82eceb92b1eb844/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17413889/23b7c487622eaa5c213b3f41b620a4bc/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17413889/23b7c487622eaa5c213b3f41b620a4bc/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20335149/bd0044b116b09282a05bf0e8d63297de/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20335149/bd0044b116b09282a05bf0e8d63297de/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17713999/850a1fc88dbe129a986ef7e1ebeaaaf1/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17713999/850a1fc88dbe129a986ef7e1ebeaaaf1/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/21151957/0ef49c57946892f9ac34a00a3d671275/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/21151957/0ef49c57946892f9ac34a00a3d671275/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17413889/631dcf17756c73102e2b5f2f5db4aac7/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17413889/631dcf17756c73102e2b5f2f5db4aac7/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17660749/eca108637f9c61ac0c8db48638d2af2a/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17660749/eca108637f9c61ac0c8db48638d2af2a/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20656151/30ae7f8058f2ce5a009eec303d739cfd/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20656151/30ae7f8058f2ce5a009eec303d739cfd/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20140694/83d9faffc0f3b82a21cbeb246c5e6faf/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20140694/83d9faffc0f3b82a21cbeb246c5e6faf/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20315033/a7802c3716db06ea3a24298b304b60f1/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20315033/a7802c3716db06ea3a24298b304b60f1/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17697421/ee0dbf1dd5fdf5806cba8e4c49e8d087/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17697421/ee0dbf1dd5fdf5806cba8e4c49e8d087/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18008089/726dfed5e316bf193230330b21048859/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18008089/726dfed5e316bf193230330b21048859/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20263483/796e773ce9c26901f84a3fe0fd24c98f/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20263483/796e773ce9c26901f84a3fe0fd24c98f/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17038387/b1f191ad63cd2f8787ee57b079217e11/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17038387/b1f191ad63cd2f8787ee57b079217e11/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20358835/ef5f69069878946fd8bb340d5460871b/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20358835/ef5f69069878946fd8bb340d5460871b/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15041766/ce7429a73ebf48d08ae931b2ec3741b4/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15041766/ce7429a73ebf48d08ae931b2ec3741b4/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18106725/b024d882b90cbfe9aad299cc0fad80fb/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18106725/b024d882b90cbfe9aad299cc0fad80fb/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18621059/0bf8a4f29699dce46efe73c484ce34f6/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18621059/0bf8a4f29699dce46efe73c484ce34f6/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/14440393/b6294bb665a561bd989cfbe500a8b0fc/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/14440393/b6294bb665a561bd989cfbe500a8b0fc/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16405070/93b5ace2bab75760bfc54b17151e76dd/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16405070/93b5ace2bab75760bfc54b17151e76dd/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17720009/cdf73a4d18080a08ff8453e3cb024df5/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17720009/cdf73a4d18080a08ff8453e3cb024df5/1200x900"
        }
      ]
    },
    {
      "inner_id": "1133372727-a2b8719c",
      "title": "Lada (ВАЗ) Granta I Рестайлинг",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1133372727-a2b8719c/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I Рестайлинг",
      "complectation": "Luxe (2018-2019)",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "98",
      "prep_transmission": "автомат",
      "prep_drive_type": "передний",
      "prep_seller_type": "компания",
      "year": 2019,
      "price_rub": 748000,
      "km_age": 122000,
      "vin": "XTA**************",
      "owners_count": "2",
      "description": "2 владельца, оригинал ПТС, оригинальный пробег, автоматическая КПП, климат-контроль 1-зонный, подогрев передних сидений, обогрев лобового стекла.\n\nLada Granta в кузове седан с классической автоматической трансмиссией отлично подходит для ритма большого города. Автомобиль 2019 года в рестайлинге — это практичное решение для тех, кто ищет привычную в управлении и понятную в содержании машину на каждый день.\n\nПреимущества:\n• Автоматическая коробка передач Jatco обеспечивает плавное движение в плотном потоке, делая ежедневные поездки по городу менее утомительными.\n• Полный зимний пакет с обогревом лобового стекла и передних сидений избавляет от лишних хлопот в морозную и сырую погоду.\n• Климат-контроль автоматически поддерживает комфортную температуру в салоне, избавляя от ручных настроек в пути.\n• Базовый комфорт включает электростеклоподъемники, зеркала с обогревом и аудиосистему с Bluetooth для удобства ежедневной эксплуатации.\n\nСостояние:\nПо технической части в автомобиле недавно обновлены задние амортизаторы, шаровая опора и проведено обслуживание стояночного тормоза. Салон и остекление не имеют повреждений. Предпродажная подготовка дилером не проводилась.\n\nИстория:\nПо ПТС — 2 владельца, оригинал ПТС, пробег 122 000 км подтверждается данными техосмотров и сервисными записями. В истории обслуживания зафиксированы регулярные визиты к официальному дилеру, последние из которых датированы концом 2024 года. Ограничений, залогов и других юридических обременений не обнаружено.\n\nПриезжайте познакомиться с автомобилем лично. Вы сможете детально осмотреть машину, проверить работу всех функций и согласовать удобное время для тест-драйва.\n\n🌟 Автосалон Гусар предлагает три эксклюзивных подарка\n1️⃣ УСТРАНЕНИЕ НЕДОСТАТКОВ АВТОМОБИЛЯ перед продажей.\n2️⃣ РАСШИРЕННАЯ ГАРАНТИЯ (действие акции до 31.10.2026)\n3️⃣ БЕСПЛАТНАЯ ЗАМЕНА МАСЛА ПРИ ПОКУПКЕ АВТОМОБИЛЯ\n\n🌟 УСЛУГИ\n✅ АВТОКРЕДИТ .\n✅ ОБМЕН Вашего автомобиля с пробегом на наш на выгодных условиях. (с доплатой в любую сторону).\n✅ ВЫКУП авто по рыночной цене (деньги сразу).\n✅ ИСТОРИЯ автомобиля БЕСПЛАТНО!\n✅ ЮРИДИЧЕСКАЯ ЧИСТОТА автомобиля гарантируется.\n\n#cme633-MnS-I4w",
      "offer_created": "2026-07-13T16:58:25.411+03:00",
      "added_at": "2026-09-08",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16989297/4cf4f6746256ea5984a7510dd6916afc/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16989297/4cf4f6746256ea5984a7510dd6916afc/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17913096/e5676a3a3b645055c1741a918c116da7/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17913096/e5676a3a3b645055c1741a918c116da7/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17933380/1f1a88c943b97638c86a5db5d4c14a69/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17933380/1f1a88c943b97638c86a5db5d4c14a69/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20272960/dc5a1ec58f06276f84b267502fd072aa/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20272960/dc5a1ec58f06276f84b267502fd072aa/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17698736/aee3984ed5cb0dfb8541156097821b71/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17698736/aee3984ed5cb0dfb8541156097821b71/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20141841/3f93adcfb3c4650eb9cabd0fba36c5e5/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20141841/3f93adcfb3c4650eb9cabd0fba36c5e5/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18148556/4173b134edc9e50425c452f223d73164/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18148556/4173b134edc9e50425c452f223d73164/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20335149/546fdb6cb96f96c5aa575aac0f6cc0da/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20335149/546fdb6cb96f96c5aa575aac0f6cc0da/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2173187/f298266f60568b57ef164b5b97acfcee/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2173187/f298266f60568b57ef164b5b97acfcee/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16793145/86cdc35f41d05b712fb68a808266f943/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16793145/86cdc35f41d05b712fb68a808266f943/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15404718/c751ccf4fafc67cc2f96aac6e7938d57/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15404718/c751ccf4fafc67cc2f96aac6e7938d57/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17844485/06d2b81d9bfa3bfd1e6d8c6440616e6d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17844485/06d2b81d9bfa3bfd1e6d8c6440616e6d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20140694/f088e23366912ef26d325adceb605e30/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20140694/f088e23366912ef26d325adceb605e30/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2101465/8ebc5dc75079dd94c25bb7b16dbce340/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2101465/8ebc5dc75079dd94c25bb7b16dbce340/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20410536/1697d55256e9314a19a45a058b30f90f/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20410536/1697d55256e9314a19a45a058b30f90f/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15185647/26bd8263c44c6ae06ba225e1d327fcca/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15185647/26bd8263c44c6ae06ba225e1d327fcca/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/10500495/064885dff21912dca60865d7010c4b62/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/10500495/064885dff21912dca60865d7010c4b62/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2174341/a40b5725fb0f1c4116ef656d9b810e06/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2174341/a40b5725fb0f1c4116ef656d9b810e06/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20035467/e27643f9d00fa1785185384709b5bd41/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20035467/e27643f9d00fa1785185384709b5bd41/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19298886/82fce912d60c4e201537c71930b4ac3e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19298886/82fce912d60c4e201537c71930b4ac3e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18866657/9b025de5b557a576ada23f9fc3eecf4e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18866657/9b025de5b557a576ada23f9fc3eecf4e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17640555/728e7962d0b12d02be650c6a2fb598ec/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17640555/728e7962d0b12d02be650c6a2fb598ec/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18101889/475ec436eb85d430d370243f74b3670f/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18101889/475ec436eb85d430d370243f74b3670f/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20116688/8174f59348358f61e1fc5d5d7c040412/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20116688/8174f59348358f61e1fc5d5d7c040412/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20193191/f500a9cc16abc7031fe2d7b8b8e75d4c/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20193191/f500a9cc16abc7031fe2d7b8b8e75d4c/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/13922065/7fc71e792f87969d42dc213ad0def297/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/13922065/7fc71e792f87969d42dc213ad0def297/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17890514/b6188e175d20a9e4f979a71843e8f450/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17890514/b6188e175d20a9e4f979a71843e8f450/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18106725/15c126acac0f8f428be04c067b65afe7/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18106725/15c126acac0f8f428be04c067b65afe7/1200x900"
        }
      ]
    },
    {
      "inner_id": "1134034689-0074333f",
      "title": "Lada (ВАЗ) Granta I Рестайлинг",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1134034689-0074333f/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I Рестайлинг",
      "complectation": "#CLUB\'23",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "лифтбек",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "90",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "частник",
      "year": 2023,
      "price_rub": 750000,
      "km_age": 89000,
      "vin": "XTA**************",
      "owners_count": "2",
      "description": "Продаю гранту в комплектации Клаб. Кондиционер. Подогревы передних сидений, лобового стекла, заднего стекла и зеркал заднего вида. Передние стеклоподъёмники. Две подушки безопасности.\nЗадний парктроник. Камера заднего вида. Фаркоп с розеткой.\nЯ второй хозяин. Покупал у дилера, там же проходил ТО.\nЛетняя резина 1 сезон- покупал весной новую.\nЕсть комплект зимних колёс.",
      "offer_created": "2026-09-06T08:53:35.717+03:00",
      "added_at": "2026-09-07",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17993102/b38079e05cbf5f2c6e15a3a64519629e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17993102/b38079e05cbf5f2c6e15a3a64519629e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/6109589/1bd3fff96b1fa640eb570027143526bb/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/6109589/1bd3fff96b1fa640eb570027143526bb/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/4121071/c6d755ba2e16c35d27e4b9ff57da9beb/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/4121071/c6d755ba2e16c35d27e4b9ff57da9beb/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2165616/bc4f5fa54d9f9c76b20358f05c15c57f/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2165616/bc4f5fa54d9f9c76b20358f05c15c57f/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/1969232/4f03652809af313c02d60ce591075a0b/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/1969232/4f03652809af313c02d60ce591075a0b/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18986119/ad9d3c3c74a0c649c9e0964b2a9021da/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18986119/ad9d3c3c74a0c649c9e0964b2a9021da/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/11621042/b5690c383fea1f956a19c02609219b3d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/11621042/b5690c383fea1f956a19c02609219b3d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20439065/050519d033f8d5dd1bf4c9fbdd9f30d0/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20439065/050519d033f8d5dd1bf4c9fbdd9f30d0/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16673081/aa09016398259487dab039131c84df00/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16673081/aa09016398259487dab039131c84df00/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2154849/eae4333f411902079f82e5168bcc5cd3/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2154849/eae4333f411902079f82e5168bcc5cd3/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/21177414/0c80aa7741b9e6029d772ce13ebb75e5/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/21177414/0c80aa7741b9e6029d772ce13ebb75e5/1200x900"
        }
      ]
    },
    {
      "inner_id": "1134041153-9abca17c",
      "title": "Lada (ВАЗ) Granta I",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1134041153-9abca17c/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I",
      "complectation": "Classic 21901-51-212 (010)",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "87",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "частник",
      "year": 2012,
      "price_rub": 370000,
      "km_age": 147935,
      "vin": "XTA**************",
      "owners_count": "4",
      "description": "Продам свой личный автомобиль Лада гранта 2012. Я 4 владелец по ПТС\nВ моем владении 3 года, пробег родной.\nХорошая машина на каждый день! Ухаживала и любила.\nПо комплектации эур, передние стеклоподъемники, стоит сигнализация с автозапуском StarLine, зимняя, летняя резина.\nПо кузову есть косяки (видно на фото).\nПо технической части в хорошем состоянии, сел и поехал, ни разу не подводила.\nПо крашеным элементам: все двери и крышка багажника, все двери обработаны антикором.\nПередние крылья поменяны на железо.\nГод назад переваривали пороги.\nПо всем остальным вопросам лучше пишите, отвечу на телефон не всегда.\nТорг строго у капота, свои цены не предлагать.",
      "offer_created": "2026-09-06T22:56:29.859+03:00",
      "added_at": "2026-09-07",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17673331/798489fa237973e9fde82e5c14bc45f9/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17673331/798489fa237973e9fde82e5c14bc45f9/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20446679/6055c3748e1e901c1b0cca9fcd7cd95d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20446679/6055c3748e1e901c1b0cca9fcd7cd95d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16348430/18da4b91b2e9ea7d93f397b62dda10e3/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16348430/18da4b91b2e9ea7d93f397b62dda10e3/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20035467/ed2fa047353d01ba5acf50f5c8bdff2e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20035467/ed2fa047353d01ba5acf50f5c8bdff2e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2143986/3dce25ad11a00aa8d3a2a056ed63e280/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2143986/3dce25ad11a00aa8d3a2a056ed63e280/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2165167/1d3546bf57fca8ad8ebdcf0db34bbdd6/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2165167/1d3546bf57fca8ad8ebdcf0db34bbdd6/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20864073/04fa97f64889fa84e86c49cad3e4bd40/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20864073/04fa97f64889fa84e86c49cad3e4bd40/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17733459/bbd6a5d87ff38e3d4e0a4631daabc0ed/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17733459/bbd6a5d87ff38e3d4e0a4631daabc0ed/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/4968723/7ace2450162a5e1c53b728856a3da608/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/4968723/7ace2450162a5e1c53b728856a3da608/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19297093/d4682e292949fe1c226b0c6ef780ad39/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19297093/d4682e292949fe1c226b0c6ef780ad39/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2128412/c54f724b152807380934db2e4c32aefe/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2128412/c54f724b152807380934db2e4c32aefe/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18129838/99bfd3e6e92fd186f57d580f7da7c2c0/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18129838/99bfd3e6e92fd186f57d580f7da7c2c0/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/21074985/1ef886a161b0d00e39bfd01b0b1db33d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/21074985/1ef886a161b0d00e39bfd01b0b1db33d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/5484094/c18abf6d56cff89841b4cf4476f1907a/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/5484094/c18abf6d56cff89841b4cf4476f1907a/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2175069/e61ac8d5118bfcb789be694f29962c82/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2175069/e61ac8d5118bfcb789be694f29962c82/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17660749/099dd6d4958d5e54dabcbe9a8014c339/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17660749/099dd6d4958d5e54dabcbe9a8014c339/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18150448/9def5b84a0ca3791664bd49b609b86a7/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18150448/9def5b84a0ca3791664bd49b609b86a7/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20331633/76039df83d34696fcb5d88e262499bd0/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20331633/76039df83d34696fcb5d88e262499bd0/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20446679/33166df6707d1eec66d28b0338c41bf3/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20446679/33166df6707d1eec66d28b0338c41bf3/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20829654/acf718a43f6e9c09bc1887223e08976a/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20829654/acf718a43f6e9c09bc1887223e08976a/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20444157/f3fac9544881c679ee6b85d2a6dc4265/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20444157/f3fac9544881c679ee6b85d2a6dc4265/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19831563/2796f33da549b00ea511d250715cd9ef/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19831563/2796f33da549b00ea511d250715cd9ef/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2078197/c9cce625620b90efb168d4c0c26df5b0/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2078197/c9cce625620b90efb168d4c0c26df5b0/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19811519/dcf42919090b18ee6716ea781e73d13b/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19811519/dcf42919090b18ee6716ea781e73d13b/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/6313114/2da72d8b04cc4441652e52f17941be41/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/6313114/2da72d8b04cc4441652e52f17941be41/1200x900"
        }
      ]
    },
    {
      "inner_id": "1134031534-bd029f74",
      "title": "Lada (ВАЗ) Granta I Рестайлинг",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1134031534-bd029f74/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I Рестайлинг",
      "complectation": "Comfort (2019-2022)",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "универсал",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "106",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "частник",
      "year": 2025,
      "price_rub": 895000,
      "km_age": 5650,
      "vin": "XTA**************",
      "owners_count": "1",
      "description": "LADA) Granta Cross На гарантии покупалась в Кирове первое т.о 1 пробег подвержен готов в любой сервис и к диллеру на проверку приветствуется новая машина 🚗 этим все сказано расписывать смысла нету !!все прозрачно - Как есть без обмана .Не битая не крашеная . В салоне запах новой ✅Комплектация очень хорошая - 🟢кондиционер - подогрев сидений подогрев лобового . Стеклоподъемники.состояние нового автомобиля 🎁 можно сказать капсула 💊 с таким пробегом найти практически не реально - эксплутация почти лето 🟡сотвественно 1 хозяин в возрасте - не битая не крашенная 💯 процентов готов на любую проверку в любой сервис лобовое без сколов свое родное подогрев рабочий б - торга к сожалению нету цена минимальная которая сумма нужна - кому дорого или кто готов торговаться прошу не беспокоить - и звонить реально заинтересованным покупателем бережем свое и мое время ✅ автозапуском .. все сервисные книжки с прохождением т.о обслуживание у диллера 1 тео сделано еще на обкатке ✅- подвеска не стучит не гремит\n🟢- кондиционер -( холодит ) 😍\n✅-салон идеально чистый запах нового \n✅-отправлю дополнительные фото видео для потенциального покупателя 🎥\n✅- свои цены предлагать не надо за автомобиль 🚗 уверен полностью\n✅-с документами 📁 полный порядок юридически чистая\n✅- звонить ☎️ тем кто готов купить без глупых вопросов\n✅-торг 💰 возможен только на месте .\n✅- на Сообщения не отвечаю только 🛎\n✅-возможен обмен на автомобиль 🚙\n✅-успей купить хороший 😍 автомобиль 🚗 пока не выросли цены\n✅- звонить строго с 9.00 до 21.00\n🚀 🚀\n✅- вин номер на фотографии\nОсновные + автомобиля\n🟡- хорошая комплектация -электроусилитель руля подогрев лобового подогрев сидений музыка сигнализация - стеклоподъемники \n🟡- родной пробег на гарантии - обслуживание у диллера 1 т.о\n🟡- зеленый отчёт вся\n🟡-1 хозяин \n🟡- маленький пробег летняя эксплуатация\n🟡- не битая не крашенная вся в родной краске 💯 любые проверки\n🟡- обслужена без каких либо вложений у диллера на обкатк🟡2 - комплекта колес лето на лите зима на штампах с вторым комплектом будет дороже 2 комплекта ключей \n🟡- салон в идеальном состоянии запах новой \n🟡- отчёт при запросе отправлю .. есть дополнительные фото видео . Могу отправить но для тех кто заинтересован к покупке Звонить только реальным заинтересованным покупателям 👌по необходимости могу выйти по видео связи готов приехать в сервис в удобное для вас время .. присутвовать в гаи для постановки на учет .. составление договора купли продажи встретит в аэропорту на жд вокзала\n🟡- все чеки заказ наряды сервисные книжки вся документация полностью есть \nМинусы автомобиля\n1 -указана минимальная цена без торга",
      "offer_created": "2026-09-05T18:22:22.038+03:00",
      "added_at": "2026-09-06",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17969032/45408eed74811ec713177d735bf88452/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17969032/45408eed74811ec713177d735bf88452/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2159858/dd6824c48fedb4aed7ed962453971ff8/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2159858/dd6824c48fedb4aed7ed962453971ff8/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20291570/cb14857a25b0ca574a5626e99e2f7e09/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20291570/cb14857a25b0ca574a5626e99e2f7e09/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17737319/fe23a93847d0899b3a452ec04f42c39d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17737319/fe23a93847d0899b3a452ec04f42c39d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20141841/4f86370d5d6fa58f326c7818a23add48/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20141841/4f86370d5d6fa58f326c7818a23add48/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/9772983/85eaf7fbb02c323ddd0e3217bfc8d348/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/9772983/85eaf7fbb02c323ddd0e3217bfc8d348/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20680164/e4451dd98408537f2dcec61d9f615d0d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20680164/e4451dd98408537f2dcec61d9f615d0d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19063053/6e1bae5cd61263587c55b0293f2f9f8d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19063053/6e1bae5cd61263587c55b0293f2f9f8d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2192078/f0db2c186628faf13f79083e148f2d2d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2192078/f0db2c186628faf13f79083e148f2d2d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20193191/1e4bd35f6c9d43941cca14ee79ef1200/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20193191/1e4bd35f6c9d43941cca14ee79ef1200/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19792100/9dae5c22ff8cadf99144d1742a3b5512/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19792100/9dae5c22ff8cadf99144d1742a3b5512/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18006848/609b14fea6257d0b7d2439664c3bccf1/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18006848/609b14fea6257d0b7d2439664c3bccf1/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/11621042/57fa97b2523a2c7618cd8ebe3d62cc35/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/11621042/57fa97b2523a2c7618cd8ebe3d62cc35/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17435526/d2259a2733db2ab40c4eff1c36a2f1b4/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17435526/d2259a2733db2ab40c4eff1c36a2f1b4/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2168768/8e46a9026b91e93a03cbb5df91b93ee4/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2168768/8e46a9026b91e93a03cbb5df91b93ee4/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17713999/42b3ac9cb0d5b4791a52507687340312/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17713999/42b3ac9cb0d5b4791a52507687340312/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2154786/27398d37fbc4d5587d60f5991941ada6/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2154786/27398d37fbc4d5587d60f5991941ada6/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20951031/42d11d1ecb57f10f64da7575da1d0ce9/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20951031/42d11d1ecb57f10f64da7575da1d0ce9/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20410536/5ea374ee1ec4f6087f7ba72afabbef90/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20410536/5ea374ee1ec4f6087f7ba72afabbef90/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16503020/66bc445c361e8cfab19fcdbcd4c0ad01/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16503020/66bc445c361e8cfab19fcdbcd4c0ad01/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/4476278/03fe4148a9789ef1e2417d5f162daf10/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/4476278/03fe4148a9789ef1e2417d5f162daf10/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19298886/0760b9588b9c8fe206dd04a7e49ab3cc/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19298886/0760b9588b9c8fe206dd04a7e49ab3cc/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2079997/fea1e03c447dcd4dc10360f350190463/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2079997/fea1e03c447dcd4dc10360f350190463/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19634380/62c918d16f05d418fa8236b4c2494fc5/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19634380/62c918d16f05d418fa8236b4c2494fc5/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20331633/25e34613f79e796fcc5f1a5df02f1c7c/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20331633/25e34613f79e796fcc5f1a5df02f1c7c/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/21151957/e3e5cfc6533dd983dd2ab1d77d28de2f/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/21151957/e3e5cfc6533dd983dd2ab1d77d28de2f/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16872780/5e3da2b557d121ba29eadd292b116931/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16872780/5e3da2b557d121ba29eadd292b116931/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2159858/f23abda677555d6f11861a5d1969676f/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2159858/f23abda677555d6f11861a5d1969676f/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20331633/1b65fda3d394d0562260cd1324272515/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20331633/1b65fda3d394d0562260cd1324272515/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16019815/4c1df8f02e5f5df95778cc786178d4e0/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16019815/4c1df8f02e5f5df95778cc786178d4e0/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/1599755/f07cca9a4045f3eec86a8437a11c629e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/1599755/f07cca9a4045f3eec86a8437a11c629e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15688781/4aef491872191b83784bdb14bc2e1d2c/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15688781/4aef491872191b83784bdb14bc2e1d2c/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/1959786/6e0169cec64ca47c9448305966e560c8/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/1959786/6e0169cec64ca47c9448305966e560c8/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19793268/4da54ef3002c5b9def634f8c606e1923/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19793268/4da54ef3002c5b9def634f8c606e1923/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20016218/5fc7bdca0f98df712383aff5fd80308b/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20016218/5fc7bdca0f98df712383aff5fd80308b/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16348430/910c1c7868afe8a04af1aaae07d63fd7/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16348430/910c1c7868afe8a04af1aaae07d63fd7/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18090779/cb3756a3c2fc0b3a1f0229a19357dc62/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18090779/cb3756a3c2fc0b3a1f0229a19357dc62/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/4470305/defaa2da0cf59db6405c1a8596a0131d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/4470305/defaa2da0cf59db6405c1a8596a0131d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20543926/78aa2557650ba2a749ed3656ef0a0be4/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20543926/78aa2557650ba2a749ed3656ef0a0be4/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/4470305/3e8a26bd40807070e955633a2ac47df5/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/4470305/3e8a26bd40807070e955633a2ac47df5/1200x900"
        }
      ]
    },
    {
      "inner_id": "1134033735-cd676e1a",
      "title": "Lada (ВАЗ) Granta I Рестайлинг",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1134033735-cd676e1a/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I Рестайлинг",
      "complectation": null,
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "87",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "компания",
      "year": 2019,
      "price_rub": 495000,
      "km_age": 161911,
      "vin": "XTA**************",
      "owners_count": "3",
      "description": "Код автомобиля: 3894\n🔥 В осень на золотых условиях в АГАТ РФ | ПРОБЕГ!\n💳 Кpедит от 9,9% годовых\n⚡ Гaрaнтиpoвaннaя скидка от диpектора при oбрaщeнии cегoдня\n🔄 Mаксимальная выгодa по пpoгpaммe трейд-ин — пpимeм ваш автомобиль пo дocтoйной ценe\n🚗 В наличии более 2500 а/м! Доставим любой в Ваш город!\nПреимущества автомобиля:\n✔ ПТС оригинал\n✔ Полный комплект ключей\n✔ Автомобиль куплен у официального дилера\n📞 Остались вопросы? Позвоните или напишите нам в чат.\nАГАТ РФ | ПРОБЕГ 🏆\n📍 35 лет на рынке\n📍 22 000 автомобилей реализовано в 2025 году\n📍 Более 2 500 автомобилей в наличии\n📍 Собственные сервисные центры\nЗвоните сейчас. Не упустите свою выгоду!\nВвиду возраста и особенностей эксплуатации автомобиля, обращаем ваше внимание, что на автомобиле возможны:\nКоррозийные поражения кузовных элементов\nЕсть косметические окрасы по кузову\nПо истории имеется дорожно-транспортное происшествие, ДТП\nКредит предоставляет АО Альфа-Банк, ИНН 7728168971 ОГРН 1027700067328 место нахождение 107078, г. Москва, ул. Каланчевская, д. 27 (лицензия ЦБ РФ от 16.01.2015 № 1326). Параметры программы «Alfa Challenge»: валюта кредита – рубли РФ; диапазон значений полной стоимости кредита составляет в % годовых от 18,865% до 33,193%.Процентная ставка 9,9% доступна только при оформлении услуг «Твоя ставка» и «Страхование жизни». Стоимость услуг рассчитывается индивидуально и зависит от параметров кредита. Первоначальный взнос от 15%. Сумма кредита от 100 000 руб. до 13 000 000 руб. Срок кредитования от 12 до 96 мес. Неустойка за просрочку платежа — 0,1% за каждый день просрочки. ИЗУЧИТЕ ВСЕ УСЛОВИЯ КРЕДИТА (ЗАЙМА) на сайте Альфа-Банка в разделе Частным лицам → Кредиты → Кредит на автомобиль. ОЦЕНИВАЙТЕ СВОИ ФИНАНСОВЫЕ ВОЗМОЖНОСТИ И РИСКИ. Банк вправе отказать в выдаче кредита. Подробнее об условиях кредитования, требованиях к страхованию, к заемщику и к документам на сайте банка-партнера. Условия актуальны на 01.09.2026 Предложение носит информационный характер, не является публичной офертой.\n#автоспробегом #амспробегом #автомобилиспробегом #пробег #агат #агатпробег #купитьавто #купитьавтоспробегом #трейдин #бу #буавто",
      "offer_created": "2026-09-05T21:24:45.672+03:00",
      "added_at": "2026-09-06",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19811519/0ede547087ab19845add1a28bc1fb8e7/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19811519/0ede547087ab19845add1a28bc1fb8e7/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16277572/9b5b7fe440bdbd57028bb18b89026510/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16277572/9b5b7fe440bdbd57028bb18b89026510/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19298886/49c45eac9e153a6a2d8a073cce94564c/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19298886/49c45eac9e153a6a2d8a073cce94564c/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/9772983/ed1f03415e71ec373ede76967aaf4a2c/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/9772983/ed1f03415e71ec373ede76967aaf4a2c/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20556383/aeabd678fcb78a0a5d972fe59357f674/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20556383/aeabd678fcb78a0a5d972fe59357f674/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/4012354/6008b98ac8581e946e03ec114b3032e1/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/4012354/6008b98ac8581e946e03ec114b3032e1/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16483913/e0dd02caf890a39978778201308b288c/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16483913/e0dd02caf890a39978778201308b288c/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17890514/e7806cbb10451f82086b9078d52dd587/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17890514/e7806cbb10451f82086b9078d52dd587/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20181844/17495a3ad8e0a5e5fdcfc77ebdb00bc1/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20181844/17495a3ad8e0a5e5fdcfc77ebdb00bc1/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20180167/b96b06d57db9525e29f7c83d44ce986b/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20180167/b96b06d57db9525e29f7c83d44ce986b/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2181062/f68ac6375f33ecaf592e932bdaca0811/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2181062/f68ac6375f33ecaf592e932bdaca0811/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2165736/ab5366c8c9058e54b6d2a219dd923dbe/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2165736/ab5366c8c9058e54b6d2a219dd923dbe/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17926310/13b5d190e484f6eeb46496c33a21d9d5/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17926310/13b5d190e484f6eeb46496c33a21d9d5/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17435526/6e365bef32f7c15ddddfae9484c0bfd3/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17435526/6e365bef32f7c15ddddfae9484c0bfd3/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20346480/60671766cb7c3a1f1adced55b69972f1/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20346480/60671766cb7c3a1f1adced55b69972f1/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17637180/01f87dd89aaec5f101e2ac5e1ea12eca/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17637180/01f87dd89aaec5f101e2ac5e1ea12eca/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2176986/754db914a4ecd52ad5918b0729120ea0/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2176986/754db914a4ecd52ad5918b0729120ea0/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17777984/5ed7aace386b189ac72e1674e3f37997/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17777984/5ed7aace386b189ac72e1674e3f37997/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18154113/1d3a96d51106704f0cd2ba404550ce27/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18154113/1d3a96d51106704f0cd2ba404550ce27/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20694092/d7dbaa8f4ef40d6270d2be859590803e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20694092/d7dbaa8f4ef40d6270d2be859590803e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17915300/c62a3087c15a650d7ecab7b5ca97dae2/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17915300/c62a3087c15a650d7ecab7b5ca97dae2/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/1966658/6928406c8f784af0e41eb26feac15d37/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/1966658/6928406c8f784af0e41eb26feac15d37/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20116688/1a74f6c66c565253e33d2dfc18876068/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20116688/1a74f6c66c565253e33d2dfc18876068/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20223974/10b0eeec59f0befdfb8c417def719bfe/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20223974/10b0eeec59f0befdfb8c417def719bfe/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16383606/3dff9752b2a15e2cf4dee26a296bb3f3/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16383606/3dff9752b2a15e2cf4dee26a296bb3f3/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16383606/53cf65c949c94ff5404b626feca1b84a/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16383606/53cf65c949c94ff5404b626feca1b84a/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20315033/6b8d9fc4f4dcd241b5d2c40cce3d29a9/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20315033/6b8d9fc4f4dcd241b5d2c40cce3d29a9/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20394542/0997065c6bd84523504aeae2aa6dc0bf/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20394542/0997065c6bd84523504aeae2aa6dc0bf/1200x900"
        }
      ]
    },
    {
      "inner_id": "1134033151-7cf26fbd",
      "title": "Lada (ВАЗ) Granta I Рестайлинг",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1134033151-7cf26fbd/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I Рестайлинг",
      "complectation": "Comfort (2023)",
      "section": "USED",
      "region": "Кировская область",
      "city": "Опарино",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "90",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "частник",
      "year": 2022,
      "price_rub": 830000,
      "km_age": 95000,
      "vin": "XTA**************",
      "owners_count": "1",
      "description": "Пpoдам Lаdа Grаnta FL 1.6 90л.с., куплен в январе 2023г, нa учётe с янвaря 2023г, пpoбeг 94500 км. (может измениться ,так как автомобиль используется каждый день ). Oдин coбcтвенник.(ЕПТС)\nВсe TO у oф.диллерa TTC (cервисная книжка), кpомe поcлeднего. Замена масла кaждыe 7-8 тыc.км. Последняя замена масла была 3500км назад.\nНовые тормоза в круг , новая сцепление вместе с корзиной , новые свечи зажигания. Вибро-шумоизоляция в круг кроме моторного щита.\nКомплектaция Clаssic\'23 Eвpo-5 : кондиционер, элeктpоуcилитeль pуля, пеpедниe электростеклоподъёмники, электропривод, подогрев заднего стекла и наружных зеркал\nСигнализация с автозапуском Pandora .Установлен ГЛОННАС, магнитола 2din 7 дюймов.\nМашина на штампованых дисках с колпаками , резина демисезонная 3-тий сезон идет \nАвтомобиль в хорошем состоянии, обслужен.\nИмеются 2 не глубокие царапины, уйдут после полировки \nПерекупам, автосалонам просьба не тратить время, автомобиль в эксплуатации каждый день.",
      "offer_created": "2026-09-05T22:25:09.408+03:00",
      "added_at": "2026-09-05",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/11621042/ff199566832eb4068150b63cfc0acb21/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/11621042/ff199566832eb4068150b63cfc0acb21/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20180167/3a5e73b531f4171656abfd8dd4411c94/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20180167/3a5e73b531f4171656abfd8dd4411c94/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2157511/a26232d66325922541f5ca9e5fb4e766/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2157511/a26232d66325922541f5ca9e5fb4e766/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19700919/f938bc6abd5ed7ceb296e0142d508257/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19700919/f938bc6abd5ed7ceb296e0142d508257/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/1976162/bbb9de832538e12c54353b23ae66f3cd/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/1976162/bbb9de832538e12c54353b23ae66f3cd/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/6109589/5e7b0022f3431a99cea2f7acb0b59ec9/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/6109589/5e7b0022f3431a99cea2f7acb0b59ec9/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18185687/4d942369fcfdf4a3c291edc13a070e22/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18185687/4d942369fcfdf4a3c291edc13a070e22/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20410536/d920aa1f69f06266719ab14695efe0b1/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20410536/d920aa1f69f06266719ab14695efe0b1/1200x900"
        }
      ]
    },
    {
      "inner_id": "1133375360-5a95314f",
      "title": "Lada (ВАЗ) Granta I Рестайлинг",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1133375360-5a95314f/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I Рестайлинг",
      "complectation": "Comfort (2018-2021)",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "87",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "частник",
      "year": 2018,
      "price_rub": 498000,
      "km_age": 153000,
      "vin": "XTA**************",
      "owners_count": "2",
      "description": "Продаю Лада Гранта ноябрь 2018 г.в\n\n2 владельца\nродной пробег\nзеленый отчёт\nцвет серебристый\nкондиционер\nавтозапуск\n2 стеклоподъемника\nгур\nабс\n\nпри осмотре торг",
      "offer_created": "2026-09-03T21:49:36.033+03:00",
      "added_at": "2026-09-04",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20104625/f785668a2d6128c17a8ff9063efb754e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20104625/f785668a2d6128c17a8ff9063efb754e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20446679/dca158e55e83997936d74681ba3f2253/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20446679/dca158e55e83997936d74681ba3f2253/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16308788/41e6068d10e042762f5fd4ef2e2e0aa8/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16308788/41e6068d10e042762f5fd4ef2e2e0aa8/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15073297/ed29ca1a160de2017e946b628349e21f/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15073297/ed29ca1a160de2017e946b628349e21f/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16348430/3cbfb352a86af72c29b2d4e54da82987/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16348430/3cbfb352a86af72c29b2d4e54da82987/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20467659/eb97ff121b88b124f8b310c9520bb1b5/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20467659/eb97ff121b88b124f8b310c9520bb1b5/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18790574/315c7e818b69c2dff5821114421afa50/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18790574/315c7e818b69c2dff5821114421afa50/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20055049/c1c4dad581250e0e99e27653e987c30f/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20055049/c1c4dad581250e0e99e27653e987c30f/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20680164/5c7aa24b236fa11ca45b4642973c9035/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20680164/5c7aa24b236fa11ca45b4642973c9035/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17645331/a1bc547104a6ae59348809eea70436b0/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17645331/a1bc547104a6ae59348809eea70436b0/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17709926/7f6e846b0af8e1297179c7e9dd93efe0/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17709926/7f6e846b0af8e1297179c7e9dd93efe0/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18790574/c7bd5a1f1a6a7174ebd22a04a751bb87/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18790574/c7bd5a1f1a6a7174ebd22a04a751bb87/1200x900"
        }
      ]
    },
    {
      "inner_id": "1133985388-3aa467cd",
      "title": "Lada (ВАЗ) Granta I Рестайлинг",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1133985388-3aa467cd/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I Рестайлинг",
      "complectation": "Comfort\'23",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "106",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "компания",
      "year": 2025,
      "price_rub": 1015000,
      "km_age": 11001,
      "vin": "XTA**************",
      "owners_count": "1",
      "description": "Автосалон “Мотор Эксперт” на Московской, 172 (напротив “МЕТРО”). Более 300 автомобилей в наличии | TRADE IN | Автокредит.\nРаботаем с 8:00 до 20:00.\nСАМЫЙ БОЛЬШОЙ ВЫБОР АВТОМОБИЛЕЙ С ПРОБЕГОМ В КИРОВЕ.\n\nСтоимость в объявлении реальная, без скрытых платежей. Мы готовы обсудить индивидуальные скидки при встрече. Звоните или пишите нам в чат.\n\nПреимущества для вас:\n- Бесплатно предоставим отчёт Автотеки\n- Автомобиль полностью юридически чист и проверен\n- 10 банков-партнёров для быстрого и выгодного кредитования\n- Выгодный обмен (трейд ин), автострахование\n\nПреимущества автомобиля:\n- 1 владелец по ПТС\n- Зеленая автотека\n- Кондиционер\n\nПриглашаем Вас к нам на тест-драйв. Будем рады ответить на Ваши вопросы! Ждём Вас по адресу: г. Киров, ул. Московская, 172 (напротив «МЕТРО»).\n\n#cmecOTY3LdCc0JBf0KbQkTAwMDYyMTI3X1NCRDAwMDE1NDg=",
      "offer_created": "2026-09-02T08:05:41.414+03:00",
      "added_at": "2026-09-03",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/6671878/2d7911e5310c9ddc0995362af3a1f9b4/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/6671878/2d7911e5310c9ddc0995362af3a1f9b4/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20556383/fa8c9f48592fe7abbcaa09e475ccfc6a/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20556383/fa8c9f48592fe7abbcaa09e475ccfc6a/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/6027420/95b25f05e8e3f3dc2eed08b2892d338a/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/6027420/95b25f05e8e3f3dc2eed08b2892d338a/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/14564401/facf79959676cfd7eb41753787ccb653/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/14564401/facf79959676cfd7eb41753787ccb653/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18068962/9d8284a7bc16def146313462b227ad7e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18068962/9d8284a7bc16def146313462b227ad7e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15628906/cdf8844ca74c00eec2dc10a683b32023/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15628906/cdf8844ca74c00eec2dc10a683b32023/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20849947/5e6d945d2c772073fb4394280f71c346/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20849947/5e6d945d2c772073fb4394280f71c346/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19873021/243bc7658df8f9998619c1524ff9f99c/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19873021/243bc7658df8f9998619c1524ff9f99c/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19831563/8c7e745ed8b15425e6ed16a9b7252f5e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19831563/8c7e745ed8b15425e6ed16a9b7252f5e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20390804/487aa833f09c9db2bf529181096c3db3/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20390804/487aa833f09c9db2bf529181096c3db3/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20289822/8b34b0569d38d0b8c18c24a6cf4c3aa5/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20289822/8b34b0569d38d0b8c18c24a6cf4c3aa5/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20191207/f22503effaaeec0390a7c44deeac3d99/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20191207/f22503effaaeec0390a7c44deeac3d99/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2181185/be40551b2ab2a07356cfd5da4123e360/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2181185/be40551b2ab2a07356cfd5da4123e360/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16149001/92271e04735f502d7d8ed5d7d740619c/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16149001/92271e04735f502d7d8ed5d7d740619c/1200x900"
        }
      ]
    },
    {
      "inner_id": "1133986647-8315c567",
      "title": "Lada (ВАЗ) Granta I Рестайлинг",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1133986647-8315c567/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I Рестайлинг",
      "complectation": "Comfort (2021-2022)",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "универсал",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "90",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "компания",
      "year": 2023,
      "price_rub": 885000,
      "km_age": 27761,
      "vin": "XTA**************",
      "owners_count": "1",
      "description": "Автосалон “Мотор Эксперт” на Московской, 172 (напротив “МЕТРО”). Более 300 автомобилей в наличии | TRADE IN | Автокредит.\nРаботаем с 8:00 до 20:00.\nСАМЫЙ БОЛЬШОЙ ВЫБОР АВТОМОБИЛЕЙ С ПРОБЕГОМ В КИРОВЕ.\n\nСтоимость в объявлении реальная, без скрытых платежей. Мы готовы обсудить индивидуальные скидки при встрече. Звоните или пишите нам в чат.\n\nПреимущества для вас:\n- Бесплатно предоставим отчёт Автотеки\n- Автомобиль полностью юридически чист и проверен\n- 10 банков-партнёров для быстрого и выгодного кредитования\n- Выгодный обмен (трейд ин), автострахование\n\nПреимущества автомобиля:\n- 1 владелец по ПТС\n- Зеленая автотека\n- Кондиционер\n\nПриглашаем Вас к нам на тест-драйв. Будем рады ответить на Ваши вопросы! Ждём Вас по адресу: г. Киров, ул. Московская, 172 (напротив «МЕТРО»).\n\n#cmecOTY3LdCc0JBf0KbQkTAwMDYyMTUzX1NCRDAwMDE1NzI=",
      "offer_created": "2026-09-02T08:51:50.115+03:00",
      "added_at": "2026-09-03",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2113863/9e41244b84c6095e4902b606812d0b52/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2113863/9e41244b84c6095e4902b606812d0b52/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2160363/071c9c84ca1ec27df08fa31e44b01782/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2160363/071c9c84ca1ec27df08fa31e44b01782/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/1898314/696b9cc0a06192842101e1c44d5dea9d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/1898314/696b9cc0a06192842101e1c44d5dea9d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/5451090/947de14858f623b1ef25d1fda5a3f88c/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/5451090/947de14858f623b1ef25d1fda5a3f88c/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2174341/2bba201fd164e3ec79b49c4e1cb9a67c/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2174341/2bba201fd164e3ec79b49c4e1cb9a67c/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19936221/4a5e93358991836753671bed784292ec/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19936221/4a5e93358991836753671bed784292ec/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/1654109/f2d5cf79faed0dcb4b6dacd2cf2149ea/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/1654109/f2d5cf79faed0dcb4b6dacd2cf2149ea/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2144762/2810c3820e8486884d9dba3376ea61c7/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2144762/2810c3820e8486884d9dba3376ea61c7/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/1973880/7a594ba5cf124c3b27fbb52a65b3da86/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/1973880/7a594ba5cf124c3b27fbb52a65b3da86/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/12852473/6ec23d164857ed74efbe73da898f6fec/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/12852473/6ec23d164857ed74efbe73da898f6fec/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15587868/af5d4bf36210603f9d19fc27d3e15c81/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15587868/af5d4bf36210603f9d19fc27d3e15c81/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19816781/a3f1c5b839b20c15ef4958dc2de66a44/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19816781/a3f1c5b839b20c15ef4958dc2de66a44/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17637180/82856a7c020808a544021383b7a38bad/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17637180/82856a7c020808a544021383b7a38bad/1200x900"
        }
      ]
    },
    {
      "inner_id": "1133996103-60f6779d",
      "title": "Lada (ВАЗ) Granta I",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1133996103-60f6779d/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I",
      "complectation": "Norma Classic 21901-51-010",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "87",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "частник",
      "year": 2013,
      "price_rub": 305000,
      "km_age": 239574,
      "vin": "XTA**************",
      "owners_count": "3",
      "description": "Продам гранту в хорошем состоянии\nСобственник \nЯ 3 владелец.\nВсе обслуживалось и менялось своевременно,за машиной следил и ездил не очень много.\nКомплектация ЭУР,2 эсп,подогрев сидений,подушки безопасности.\nДанным авто владею 4-ый год,так что по машине знаю всё.\nПолный кап ремонт двигателя в 2023 году с расточкой блока и заменой поршневой группы (кольца,пальцы,поршни,клапана)\nМасло от замены до замены.\nНе ест ни грамма,тяга отличная.\nВ машине все менялось своевременно\nЧастично проварена,дно обработано без дыр,без гнили,без ржавчины по кузову. \nLed лампы \nЛето на 14 литье x-traik(покупал новое) \nЗима на штампах\nИз последнего заменено тормозные колодки,основной радиатор,пыльники,комплект сцепления,прокладка поддона.\nМасло лью синтетику 10w40 (заменено 1000 км назад)\nМашина обслужена,езжу каждый день.\nМинимальный торг и только при осмотре.",
      "offer_created": "2026-09-02T22:04:02.386+03:00",
      "added_at": "2026-09-02",
      "images_list": [
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/5309308/ZPAybxX506YGIxHW9dgeu5kxEX9EIX3Atox7oBNuXu7fX3aUXr7gV1b1_vra4_DvPcvD6lZGKbBOLeubO4WoC7MFI9wopvOciMefsMDDhfZf9sFz6_OIdTP2luGE2f4sWsF2YcB0u0NWMFrO5zAmtcQzQQHw0u8v540DQNAKoWiavCA/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/5309308/ZPAybxX506YGIxHW9dgeu5kxEX9EIX3Atox7oBNuXu7fX3aUXr7gV1b1_vra4_DvPcvD6lZGKbBOLeubO4WoC7MFI9wopvOciMefsMDDhfZf9sFz6_OIdTP2luGE2f4sWsF2YcB0u0NWMFrO5zAmtcQzQQHw0u8v540DQNAKoWiavCA/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/1960172/jT9lDkvhz2EenR3zVO_6T0D8FOkueBxXXRMkc_thZ25sc2AjVtclBbnwapH4sORILdkwt_2qHF0A-5pMW99iVz3ALl2sh5Wff2BH-BbZ5elsq1O3BsdIg9suMjUj8HBRTfyEePFnN16OnO4cjzfxAUeHN_G9UXYYC-0pJ0vo2hNr5EE/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/1960172/jT9lDkvhz2EenR3zVO_6T0D8FOkueBxXXRMkc_thZ25sc2AjVtclBbnwapH4sORILdkwt_2qHF0A-5pMW99iVz3ALl2sh5Wff2BH-BbZ5elsq1O3BsdIg9suMjUj8HBRTfyEePFnN16OnO4cjzfxAUeHN_G9UXYYC-0pJ0vo2hNr5EE/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/20657148/hW8gWZlFpmKi0NO9R2O83UGLyDSlwpXKX5X5xRisIBy67CrkvTtf_AKwiPz8MknYHX55djLpW7hCUwfgBNq16QZ8uePbeoe7K9niRCUZeYWwH8lxxxFmxTAKF4yQcbzJb1HST21S9KDZPw7vGz5KLQE_LazjqWVvMnF1DHjtoE2vd80/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/20657148/hW8gWZlFpmKi0NO9R2O83UGLyDSlwpXKX5X5xRisIBy67CrkvTtf_AKwiPz8MknYHX55djLpW7hCUwfgBNq16QZ8uePbeoe7K9niRCUZeYWwH8lxxxFmxTAKF4yQcbzJb1HST21S9KDZPw7vGz5KLQE_LazjqWVvMnF1DHjtoE2vd80/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/18006848/ZahssejjEBhXUsevScNHQ2OG13LADaMbKB_Z4aGOYe0X3WDluJIA0Pe9j1Px3cz-VMEaxPIOtIzi5Tfv3oyGnxENoxJTYEjzX5ggGNb4RoyUjDo_kjDJfkbUkYHebXnubyNl7Nn6seJ54qomFaOS6qBGIZ_6O2wuVcEy05FwcBwirUU/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/18006848/ZahssejjEBhXUsevScNHQ2OG13LADaMbKB_Z4aGOYe0X3WDluJIA0Pe9j1Px3cz-VMEaxPIOtIzi5Tfv3oyGnxENoxJTYEjzX5ggGNb4RoyUjDo_kjDJfkbUkYHebXnubyNl7Nn6seJ54qomFaOS6qBGIZ_6O2wuVcEy05FwcBwirUU/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/2023653/f4aZgh_9qesbRp8o1muqfV_0QIeA5ihtSk70t9QldDhbf2pjUxO8dEsKshyLjEC9_U1Xm2PYdlqzbFK4RmAu2Qjs2Y26qmrSeYXvQ8RM7LL7bmjkMARXxI5eW3TIiM7TSVnALC-REdViu_PIQf5SUjotnYm9omveG1Ootn0JWSDf_K4/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/2023653/f4aZgh_9qesbRp8o1muqfV_0QIeA5ihtSk70t9QldDhbf2pjUxO8dEsKshyLjEC9_U1Xm2PYdlqzbFK4RmAu2Qjs2Y26qmrSeYXvQ8RM7LL7bmjkMARXxI5eW3TIiM7TSVnALC-REdViu_PIQf5SUjotnYm9omveG1Ootn0JWSDf_K4/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/18150448/uUuRpOs9_TY7Faukd9bkpJSxF5hPu0u6OWYUz20_7aJXSdAYrcqXxtwCUQLNqfFlUyJlosq8uRDvRBwxgXBWqmuMHRWs7O9yskHvdGrjyI9Ppgqgcja8jaCX_QrVAbzd-UGMtkSnBJJmLQDbCFMZaIQ_x1jIOWXutBVoyxTHhU3v-hk/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/18150448/uUuRpOs9_TY7Faukd9bkpJSxF5hPu0u6OWYUz20_7aJXSdAYrcqXxtwCUQLNqfFlUyJlosq8uRDvRBwxgXBWqmuMHRWs7O9yskHvdGrjyI9Ppgqgcja8jaCX_QrVAbzd-UGMtkSnBJJmLQDbCFMZaIQ_x1jIOWXutBVoyxTHhU3v-hk/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/20191207/k8d0c1_mmFrdM6vXbkHqmFfENoFV7kstaf4mHSeApkJid0qJcqRwsOYfQPjRjfrKyTESw31aBwrfItrsrL-VTTkc4v08D2Q_xBqW6z9NH5-KQlmsMEkf96bRuXs_pC8kSo-J9XMmU60h5cEa5nlk9k7YZMlY9QtAL7z-fDKAzhoqHzU/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/20191207/k8d0c1_mmFrdM6vXbkHqmFfENoFV7kstaf4mHSeApkJid0qJcqRwsOYfQPjRjfrKyTESw31aBwrfItrsrL-VTTkc4v08D2Q_xBqW6z9NH5-KQlmsMEkf96bRuXs_pC8kSo-J9XMmU60h5cEa5nlk9k7YZMlY9QtAL7z-fDKAzhoqHzU/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/20467659/ic2BTsHG7wgb170jeITOSjx8CWZ8B6Vb14Zpo7F_Gan1I4Y8TOuZdiGa72B67VgplI7eCBV58yB6Olxz-S-Fn3_QIajlGG4gvoCWaSFYykEa_HW3vfgOTzs9llBom9jBUseVC-_3mzXIrtvv3XzKj4Kuk52yNMX57e9egEbl7uajCzQ/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/20467659/ic2BTsHG7wgb170jeITOSjx8CWZ8B6Vb14Zpo7F_Gan1I4Y8TOuZdiGa72B67VgplI7eCBV58yB6Olxz-S-Fn3_QIajlGG4gvoCWaSFYykEa_HW3vfgOTzs9llBom9jBUseVC-_3mzXIrtvv3XzKj4Kuk52yNMX57e9egEbl7uajCzQ/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/20740510/ln50poqvGroEVfi6zQ6E2Ssrj-TmmbAbKX2jO8t7ryV-HYqn3qCNDRUvKzW848CVfRNzFYrMFtA-DIH2mFv4EPRNf6aEhPgyCcOGiwcaWH_q-E1ciJ01VjyFmWCtkc2TWpWao-kBeumTCtfpVZI5X_-GBOu_uQM8juWUP7aCNTADyNg/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/20740510/ln50poqvGroEVfi6zQ6E2Ssrj-TmmbAbKX2jO8t7ryV-HYqn3qCNDRUvKzW848CVfRNzFYrMFtA-DIH2mFv4EPRNf6aEhPgyCcOGiwcaWH_q-E1ciJ01VjyFmWCtkc2TWpWao-kBeumTCtfpVZI5X_-GBOu_uQM8juWUP7aCNTADyNg/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/20673651/BY_wop7V03q5_DI7QezLKWIwmwdKI_vPesklGLfCoTme3PcdSv_N1QEMYUhVsOx88rs0JTfi1jF1utKByL73-JWd0ueq0jMJ5OdeJDc37O0lTi6zCJx9UDkDae6D7DsdnodXjLwrYOE5gsBl3rv6TEskLgqLY4Fq3_-PvfwEQbAMj3M/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/20673651/BY_wop7V03q5_DI7QezLKWIwmwdKI_vPesklGLfCoTme3PcdSv_N1QEMYUhVsOx88rs0JTfi1jF1utKByL73-JWd0ueq0jMJ5OdeJDc37O0lTi6zCJx9UDkDae6D7DsdnodXjLwrYOE5gsBl3rv6TEskLgqLY4Fq3_-PvfwEQbAMj3M/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/2079122/2pFOPqeAt0hYMYkZmUbQ0R6yGfPEuN3UGtK1TfbheQiirP4mg4IUIHpORn_oiZI9HzTGfy55zzJqGi97TfiXGFFM17O60MlqPiFMH7ZKaRlqYuFHY7lK6HV5APCF2S_Ocnpnc_jApQZWuwMNwA8dCk6DfuA6wUTLb2GruTJnKL8HOVI/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/2079122/2pFOPqeAt0hYMYkZmUbQ0R6yGfPEuN3UGtK1TfbheQiirP4mg4IUIHpORn_oiZI9HzTGfy55zzJqGi97TfiXGFFM17O60MlqPiFMH7ZKaRlqYuFHY7lK6HV5APCF2S_Ocnpnc_jApQZWuwMNwA8dCk6DfuA6wUTLb2GruTJnKL8HOVI/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/18141476/4Gnp7sO-H1Ik3JT1klzP6GgNEeOqhIrwtzKT3NtXv4VsKQDxgbw_Tsypp6abwJqN8e9bfDE12e8lo7RGnBeiS-fpuB678EiQnzI4juvr7rV9zdTZxaX_szKkewzuq6_tLsYAgR9shvQmLZytCwm2xfuqea3TTh-j_jJSG4A0grv6H38/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/18141476/4Gnp7sO-H1Ik3JT1klzP6GgNEeOqhIrwtzKT3NtXv4VsKQDxgbw_Tsypp6abwJqN8e9bfDE12e8lo7RGnBeiS-fpuB678EiQnzI4juvr7rV9zdTZxaX_szKkewzuq6_tLsYAgR9shvQmLZytCwm2xfuqea3TTh-j_jJSG4A0grv6H38/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/18536126/MalqQN_cRjZs_k6d0teklzkxkq9GZNvdllPJesD7cspfwLi2TPBCcXauFOexMGA2x2PvT9wdAE-0tUuxaT2dunTetqEPcOfu4z_VAYSML61nUSThu-va1koRDXRgNuhlk6eTQQBozmnvyw8cICVbkRZ8zBgIlJnb7r9jv1zrQgopPrs/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/18536126/MalqQN_cRjZs_k6d0teklzkxkq9GZNvdllPJesD7cspfwLi2TPBCcXauFOexMGA2x2PvT9wdAE-0tUuxaT2dunTetqEPcOfu4z_VAYSML61nUSThu-va1koRDXRgNuhlk6eTQQBozmnvyw8cICVbkRZ8zBgIlJnb7r9jv1zrQgopPrs/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/20033387/z9Iak_V7e9SMEHkqXXhzYpYOvYCqNKfwn5ZRnmGpjkr2-C46BxzMonb39YMNA47RciCT-EpaUViCbEuPpe15jwzB9E_YNPRJ9SeLShyFZIJdC89jb4yBqPD5OAOPm_YWyZuKDxQRccJk7yxyLGfBdb8-SpVMQRBcX4dmR_suYWkOEBA/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/20033387/z9Iak_V7e9SMEHkqXXhzYpYOvYCqNKfwn5ZRnmGpjkr2-C46BxzMonb39YMNA47RciCT-EpaUViCbEuPpe15jwzB9E_YNPRJ9SeLShyFZIJdC89jb4yBqPD5OAOPm_YWyZuKDxQRccJk7yxyLGfBdb8-SpVMQRBcX4dmR_suYWkOEBA/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/4332304/CVNSoilVo5kheWdfBKiIvywWbG1Gl1FJvitRFgVjwq38hbqiE5C6zuoB4tLIlx-I66bUU4FOs7CwcR_T8Il27TgiWvsONzoG9m9Wl0fgRWF2mmm1-SwMZeS3BJa9AWDgIPvFOvkJ5XjNgXtjZ2MpJbXoWt7pTfd8DvFX8Jcc4lU5-P8/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/4332304/CVNSoilVo5kheWdfBKiIvywWbG1Gl1FJvitRFgVjwq38hbqiE5C6zuoB4tLIlx-I66bUU4FOs7CwcR_T8Il27TgiWvsONzoG9m9Wl0fgRWF2mmm1-SwMZeS3BJa9AWDgIPvFOvkJ5XjNgXtjZ2MpJbXoWt7pTfd8DvFX8Jcc4lU5-P8/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/2155411/NUVMzGeDH7llEuHP4P8IZEe3K9zgtA0NzIqqVYxYqA7eCnroR5blImb58cOrsCajvqyr8UzGF_4GyfYt5dF9NUzUwGwzZpMZ476PsR1FIxIDIavHqfHYsqHdZfASWWr21R6AxvXf4-SYPL0dJ6dc6CVXRaDREOH2eLqRWPZXDCPIJT8/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/2155411/NUVMzGeDH7llEuHP4P8IZEe3K9zgtA0NzIqqVYxYqA7eCnroR5blImb58cOrsCajvqyr8UzGF_4GyfYt5dF9NUzUwGwzZpMZ476PsR1FIxIDIavHqfHYsqHdZfASWWr21R6AxvXf4-SYPL0dJ6dc6CVXRaDREOH2eLqRWPZXDCPIJT8/1200x900"
        }
      ]
    },
    {
      "inner_id": "1133979956-cd53e842",
      "title": "Lada (ВАЗ) Granta I",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1133979956-cd53e842/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I",
      "complectation": "Standard 21911-50-710",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "лифтбек",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "87",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "компания",
      "year": 2015,
      "price_rub": 250000,
      "km_age": 134000,
      "vin": "XTA**************",
      "owners_count": "1",
      "description": "Преимущества автомобиля:\n- 1 владелец по ПТС\n- Базовая комплектация\n- Аудиосистема\n\nПриглашаем Вас к нам на тест-драйв. Будем рады ответить на Ваши вопросы! Ждём Вас по адресу: г. Киров, ул. Московская, 172 (напротив «МЕТРО»).\n\nПо кузову автомобиля присутствуют следы коррозии, притертости, царапины, соответствующие возрасту автомобиля.\nПо истории авто числится небольшое ДТП, расчет стоимости ремонта.",
      "offer_created": "2026-09-01T16:01:54.239+03:00",
      "added_at": "2026-09-02",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19793268/202bf598f477e6fe7d1ac08592ae0217/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19793268/202bf598f477e6fe7d1ac08592ae0217/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/11397870/f0c9f922e0f2bdb61e26d6dd91aa6ac4/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/11397870/f0c9f922e0f2bdb61e26d6dd91aa6ac4/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/4266363/bd695d896f3832db679eb075ac68c67c/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/4266363/bd695d896f3832db679eb075ac68c67c/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18105905/9849f3e084bea39e06e7743721f7e591/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18105905/9849f3e084bea39e06e7743721f7e591/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2079122/356e9af68c3e23f04e742df4fa82a0d3/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2079122/356e9af68c3e23f04e742df4fa82a0d3/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2131711/8e5e85a646b5a75fa192fbae76b38555/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2131711/8e5e85a646b5a75fa192fbae76b38555/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16135405/484fb7d5560340dff9c6510bbd100b96/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16135405/484fb7d5560340dff9c6510bbd100b96/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15584432/86f1433c44f9733f20acd8edd8cac205/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15584432/86f1433c44f9733f20acd8edd8cac205/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/1920751/5fee3f1aa7cbe4dd59efcba8f17d553f/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/1920751/5fee3f1aa7cbe4dd59efcba8f17d553f/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20770109/dddef269eb4d7ba4597595938ea664b4/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20770109/dddef269eb4d7ba4597595938ea664b4/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16976316/870f8af057cb5d3ff125eae065a32e98/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16976316/870f8af057cb5d3ff125eae065a32e98/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17698338/7ce05a099d21639c615d9f82c9965223/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17698338/7ce05a099d21639c615d9f82c9965223/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/13922065/048c9a089fbc5425a7b9e687f5bc91ba/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/13922065/048c9a089fbc5425a7b9e687f5bc91ba/1200x900"
        }
      ]
    },
    {
      "inner_id": "1133950906-eccbaebd",
      "title": "Lada (ВАЗ) Granta I Рестайлинг",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1133950906-eccbaebd/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I Рестайлинг",
      "complectation": "Comfort (2021-2022)",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "универсал",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "90",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "компания",
      "year": 2023,
      "price_rub": 850000,
      "km_age": 48496,
      "vin": "XTA**************",
      "owners_count": "1",
      "description": "Автосалон “Мотор Эксперт” на Московской, 172 (напротив “МЕТРО”). Более 300 автомобилей в наличии | TRADE IN | Автокредит.\nРаботаем с 8:00 до 20:00.\nСАМЫЙ БОЛЬШОЙ ВЫБОР АВТОМОБИЛЕЙ С ПРОБЕГОМ В КИРОВЕ.\n\nСтоимость в объявлении реальная, без скрытых платежей. Мы готовы обсудить индивидуальные скидки при встрече. Звоните или пишите нам в чат.\n\nПреимущества для вас:\n- Бесплатно предоставим отчёт Автотеки\n- Автомобиль полностью юридически чист и проверен\n- 10 банков-партнёров для быстрого и выгодного кредитования\n- Выгодный обмен (трейд ин), автострахование\n\nПреимущества автомобиля:\n- 1 владелец по ПТС\n- Зеленая автотека\n- Автомобиль в заводском окрасе\n- Кондиционер\n- Подогрев передних сидений\n- Штатная аудиосистема\n\nПриглашаем Вас к нам на тест-драйв. Будем рады ответить на Ваши вопросы! Ждём Вас по адресу: г. Киров, ул. Московская, 172 (напротив «МЕТРО»).\n\n#cmecOTY3LdCc0JBf0KbQkTAwMDYyMTE2X1NCRDAwMDE1MzY=",
      "offer_created": "2026-08-30T11:57:42.255+03:00",
      "added_at": "2026-08-31",
      "images_list": [
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/20784906/ihXxyRVHAk247wfRGgfRs4AjYhQFq5i_M8s0EJ62GdrquuLtflvWsbmPUh6-4D7o4xOnBFPqpyP0hSxKHuOfSUhviVfsMOERMJG3F3Zype49I83OOF62TTVfSHu6FRT_NHAg6YcuKDVEIjaIQFcxniwQ-14vCSM0n5VICp1u9s2pE4c/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/20784906/ihXxyRVHAk247wfRGgfRs4AjYhQFq5i_M8s0EJ62GdrquuLtflvWsbmPUh6-4D7o4xOnBFPqpyP0hSxKHuOfSUhviVfsMOERMJG3F3Zype49I83OOF62TTVfSHu6FRT_NHAg6YcuKDVEIjaIQFcxniwQ-14vCSM0n5VICp1u9s2pE4c/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/19816781/GmXGVlbBC3Qj7uN4I1vl1tuWRNwkzfwX8oA42XKwsy0w7myaYKy8Oy_hFR56no9A8ToHLyfIugkBPfS-qIMEJc-QI2qQ3uTPxv_Mm_OKcoYWlMH4W7E6xqFXoe2X1krYNWVmdTuaY0kQKPxg9rMN-UO7E_fGGNNntQmr8TD-LcQpQd4/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/19816781/GmXGVlbBC3Qj7uN4I1vl1tuWRNwkzfwX8oA42XKwsy0w7myaYKy8Oy_hFR56no9A8ToHLyfIugkBPfS-qIMEJc-QI2qQ3uTPxv_Mm_OKcoYWlMH4W7E6xqFXoe2X1krYNWVmdTuaY0kQKPxg9rMN-UO7E_fGGNNntQmr8TD-LcQpQd4/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/18008089/VCQAVhRqXY9CaSUHQTlZIldsMZ5_zUZNbNFflmaTw-aaXkGSjPDD9kUqNtJcs_qplaFaHSMy-WRrvDMrtNwCGpFt1FgTjuady_YIYE1xelXJZtaUCqwuGOwzPrZve2J2IvtN-sV8sxtxlEoTnv5FwB0TColAtPrZyVfGibp4mApRk9A/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/18008089/VCQAVhRqXY9CaSUHQTlZIldsMZ5_zUZNbNFflmaTw-aaXkGSjPDD9kUqNtJcs_qplaFaHSMy-WRrvDMrtNwCGpFt1FgTjuady_YIYE1xelXJZtaUCqwuGOwzPrZve2J2IvtN-sV8sxtxlEoTnv5FwB0TColAtPrZyVfGibp4mApRk9A/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/2159583/dEiKFd7iICwfBYT83Tqy3zBZzqZ-Q67ezhhEFGNKHGvB4HwcjVQHmE7donorvJsuM7wMwYUO1rbqT5KW42R22EEc1wfr-Sbh3iADjwzg-UhNP3BneW3F8z_IIo8jHOOHR_GmZgRPfvCMAEDYFSU6DVPY38t6saqCh-mSXrXnqVH9X64/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/2159583/dEiKFd7iICwfBYT83Tqy3zBZzqZ-Q67ezhhEFGNKHGvB4HwcjVQHmE7donorvJsuM7wMwYUO1rbqT5KW42R22EEc1wfr-Sbh3iADjwzg-UhNP3BneW3F8z_IIo8jHOOHR_GmZgRPfvCMAEDYFSU6DVPY38t6saqCh-mSXrXnqVH9X64/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/2168853/v65FlyZqflEcvRbOCg5fImANwb1ny3PpuCvXDGmHDnf_yET06IiU-VjiRHo_f886vjp-W8S51XtL86wpSEn7dLsVvdaGp5sg_nRWeRU8kiZdAUmxoLWEd9VBc6iK3sNPl2PhEIoDpxQ72-mf0ahKOqd9duUZB5BLooV2NEAqr8TqbbY/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/2168853/v65FlyZqflEcvRbOCg5fImANwb1ny3PpuCvXDGmHDnf_yET06IiU-VjiRHo_f886vjp-W8S51XtL86wpSEn7dLsVvdaGp5sg_nRWeRU8kiZdAUmxoLWEd9VBc6iK3sNPl2PhEIoDpxQ72-mf0ahKOqd9duUZB5BLooV2NEAqr8TqbbY/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/20331633/fRdUwumhrJ_bvmqlae5InlSZwe-Wj2pOI6siLcmumN2td8IZ0nQ2rcJcEgCR052WaqSlHPPD8NpoMgwNAUX_4Q-kT7pSB7HG7qRomwi4oDZECMQX2EU3mo_nRcTUnGrY5SMoSPmRpWxyjzh1zgU6wOKz27QQtNBmzigWCHeqzex08C8/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/20331633/fRdUwumhrJ_bvmqlae5InlSZwe-Wj2pOI6siLcmumN2td8IZ0nQ2rcJcEgCR052WaqSlHPPD8NpoMgwNAUX_4Q-kT7pSB7HG7qRomwi4oDZECMQX2EU3mo_nRcTUnGrY5SMoSPmRpWxyjzh1zgU6wOKz27QQtNBmzigWCHeqzex08C8/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/2164533/9-NwW6Zp3UlA5iWZSxrkohbET2G1RxnIy655yEqN-xTWcLyNJ8FFmhJ3VNp9RKLlb2v6wplXCnchVZi4Jisfmirg-mICf8Bbv7J97Kzw5wgNbvDIn7Lh9ud1wxq0GaJsKZNlajx-mA1cLRtM3UqSibUckFnMEoZT4d_kc_mKCJXU5AQ/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/2164533/9-NwW6Zp3UlA5iWZSxrkohbET2G1RxnIy655yEqN-xTWcLyNJ8FFmhJ3VNp9RKLlb2v6wplXCnchVZi4Jisfmirg-mICf8Bbv7J97Kzw5wgNbvDIn7Lh9ud1wxq0GaJsKZNlajx-mA1cLRtM3UqSibUckFnMEoZT4d_kc_mKCJXU5AQ/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/18143138/jbyn-KNLpyvdI5ox8NdscdulU2x5N_bPau31AjIr3ArhGnoKAkSN7cLvfDFLoF2KcKhvMK2W-GldHwe24jodHaZP6187C8Xn-EXIbEU5DJiCqmqnZRN7UfoHFy4XEZUCWrSsgil8jRqeTWDkzrWjbJmfssi1oIQotZkWeV7yg3BYzqo/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/18143138/jbyn-KNLpyvdI5ox8NdscdulU2x5N_bPau31AjIr3ArhGnoKAkSN7cLvfDFLoF2KcKhvMK2W-GldHwe24jodHaZP6187C8Xn-EXIbEU5DJiCqmqnZRN7UfoHFy4XEZUCWrSsgil8jRqeTWDkzrWjbJmfssi1oIQotZkWeV7yg3BYzqo/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/5231043/xUSnqtwkoYMlmx2H-qod6s1HAJWtGNTdG2kUm50zjx_-97BHfGudM0OdKIx59b2bpJ21sIRwPT6E76BSAcIzgnV5XqhlrXbLr0i5ZhI_0b7nbFa7higKjYNhBJ8_Ga8Abqq8LLgaqQsUtuAkyNlwLC4nqmZpAjPgEwL_vFVlTS_SXrI/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/5231043/xUSnqtwkoYMlmx2H-qod6s1HAJWtGNTdG2kUm50zjx_-97BHfGudM0OdKIx59b2bpJ21sIRwPT6E76BSAcIzgnV5XqhlrXbLr0i5ZhI_0b7nbFa7higKjYNhBJ8_Ga8Abqq8LLgaqQsUtuAkyNlwLC4nqmZpAjPgEwL_vFVlTS_SXrI/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/1654109/yVxAYMLTm_TN-piQJWS_dg_nppkf1rymj1ozRd7i-3gX1U3QFfeMK0XKh3dq9z7H0ISGovPted3rOWhRkPBjU3QLIJNWjJ6LX-YZ73jSwTMfsJUYei-q056QBkg7_wROG2extzQ4INnoZJGuScZH0FroQbgXucrhDmUSABxQqp6Pmfg/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/1654109/yVxAYMLTm_TN-piQJWS_dg_nppkf1rymj1ozRd7i-3gX1U3QFfeMK0XKh3dq9z7H0ISGovPted3rOWhRkPBjU3QLIJNWjJ6LX-YZ73jSwTMfsJUYei-q056QBkg7_wROG2extzQ4INnoZJGuScZH0FroQbgXucrhDmUSABxQqp6Pmfg/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/4035980/Tm2UZinqqJCv1-HOOhePvnesRLjT-mItk4Vzb8B0Zy9JXqCMovoekWYcOMDTeJFJm95ZljM6tNUfrUVdDFHmHx5f5pNoHh90v066svV-yP1epLuUUXvO6jz8KyO7PMKHiqNznFDcAjsakkx4SstwOjMV3L4CTvL12E1msGiml09vH8I/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/4035980/Tm2UZinqqJCv1-HOOhePvnesRLjT-mItk4Vzb8B0Zy9JXqCMovoekWYcOMDTeJFJm95ZljM6tNUfrUVdDFHmHx5f5pNoHh90v066svV-yP1epLuUUXvO6jz8KyO7PMKHiqNznFDcAjsakkx4SstwOjMV3L4CTvL12E1msGiml09vH8I/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/10868311/Ip2jp_R4mlIMYONRyY6mQHaM74GGenqmAiyrjalTmllhn4sfOWPCJNWegkuu1siQ2DCOa0X4964n8l9LsYxrh3AZmGWy0H77fSHFkTXyod8tFiUwh5TSS3WXKqnE4oj1f9wQB4WGic2yhD5u1b7B8T3brYmD4TlZ9CKUtYEOeFIWkOU/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/10868311/Ip2jp_R4mlIMYONRyY6mQHaM74GGenqmAiyrjalTmllhn4sfOWPCJNWegkuu1siQ2DCOa0X4964n8l9LsYxrh3AZmGWy0H77fSHFkTXyod8tFiUwh5TSS3WXKqnE4oj1f9wQB4WGic2yhD5u1b7B8T3brYmD4TlZ9CKUtYEOeFIWkOU/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/2049300/Fyimca9DKYv5JSEx14kbtAhLtkjLIy90AiYkoY9Mviwg41HTqxt_kPNzzWPwNfSduC4zUejCzoR3BrCJDhb7nMzhnt4o5FIy-TXjuMDKhqDmX-bsR8UCf2bwKNxx_OI9wtpzetN4KlWNZ_Bet-BkRecAF1D664G24LO35i7MLJQm6aE/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/2049300/Fyimca9DKYv5JSEx14kbtAhLtkjLIy90AiYkoY9Mviwg41HTqxt_kPNzzWPwNfSduC4zUejCzoR3BrCJDhb7nMzhnt4o5FIy-TXjuMDKhqDmX-bsR8UCf2bwKNxx_OI9wtpzetN4KlWNZ_Bet-BkRecAF1D664G24LO35i7MLJQm6aE/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/20507677/0D2-pYjuFTtrKm_h0vP-lLu6AGHd9B9bxdjDBuy4rpgy6QpvV5nhFjKYBW9unaMnCLYxCkSykOVHWMlKhK9YCXWE9ckZrL0RuS0ttADHFMkIHpKUnKWYISqSj4HBAyzgeSAXkwXYBkAYvPBBZrsSPnTHElVkR6cWp1VWnCPlPTRmhHk/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/20507677/0D2-pYjuFTtrKm_h0vP-lLu6AGHd9B9bxdjDBuy4rpgy6QpvV5nhFjKYBW9unaMnCLYxCkSykOVHWMlKhK9YCXWE9ckZrL0RuS0ttADHFMkIHpKUnKWYISqSj4HBAyzgeSAXkwXYBkAYvPBBZrsSPnTHElVkR6cWp1VWnCPlPTRmhHk/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/2155199/vOfpVkoZpTthWJAFuFC7t6URhLLFpiTvkU_dEq6TON-8qfkZ7WX0oYyPpWqRkTAr-s3xCsj4yDz2BloY1Z2MU9q_unekes84K_cT39C2ZpHY17n_rEI-tvgtr72BXCoHXZNYaYhAr-m8WX3IbjE1XqabRQXagko-eZFpQ2y6FXFdpzw/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/2155199/vOfpVkoZpTthWJAFuFC7t6URhLLFpiTvkU_dEq6TON-8qfkZ7WX0oYyPpWqRkTAr-s3xCsj4yDz2BloY1Z2MU9q_unekes84K_cT39C2ZpHY17n_rEI-tvgtr72BXCoHXZNYaYhAr-m8WX3IbjE1XqabRQXagko-eZFpQ2y6FXFdpzw/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/20394542/ck7l2LxsOgkIuWMt2B_wYDtUGTSLbKsqwWhtTDK2I6y0kRlvEwLc4LY7RCumSZxXMvA0GSfUh31G7SFP-IECbXxyrJQuiBhVTETxnXLH0S20AmJS5nZ3zAaBBWk4jyJRnjd2e7TxjIaFQnSDaWUYKHwogozLwcNqHPd0KKf1B-CMY44/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/20394542/ck7l2LxsOgkIuWMt2B_wYDtUGTSLbKsqwWhtTDK2I6y0kRlvEwLc4LY7RCumSZxXMvA0GSfUh31G7SFP-IECbXxyrJQuiBhVTETxnXLH0S20AmJS5nZ3zAaBBWk4jyJRnjd2e7TxjIaFQnSDaWUYKHwogozLwcNqHPd0KKf1B-CMY44/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/2163091/5wcMB4OSgV_vceyXz7iSg0aCN96coX5dx7x4dpXgvfWyKa-UqzboQg5u-dI-iWMSf0mrg_-VUrY3ICWk6kiXCpPE7QV6BWAj2gCDj2s3lon9OTmK137q-rES0wAkjKTZSNZKcRWKKKSFOBBDBnhmFo-hGw1unWorCEfDT6J0F7tCLRQ/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/2163091/5wcMB4OSgV_vceyXz7iSg0aCN96coX5dx7x4dpXgvfWyKa-UqzboQg5u-dI-iWMSf0mrg_-VUrY3ICWk6kiXCpPE7QV6BWAj2gCDj2s3lon9OTmK137q-rES0wAkjKTZSNZKcRWKKKSFOBBDBnhmFo-hGw1unWorCEfDT6J0F7tCLRQ/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/20140694/t0stE3agozOXcc6dLRN6RuBLpkDl_YOU_4vz9sg_odW7JVQE9w8Oq-8IqZr4qbKP2RYxaNPbqn30tbZv-fgbQ3TQ_lVOCDUd7GLRf1M5vqMdwlDhd3hCyHbhq0cAwID12vo2ehY135BwL0_Xptg_nRN5fsQyR0YsYZ0sl67GwV9YciQ/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/20140694/t0stE3agozOXcc6dLRN6RuBLpkDl_YOU_4vz9sg_odW7JVQE9w8Oq-8IqZr4qbKP2RYxaNPbqn30tbZv-fgbQ3TQ_lVOCDUd7GLRf1M5vqMdwlDhd3hCyHbhq0cAwID12vo2ehY135BwL0_Xptg_nRN5fsQyR0YsYZ0sl67GwV9YciQ/1200x900"
        },
        {
          "small": "//photo.auto.ru/photo/get-autoru-vos/11153101/_OUIBqwuSJ1TMiEJdS1qyudfkpqr5V7IpAxpbkJvq0K2jSfbiWPt1h-Zy0e0lm0cs77WNAweAvtDuIYcYuRIL9N6H7ijrlvm7rvR1tu6aw2-to5pmY-krL1_t7KgtWVj92kpqwZG1FR0dYUZEfMQJhNYunkpqOKTW1qQks3dNQ-7Yto/456x342",
          "big": "//photo.auto.ru/photo/get-autoru-vos/11153101/_OUIBqwuSJ1TMiEJdS1qyudfkpqr5V7IpAxpbkJvq0K2jSfbiWPt1h-Zy0e0lm0cs77WNAweAvtDuIYcYuRIL9N6H7ijrlvm7rvR1tu6aw2-to5pmY-krL1_t7KgtWVj92kpqwZG1FR0dYUZEfMQJhNYunkpqOKTW1qQks3dNQ-7Yto/1200x900"
        }
      ]
    },
    {
      "inner_id": "1133922756-02f152cb",
      "title": "Lada (ВАЗ) Granta I",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1133922756-02f152cb/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I",
      "complectation": null,
      "section": "USED",
      "region": "Кировская область",
      "city": "Кирово-Чепецк",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "87",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "частник",
      "year": 2012,
      "price_rub": 290000,
      "km_age": 99000,
      "vin": "XTA**************",
      "owners_count": "1",
      "description": "Один хозяин, пробег реальный без ДТП, в 2026 проведён капремонт ДВС, передние электростеклоподъёмники, кузов в заводском окрасе, в 2025 проведена ревизия ходовой с заменой расходников.\nИз минусов возрастные рыжики, сквозняков нет.\nСрочных вложений не требует.\nСостояние отличное: сел-поехал.\nТорг у капота, реальному покупателю подарок. ",
      "offer_created": "2026-08-27T18:37:23.089+03:00",
      "added_at": "2026-08-27",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20951031/fcc9bb4d4a75831b244b740e2fc39acc/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20951031/fcc9bb4d4a75831b244b740e2fc39acc/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2051006/ede88c736a39ee774062bc58300885c4/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2051006/ede88c736a39ee774062bc58300885c4/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16135405/20cacf3ef00bcf3c846b4de154bdde87/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16135405/20cacf3ef00bcf3c846b4de154bdde87/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2162758/8b7bbfa581c29d9e61a6e2c1416c8a77/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2162758/8b7bbfa581c29d9e61a6e2c1416c8a77/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/1687199/3eb8e754185ec411432f146c798abd0a/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/1687199/3eb8e754185ec411432f146c798abd0a/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16308788/5e45e4a4628f87a3c2b1b586feea6844/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16308788/5e45e4a4628f87a3c2b1b586feea6844/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/4432101/13e025be2bb26b7a7f517d6a231d6e42/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/4432101/13e025be2bb26b7a7f517d6a231d6e42/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/1977683/16787e3773908fae91cb234bec3424d1/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/1977683/16787e3773908fae91cb234bec3424d1/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/5378276/235c16095880af9a87441412d4686825/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/5378276/235c16095880af9a87441412d4686825/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20507677/5c3d41008f8286425dc3d2e6f2d86a06/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20507677/5c3d41008f8286425dc3d2e6f2d86a06/1200x900"
        }
      ]
    },
    {
      "inner_id": "1133891796-28007989",
      "title": "Lada (ВАЗ) Granta I",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1133891796-28007989/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I",
      "complectation": "Classic 21911-51-000",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "лифтбек",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "87",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "частник",
      "year": 2014,
      "price_rub": 330000,
      "km_age": 320000,
      "vin": "XTA**************",
      "owners_count": "4",
      "description": "Продам ладу гранту, Лифтбек. 2014 года в черном цвете. Литые диски, усилитель, Туманки и лампочки диодные! Торг присутствует, строго у капота! По телефону не торгуюсь. Действующий техосмотр до сентября!",
      "offer_created": "2026-08-25T14:14:40.585+03:00",
      "added_at": "2026-08-26",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20845579/8bae5911180152bfeec529f41469db77/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20845579/8bae5911180152bfeec529f41469db77/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17435526/900f7bb5e7bff72df7e29c39b0938d3d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17435526/900f7bb5e7bff72df7e29c39b0938d3d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20363536/eed7bcdc0c05921f30706ea6f4603a3a/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20363536/eed7bcdc0c05921f30706ea6f4603a3a/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18031053/7b5ca8c8716c857446cd7a606edb142e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18031053/7b5ca8c8716c857446cd7a606edb142e/1200x900"
        }
      ]
    },
    {
      "inner_id": "1133872718-d93420d5",
      "title": "Lada (ВАЗ) Granta I",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1133872718-d93420d5/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I",
      "complectation": null,
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "82",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "компания",
      "year": 2015,
      "price_rub": 244000,
      "km_age": 235672,
      "vin": "XTA**************",
      "owners_count": "2",
      "description": "В продаже народная любимица — Lada Granta 2015 года в редком и очень симпатичном золотисто-коричневом цвете! 🌟 Эта машина идеально подойдет для тех, кто ищет простой и понятный автомобиль для поездок по городу или на дачу 🏡\n\nГлавный плюс этого экземпляра — его кристально чистая история! ✅ У машины всего 2 владельца за всё время, «зелёная» Автотека и, что самое важное, полное отсутствие происшествий в базе. Никаких записей о столкновениях! 🙅‍♂️ В комплекте идет оригинальный ПТС.\nМашина честно прошла через годы эксплуатации, поэтому мы открыто рассказываем о её состоянии 🔍 По кузову есть возрастные нюансы.\n\nАвтомобиль прошел:\n⚡️ Комплексную проверку лакокрасочного покрытия кузова.\n⚡️ Техническую диагностику и предпродажную подготовку.\n⚡️ На все автомобили компания предоставляет гарантию юридической чистоты.\nАвтомобиль с пробегом может иметь определенный эксплуатационный износ в виде вторичного окраса одного или нескольких элементов, а также дорожно-транспортные происшествия в своей истории. Мы открыто делимся всей информацией по каждому техническому обслуживанию и инциденту.\n\n🚗 Автосалон ГУСАР — мы более 30 лет помогаем людям находить надежные автомобили. Для нас важно, чтобы каждый гость чувствовал себя уверенно при выборе машины с пробегом.\n\n🚗 Мы продаём проверенные автомобили, гарантируя их техническую исправность и чистоту перед законом. Персональный подход — это не просто слова, мы берем на себя всю бумажную волокиту и помогаем с кредитом или страховкой.\n\nДля вас:\n👉 более 100 проверенных вариантов в наличии,\n👉 честный обмен (Trade-in) Вашего авто на новый,\n👉 выгодные программы кредитования и страховки,\n👉 выкуп Вашего автомобиля по рыночной цене за 30 минут",
      "offer_created": "2026-08-24T04:32:59.745+03:00",
      "added_at": "2026-08-25",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20335149/30a9f442017428b888ebcafee03b2488/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20335149/30a9f442017428b888ebcafee03b2488/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15051405/a7cc3eaba2f257cf9b815f61087a1e65/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15051405/a7cc3eaba2f257cf9b815f61087a1e65/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18129838/e5d56240a4f924c229275e30ce92759e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18129838/e5d56240a4f924c229275e30ce92759e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2023766/a1affe6acb36f73474f85d3c8e77ab72/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2023766/a1affe6acb36f73474f85d3c8e77ab72/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17716217/996aefb27f2e5898a784d699113a4654/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17716217/996aefb27f2e5898a784d699113a4654/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17686578/0fff91545d1cff6647a3ab4947d6e85d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17686578/0fff91545d1cff6647a3ab4947d6e85d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15587596/844355e118e455b44f3a297419df63de/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15587596/844355e118e455b44f3a297419df63de/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20680164/89b4adf0c2c3e5bcf6c4f0776f524bb1/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20680164/89b4adf0c2c3e5bcf6c4f0776f524bb1/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/1904718/b1bf510c558f414832c5a1dbf098d0a5/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/1904718/b1bf510c558f414832c5a1dbf098d0a5/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/21068092/6f47c530ff890a31e037bd179bebeb91/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/21068092/6f47c530ff890a31e037bd179bebeb91/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17999629/c442fddfe7c9082c776798f81b0b64b3/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17999629/c442fddfe7c9082c776798f81b0b64b3/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/4763630/c0ae227e8751a6a5c98265cb6ff65631/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/4763630/c0ae227e8751a6a5c98265cb6ff65631/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/8381424/6e21f213ea0647eca77d0f26402db55e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/8381424/6e21f213ea0647eca77d0f26402db55e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/3906174/f35d04100ff4223559344b6dd728b06c/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/3906174/f35d04100ff4223559344b6dd728b06c/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18129838/385cfa77afddbf61a6692efe72b9c5c9/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18129838/385cfa77afddbf61a6692efe72b9c5c9/1200x900"
        }
      ]
    },
    {
      "inner_id": "1133865392-bb26c84d",
      "title": "Lada (ВАЗ) Granta I",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1133865392-bb26c84d/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I",
      "complectation": null,
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "87",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "компания",
      "year": 2018,
      "price_rub": 436000,
      "km_age": 66000,
      "vin": "XTA**************",
      "owners_count": "1",
      "description": "Lada Granta 2018, седан, 1.6 (87 л.с.), МКПП. 1 ВЛАДЕЛЕЦ! ОРИГИНАЛЬНЫЙ ПРОБЕГ 66 000 КМ. ПТС ОРИГИНАЛ. ПОНЯТНАЯ ИСТОРИЯ ОБСЛУЖИВАНИЯ!\n\nПродаётся Lada Granta 2018 года — практичный и неприхотливый седан в классическом белом цвете. Автомобиль от одного собственника, с честным небольшим пробегом и прозрачным прошлым. Это отличный вариант для тех, кто ищет простую в содержании машину с понятной биографией.\n\nПочему стоит купить именно этот автомобиль:\n\n✅ 1 владелец! ПТС оригинал. Автомобиль находился в одних руках с момента покупки, что всегда положительно сказывается на его техническом состоянии.\n✅ Оригинальный пробег 66 000 км! Для автомобиля 2018 года это всего 11 000 км в год — бережная эксплуатация. Пробег подтверждается записями в сервисной истории и данными технического осмотра.\n✅ Прозрачная история обслуживания! В базе зафиксированы визиты на сервис к официальному дилеру. На пробеге 65 000 км была произведена важная регламентная работа — замена ГРМ, что избавляет нового владельца от этой задачи в ближайшее время.\n✅ Юридическая чистота! Ограничений и залогов не обнаружено. Не найдено сведений об использовании в такси, каршеринге или лизинге — автомобиль использовался для личных целей.\n✅ Двигатель 1.6 (87 л.с.) — проверенный временем 8-клапанный мотор. Экономичный, тяговитый и максимально простой в обслуживании. Номер двигателя сверен и соответствует документам.\n✅ Механическая коробка передач — классическая трансмиссия, которая обеспечивает полный контроль над автомобилем и отличается высокой надежностью.\n\nКому подойдёт:\nТем, кто ищет доступный в обслуживании автомобиль с небольшим пробегом.\nДля ежедневных поездок по городу и работы, где важна экономичность.\nДля тех, кто ценит прозрачность истории владения и наличие одного хозяина в ПТС.\n\nАвтомобиль прошел:\n⚡️ Комплексную проверку лакокрасочного покрытия кузова.\n⚡️ Техническую диагностику и предпродажную подготовку.\n⚡️ На все автомобили компания предоставляет гарантию юридической чистоты.\nАвтомобиль с пробегом может иметь определенный эксплуатационный износ в виде вторичного окраса одного или нескольких элементов, а также дорожно-транспортные происшествия в своей истории. Мы открыто делимся всей информацией по каждому техническому обслуживанию и инциденту.\n\n🚗 Автосалон ГУСАР — мы более 30 лет помогаем людям находить надежные автомобили. Для нас важно, чтобы каждый гость чувствовал себя уверенно при выборе машины с пробегом.\n\n🚗 Мы продаём проверенные автомобили, гарантируя их техническую исправность и чистоту перед законом. Персональный подход — это не просто слова, мы берем на себя всю бумажную волокиту и помогаем с кредитом или страховкой.\n\nДля вас:\n👉 более 100 проверенных вариантов в наличии,\n👉 честный обмен (Trade-in) Вашего авто на новый,\n👉 выгодные программы кредитования и страховки,\n👉 выкуп Вашего автомобиля по рыночной цене за 30 минут,\n👉 гарантия на автомобили до 30 дней или 2000 км пробега.",
      "offer_created": "2026-08-23T06:31:33.473+03:00",
      "added_at": "2026-08-24",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19700919/5ec67db734502c3d7bd582c041d86d71/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19700919/5ec67db734502c3d7bd582c041d86d71/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/4358874/22e39af3cd46aacf773bae58e98a9c1e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/4358874/22e39af3cd46aacf773bae58e98a9c1e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17672043/5ee488a54e0ca7a7b88a85e5ca1ae7e6/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17672043/5ee488a54e0ca7a7b88a85e5ca1ae7e6/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18005086/1af23334de161bfe7c6f9e7213784dc4/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18005086/1af23334de161bfe7c6f9e7213784dc4/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15404718/69e094e0d36f2b06914fd119b3ec90bd/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15404718/69e094e0d36f2b06914fd119b3ec90bd/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20181844/3d01115db3f69a9ad8e64d7eb948f43f/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20181844/3d01115db3f69a9ad8e64d7eb948f43f/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18152297/4a4c3dc8cd8da6a348ff03333790341d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18152297/4a4c3dc8cd8da6a348ff03333790341d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18105905/52234919e490fe6e1b07b21836859426/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18105905/52234919e490fe6e1b07b21836859426/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/6366648/9d8f8cc4ee4d5ed086f6868356d329e8/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/6366648/9d8f8cc4ee4d5ed086f6868356d329e8/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19816781/c83a0986b2d17e627fa5b36f7c7fefc5/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19816781/c83a0986b2d17e627fa5b36f7c7fefc5/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/2181950/6b8cbf9675c858811ce7ad3935c816a1/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/2181950/6b8cbf9675c858811ce7ad3935c816a1/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20656151/678c2925b0cefbb249843b7a61ce7e7d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20656151/678c2925b0cefbb249843b7a61ce7e7d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20467659/517371d48cfedfe9c8115062cfd1577e/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20467659/517371d48cfedfe9c8115062cfd1577e/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16793145/38c2d37ef2e57831437f73aed297b161/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16793145/38c2d37ef2e57831437f73aed297b161/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17697421/dac4363db5c3bf68703a79966ca620e0/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17697421/dac4363db5c3bf68703a79966ca620e0/1200x900"
        }
      ]
    },
    {
      "inner_id": "1132477449-a00e2294",
      "title": "Lada (ВАЗ) Granta I Рестайлинг",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1132477449-a00e2294/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I Рестайлинг",
      "complectation": "Comfort\'24",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "90",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "компания",
      "year": 2024,
      "price_rub": 829000,
      "km_age": 27000,
      "vin": "XTA**************",
      "owners_count": "1",
      "description": "В продаже лада гранта седан 2024 г.в.\n✅ Комплектация с кондиционером , подогревами передних сидений , передними электростеклоподъемниками, электроусилителем руля .\n✅ Всего 1 владелец.\n✅ Оригинальное лобовое стекло без видимых повреждений.\n✅ Кузов в оригинальном окрасе , без ДТП .\n\nLada (ВАЗ) Granta, поколение I Рестайлинг, 2024 год — практичный седан, который подойдёт как для городских поездок, так и для путешествий за город. Чёрный кузов придаёт автомобилю сдержанный и универсальный вид, а проверенная временем конструкция гарантирует надёжность в эксплуатации.\n\nПод капотом — бензиновый двигатель объёмом 1,6 литра и мощностью 90 лошадиных сил. Механическая коробка передач обеспечивает лёгкость в обслуживании и позволяет лучше контролировать автомобиль в различных условиях. Экономичный двигатель делает эту модель выгодным выбором для ежедневных поездок.\n\nПробег автомобиля составляет 27 000 км — это свидетельствует о том, что машина уже прошла первичную обкатку, но при этом сохранила все свои технические характеристики.\n\nФункциональность Granta проявляется в наличии удобных опций для повседневного использования. В автомобиле есть бортовой компьютер, который поможет следить за основными параметрами. Аудиосистема с поддержкой Bluetooth и USB позволит наслаждаться музыкой в дороге, а розетка на 12V пригодится для зарядки мобильных устройств.\n\nКомфорт в салоне обеспечивают:\n* кондиционер, который создаст приятную атмосферу в жаркую погоду;\n* подогрев передних сидений, который будет актуален в холодное время года;\n* передние электростеклоподъёмники;\n* регулировка руля по высоте.\n\nБезопасность вождения обеспечивают:\n* антиблокировочная система тормозов (ABS);\n* система помощи при торможении;\n* система ЭРА-ГЛОНАСС.\n\nДля семей с детьми будет актуально наличие креплений для детского кресла на заднем ряду и блокировки замков задних дверей.\n\nСреди других полезных опций — обогрев и электрорегулировка зеркал, центральный замок и полноразмерное запасное колесо. Стальные диски размером 14 дюймов обеспечивают надёжность и простоту обслуживания.\n\nLada Granta — это автомобиль, который сочетает в себе практичность, функциональность и надёжность.\n\nДанный автомобиль можно приобрести в кредит или за наличную оплату.\nЦена указана при покупке как в кредит, так и за наличный расчет.\nЕсть условия обмена вашего автомобиля на любой другой, который понравится Вам, по программе трейд-ин.\n\nАвтосалон Гусар — это большой склад автомобилей с пробегом различных ценовых категорий, а также широкий спектр дополнительных услуг и гарантий!\n\nДля вас:\n📌 более 100 вариантов автомобилей с пробегом (проверенные технически и юридически),\n📌 выгодный обмен на ваш автомобиль,\n📌 услуги страхования и кредитования, продажа специализированных юридических продуктов.\n📌 оформление всех необходимых документов и гарантия чистоты сделки. Выкуп авто с пробегом.\n📌 выкуп автомобилей с пробегом ЛЮБОЙ марки, оценка по рыночной цене;\n📌 возможность обмена на новый Москвич или на автомобиль с пробегом из наличия;\n📌 деньги в день сделки, гарантированно.\n\nАвтосалон Гусар — выкуп, продажа, обмен автомобилей с пробегом в г.Вятские Поляны. Выгодные условия покупки в трейд-ин, кредит, скидки от производителя, ГОСПРОГРАММА на автомобили Лада.",
      "offer_created": "2026-05-07T10:25:51.734+03:00",
      "added_at": "2026-08-23",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16135405/15968ecf5511af81d353a7704a8034f1/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16135405/15968ecf5511af81d353a7704a8034f1/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19811519/fc82bdab525e4d7175968cdacd2902a4/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19811519/fc82bdab525e4d7175968cdacd2902a4/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18720861/bca8d3111ce58b1195a73dbb246fdcde/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18720861/bca8d3111ce58b1195a73dbb246fdcde/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17720009/50d0b132baa3a653c0b07dcc16576b05/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17720009/50d0b132baa3a653c0b07dcc16576b05/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17674678/26b11a8c549a535cf99025f32e475d89/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17674678/26b11a8c549a535cf99025f32e475d89/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20341802/e45b04583af79ed30e306a5ffb784cfd/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20341802/e45b04583af79ed30e306a5ffb784cfd/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17720009/9a449e4c5f77bc82cdd03493122ae2c9/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17720009/9a449e4c5f77bc82cdd03493122ae2c9/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20680164/43a0c477257db061efade21d4a675668/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20680164/43a0c477257db061efade21d4a675668/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19811519/453377bd6039ecd48759179309b7adde/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19811519/453377bd6039ecd48759179309b7adde/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17755365/32fa1987d1bfcc8a4d987521d6ec9acb/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17755365/32fa1987d1bfcc8a4d987521d6ec9acb/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/13922065/0abfd23e4837b528897ee8e0dac21fe0/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/13922065/0abfd23e4837b528897ee8e0dac21fe0/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17783177/f31c16f09081079ffac7b8252b3d4f5f/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17783177/f31c16f09081079ffac7b8252b3d4f5f/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15548469/d7b8d53f89d6cee4bf559c3357000c94/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15548469/d7b8d53f89d6cee4bf559c3357000c94/1200x900"
        }
      ]
    },
    {
      "inner_id": "1132998361-c998eca5",
      "title": "Lada (ВАЗ) Granta I Рестайлинг",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1132998361-c998eca5/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I Рестайлинг",
      "complectation": "Комфорт",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "90",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "компания",
      "year": 2023,
      "price_rub": 805000,
      "km_age": 51000,
      "vin": "XTA**************",
      "owners_count": "1",
      "description": "В продаже Гранта в комплектации Комфорт 1,6 90 л.с.\n\n- 1 владелец\n- Зеленая автотека\n- Кузов в родном окрасе\n- Родной пробег\n- Хорошая комплектация\n- Стоит сигнализация с автозапуском\n- Версия с 2мя подушками безопасности!\n\nДанный автомобиль можно приобрести в кредит или за наличную оплату.\nЦена указана при покупке как в кредит, так и за наличный расчет.\nЕсть условия обмена вашего автомобиля на любой другой, который понравится Вам, по программе трейд-ин.\n\nАвтосалон Гусар — это большой склад автомобилей с пробегом различных ценовых категорий, а также широкий спектр дополнительных услуг и гарантий!\n\nДля вас:\n📌 более 100 вариантов автомобилей с пробегом (проверенные технически и юридически),\n📌 выгодный обмен на ваш автомобиль,\n📌 услуги страхования и кредитования, продажа специализированных юридических продуктов.\n📌 оформление всех необходимых документов и гарантия чистоты сделки. Выкуп авто с пробегом.\n📌 выкуп автомобилей с пробегом ЛЮБОЙ марки, оценка по рыночной цене;\n📌 возможность обмена на новый Москвич или на автомобиль с пробегом из наличия;\n📌 деньги в день сделки, гарантированно.\n\nАвтосалон Гусар — выкуп, продажа, обмен автомобилей с пробегом в г.Вятские Поляны. Выгодные условия покупки в трейд-ин, кредит, скидки от производителя, ГОСПРОГРАММА на автомобили Лада.",
      "offer_created": "2026-06-14T10:08:35.780+03:00",
      "added_at": "2026-08-23",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17686578/1a03bd519dbdb824a73ad858820ea676/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17686578/1a03bd519dbdb824a73ad858820ea676/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15049662/adef2862c2d658828efca6769e206ec3/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15049662/adef2862c2d658828efca6769e206ec3/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17841911/ef8c3f162883c1f46db0b40ced141fc6/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17841911/ef8c3f162883c1f46db0b40ced141fc6/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20036242/3b5c6659e856bbd8cdaf7c8dd3588c7d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20036242/3b5c6659e856bbd8cdaf7c8dd3588c7d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18008089/417822c79b14d4058441ae9b96aec541/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18008089/417822c79b14d4058441ae9b96aec541/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17971448/2a04199a031e1e03075c7367ace8f525/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17971448/2a04199a031e1e03075c7367ace8f525/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20394542/cdeba0bae78a669ff25a4935aab83ea8/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20394542/cdeba0bae78a669ff25a4935aab83ea8/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20680164/9848a0304e0398a50e7159a193d255ea/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20680164/9848a0304e0398a50e7159a193d255ea/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17926310/60dcdcbf6380aa67e1bdb3bfb03004a4/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17926310/60dcdcbf6380aa67e1bdb3bfb03004a4/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20673651/f44ad87db46b13ada238ee432d49fabe/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20673651/f44ad87db46b13ada238ee432d49fabe/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/19297093/bb2b2918082bfd04c71e886cacbbc85a/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/19297093/bb2b2918082bfd04c71e886cacbbc85a/1200x900"
        }
      ]
    },
    {
      "inner_id": "1133365780-b8daa0da",
      "title": "Lada (ВАЗ) Granta I Рестайлинг",
      "url": "https://auto.ru/cars/used/sale/vaz/granta/1133365780-b8daa0da/",
      "mark": "Lada (ВАЗ)",
      "model": "Granta",
      "generation": "I Рестайлинг",
      "complectation": "Classic Optima",
      "section": "USED",
      "region": "Кировская область",
      "city": "Киров",
      "prep_body_type": "седан",
      "prep_engine_type": "бензин",
      "displacement": "1.6",
      "horse_power": "90",
      "prep_transmission": "механика",
      "prep_drive_type": "передний",
      "prep_seller_type": "компания",
      "year": 2025,
      "price_rub": 787000,
      "km_age": 39655,
      "vin": "XTA**************",
      "owners_count": "1",
      "description": "Lada Granta 2025, седан, 1.6 (90 л.с.), МКПП. Практически новая! 1 владелец, заводской окрас, без ДТП.\n\nПродаётся Lada Granta 2025 года выпуска в кузове седан. Автомобиль куплен новым у официального дилера, эксплуатировался аккуратно одним владельцем. Отличная возможность получить почти новый автомобиль без ожидания и без потери в цене при выезде из салона.\n\nПочему стоит купить именно этот экземпляр:\n✅ 2025 год — фактически новый авто. Пробег 39655 км — это всего около 3 500 км в месяц, режим смешанный (город/трасса). Машина в идеальном техническом состоянии.\n✅ 1 владелец, электронный ПТС — никаких переоформлений, история прозрачна.\n✅ Идеальное состояние кузова: по данным «Зелёной автотеки» — без ДТП, полностью заводской окрас. Ни одной перекрашенной детали! Толщина ЛКП родная, геометрия идеальна.\n✅ Двигатель 1.6 (90 л.с.) — современный 8-клапанник (индекс 11182), надёжный, экономичный. Расход топлива 6-7 л по трассе, 8-9 л по городу. Ест 92-й бензин, масло не расходует.\n✅ Техническое состояние: Механика переключается мягко, сцепление как новое. Подвеска не гремит, тормоза отличные. Автомобиль полностью на ходу, готов к ежедневной эксплуатации.\n✅ Салон: тканевый, чистый, без затяжек и запахов. Всё оборудование работает: печка, кондиционер, электростеклоподъёмники, аудиоподготовка.\n\nКому подойдёт:\nТем, кто хочет новую Гранту, но не хочет ждать или переплачивать за «первый выезд».\nПервый автомобиль для семьи или молодого водителя.\nНадёжный рабочий вариант с остаточной заводской гарантией.\n\n🚗 Автосалон ГУСАР — автосалон по продаже автомобилей с пробегом, входит в группу компаний «Автосалон Гусар», которая уже более 30 лет осуществляет продажу автомобилей различных брендов в регионе и за его пределами.\n\n🚗 Мы продаём проверенные автомобили с пробегом. Гарантируем их качественное техническое состояние и юридическую чистоту.\n\n🚗 Основной принцип нашей работы — это персональный подход к каждому покупателю. Мы берем на себя все вопросы по оформлению сделки, оказываем услуги по кредитованию и страхованию.\n\nАвтосалон ГУСАР — это большой склад автомобилей с пробегом различных ценовых категорий, а также широкий спектр дополнительных услуг и гарантий!\n\nДля вас:\n👉 более 100 вариантов автомобилей с пробегом (проверенные технически и юридически),\n👉 выгодный обмен на ваш автомобиль,\n👉 услуги страхования и кредитования, продажа специализированных юридических продуктов.\n👉 оформление всех необходимых документов и гарантия чистоты сделки.\n👉 выкуп автомобилей с пробегом ЛЮБОЙ марки, оценка по рыночной цене;\n👉 гарантия на автомобили до 30 дней или 2000 км пробега.\n\nНаш сервисный центр осуществляет ремонт и техническое обслуживание автомобилей любых марок. Большой склад запасных частей, специализированное оборудование и сертифицированный персонал — всё для заботы о Вашем автомобиле.",
      "offer_created": "2026-07-13T08:11:15.549+03:00",
      "added_at": "2026-08-23",
      "images_list": [
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20036242/4c3ef04e6d14e496c2bf0880e180d3dc/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20036242/4c3ef04e6d14e496c2bf0880e180d3dc/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17712067/8bc4d6cdc52ef2cb761bbab435f134a9/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17712067/8bc4d6cdc52ef2cb761bbab435f134a9/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16569097/9a2125431788c1da7d12e200c0218a95/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16569097/9a2125431788c1da7d12e200c0218a95/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17973062/22925bf73e70c6237de2b431392025a7/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17973062/22925bf73e70c6237de2b431392025a7/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20657148/719e5fd31d2b96a89858f195c1bafaaf/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20657148/719e5fd31d2b96a89858f195c1bafaaf/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18154113/b8af0c24d9ac62ec674f48426135bcab/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18154113/b8af0c24d9ac62ec674f48426135bcab/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17913096/dd67ddeecc077c0e6db9f79f75030e92/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17913096/dd67ddeecc077c0e6db9f79f75030e92/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17786981/9a4df2fc58e5e43ceee743ab26a6b94d/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17786981/9a4df2fc58e5e43ceee743ab26a6b94d/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16383606/3bf7d8e9f19125f5371af573e1db240a/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16383606/3bf7d8e9f19125f5371af573e1db240a/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17755365/ccc27395e917c79f34a8ffe9ccffe965/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17755365/ccc27395e917c79f34a8ffe9ccffe965/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/18004164/6b4048f6c5a5125d9e891ff4334b622c/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/18004164/6b4048f6c5a5125d9e891ff4334b622c/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17707957/a0374e17266df9a3db3d28c72ed96f4a/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17707957/a0374e17266df9a3db3d28c72ed96f4a/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/17841911/b9b047ac5584e3a9a6a8202946a97eea/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/17841911/b9b047ac5584e3a9a6a8202946a97eea/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/14008031/b0c0c90ddec8faa06edee010d32abf73/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/14008031/b0c0c90ddec8faa06edee010d32abf73/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/20315033/c3398ceb90600599f095cb4bac4ec57b/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/20315033/c3398ceb90600599f095cb4bac4ec57b/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/16483913/68f47c8d9f0a331a92eba931e9d3d908/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/16483913/68f47c8d9f0a331a92eba931e9d3d908/1200x900"
        },
        {
          "small": "//avatars.avto.ru/get-autoru-vos/15584432/0cb6675c964da8961042765cee958fb9/456x342",
          "big": "//avatars.avto.ru/get-autoru-vos/15584432/0cb6675c964da8961042765cee958fb9/1200x900"
        }
      ]
    }
  ],
  "meta": {
    "page": 1,
    "next_page": 2,
    "limit": 20,
    "requests_made": 8,
    "requests_left": 142,
    "requests_limit": 150,
    "period_start": "2026-09"
  }
}
    ';


    private const PRICES = '
    {"start":"20.08.2026 16:20:37","end":"20.08.2026 16:20:37","marka":"VAZ","model":"GRANTA","year":2014,"gen":"I","volume":1.6,"horse":87,"kpp":null,"error":false,"error_msg":"","result":{"average":341213.50065703021,"minimalAverage":228828.72368421053,"offers_count":761,"offers":[{"price":430000,"distance":278000,"url":"https://tronk.pro/ads/detail/1303834648"},{"price":210000,"distance":150000,"url":"https://tronk.pro/ads/detail/1304242473"},{"price":317000,"distance":132400,"url":"https://tronk.pro/ads/detail/1304643328"},{"price":373000,"distance":170000,"url":"https://tronk.pro/ads/detail/1308179178"},{"price":320000,"distance":92000,"url":"https://tronk.pro/ads/detail/1305018358"},{"price":299000,"distance":140100,"url":"https://tronk.pro/ads/detail/1307198677"},{"price":350000,"distance":149500,"url":"https://tronk.pro/ads/detail/1307904173"},{"price":399000,"distance":14,"url":"https://tronk.pro/ads/detail/1304805063"},{"price":520000,"distance":95000,"url":"https://tronk.pro/ads/detail/1303682134"},{"price":367000,"distance":121769,"url":"https://tronk.pro/ads/detail/1307363376"},{"price":450000,"distance":106000,"url":"https://tronk.pro/ads/detail/1304227429"},{"price":290123,"distance":230000,"url":"https://tronk.pro/ads/detail/1309857355"},{"price":270000,"distance":255070,"url":"https://tronk.pro/ads/detail/1307209067"},{"price":415000,"distance":109947,"url":"https://tronk.pro/ads/detail/1310044344"},{"price":370000,"distance":214000,"url":"https://tronk.pro/ads/detail/1304683093"},{"price":215000,"distance":200900,"url":"https://tronk.pro/ads/detail/1307188287"},{"price":120000,"distance":223000,"url":"https://tronk.pro/ads/detail/1309025036"},{"price":50000,"distance":300000,"url":"https://tronk.pro/ads/detail/1309260729"},{"price":220000,"distance":199189,"url":"https://tronk.pro/ads/detail/1303154426"},{"price":395000,"distance":138000,"url":"https://tronk.pro/ads/detail/1306915619"},{"price":420000,"distance":0,"url":"https://tronk.pro/ads/detail/1304424719"},{"price":490000,"distance":318880,"url":"https://tronk.pro/ads/detail/1306508143"},{"price":325000,"distance":169000,"url":"https://tronk.pro/ads/detail/1304142978"},{"price":400000,"distance":161000,"url":"https://tronk.pro/ads/detail/1307479168"},{"price":435000,"distance":100000,"url":"https://tronk.pro/ads/detail/1303532004"},{"price":269000,"distance":197500,"url":"https://tronk.pro/ads/detail/1310732460"},{"price":390000,"distance":115000,"url":"https://tronk.pro/ads/detail/1307206577"},{"price":320000,"distance":205150,"url":"https://tronk.pro/ads/detail/1303689192"},{"price":258000,"distance":210000,"url":"https://tronk.pro/ads/detail/1305342986"},{"price":250000,"distance":231888,"url":"https://tronk.pro/ads/detail/1303801817"},{"price":549000,"distance":97291,"url":"https://tronk.pro/ads/detail/1305628372"},{"price":430000,"distance":150000,"url":"https://tronk.pro/ads/detail/1310070037"},{"price":350000,"distance":80000,"url":"https://tronk.pro/ads/detail/1305452715"},{"price":210000,"distance":190000,"url":"https://tronk.pro/ads/detail/1307774984"},{"price":335000,"distance":165456,"url":"https://tronk.pro/ads/detail/1307195724"},{"price":215000,"distance":0,"url":"https://tronk.pro/ads/detail/1305749689"},{"price":350000,"distance":263000,"url":"https://tronk.pro/ads/detail/1309127407"},{"price":200000,"distance":248877,"url":"https://tronk.pro/ads/detail/1303107956"},{"price":230000,"distance":194000,"url":"https://tronk.pro/ads/detail/1306330921"},{"price":152000,"distance":197224,"url":"https://tronk.pro/ads/detail/1309127411"},{"price":140000,"distance":163399,"url":"https://tronk.pro/ads/detail/1304758858"},{"price":415000,"distance":110653,"url":"https://tronk.pro/ads/detail/1304106714"},{"price":255000,"distance":203000,"url":"https://tronk.pro/ads/detail/1305824093"},{"price":150000,"distance":477000,"url":"https://tronk.pro/ads/detail/1305296282"},{"price":185000,"distance":137000,"url":"https://tronk.pro/ads/detail/1309199289"},{"price":374000,"distance":171000,"url":"https://tronk.pro/ads/detail/1302848277"},{"price":370000,"distance":187000,"url":"https://tronk.pro/ads/detail/1303197621"},{"price":445000,"distance":89874,"url":"https://tronk.pro/ads/detail/1303519848"},{"price":365000,"distance":137000,"url":"https://tronk.pro/ads/detail/1310721717"},{"price":300000,"distance":286827,"url":"https://tronk.pro/ads/detail/1311669520"},{"price":450000,"distance":21500,"url":"https://tronk.pro/ads/detail/1311166074"},{"price":299000,"distance":179900,"url":"https://tronk.pro/ads/detail/1302796741"},{"price":290000,"distance":215000,"url":"https://tronk.pro/ads/detail/1306719654"},{"price":330000,"distance":120650,"url":"https://tronk.pro/ads/detail/1308775612"},{"price":365000,"distance":130000,"url":"https://tronk.pro/ads/detail/1303149481"},{"price":360000,"distance":87000,"url":"https://tronk.pro/ads/detail/1305636792"},{"price":389000,"distance":169180,"url":"https://tronk.pro/ads/detail/1310248523"},{"price":385000,"distance":158000,"url":"https://tronk.pro/ads/detail/1303175942"},{"price":250000,"distance":200000,"url":"https://tronk.pro/ads/detail/1310313475"},{"price":270000,"distance":177833,"url":"https://tronk.pro/ads/detail/1305230182"},{"price":425000,"distance":100000,"url":"https://tronk.pro/ads/detail/1306579508"},{"price":495000,"distance":85300,"url":"https://tronk.pro/ads/detail/1307011444"},{"price":415000,"distance":123000,"url":"https://tronk.pro/ads/detail/1306159796"},{"price":165000,"distance":165000,"url":"https://tronk.pro/ads/detail/1303489013"},{"price":240000,"distance":177000,"url":"https://tronk.pro/ads/detail/1307994652"},{"price":470000,"distance":132000,"url":"https://tronk.pro/ads/detail/1303039499"},{"price":340000,"distance":200549,"url":"https://tronk.pro/ads/detail/1303028368"},{"price":339000,"distance":106293,"url":"https://tronk.pro/ads/detail/1306570809"},{"price":325000,"distance":92000,"url":"https://tronk.pro/ads/detail/1305019723"},{"price":250000,"distance":155000,"url":"https://tronk.pro/ads/detail/1305288280"},{"price":305000,"distance":133000,"url":"https://tronk.pro/ads/detail/1310479961"},{"price":310000,"distance":165000,"url":"https://tronk.pro/ads/detail/1306033095"},{"price":240000,"distance":290000,"url":"https://tronk.pro/ads/detail/1305695440"},{"price":397000,"distance":165000,"url":"https://tronk.pro/ads/detail/1307737378"},{"price":410000,"distance":152000,"url":"https://tronk.pro/ads/detail/1309105147"},{"price":335500,"distance":156800,"url":"https://tronk.pro/ads/detail/1303669813"},{"price":275000,"distance":159000,"url":"https://tronk.pro/ads/detail/1309131120"},{"price":200000,"distance":184000,"url":"https://tronk.pro/ads/detail/1303042250"},{"price":380000,"distance":100000,"url":"https://tronk.pro/ads/detail/1305254591"},{"price":430000,"distance":123767,"url":"https://tronk.pro/ads/detail/1307010489"},{"price":355000,"distance":230000,"url":"https://tronk.pro/ads/detail/1305755317"},{"price":375000,"distance":157600,"url":"https://tronk.pro/ads/detail/1311294399"},{"price":343000,"distance":158100,"url":"https://tronk.pro/ads/detail/1308278067"},{"price":395000,"distance":92534,"url":"https://tronk.pro/ads/detail/1311480043"},{"price":369000,"distance":134000,"url":"https://tronk.pro/ads/detail/1304154417"},{"price":315000,"distance":145333,"url":"https://tronk.pro/ads/detail/1302768994"},{"price":375000,"distance":138000,"url":"https://tronk.pro/ads/detail/1305519837"},{"price":347000,"distance":193150,"url":"https://tronk.pro/ads/detail/1311349817"},{"price":350000,"distance":200000,"url":"https://tronk.pro/ads/detail/1311072002"},{"price":340000,"distance":170320,"url":"https://tronk.pro/ads/detail/1303523536"},{"price":400000,"distance":125000,"url":"https://tronk.pro/ads/detail/1310617231"},{"price":400000,"distance":146000,"url":"https://tronk.pro/ads/detail/1309606524"},{"price":300000,"distance":160000,"url":"https://tronk.pro/ads/detail/1303055521"},{"price":350000,"distance":187000,"url":"https://tronk.pro/ads/detail/1303175273"},{"price":360000,"distance":135000,"url":"https://tronk.pro/ads/detail/1306023776"},{"price":220000,"distance":195200,"url":"https://tronk.pro/ads/detail/1303495106"},{"price":330000,"distance":209000,"url":"https://tronk.pro/ads/detail/1311109388"},{"price":330000,"distance":175998,"url":"https://tronk.pro/ads/detail/1305464879"},{"price":330000,"distance":203000,"url":"https://tronk.pro/ads/detail/1303149390"},{"price":315000,"distance":120054,"url":"https://tronk.pro/ads/detail/1310867109"},{"price":385000,"distance":162300,"url":"https://tronk.pro/ads/detail/1303809924"},{"price":270000,"distance":128000,"url":"https://tronk.pro/ads/detail/1310716089"},{"price":200000,"distance":181000,"url":"https://tronk.pro/ads/detail/1307816970"},{"price":385000,"distance":200000,"url":"https://tronk.pro/ads/detail/1306146621"},{"price":350000,"distance":240000,"url":"https://tronk.pro/ads/detail/1309075531"},{"price":330000,"distance":70070,"url":"https://tronk.pro/ads/detail/1307663556"},{"price":215000,"distance":150000,"url":"https://tronk.pro/ads/detail/1303704180"},{"price":265000,"distance":187477,"url":"https://tronk.pro/ads/detail/1302930680"},{"price":375000,"distance":101456,"url":"https://tronk.pro/ads/detail/1304089483"},{"price":310000,"distance":134389,"url":"https://tronk.pro/ads/detail/1308916851"},{"price":500000,"distance":30000,"url":"https://tronk.pro/ads/detail/1308884149"},{"price":235000,"distance":154865,"url":"https://tronk.pro/ads/detail/1309467247"},{"price":185000,"distance":168000,"url":"https://tronk.pro/ads/detail/1308155729"},{"price":300000,"distance":187422,"url":"https://tronk.pro/ads/detail/1306583219"},{"price":430000,"distance":114000,"url":"https://tronk.pro/ads/detail/1310978249"},{"price":270000,"distance":160000,"url":"https://tronk.pro/ads/detail/1304051372"},{"price":350000,"distance":157000,"url":"https://tronk.pro/ads/detail/1309935378"},{"price":285000,"distance":167500,"url":"https://tronk.pro/ads/detail/1307559040"},{"price":280000,"distance":217000,"url":"https://tronk.pro/ads/detail/1308501212"},{"price":300000,"distance":300000,"url":"https://tronk.pro/ads/detail/1306244741"},{"price":320000,"distance":165000,"url":"https://tronk.pro/ads/detail/1308160002"},{"price":350000,"distance":115000,"url":"https://tronk.pro/ads/detail/1305673591"},{"price":350000,"distance":100000,"url":"https://tronk.pro/ads/detail/1302822706"},{"price":396000,"distance":116000,"url":"https://tronk.pro/ads/detail/1309891674"},{"price":355000,"distance":185000,"url":"https://tronk.pro/ads/detail/1309503145"},{"price":350000,"distance":129657,"url":"https://tronk.pro/ads/detail/1303117415"},{"price":430000,"distance":128000,"url":"https://tronk.pro/ads/detail/1305267462"},{"price":298000,"distance":263000,"url":"https://tronk.pro/ads/detail/1306281900"},{"price":325000,"distance":180350,"url":"https://tronk.pro/ads/detail/1304208706"},{"price":370000,"distance":88250,"url":"https://tronk.pro/ads/detail/1307759258"},{"price":220000,"distance":234000,"url":"https://tronk.pro/ads/detail/1308468104"},{"price":320000,"distance":170000,"url":"https://tronk.pro/ads/detail/1305694066"},{"price":360000,"distance":103809,"url":"https://tronk.pro/ads/detail/1311721792"},{"price":290000,"distance":240948,"url":"https://tronk.pro/ads/detail/1308533187"},{"price":270000,"distance":151550,"url":"https://tronk.pro/ads/detail/1310603175"},{"price":385000,"distance":89000,"url":"https://tronk.pro/ads/detail/1309425698"},{"price":255000,"distance":217300,"url":"https://tronk.pro/ads/detail/1311713689"},{"price":370000,"distance":130000,"url":"https://tronk.pro/ads/detail/1308323939"},{"price":250000,"distance":371000,"url":"https://tronk.pro/ads/detail/1305802787"},{"price":365000,"distance":212000,"url":"https://tronk.pro/ads/detail/1303694518"},{"price":370000,"distance":151000,"url":"https://tronk.pro/ads/detail/1303185856"},{"price":325000,"distance":129544,"url":"https://tronk.pro/ads/detail/1307580059"},{"price":430000,"distance":150000,"url":"https://tronk.pro/ads/detail/1310063881"},{"price":400000,"distance":129319,"url":"https://tronk.pro/ads/detail/1302806340"},{"price":269000,"distance":165000,"url":"https://tronk.pro/ads/detail/1305193963"},{"price":220000,"distance":233000,"url":"https://tronk.pro/ads/detail/1306842975"},{"price":380000,"distance":70360,"url":"https://tronk.pro/ads/detail/1309165178"},{"price":350000,"distance":230000,"url":"https://tronk.pro/ads/detail/1309341802"},{"price":390000,"distance":204800,"url":"https://tronk.pro/ads/detail/1309248405"},{"price":320000,"distance":240000,"url":"https://tronk.pro/ads/detail/1309138999"},{"price":330000,"distance":147000,"url":"https://tronk.pro/ads/detail/1309414397"},{"price":285000,"distance":230000,"url":"https://tronk.pro/ads/detail/1302711824"},{"price":250000,"distance":164000,"url":"https://tronk.pro/ads/detail/1302910704"},{"price":360000,"distance":165000,"url":"https://tronk.pro/ads/detail/1308810440"},{"price":379000,"distance":115328,"url":"https://tronk.pro/ads/detail/1310529899"},{"price":350000,"distance":288443,"url":"https://tronk.pro/ads/detail/1309767523"},{"price":300000,"distance":155000,"url":"https://tronk.pro/ads/detail/1310637267"},{"price":315000,"distance":138000,"url":"https://tronk.pro/ads/detail/1304321067"},{"price":250000,"distance":191111,"url":"https://tronk.pro/ads/detail/1307441595"},{"price":250000,"distance":117000,"url":"https://tronk.pro/ads/detail/1308992229"},{"price":415000,"distance":85000,"url":"https://tronk.pro/ads/detail/1307909061"},{"price":240000,"distance":183000,"url":"https://tronk.pro/ads/detail/1308929942"},{"price":330000,"distance":72000,"url":"https://tronk.pro/ads/detail/1310747436"},{"price":370000,"distance":169000,"url":"https://tronk.pro/ads/detail/1311039684"},{"price":520000,"distance":85000,"url":"https://tronk.pro/ads/detail/1309192951"},{"price":380000,"distance":100000,"url":"https://tronk.pro/ads/detail/1305201645"},{"price":350000,"distance":144000,"url":"https://tronk.pro/ads/detail/1303578164"},{"price":425000,"distance":72000,"url":"https://tronk.pro/ads/detail/1309655740"},{"price":395000,"distance":110000,"url":"https://tronk.pro/ads/detail/1307318112"},{"price":247000,"distance":259000,"url":"https://tronk.pro/ads/detail/1306372277"},{"price":540000,"distance":151100,"url":"https://tronk.pro/ads/detail/1308324220"},{"price":390000,"distance":176100,"url":"https://tronk.pro/ads/detail/1308154380"},{"price":340000,"distance":130000,"url":"https://tronk.pro/ads/detail/1309329495"},{"price":345000,"distance":145000,"url":"https://tronk.pro/ads/detail/1302878413"},{"price":380000,"distance":60000,"url":"https://tronk.pro/ads/detail/1307574202"},{"price":290000,"distance":152000,"url":"https://tronk.pro/ads/detail/1307684233"},{"price":290000,"distance":265000,"url":"https://tronk.pro/ads/detail/1307535991"},{"price":290000,"distance":214000,"url":"https://tronk.pro/ads/detail/1306862660"},{"price":350000,"distance":128000,"url":"https://tronk.pro/ads/detail/1308097908"},{"price":350000,"distance":217200,"url":"https://tronk.pro/ads/detail/1309735929"},{"price":459000,"distance":0,"url":"https://tronk.pro/ads/detail/1306288463"},{"price":220000,"distance":257100,"url":"https://tronk.pro/ads/detail/1310782203"},{"price":340000,"distance":162000,"url":"https://tronk.pro/ads/detail/1303330929"},{"price":305000,"distance":113500,"url":"https://tronk.pro/ads/detail/1307841390"},{"price":359000,"distance":159000,"url":"https://tronk.pro/ads/detail/1309115487"},{"price":270000,"distance":170000,"url":"https://tronk.pro/ads/detail/1311329163"},{"price":370000,"distance":73591,"url":"https://tronk.pro/ads/detail/1306965579"},{"price":409999,"distance":172842,"url":"https://tronk.pro/ads/detail/1306733686"},{"price":365000,"distance":317000,"url":"https://tronk.pro/ads/detail/1310470655"},{"price":550000,"distance":101000,"url":"https://tronk.pro/ads/detail/1308743741"},{"price":420000,"distance":119260,"url":"https://tronk.pro/ads/detail/1310272975"},{"price":255000,"distance":123000,"url":"https://tronk.pro/ads/detail/1306221568"},{"price":460000,"distance":125000,"url":"https://tronk.pro/ads/detail/1309929891"},{"price":345000,"distance":198000,"url":"https://tronk.pro/ads/detail/1302819892"},{"price":315000,"distance":178398,"url":"https://tronk.pro/ads/detail/1307180954"},{"price":390000,"distance":199000,"url":"https://tronk.pro/ads/detail/1308697221"},{"price":285000,"distance":216000,"url":"https://tronk.pro/ads/detail/1306910729"},{"price":280000,"distance":148000,"url":"https://tronk.pro/ads/detail/1307886540"},{"price":295000,"distance":127000,"url":"https://tronk.pro/ads/detail/1309632134"},{"price":350000,"distance":204000,"url":"https://tronk.pro/ads/detail/1305226722"},{"price":385000,"distance":200000,"url":"https://tronk.pro/ads/detail/1308347512"},{"price":295000,"distance":158000,"url":"https://tronk.pro/ads/detail/1311446128"},{"price":355000,"distance":197536,"url":"https://tronk.pro/ads/detail/1305474847"},{"price":415000,"distance":92819,"url":"https://tronk.pro/ads/detail/1305056197"},{"price":325000,"distance":247000,"url":"https://tronk.pro/ads/detail/1311274439"},{"price":400000,"distance":145000,"url":"https://tronk.pro/ads/detail/1307341742"},{"price":345000,"distance":213000,"url":"https://tronk.pro/ads/detail/1306641109"},{"price":500000,"distance":111851,"url":"https://tronk.pro/ads/detail/1305652340"},{"price":425000,"distance":124029,"url":"https://tronk.pro/ads/detail/1308630813"},{"price":260000,"distance":158034,"url":"https://tronk.pro/ads/detail/1305363517"},{"price":360000,"distance":230000,"url":"https://tronk.pro/ads/detail/1309336942"},{"price":350000,"distance":166274,"url":"https://tronk.pro/ads/detail/1310571732"},{"price":290000,"distance":197100,"url":"https://tronk.pro/ads/detail/1302934854"},{"price":330000,"distance":156488,"url":"https://tronk.pro/ads/detail/1306875658"},{"price":335000,"distance":196000,"url":"https://tronk.pro/ads/detail/1308635137"},{"price":350000,"distance":144705,"url":"https://tronk.pro/ads/detail/1303250190"},{"price":426000,"distance":29098,"url":"https://tronk.pro/ads/detail/1309242468"},{"price":227000,"distance":215128,"url":"https://tronk.pro/ads/detail/1303337436"},{"price":310000,"distance":171000,"url":"https://tronk.pro/ads/detail/1309116887"},{"price":320000,"distance":150000,"url":"https://tronk.pro/ads/detail/1310595728"},{"price":330000,"distance":129000,"url":"https://tronk.pro/ads/detail/1309688448"},{"price":150000,"distance":30000,"url":"https://tronk.pro/ads/detail/1302911963"},{"price":350000,"distance":86000,"url":"https://tronk.pro/ads/detail/1306344436"},{"price":370000,"distance":190000,"url":"https://tronk.pro/ads/detail/1309572413"},{"price":295000,"distance":137000,"url":"https://tronk.pro/ads/detail/1309695908"},{"price":320000,"distance":148000,"url":"https://tronk.pro/ads/detail/1303198020"},{"price":470000,"distance":74000,"url":"https://tronk.pro/ads/detail/1306131953"},{"price":260000,"distance":240000,"url":"https://tronk.pro/ads/detail/1309711625"},{"price":340000,"distance":206000,"url":"https://tronk.pro/ads/detail/1305078441"},{"price":396000,"distance":116000,"url":"https://tronk.pro/ads/detail/1310001941"},{"price":365000,"distance":169000,"url":"https://tronk.pro/ads/detail/1304511271"},{"price":268000,"distance":0,"url":"https://tronk.pro/ads/detail/1306687739"},{"price":369000,"distance":135000,"url":"https://tronk.pro/ads/detail/1311450751"},{"price":470000,"distance":123726,"url":"https://tronk.pro/ads/detail/1309556723"},{"price":170000,"distance":265000,"url":"https://tronk.pro/ads/detail/1305354435"},{"price":320000,"distance":166949,"url":"https://tronk.pro/ads/detail/1304557041"},{"price":275000,"distance":179835,"url":"https://tronk.pro/ads/detail/1304152538"},{"price":350099,"distance":150000,"url":"https://tronk.pro/ads/detail/1303437776"},{"price":155000,"distance":378000,"url":"https://tronk.pro/ads/detail/1306101427"},{"price":290000,"distance":122000,"url":"https://tronk.pro/ads/detail/1302989640"},{"price":369000,"distance":116710,"url":"https://tronk.pro/ads/detail/1309109825"},{"price":270000,"distance":181026,"url":"https://tronk.pro/ads/detail/1304786815"},{"price":300000,"distance":180000,"url":"https://tronk.pro/ads/detail/1308684617"},{"price":340000,"distance":215526,"url":"https://tronk.pro/ads/detail/1307303956"},{"price":650000,"distance":6100,"url":"https://tronk.pro/ads/detail/1304817925"},{"price":353000,"distance":149000,"url":"https://tronk.pro/ads/detail/1310494139"},{"price":520000,"distance":85000,"url":"https://tronk.pro/ads/detail/1305592249"},{"price":375000,"distance":150000,"url":"https://tronk.pro/ads/detail/1310196781"},{"price":360000,"distance":107500,"url":"https://tronk.pro/ads/detail/1307304469"},{"price":290000,"distance":154000,"url":"https://tronk.pro/ads/detail/1307894720"},{"price":400000,"distance":105000,"url":"https://tronk.pro/ads/detail/1306122039"},{"price":369000,"distance":150000,"url":"https://tronk.pro/ads/detail/1308526483"},{"price":450000,"distance":72000,"url":"https://tronk.pro/ads/detail/1309123125"},{"price":478300,"distance":38100,"url":"https://tronk.pro/ads/detail/1306181160"},{"price":335000,"distance":115000,"url":"https://tronk.pro/ads/detail/1303497168"},{"price":315000,"distance":171100,"url":"https://tronk.pro/ads/detail/1304043048"},{"price":290000,"distance":265000,"url":"https://tronk.pro/ads/detail/1307915899"},{"price":349000,"distance":0,"url":"https://tronk.pro/ads/detail/1303520185"},{"price":260000,"distance":140000,"url":"https://tronk.pro/ads/detail/1311588179"},{"price":310000,"distance":180865,"url":"https://tronk.pro/ads/detail/1310341940"},{"price":250000,"distance":26000,"url":"https://tronk.pro/ads/detail/1304088218"},{"price":600000,"distance":90000,"url":"https://tronk.pro/ads/detail/1306032015"},{"price":140000,"distance":210000,"url":"https://tronk.pro/ads/detail/1311044626"},{"price":340000,"distance":72180,"url":"https://tronk.pro/ads/detail/1303957805"},{"price":340000,"distance":246970,"url":"https://tronk.pro/ads/detail/1304371282"},{"price":350000,"distance":19543,"url":"https://tronk.pro/ads/detail/1308212553"},{"price":438000,"distance":159900,"url":"https://tronk.pro/ads/detail/1310258648"},{"price":318000,"distance":152000,"url":"https://tronk.pro/ads/detail/1307593971"},{"price":260000,"distance":251000,"url":"https://tronk.pro/ads/detail/1305653694"},{"price":425000,"distance":160000,"url":"https://tronk.pro/ads/detail/1305362561"},{"price":300000,"distance":181550,"url":"https://tronk.pro/ads/detail/1306909458"},{"price":225000,"distance":183123,"url":"https://tronk.pro/ads/detail/1306532153"},{"price":444000,"distance":131000,"url":"https://tronk.pro/ads/detail/1307745099"},{"price":320000,"distance":180200,"url":"https://tronk.pro/ads/detail/1304388564"},{"price":430000,"distance":256000,"url":"https://tronk.pro/ads/detail/1307586826"},{"price":325000,"distance":236023,"url":"https://tronk.pro/ads/detail/1303469561"},{"price":395000,"distance":107000,"url":"https://tronk.pro/ads/detail/1309673314"},{"price":240000,"distance":207000,"url":"https://tronk.pro/ads/detail/1309906614"},{"price":300000,"distance":136000,"url":"https://tronk.pro/ads/detail/1305447994"},{"price":390000,"distance":116000,"url":"https://tronk.pro/ads/detail/1310992552"},{"price":380000,"distance":158604,"url":"https://tronk.pro/ads/detail/1304350576"},{"price":439000,"distance":109426,"url":"https://tronk.pro/ads/detail/1309097582"},{"price":285000,"distance":200000,"url":"https://tronk.pro/ads/detail/1303194119"},{"price":340000,"distance":168000,"url":"https://tronk.pro/ads/detail/1308006362"},{"price":380000,"distance":182000,"url":"https://tronk.pro/ads/detail/1309878170"},{"price":365000,"distance":98000,"url":"https://tronk.pro/ads/detail/1309454183"},{"price":349000,"distance":155945,"url":"https://tronk.pro/ads/detail/1308194051"},{"price":400000,"distance":118490,"url":"https://tronk.pro/ads/detail/1311705418"},{"price":320000,"distance":151647,"url":"https://tronk.pro/ads/detail/1303657543"},{"price":340000,"distance":125000,"url":"https://tronk.pro/ads/detail/1309498956"},{"price":395000,"distance":106450,"url":"https://tronk.pro/ads/detail/1308534816"},{"price":350000,"distance":269308,"url":"https://tronk.pro/ads/detail/1309942386"},{"price":351000,"distance":169000,"url":"https://tronk.pro/ads/detail/1306866007"},{"price":425000,"distance":92367,"url":"https://tronk.pro/ads/detail/1303823301"},{"price":850000,"distance":200000,"url":"https://tronk.pro/ads/detail/1305303657"},{"price":375000,"distance":115000,"url":"https://tronk.pro/ads/detail/1303657710"},{"price":287000,"distance":310000,"url":"https://tronk.pro/ads/detail/1305225125"},{"price":395000,"distance":118000,"url":"https://tronk.pro/ads/detail/1307932183"},{"price":423500,"distance":183596,"url":"https://tronk.pro/ads/detail/1309681414"},{"price":375000,"distance":17120,"url":"https://tronk.pro/ads/detail/1305519623"},{"price":320000,"distance":140000,"url":"https://tronk.pro/ads/detail/1310147701"},{"price":275000,"distance":224000,"url":"https://tronk.pro/ads/detail/1305262418"},{"price":288000,"distance":138000,"url":"https://tronk.pro/ads/detail/1311144245"},{"price":359000,"distance":193285,"url":"https://tronk.pro/ads/detail/1305263563"},{"price":290000,"distance":197500,"url":"https://tronk.pro/ads/detail/1309671643"},{"price":370000,"distance":113342,"url":"https://tronk.pro/ads/detail/1304523566"},{"price":370000,"distance":200000,"url":"https://tronk.pro/ads/detail/1311166003"},{"price":300000,"distance":160000,"url":"https://tronk.pro/ads/detail/1305496332"},{"price":335000,"distance":179000,"url":"https://tronk.pro/ads/detail/1305835600"},{"price":460000,"distance":109000,"url":"https://tronk.pro/ads/detail/1305231734"},{"price":235000,"distance":216639,"url":"https://tronk.pro/ads/detail/1303351982"},{"price":315000,"distance":180000,"url":"https://tronk.pro/ads/detail/1307519987"},{"price":400000,"distance":183838,"url":"https://tronk.pro/ads/detail/1305479711"},{"price":430000,"distance":143000,"url":"https://tronk.pro/ads/detail/1303161877"},{"price":232960,"distance":327123,"url":"https://tronk.pro/ads/detail/1310763432"},{"price":430000,"distance":128000,"url":"https://tronk.pro/ads/detail/1305267729"},{"price":395000,"distance":110000,"url":"https://tronk.pro/ads/detail/1307317667"},{"price":290000,"distance":220000,"url":"https://tronk.pro/ads/detail/1308864198"},{"price":249000,"distance":160000,"url":"https://tronk.pro/ads/detail/1308060966"},{"price":390000,"distance":130000,"url":"https://tronk.pro/ads/detail/1307544133"},{"price":250000,"distance":165000,"url":"https://tronk.pro/ads/detail/1307851259"},{"price":260000,"distance":168500,"url":"https://tronk.pro/ads/detail/1311035701"},{"price":290000,"distance":265000,"url":"https://tronk.pro/ads/detail/1307905328"},{"price":330000,"distance":140000,"url":"https://tronk.pro/ads/detail/1310146226"},{"price":490000,"distance":119000,"url":"https://tronk.pro/ads/detail/1304716757"},{"price":305000,"distance":113000,"url":"https://tronk.pro/ads/detail/1307842202"},{"price":450000,"distance":129152,"url":"https://tronk.pro/ads/detail/1310661441"},{"price":305000,"distance":179000,"url":"https://tronk.pro/ads/detail/1306019665"},{"price":470000,"distance":107985,"url":"https://tronk.pro/ads/detail/1302943653"},{"price":395000,"distance":128900,"url":"https://tronk.pro/ads/detail/1308836204"},{"price":325000,"distance":204000,"url":"https://tronk.pro/ads/detail/1305243133"},{"price":339000,"distance":129000,"url":"https://tronk.pro/ads/detail/1307862161"},{"price":225000,"distance":216432,"url":"https://tronk.pro/ads/detail/1303239250"},{"price":395000,"distance":107000,"url":"https://tronk.pro/ads/detail/1309534474"},{"price":335000,"distance":149281,"url":"https://tronk.pro/ads/detail/1303394771"},{"price":255000,"distance":210000,"url":"https://tronk.pro/ads/detail/1310819996"},{"price":320000,"distance":129657,"url":"https://tronk.pro/ads/detail/1310934878"},{"price":269000,"distance":250000,"url":"https://tronk.pro/ads/detail/1305241680"},{"price":400000,"distance":140000,"url":"https://tronk.pro/ads/detail/1310167280"},{"price":225000,"distance":340000,"url":"https://tronk.pro/ads/detail/1304139927"},{"price":375000,"distance":190000,"url":"https://tronk.pro/ads/detail/1307661629"},{"price":377000,"distance":193800,"url":"https://tronk.pro/ads/detail/1310518358"},{"price":370000,"distance":162000,"url":"https://tronk.pro/ads/detail/1309042469"},{"price":330000,"distance":181800,"url":"https://tronk.pro/ads/detail/1308973085"},{"price":460000,"distance":95211,"url":"https://tronk.pro/ads/detail/1309402253"},{"price":449000,"distance":90000,"url":"https://tronk.pro/ads/detail/1307306162"},{"price":353000,"distance":116000,"url":"https://tronk.pro/ads/detail/1310149310"},{"price":249000,"distance":326000,"url":"https://tronk.pro/ads/detail/1308364731"},{"price":315000,"distance":201000,"url":"https://tronk.pro/ads/detail/1306788710"},{"price":320000,"distance":188000,"url":"https://tronk.pro/ads/detail/1308888945"},{"price":499000,"distance":50000,"url":"https://tronk.pro/ads/detail/1306297294"},{"price":195000,"distance":101600,"url":"https://tronk.pro/ads/detail/1309585857"},{"price":297000,"distance":150000,"url":"https://tronk.pro/ads/detail/1311454936"},{"price":345000,"distance":119432,"url":"https://tronk.pro/ads/detail/1310406996"},{"price":240000,"distance":257000,"url":"https://tronk.pro/ads/detail/1310376774"},{"price":500000,"distance":265000,"url":"https://tronk.pro/ads/detail/1305277206"},{"price":310000,"distance":159500,"url":"https://tronk.pro/ads/detail/1303748342"},{"price":380000,"distance":170000,"url":"https://tronk.pro/ads/detail/1311282297"},{"price":350000,"distance":140000,"url":"https://tronk.pro/ads/detail/1303041982"},{"price":170000,"distance":289000,"url":"https://tronk.pro/ads/detail/1306450043"},{"price":299999,"distance":134909,"url":"https://tronk.pro/ads/detail/1305208583"},{"price":340000,"distance":167668,"url":"https://tronk.pro/ads/detail/1307941398"},{"price":350000,"distance":124000,"url":"https://tronk.pro/ads/detail/1311068253"},{"price":285000,"distance":196000,"url":"https://tronk.pro/ads/detail/1302929656"},{"price":190000,"distance":200000,"url":"https://tronk.pro/ads/detail/1308841767"},{"price":275000,"distance":167000,"url":"https://tronk.pro/ads/detail/1307098804"},{"price":255000,"distance":215128,"url":"https://tronk.pro/ads/detail/1303845041"},{"price":390000,"distance":120000,"url":"https://tronk.pro/ads/detail/1310638060"},{"price":285000,"distance":153000,"url":"https://tronk.pro/ads/detail/1309542132"},{"price":260000,"distance":200000,"url":"https://tronk.pro/ads/detail/1302798665"},{"price":290000,"distance":167000,"url":"https://tronk.pro/ads/detail/1304367282"},{"price":325000,"distance":180000,"url":"https://tronk.pro/ads/detail/1304805855"},{"price":310000,"distance":190000,"url":"https://tronk.pro/ads/detail/1303673078"},{"price":350000,"distance":157123,"url":"https://tronk.pro/ads/detail/1304232377"},{"price":389000,"distance":167000,"url":"https://tronk.pro/ads/detail/1308334750"},{"price":268000,"distance":210000,"url":"https://tronk.pro/ads/detail/1305962319"},{"price":260000,"distance":180000,"url":"https://tronk.pro/ads/detail/1304855117"},{"price":420000,"distance":42000,"url":"https://tronk.pro/ads/detail/1310090831"},{"price":330000,"distance":333000,"url":"https://tronk.pro/ads/detail/1304875184"},{"price":148000,"distance":221000,"url":"https://tronk.pro/ads/detail/1307782695"},{"price":330000,"distance":176000,"url":"https://tronk.pro/ads/detail/1308980132"},{"price":340000,"distance":206000,"url":"https://tronk.pro/ads/detail/1305322681"},{"price":320000,"distance":166949,"url":"https://tronk.pro/ads/detail/1304391971"},{"price":460000,"distance":71500,"url":"https://tronk.pro/ads/detail/1311600735"},{"price":297800,"distance":173000,"url":"https://tronk.pro/ads/detail/1307260691"},{"price":360000,"distance":195000,"url":"https://tronk.pro/ads/detail/1307764452"},{"price":185000,"distance":0,"url":"https://tronk.pro/ads/detail/1309287171"},{"price":380000,"distance":87000,"url":"https://tronk.pro/ads/detail/1306805184"},{"price":480000,"distance":67000,"url":"https://tronk.pro/ads/detail/1303059420"},{"price":479000,"distance":86000,"url":"https://tronk.pro/ads/detail/1307813481"},{"price":160000,"distance":155000,"url":"https://tronk.pro/ads/detail/1307033634"},{"price":550000,"distance":46840,"url":"https://tronk.pro/ads/detail/1303169816"},{"price":347000,"distance":156000,"url":"https://tronk.pro/ads/detail/1303055177"},{"price":500000,"distance":111849,"url":"https://tronk.pro/ads/detail/1305643208"},{"price":400000,"distance":205752,"url":"https://tronk.pro/ads/detail/1306266788"},{"price":350000,"distance":155000,"url":"https://tronk.pro/ads/detail/1310633872"},{"price":190000,"distance":233000,"url":"https://tronk.pro/ads/detail/1307000812"},{"price":260000,"distance":181000,"url":"https://tronk.pro/ads/detail/1311624630"},{"price":350000,"distance":217300,"url":"https://tronk.pro/ads/detail/1308265320"},{"price":495000,"distance":95000,"url":"https://tronk.pro/ads/detail/1305405103"},{"price":320000,"distance":230000,"url":"https://tronk.pro/ads/detail/1305633193"},{"price":440000,"distance":172777,"url":"https://tronk.pro/ads/detail/1303998513"},{"price":335000,"distance":72200,"url":"https://tronk.pro/ads/detail/1305438932"},{"price":370000,"distance":109000,"url":"https://tronk.pro/ads/detail/1305229724"},{"price":370000,"distance":185000,"url":"https://tronk.pro/ads/detail/1304619854"},{"price":340000,"distance":132000,"url":"https://tronk.pro/ads/detail/1309112768"},{"price":375000,"distance":78560,"url":"https://tronk.pro/ads/detail/1310086989"},{"price":380000,"distance":143000,"url":"https://tronk.pro/ads/detail/1307925667"},{"price":245000,"distance":200000,"url":"https://tronk.pro/ads/detail/1303280932"},{"price":350000,"distance":142000,"url":"https://tronk.pro/ads/detail/1310973006"},{"price":349000,"distance":180000,"url":"https://tronk.pro/ads/detail/1307180394"},{"price":400000,"distance":120000,"url":"https://tronk.pro/ads/detail/1303150352"},{"price":385000,"distance":94000,"url":"https://tronk.pro/ads/detail/1303037763"},{"price":320000,"distance":120000,"url":"https://tronk.pro/ads/detail/1308303514"},{"price":390000,"distance":114000,"url":"https://tronk.pro/ads/detail/1307474191"},{"price":310000,"distance":155000,"url":"https://tronk.pro/ads/detail/1303773687"},{"price":370000,"distance":187000,"url":"https://tronk.pro/ads/detail/1303190758"},{"price":390000,"distance":106327,"url":"https://tronk.pro/ads/detail/1306686281"},{"price":300000,"distance":184000,"url":"https://tronk.pro/ads/detail/1304270658"},{"price":520000,"distance":21000,"url":"https://tronk.pro/ads/detail/1305116582"},{"price":375000,"distance":180000,"url":"https://tronk.pro/ads/detail/1303150359"},{"price":280000,"distance":146224,"url":"https://tronk.pro/ads/detail/1309197115"},{"price":360000,"distance":175000,"url":"https://tronk.pro/ads/detail/1308309235"},{"price":345000,"distance":188000,"url":"https://tronk.pro/ads/detail/1307859827"},{"price":325000,"distance":149799,"url":"https://tronk.pro/ads/detail/1309924005"},{"price":250000,"distance":210000,"url":"https://tronk.pro/ads/detail/1304121786"},{"price":420000,"distance":140649,"url":"https://tronk.pro/ads/detail/1308368110"},{"price":249000,"distance":250000,"url":"https://tronk.pro/ads/detail/1311754847"},{"price":375000,"distance":132000,"url":"https://tronk.pro/ads/detail/1308082695"},{"price":370000,"distance":213795,"url":"https://tronk.pro/ads/detail/1307539789"},{"price":360000,"distance":157000,"url":"https://tronk.pro/ads/detail/1306360090"},{"price":325000,"distance":125000,"url":"https://tronk.pro/ads/detail/1310369476"},{"price":395000,"distance":104602,"url":"https://tronk.pro/ads/detail/1310370660"},{"price":175000,"distance":210000,"url":"https://tronk.pro/ads/detail/1310261864"},{"price":650000,"distance":172000,"url":"https://tronk.pro/ads/detail/1309825474"},{"price":299000,"distance":151000,"url":"https://tronk.pro/ads/detail/1307598866"},{"price":310000,"distance":232000,"url":"https://tronk.pro/ads/detail/1306872212"},{"price":275000,"distance":142800,"url":"https://tronk.pro/ads/detail/1305470686"},{"price":380000,"distance":182000,"url":"https://tronk.pro/ads/detail/1310108390"},{"price":350000,"distance":124000,"url":"https://tronk.pro/ads/detail/1307022006"},{"price":285000,"distance":203000,"url":"https://tronk.pro/ads/detail/1304034521"},{"price":390000,"distance":162000,"url":"https://tronk.pro/ads/detail/1307893934"},{"price":380000,"distance":132340,"url":"https://tronk.pro/ads/detail/1306341935"},{"price":346700,"distance":118239,"url":"https://tronk.pro/ads/detail/1308511891"},{"price":260000,"distance":188000,"url":"https://tronk.pro/ads/detail/1303612249"},{"price":247000,"distance":0,"url":"https://tronk.pro/ads/detail/1308035983"},{"price":400000,"distance":144000,"url":"https://tronk.pro/ads/detail/1304026784"},{"price":395000,"distance":110000,"url":"https://tronk.pro/ads/detail/1307204805"},{"price":380000,"distance":115100,"url":"https://tronk.pro/ads/detail/1307610136"},{"price":499000,"distance":183000,"url":"https://tronk.pro/ads/detail/1308339703"},{"price":450000,"distance":129152,"url":"https://tronk.pro/ads/detail/1310661237"},{"price":320000,"distance":133000,"url":"https://tronk.pro/ads/detail/1308586105"},{"price":435000,"distance":122000,"url":"https://tronk.pro/ads/detail/1309195449"},{"price":197000,"distance":184975,"url":"https://tronk.pro/ads/detail/1305808027"},{"price":390000,"distance":80000,"url":"https://tronk.pro/ads/detail/1303600548"},{"price":310000,"distance":245555,"url":"https://tronk.pro/ads/detail/1305252132"},{"price":380000,"distance":70000,"url":"https://tronk.pro/ads/detail/1307409991"},{"price":300000,"distance":154000,"url":"https://tronk.pro/ads/detail/1305566382"},{"price":600000,"distance":220000,"url":"https://tronk.pro/ads/detail/1311041587"},{"price":275000,"distance":77000,"url":"https://tronk.pro/ads/detail/1302933019"},{"price":320000,"distance":230000,"url":"https://tronk.pro/ads/detail/1305627297"},{"price":350000,"distance":210533,"url":"https://tronk.pro/ads/detail/1305497036"},{"price":360000,"distance":107500,"url":"https://tronk.pro/ads/detail/1307484317"},{"price":299000,"distance":107344,"url":"https://tronk.pro/ads/detail/1306094216"},{"price":520000,"distance":99543,"url":"https://tronk.pro/ads/detail/1310954263"},{"price":320000,"distance":0,"url":"https://tronk.pro/ads/detail/1308837426"},{"price":270000,"distance":171456,"url":"https://tronk.pro/ads/detail/1303651297"},{"price":330000,"distance":200000,"url":"https://tronk.pro/ads/detail/1306325517"},{"price":290000,"distance":180000,"url":"https://tronk.pro/ads/detail/1311691732"},{"price":180000,"distance":100000,"url":"https://tronk.pro/ads/detail/1305766107"},{"price":345000,"distance":158604,"url":"https://tronk.pro/ads/detail/1304390276"},{"price":350000,"distance":182000,"url":"https://tronk.pro/ads/detail/1311710569"},{"price":250000,"distance":200000,"url":"https://tronk.pro/ads/detail/1305347537"},{"price":320000,"distance":164500,"url":"https://tronk.pro/ads/detail/1307413922"},{"price":325000,"distance":220640,"url":"https://tronk.pro/ads/detail/1307392244"},{"price":270000,"distance":300000,"url":"https://tronk.pro/ads/detail/1304273657"},{"price":389000,"distance":107000,"url":"https://tronk.pro/ads/detail/1305845307"},{"price":395000,"distance":88300,"url":"https://tronk.pro/ads/detail/1307542838"},{"price":310000,"distance":165000,"url":"https://tronk.pro/ads/detail/1305372470"},{"price":370000,"distance":187860,"url":"https://tronk.pro/ads/detail/1309860860"},{"price":305000,"distance":178000,"url":"https://tronk.pro/ads/detail/1307395027"},{"price":339000,"distance":186000,"url":"https://tronk.pro/ads/detail/1309734765"},{"price":269000,"distance":158000,"url":"https://tronk.pro/ads/detail/1307031116"},{"price":495000,"distance":106000,"url":"https://tronk.pro/ads/detail/1310629971"},{"price":265000,"distance":270000,"url":"https://tronk.pro/ads/detail/1307652177"},{"price":320000,"distance":170000,"url":"https://tronk.pro/ads/detail/1310630427"},{"price":335000,"distance":70000,"url":"https://tronk.pro/ads/detail/1306808615"},{"price":399000,"distance":132500,"url":"https://tronk.pro/ads/detail/1307255553"},{"price":240000,"distance":252500,"url":"https://tronk.pro/ads/detail/1310795696"},{"price":300000,"distance":300000,"url":"https://tronk.pro/ads/detail/1308838991"},{"price":395000,"distance":133000,"url":"https://tronk.pro/ads/detail/1308360165"},{"price":400000,"distance":161000,"url":"https://tronk.pro/ads/detail/1308181732"},{"price":380000,"distance":100000,"url":"https://tronk.pro/ads/detail/1304216941"},{"price":389000,"distance":129000,"url":"https://tronk.pro/ads/detail/1311538559"},{"price":300000,"distance":185,"url":"https://tronk.pro/ads/detail/1310662680"},{"price":480000,"distance":100010,"url":"https://tronk.pro/ads/detail/1306949425"},{"price":265000,"distance":290000,"url":"https://tronk.pro/ads/detail/1305619806"},{"price":380000,"distance":173000,"url":"https://tronk.pro/ads/detail/1307352498"},{"price":350000,"distance":175000,"url":"https://tronk.pro/ads/detail/1309747153"},{"price":370000,"distance":112822,"url":"https://tronk.pro/ads/detail/1311426538"},{"price":269000,"distance":250000,"url":"https://tronk.pro/ads/detail/1305226499"},{"price":260000,"distance":187000,"url":"https://tronk.pro/ads/detail/1303271427"},{"price":390000,"distance":137215,"url":"https://tronk.pro/ads/detail/1310614256"},{"price":335000,"distance":70070,"url":"https://tronk.pro/ads/detail/1302918932"},{"price":550000,"distance":109000,"url":"https://tronk.pro/ads/detail/1309337683"},{"price":335000,"distance":230000,"url":"https://tronk.pro/ads/detail/1308404207"},{"price":320000,"distance":127498,"url":"https://tronk.pro/ads/detail/1303125642"},{"price":440000,"distance":111600,"url":"https://tronk.pro/ads/detail/1306319650"},{"price":389000,"distance":149500,"url":"https://tronk.pro/ads/detail/1303697031"},{"price":260000,"distance":188000,"url":"https://tronk.pro/ads/detail/1303751695"},{"price":600000,"distance":93077,"url":"https://tronk.pro/ads/detail/1305830617"},{"price":327000,"distance":175000,"url":"https://tronk.pro/ads/detail/1303436230"},{"price":320000,"distance":152000,"url":"https://tronk.pro/ads/detail/1305367224"},{"price":299999,"distance":136000,"url":"https://tronk.pro/ads/detail/1310501900"},{"price":480000,"distance":148000,"url":"https://tronk.pro/ads/detail/1302945292"},{"price":340000,"distance":138000,"url":"https://tronk.pro/ads/detail/1303500126"},{"price":530000,"distance":24500,"url":"https://tronk.pro/ads/detail/1310101807"},{"price":230000,"distance":256000,"url":"https://tronk.pro/ads/detail/1303757564"},{"price":335000,"distance":101000,"url":"https://tronk.pro/ads/detail/1303516509"},{"price":330000,"distance":130000,"url":"https://tronk.pro/ads/detail/1307394898"},{"price":280000,"distance":197542,"url":"https://tronk.pro/ads/detail/1309957156"},{"price":350000,"distance":197888,"url":"https://tronk.pro/ads/detail/1303591236"},{"price":380000,"distance":83000,"url":"https://tronk.pro/ads/detail/1305231628"},{"price":215000,"distance":170166,"url":"https://tronk.pro/ads/detail/1304440220"},{"price":530000,"distance":270000,"url":"https://tronk.pro/ads/detail/1309696943"},{"price":390000,"distance":247000,"url":"https://tronk.pro/ads/detail/1306339148"},{"price":430000,"distance":100000,"url":"https://tronk.pro/ads/detail/1305609310"},{"price":389000,"distance":210000,"url":"https://tronk.pro/ads/detail/1304594640"},{"price":330000,"distance":216000,"url":"https://tronk.pro/ads/detail/1304500832"},{"price":349000,"distance":128000,"url":"https://tronk.pro/ads/detail/1304356166"},{"price":250000,"distance":192000,"url":"https://tronk.pro/ads/detail/1306085312"},{"price":250000,"distance":165000,"url":"https://tronk.pro/ads/detail/1305146121"},{"price":450000,"distance":199800,"url":"https://tronk.pro/ads/detail/1302679331"},{"price":460000,"distance":167000,"url":"https://tronk.pro/ads/detail/1304888346"},{"price":387000,"distance":139000,"url":"https://tronk.pro/ads/detail/1303013833"},{"price":345000,"distance":151500,"url":"https://tronk.pro/ads/detail/1307356163"},{"price":199999,"distance":19000,"url":"https://tronk.pro/ads/detail/1309558389"},{"price":215000,"distance":141500,"url":"https://tronk.pro/ads/detail/1309566536"},{"price":460000,"distance":167000,"url":"https://tronk.pro/ads/detail/1304885813"},{"price":300000,"distance":145000,"url":"https://tronk.pro/ads/detail/1307143416"},{"price":230000,"distance":196051,"url":"https://tronk.pro/ads/detail/1306329391"},{"price":600000,"distance":156000,"url":"https://tronk.pro/ads/detail/1311251717"},{"price":315000,"distance":227000,"url":"https://tronk.pro/ads/detail/1310454148"},{"price":265000,"distance":258000,"url":"https://tronk.pro/ads/detail/1305736030"},{"price":430000,"distance":128000,"url":"https://tronk.pro/ads/detail/1305263376"},{"price":440000,"distance":111600,"url":"https://tronk.pro/ads/detail/1306278886"},{"price":335000,"distance":230000,"url":"https://tronk.pro/ads/detail/1311733010"},{"price":255000,"distance":135700,"url":"https://tronk.pro/ads/detail/1305347296"},{"price":315000,"distance":187000,"url":"https://tronk.pro/ads/detail/1305244908"},{"price":400000,"distance":114844,"url":"https://tronk.pro/ads/detail/1308291852"},{"price":370000,"distance":156000,"url":"https://tronk.pro/ads/detail/1308291873"},{"price":380000,"distance":240000,"url":"https://tronk.pro/ads/detail/1305980179"},{"price":330000,"distance":249000,"url":"https://tronk.pro/ads/detail/1311662518"},{"price":395000,"distance":84000,"url":"https://tronk.pro/ads/detail/1311450683"},{"price":350000,"distance":124000,"url":"https://tronk.pro/ads/detail/1307022447"},{"price":180000,"distance":123820,"url":"https://tronk.pro/ads/detail/1305598609"},{"price":300000,"distance":185000,"url":"https://tronk.pro/ads/detail/1310663068"},{"price":315000,"distance":153000,"url":"https://tronk.pro/ads/detail/1309945603"},{"price":260000,"distance":272167,"url":"https://tronk.pro/ads/detail/1309023484"},{"price":280000,"distance":114000,"url":"https://tronk.pro/ads/detail/1303519961"},{"price":330000,"distance":179850,"url":"https://tronk.pro/ads/detail/1309344781"},{"price":439000,"distance":80000,"url":"https://tronk.pro/ads/detail/1303944677"},{"price":450000,"distance":105000,"url":"https://tronk.pro/ads/detail/1311059122"},{"price":300000,"distance":220134,"url":"https://tronk.pro/ads/detail/1311693322"},{"price":350000,"distance":210000,"url":"https://tronk.pro/ads/detail/1302725814"},{"price":384500,"distance":132263,"url":"https://tronk.pro/ads/detail/1307996777"},{"price":380000,"distance":146,"url":"https://tronk.pro/ads/detail/1304813256"},{"price":200000,"distance":200000,"url":"https://tronk.pro/ads/detail/1303936443"},{"price":310000,"distance":171000,"url":"https://tronk.pro/ads/detail/1309129557"},{"price":390000,"distance":137215,"url":"https://tronk.pro/ads/detail/1310535380"},{"price":399000,"distance":124162,"url":"https://tronk.pro/ads/detail/1310599619"},{"price":330000,"distance":217000,"url":"https://tronk.pro/ads/detail/1304561848"},{"price":250000,"distance":239759,"url":"https://tronk.pro/ads/detail/1303135738"},{"price":380000,"distance":173000,"url":"https://tronk.pro/ads/detail/1308834551"},{"price":350000,"distance":142000,"url":"https://tronk.pro/ads/detail/1311706889"},{"price":390000,"distance":114000,"url":"https://tronk.pro/ads/detail/1307015762"},{"price":315000,"distance":120000,"url":"https://tronk.pro/ads/detail/1310843662"},{"price":369000,"distance":76704,"url":"https://tronk.pro/ads/detail/1307153965"},{"price":340000,"distance":170000,"url":"https://tronk.pro/ads/detail/1307746349"},{"price":469000,"distance":87157,"url":"https://tronk.pro/ads/detail/1310263819"},{"price":379000,"distance":188413,"url":"https://tronk.pro/ads/detail/1309009614"},{"price":345000,"distance":155000,"url":"https://tronk.pro/ads/detail/1310010604"},{"price":490000,"distance":318880,"url":"https://tronk.pro/ads/detail/1306613457"},{"price":360000,"distance":104000,"url":"https://tronk.pro/ads/detail/1303608227"},{"price":400000,"distance":124000,"url":"https://tronk.pro/ads/detail/1308146167"},{"price":345000,"distance":140000,"url":"https://tronk.pro/ads/detail/1311751364"},{"price":225000,"distance":230000,"url":"https://tronk.pro/ads/detail/1308119354"},{"price":215000,"distance":150854,"url":"https://tronk.pro/ads/detail/1308804618"},{"price":405000,"distance":257000,"url":"https://tronk.pro/ads/detail/1309186351"},{"price":365000,"distance":114222,"url":"https://tronk.pro/ads/detail/1304870074"},{"price":348000,"distance":154823,"url":"https://tronk.pro/ads/detail/1303360644"},{"price":435000,"distance":239000,"url":"https://tronk.pro/ads/detail/1304727745"},{"price":470000,"distance":69000,"url":"https://tronk.pro/ads/detail/1308518756"},{"price":250000,"distance":180000,"url":"https://tronk.pro/ads/detail/1310153984"},{"price":600000,"distance":90000,"url":"https://tronk.pro/ads/detail/1306025675"},{"price":285000,"distance":160000,"url":"https://tronk.pro/ads/detail/1306285589"},{"price":300000,"distance":215289,"url":"https://tronk.pro/ads/detail/1302937941"},{"price":390000,"distance":124000,"url":"https://tronk.pro/ads/detail/1311475557"},{"price":310000,"distance":217000,"url":"https://tronk.pro/ads/detail/1309134823"},{"price":309000,"distance":123456,"url":"https://tronk.pro/ads/detail/1303471642"},{"price":239000,"distance":184000,"url":"https://tronk.pro/ads/detail/1303853022"},{"price":354000,"distance":133000,"url":"https://tronk.pro/ads/detail/1306296444"},{"price":350000,"distance":152000,"url":"https://tronk.pro/ads/detail/1311358186"},{"price":600000,"distance":220000,"url":"https://tronk.pro/ads/detail/1310987517"},{"price":310000,"distance":136901,"url":"https://tronk.pro/ads/detail/1307367884"},{"price":350000,"distance":220000,"url":"https://tronk.pro/ads/detail/1304625521"},{"price":315000,"distance":197000,"url":"https://tronk.pro/ads/detail/1310117090"},{"price":340000,"distance":116361,"url":"https://tronk.pro/ads/detail/1303133417"},{"price":300000,"distance":213516,"url":"https://tronk.pro/ads/detail/1307892151"},{"price":395000,"distance":109000,"url":"https://tronk.pro/ads/detail/1310760802"},{"price":265000,"distance":206000,"url":"https://tronk.pro/ads/detail/1303077142"},{"price":350000,"distance":170000,"url":"https://tronk.pro/ads/detail/1306494150"},{"price":220000,"distance":285000,"url":"https://tronk.pro/ads/detail/1307895010"},{"price":650000,"distance":105000,"url":"https://tronk.pro/ads/detail/1309035181"},{"price":279000,"distance":171000,"url":"https://tronk.pro/ads/detail/1308390524"},{"price":365000,"distance":143500,"url":"https://tronk.pro/ads/detail/1302731416"},{"price":389000,"distance":216134,"url":"https://tronk.pro/ads/detail/1311278830"},{"price":350000,"distance":125844,"url":"https://tronk.pro/ads/detail/1302936058"},{"price":375000,"distance":165000,"url":"https://tronk.pro/ads/detail/1303830282"},{"price":320000,"distance":219300,"url":"https://tronk.pro/ads/detail/1309872502"},{"price":270000,"distance":132500,"url":"https://tronk.pro/ads/detail/1304671704"},{"price":325000,"distance":193800,"url":"https://tronk.pro/ads/detail/1308883657"},{"price":190000,"distance":273000,"url":"https://tronk.pro/ads/detail/1310905187"},{"price":350000,"distance":118830,"url":"https://tronk.pro/ads/detail/1310078151"},{"price":299000,"distance":186740,"url":"https://tronk.pro/ads/detail/1304410524"},{"price":310000,"distance":116000,"url":"https://tronk.pro/ads/detail/1306468849"},{"price":325000,"distance":155000,"url":"https://tronk.pro/ads/detail/1311311609"},{"price":230000,"distance":260000,"url":"https://tronk.pro/ads/detail/1309082986"},{"price":417000,"distance":187981,"url":"https://tronk.pro/ads/detail/1306269410"},{"price":398000,"distance":125968,"url":"https://tronk.pro/ads/detail/1309164621"},{"price":350000,"distance":92695,"url":"https://tronk.pro/ads/detail/1307893137"},{"price":400000,"distance":107221,"url":"https://tronk.pro/ads/detail/1307378984"},{"price":255000,"distance":157000,"url":"https://tronk.pro/ads/detail/1303029811"},{"price":285000,"distance":129000,"url":"https://tronk.pro/ads/detail/1309081760"},{"price":365000,"distance":193000,"url":"https://tronk.pro/ads/detail/1307399251"},{"price":430000,"distance":99834,"url":"https://tronk.pro/ads/detail/1310425484"},{"price":240000,"distance":235000,"url":"https://tronk.pro/ads/detail/1305785035"},{"price":330000,"distance":246000,"url":"https://tronk.pro/ads/detail/1309196435"},{"price":330000,"distance":123523,"url":"https://tronk.pro/ads/detail/1308367364"},{"price":389000,"distance":121000,"url":"https://tronk.pro/ads/detail/1308995485"},{"price":314990,"distance":112000,"url":"https://tronk.pro/ads/detail/1305199953"},{"price":235000,"distance":124000,"url":"https://tronk.pro/ads/detail/1304856687"},{"price":535000,"distance":109000,"url":"https://tronk.pro/ads/detail/1303946944"},{"price":295000,"distance":0,"url":"https://tronk.pro/ads/detail/1309009091"},{"price":230000,"distance":214612,"url":"https://tronk.pro/ads/detail/1307031571"},{"price":385000,"distance":128800,"url":"https://tronk.pro/ads/detail/1306934950"},{"price":360000,"distance":186595,"url":"https://tronk.pro/ads/detail/1307574151"},{"price":310000,"distance":180860,"url":"https://tronk.pro/ads/detail/1304080244"},{"price":480000,"distance":148000,"url":"https://tronk.pro/ads/detail/1302945381"},{"price":240000,"distance":225000,"url":"https://tronk.pro/ads/detail/1306872745"},{"price":280000,"distance":188000,"url":"https://tronk.pro/ads/detail/1304963645"},{"price":470000,"distance":48862,"url":"https://tronk.pro/ads/detail/1308515516"},{"price":245000,"distance":198000,"url":"https://tronk.pro/ads/detail/1303543207"},{"price":190000,"distance":89173,"url":"https://tronk.pro/ads/detail/1306746469"},{"price":228000,"distance":221000,"url":"https://tronk.pro/ads/detail/1308395169"},{"price":235000,"distance":0,"url":"https://tronk.pro/ads/detail/1304387601"},{"price":380000,"distance":71924,"url":"https://tronk.pro/ads/detail/1309608762"},{"price":410000,"distance":98993,"url":"https://tronk.pro/ads/detail/1309595512"},{"price":470000,"distance":120000,"url":"https://tronk.pro/ads/detail/1306058626"},{"price":230000,"distance":133000,"url":"https://tronk.pro/ads/detail/1311129512"},{"price":265000,"distance":270000,"url":"https://tronk.pro/ads/detail/1306639030"},{"price":510000,"distance":94284,"url":"https://tronk.pro/ads/detail/1302955685"},{"price":315000,"distance":129800,"url":"https://tronk.pro/ads/detail/1306283960"},{"price":360000,"distance":144000,"url":"https://tronk.pro/ads/detail/1311159683"},{"price":500000,"distance":169000,"url":"https://tronk.pro/ads/detail/1303702081"},{"price":270000,"distance":166500,"url":"https://tronk.pro/ads/detail/1309151775"},{"price":390000,"distance":157000,"url":"https://tronk.pro/ads/detail/1308977094"},{"price":225000,"distance":215089,"url":"https://tronk.pro/ads/detail/1307043861"},{"price":430000,"distance":140000,"url":"https://tronk.pro/ads/detail/1305946978"},{"price":320000,"distance":130000,"url":"https://tronk.pro/ads/detail/1309479902"},{"price":270000,"distance":202000,"url":"https://tronk.pro/ads/detail/1308251941"},{"price":495000,"distance":95000,"url":"https://tronk.pro/ads/detail/1305529302"},{"price":290000,"distance":138000,"url":"https://tronk.pro/ads/detail/1310200549"},{"price":350000,"distance":132000,"url":"https://tronk.pro/ads/detail/1309688357"},{"price":409000,"distance":70617,"url":"https://tronk.pro/ads/detail/1307528393"},{"price":330000,"distance":217000,"url":"https://tronk.pro/ads/detail/1305734147"},{"price":360000,"distance":507637,"url":"https://tronk.pro/ads/detail/1307804731"},{"price":360000,"distance":174000,"url":"https://tronk.pro/ads/detail/1311710213"},{"price":315000,"distance":137000,"url":"https://tronk.pro/ads/detail/1302755645"},{"price":389000,"distance":128261,"url":"https://tronk.pro/ads/detail/1303495960"},{"price":285000,"distance":265000,"url":"https://tronk.pro/ads/detail/1308011858"},{"price":330000,"distance":226000,"url":"https://tronk.pro/ads/detail/1307035594"},{"price":175000,"distance":344435,"url":"https://tronk.pro/ads/detail/1309557963"},{"price":249000,"distance":203986,"url":"https://tronk.pro/ads/detail/1305043525"},{"price":400000,"distance":103000,"url":"https://tronk.pro/ads/detail/1311705260"},{"price":299999,"distance":120000,"url":"https://tronk.pro/ads/detail/1309504290"},{"price":250000,"distance":135000,"url":"https://tronk.pro/ads/detail/1310226290"},{"price":299000,"distance":240000,"url":"https://tronk.pro/ads/detail/1309200128"},{"price":380000,"distance":115000,"url":"https://tronk.pro/ads/detail/1307950820"},{"price":389000,"distance":86000,"url":"https://tronk.pro/ads/detail/1305500844"},{"price":215000,"distance":91000,"url":"https://tronk.pro/ads/detail/1311411506"},{"price":465000,"distance":122000,"url":"https://tronk.pro/ads/detail/1306980683"},{"price":299000,"distance":240558,"url":"https://tronk.pro/ads/detail/1306835653"},{"price":385000,"distance":141500,"url":"https://tronk.pro/ads/detail/1311477544"},{"price":440000,"distance":109000,"url":"https://tronk.pro/ads/detail/1304546912"},{"price":370000,"distance":134000,"url":"https://tronk.pro/ads/detail/1304260134"},{"price":350000,"distance":120000,"url":"https://tronk.pro/ads/detail/1308857763"},{"price":400000,"distance":80000,"url":"https://tronk.pro/ads/detail/1311627470"},{"price":230008,"distance":0,"url":"https://tronk.pro/ads/detail/1309349857"},{"price":379000,"distance":188413,"url":"https://tronk.pro/ads/detail/1309007741"},{"price":255000,"distance":220000,"url":"https://tronk.pro/ads/detail/1307670813"},{"price":270000,"distance":162000,"url":"https://tronk.pro/ads/detail/1307266996"},{"price":360000,"distance":138000,"url":"https://tronk.pro/ads/detail/1310948262"},{"price":355000,"distance":165000,"url":"https://tronk.pro/ads/detail/1304353132"},{"price":330000,"distance":216000,"url":"https://tronk.pro/ads/detail/1308858697"},{"price":345000,"distance":149000,"url":"https://tronk.pro/ads/detail/1303287964"},{"price":410000,"distance":87000,"url":"https://tronk.pro/ads/detail/1304422152"},{"price":295000,"distance":145000,"url":"https://tronk.pro/ads/detail/1302773609"},{"price":350000,"distance":230000,"url":"https://tronk.pro/ads/detail/1309341305"},{"price":250000,"distance":197000,"url":"https://tronk.pro/ads/detail/1309544606"},{"price":235000,"distance":180000,"url":"https://tronk.pro/ads/detail/1305096986"},{"price":285000,"distance":132000,"url":"https://tronk.pro/ads/detail/1311160328"},{"price":285000,"distance":190000,"url":"https://tronk.pro/ads/detail/1310484882"},{"price":220000,"distance":148000,"url":"https://tronk.pro/ads/detail/1309469468"},{"price":350000,"distance":142000,"url":"https://tronk.pro/ads/detail/1303168741"},{"price":600000,"distance":90000,"url":"https://tronk.pro/ads/detail/1306025974"},{"price":395000,"distance":137943,"url":"https://tronk.pro/ads/detail/1309126193"},{"price":330000,"distance":206000,"url":"https://tronk.pro/ads/detail/1305322415"},{"price":328000,"distance":120739,"url":"https://tronk.pro/ads/detail/1310231889"},{"price":380000,"distance":161889,"url":"https://tronk.pro/ads/detail/1305819116"},{"price":380000,"distance":109700,"url":"https://tronk.pro/ads/detail/1308881906"},{"price":410000,"distance":15326,"url":"https://tronk.pro/ads/detail/1304807197"},{"price":380000,"distance":130000,"url":"https://tronk.pro/ads/detail/1307883451"},{"price":385000,"distance":89000,"url":"https://tronk.pro/ads/detail/1303160364"},{"price":351000,"distance":192200,"url":"https://tronk.pro/ads/detail/1302793802"},{"price":345000,"distance":150000,"url":"https://tronk.pro/ads/detail/1305625016"},{"price":245000,"distance":89000,"url":"https://tronk.pro/ads/detail/1308725639"},{"price":340000,"distance":152000,"url":"https://tronk.pro/ads/detail/1304405123"},{"price":360000,"distance":190323,"url":"https://tronk.pro/ads/detail/1306373836"},{"price":400000,"distance":140000,"url":"https://tronk.pro/ads/detail/1307598992"},{"price":400000,"distance":200000,"url":"https://tronk.pro/ads/detail/1303517675"},{"price":375000,"distance":152734,"url":"https://tronk.pro/ads/detail/1308367756"},{"price":300000,"distance":152000,"url":"https://tronk.pro/ads/detail/1303965640"},{"price":350000,"distance":153110,"url":"https://tronk.pro/ads/detail/1310032595"},{"price":385000,"distance":134666,"url":"https://tronk.pro/ads/detail/1304351506"},{"price":350000,"distance":164000,"url":"https://tronk.pro/ads/detail/1308349586"},{"price":199999,"distance":207000,"url":"https://tronk.pro/ads/detail/1308217467"},{"price":250000,"distance":260637,"url":"https://tronk.pro/ads/detail/1304319120"},{"price":195000,"distance":123800,"url":"https://tronk.pro/ads/detail/1306110500"},{"price":375000,"distance":170500,"url":"https://tronk.pro/ads/detail/1304077211"},{"price":235000,"distance":208137,"url":"https://tronk.pro/ads/detail/1307035478"},{"price":299000,"distance":135077,"url":"https://tronk.pro/ads/detail/1306278214"},{"price":430000,"distance":150000,"url":"https://tronk.pro/ads/detail/1302695291"},{"price":400000,"distance":118414,"url":"https://tronk.pro/ads/detail/1310541149"},{"price":340000,"distance":181056,"url":"https://tronk.pro/ads/detail/1304724273"},{"price":350000,"distance":150000,"url":"https://tronk.pro/ads/detail/1307468379"},{"price":250000,"distance":204000,"url":"https://tronk.pro/ads/detail/1305743929"},{"price":370000,"distance":70731,"url":"https://tronk.pro/ads/detail/1305956094"},{"price":350000,"distance":123772,"url":"https://tronk.pro/ads/detail/1309482629"},{"price":298000,"distance":250000,"url":"https://tronk.pro/ads/detail/1306969381"},{"price":420000,"distance":196000,"url":"https://tronk.pro/ads/detail/1304773399"},{"price":220000,"distance":192000,"url":"https://tronk.pro/ads/detail/1307491400"},{"price":250000,"distance":248000,"url":"https://tronk.pro/ads/detail/1308263382"},{"price":280000,"distance":238000,"url":"https://tronk.pro/ads/detail/1311665658"},{"price":340000,"distance":160000,"url":"https://tronk.pro/ads/detail/1310669314"},{"price":378000,"distance":151164,"url":"https://tronk.pro/ads/detail/1306682658"},{"price":337000,"distance":169000,"url":"https://tronk.pro/ads/detail/1306862009"},{"price":350000,"distance":129657,"url":"https://tronk.pro/ads/detail/1302989480"},{"price":215000,"distance":112000,"url":"https://tronk.pro/ads/detail/1309423989"},{"price":249000,"distance":170000,"url":"https://tronk.pro/ads/detail/1311121027"},{"price":225000,"distance":167000,"url":"https://tronk.pro/ads/detail/1304332040"}]}}

    ';

    // private const OFFERS = '
    // {"start":"20.08.2026 13:49:00","end":"20.08.2026 13:49:01","command":"list","source":".2.","startyear":2014,"endyear":2014,"pricestart":0,"priceend":0,"remont":-1,"distancestart":0,"distanceend":0,"model":".GRANTA.","marka":".VAZ.","region":"","regionid":-1,"listregionid":"","error":false,"result":{"ListOffer":[{"Title":"LADA (ВАЗ) Granta","ID":1312313079,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"20.08.2026 12:56:57","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Седан","Price":230000,"PhoneExist":0,"Source":2,"City":"Ижевск","Descr":"Родной пробег , 2 владелец, до меня машина была в 1 семье. есть косяк по крыше, масло не ест, расходники менялись своевременно, коробка работает отлично, есть новый ремень грм осталось только поменять, расходники заменены, авто конец 2014 года, начало 2015. , ТОРГ уместен . Дно, пороги целые. зеленый отчёт, если на тел не отвечаю пишите","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":135000,"View":0,"FotoExist":1,"IsFakePhone":1,"Delta":-23298,"Vin":"","Engine":1,"Drive":1,"Horsepower":82,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312312873,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"20.08.2026 12:55:50","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Седан","Price":320000,"PhoneExist":0,"Source":2,"City":"Нижний Тагил","Descr":"В продаже GRANTA 2014 года, в хорошем состоянии, сел и поехал.\nНо есть, куда приложить руки, машина не новая.\nВладею с 2021 года.\nВ серьёзных ДТП не была, по крайней мере, при мне.\nПо кузову есть нюансы, без дыр.\nНовое лобовое стекло, замена прямо перед продажей (старое с трещиной).\nФары свежие.\nПротивотуманные фары.\nЛитые диски R15 на летней свежей резине.\nШумка дверей в три слоя.\nХодовку нужно смотреть.\nСтойки SS20 – 50.\nНовые тормозные колодки.\nМасло менялось вовремя.\nНеплохая музыка: магнитола Pioneer, передние динамики DL Audio Barracuda, сзади полка с блинами, активный сабвуфер «Урал» — всё оставляю.\nПодлокотник, ЭВА-коврики.\nСигнализация с А/С и А/З.\nДва стеклоподъёмника.\nЗапасное колесо на штампе.\nС документами полный порядок, без залогов, штрафов и т. п.\n\nТорг присутствует реальному покупателю.","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":226000,"View":0,"FotoExist":1,"IsFakePhone":1,"Delta":27160,"Vin":"","Engine":1,"Drive":1,"Horsepower":87,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312299329,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"20.08.2026 11:43:07","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Лифтбек","Price":140000,"PhoneExist":0,"Source":2,"City":"Наро-Фоминск","Descr":"Машина ездит каждый день бодро тянет коробка хорошая не вылетает вообще, движок хороший, по кузову видно по фото машина доедет куда угодно, нет запретов и штрафов машина на учоте стоить ","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":1,"Distance":225112,"View":2,"FotoExist":1,"IsFakePhone":1,"Delta":-188380,"Vin":"","Engine":1,"Drive":1,"Horsepower":87,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312293440,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"20.08.2026 11:15:36","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Седан","Price":355000,"PhoneExist":0,"Source":2,"City":"Москва","Descr":"не Бити Завадская машина ест царапини","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":135000,"View":2,"FotoExist":1,"IsFakePhone":1,"Delta":14229,"Vin":"","Engine":1,"Drive":1,"Horsepower":87,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312261005,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"20.08.2026 7:07:31","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Седан","Price":420000,"PhoneExist":0,"Source":2,"City":"Люберцы","Descr":"Срочно \nКомплектация гидроусилител руля \nКондиционер ","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":130000,"View":6,"FotoExist":1,"IsFakePhone":1,"Delta":79836,"Vin":"","Engine":1,"Drive":1,"Horsepower":87,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312227408,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"19.08.2026 21:28:21","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Лифтбек","Price":450000,"PhoneExist":0,"Source":2,"City":"Вологда","Descr":"Машина технически исправна \nПодвеска -90 брал новую пару месяцев назад (передние стоковые стойки в сборе отдам с авто)\n4 стеклоподьемника,кондиционер, проекция спидометра на лобовом,круговая подсветка салона управление любым цветом через телефон \nШитая под евро 0 отшита электро педаль газа,зашиты отстрелы если не понравится или надоест подскажу как убрать.\nУстановлена полностью вся трасса дк про\nСтоит хороший автозвук \nСобственник я продавать так же буду я\nПо документам чистая штрафы запреты и прочее отсутствуют. \nДнище пороги стаканы лонжероны близки к заводскому состоянию без дыр и коррозии\nПо лкп присутствуют рыжики в силу возраста (без сквозняка)\nИдеальный готовый вариант для молодого парня\nМашину можно посмотреть после 18:00\nТорг","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":170000,"View":0,"FotoExist":1,"IsFakePhone":1,"Delta":125698,"Vin":"","Engine":1,"Drive":1,"Horsepower":106,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312219357,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"19.08.2026 20:29:06","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Лифтбек","Price":500000,"PhoneExist":0,"Source":2,"City":"Пересыпь","Descr":"Продаю Lada Granta 2014г. 8 клапанов, самый надежный мотор. \n\nМашину купили в начале августа, оформили на себя, планировали на ней ездить, но по итогу решили продать. Причина продажи, нужен автомат. Задумываюсь поставить в нее японский, но пока только думаю. Купил за 415.000 и далее вложил для комфорта и надежности , все опишу ниже, сразу информация к тому, что торговаться я не буду, отдаю чтобы ровно вернуть свое и не беру за работу) \n\nМного сделано за две недели с момента покупки:\nКондиционер полностью обслужен и работает. (Промывка системы, промывка воздуходувов, заправка 6.000) Поставил Андройд магнитолу с камерой заднего вида за 15.000 хорошую и камеру за 3.000, новые колонки Урал, спереди 13 сзади 16 - 8.000 и задняя полка новая 6500, замена всех жидкостей 10.000, замена свечей 3000, установка всех новых лампочек в круг 5.000, диоды в ближний дальний. Замена газлифтов багажника и установка газлифтов на капот 6.000. Резина в авто с покупки новая, заменены были так же элементы салона (бардачок 5000, некоторые элементы панели 6500) мы готовили машину для себя, не на продажу, потому все делал качественно. Заменил все 4 концевика дверей, Отхимчистил сидения. Поставил новый кожаный бардачок подлокотник 4.000, установил теплоэкран на глушитель 2500. Заменил тормозные колодки в круг и многое другое, это что пока вспомнил пока пишу объявление. \n\nИз косяков есть места с рыжиками из всего три места (в крышке заправочной горловины, на задней правой двери немного под молдингом, дверях около стойки задних в одном и том же месте с двух сторон) оцениваю машину на 8 из 10 крашенные элементы есть, но ничего критичного с авто не было, ездил до покупки человек с ограниченными возможностями, была установка тормоза ручного, потому на авто ездили аккуратно, была небольшая авария в крышку багажника все исправили. \n\nЯ пишу все открыто, чтобы было понимание что машина сделана отлично, ей 12 лет, косяки которые есть, я описал. \nС продажей не спешу от слова совсем, просто готов продать. \n\n140 по трассе едет уверенно, (разгонял до 170 дальше мне страшно) мотор работает на отлично. Коробка не хрустит не выбивает. Вообщем машина отличная.","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":178000,"View":0,"FotoExist":1,"IsFakePhone":1,"Delta":171817,"Vin":"","Engine":1,"Drive":1,"Horsepower":87,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312207196,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"19.08.2026 19:22:34","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Седан","Price":550000,"PhoneExist":0,"Source":2,"City":"Ершов","Descr":"не гнилая , в хорошем состоянии ","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":156000,"View":0,"FotoExist":1,"IsFakePhone":1,"Delta":220614,"Vin":"","Engine":1,"Drive":1,"Horsepower":87,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312165647,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"19.08.2026 15:32:29","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Седан","Price":250000,"PhoneExist":0,"Source":2,"City":"Москва","Descr":"Продам 8-клапанную Гранту, покупал себе, но появилась возможность взять другой автомобиль.\nФактически была в одной семье (брал у дальних родственников в прошлом году, оформлена на меня, продажа только по ДКП с переоформлением).\nТехнически всё в отличном состоянии, пробег родной, двигатель внутри чистый, передачи переключаются отлично, в ходовой ничего не стучит, по электрике всё работает, ДТП не было. Есть недостатки по кузову (днище, лонжероны - живые, без гнили, домкратится спокойно в штатные места).\nЗа время своего владения практически не ездил на ней, из того, что было сделано (всё за последние 300 км):\n- заменены оба передних крыла на новые оригинальные, покрашены и залиты антикором;\n- заменён радиатор в сборе с вентилятором и патрубками (оригинал АвтоВАЗ, который шёл на конвейер), термостат в сборе с корпусом и антифриз;\n- заменён комплект ГРМ (Continental) с помпой (помпа заводская АвтоВАЗ);\n- заменено лобовое стекло (Fuyao);\n- разобран, почищен и смазан стартер;\n- заменено масло и фильтры (ТО делать не нужно, можно садиться и ехать);\n- заменён вакуумный усилитель тормозов с главным тормозным цилиндром (оригинал);\n- заменены кнопки стеклоподъёмников и обогрева заднего стекла;\n- прокладка клапанной крышки, патрубок ВКГ, часть хомутов системы охлаждения;\n2 года и 10.000 км назад (до меня) заменён резонатор и свечи зажигания.\nСтоит сигнализация с центральным замком, магнитола с Bluetooth и задний парктроник.\nЛетняя резина в отличном состоянии, свежего года выпуска (кажется, 2022).\nКомплект зимней резины на дисках отдам вместе с автомобилем (остаток протектора большой, можно ездить).\nНа учёт встаёт без проблем, ТО проходит (пока ещё есть действующая диагностическая карта), юридически чистая.\nПечка работает, масло и антифриз нигде не текут.\nЗвоните, спрашивайте, смотрите.","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":110900,"View":6,"FotoExist":1,"IsFakePhone":1,"Delta":-77328,"Vin":"","Engine":1,"Drive":1,"Horsepower":87,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312150237,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"19.08.2026 14:13:49","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Седан","Price":355000,"PhoneExist":0,"Source":2,"City":"Бугуруслан","Descr":"Авто в хорошем состоянии без гнили и ржавчины. двигатель не дымит не троит.","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":130000,"View":0,"FotoExist":1,"IsFakePhone":2,"Delta":25614,"Vin":"","Engine":1,"Drive":1,"Horsepower":87,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312133549,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"19.08.2026 12:47:45","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Седан","Price":360000,"PhoneExist":0,"Source":2,"City":"Сарапул","Descr":"Машина в хорошем состоянии ","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":144000,"View":0,"FotoExist":1,"IsFakePhone":1,"Delta":106702,"Vin":"XTA219110FY136180","Engine":1,"Drive":1,"Horsepower":82,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312120828,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"19.08.2026 11:36:18","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Седан","Price":250000,"PhoneExist":0,"Source":2,"City":"Вологда","Descr":"едет","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":240000,"View":0,"FotoExist":1,"IsFakePhone":1,"Delta":-145,"Vin":"","Engine":1,"Drive":1,"Horsepower":82,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312051911,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"18.08.2026 21:06:49","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Седан","Price":400000,"PhoneExist":0,"Source":2,"City":"Арзамас","Descr":"машина в хорошем состоянии","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":130000,"View":0,"FotoExist":1,"IsFakePhone":1,"Delta":137567,"Vin":"","Engine":1,"Drive":1,"Horsepower":82,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312045124,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"18.08.2026 20:04:10","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Лифтбек","Price":435000,"PhoneExist":0,"Source":2,"City":"Архангельск","Descr":"Лада Гранта, в хорошем состоянии,родной пробег, чистый и аккуратный салон,8 клоп,есть фаркоп, простая комплектация\nКузов на твердую 4, косяк по переднему бамперу\nСобственник, третий владелец, машина на учёте на мне. \nПригнал с Вологды, за время владения сделано:\nзамена тормозных колодок в круг\nрегулировка клапанов и замена прокладки\nзамена лобового\nв целом по технике хорошо,есть чем ещё позаниматься","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":146978,"View":1,"FotoExist":1,"IsFakePhone":1,"Delta":148304,"Vin":"","Engine":1,"Drive":1,"Horsepower":87,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312040552,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"18.08.2026 19:33:40","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Лифтбек","Price":450000,"PhoneExist":0,"Source":2,"City":"Балашов","Descr":"Продам Лада Гранта 2014г. Один хозяин. Пробег 105000. Технически полностью исправна. Есть небольшие недочёты по кузову (кое где рыжики, но все таки машина не новая). Стоит сигнализация в автозапуском. 2 комплекта колёс. Цена 450000 руб. Торг присутствует. Подробности в л/с или по тел. 89053220018","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":105000,"View":0,"FotoExist":1,"IsFakePhone":1,"Delta":108184,"Vin":"","Engine":1,"Drive":1,"Horsepower":87,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312010659,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"18.08.2026 16:58:18","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Седан","Price":700000,"PhoneExist":0,"Source":2,"City":"Сочи","Descr":"Продаю личный автомобиль, я 3 хозяин, в моих руках 4 года. Установлена музыка: фронт- 2 пары динамиков прайд апокалипсис, 1 пара рупоров, 12 саб на 1.5 киловатта, голова пионер 520! Шумоизоляция и виброизоляция- двери (5 слоев) крыша(3 слоя) капот и багажник (1слой). Новая летняя резина на рельсах, зимняя резина на штампах в подарок. Так же потолок и стойки перетянуты в черную альконтару видно на фото! Установлен приятный, ненадаедающий басистый выхлоп и сделана грамотная онлайн прошивка. То техничке нужно поменять ремень грм и помпу(работа + запчасти выйдут на 20к) так же у всех грант проблема в том что стучат гидрики, но после того как машина нагревается до рабочей температуры ничего не стучит. По кузову нужно поменять задний бампер ( я ночью сдавал назад и не заметил холмик, наехал и сломал его) так же в меня въехал таксист в левое переднее крыло, до лонжерона не дошел удар, фото скину. Поставил новое крыло и новый бампер. На машине езжу каждый день, ни разу не подвела меня! Заправляю только 95 бензин на Лукойле! Салон очень ухоженный. Звоните, на все вопросы отвечу! Если будете писать, могу не увидеть сразу. За машину не стыдно! Продаю из-за того что купил новый автомобиль.\nАвто в кредите, при покупке сразу его закрою и придоставлю соответствующие документы.\n","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":167000,"View":0,"FotoExist":1,"IsFakePhone":1,"Delta":170107,"Vin":"","Engine":1,"Drive":1,"Horsepower":118,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1312005598,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"18.08.2026 16:33:27","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Седан","Price":250000,"PhoneExist":0,"Source":2,"City":"Соликамск","Descr":"Продам свою машину \nЯ собственник ,учет на мне \nДокументы чистые ,автотека зеленая \n ДВС и кпп работают четко ,без нареканий ,двс 8кл \nДно пороги все целое ,без гнили \nВсе горит ,моргает ,все работает исправно \nЭур,2 эсп ,эл. зеркала , Ц/З ,сигналка с А/З ,регистратор ,чехлы ,ковры ,музыка 4 динамика и магнитола с бт , линзы в фарах , \n14 литье на отличной летнец резине Yokohama \nОчень много всего менял ,кому интересно пишите \n\nИмеются недочеты по кузову в силу возраста ,подустала передняя подвеска ,трещина на лобаше \nДвигатель новый ,весь обслуженный ,родной в багажнике \n\nЦена : 250.000 торг обмен \nСоликамск","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":193111,"View":0,"FotoExist":1,"IsFakePhone":1,"Delta":-54527,"Vin":"","Engine":1,"Drive":1,"Horsepower":87,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1311989544,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"18.08.2026 15:15:24","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Седан","Price":350000,"PhoneExist":0,"Source":2,"City":"Сухой Лог","Descr":"Автомобиль в хорошем состоянии, эксплуатировался бережно.\nОбмен не интересен, на площадку не поставлю. Умеренный торг у капота","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":102940,"View":1,"FotoExist":1,"IsFakePhone":1,"Delta":82713,"Vin":"","Engine":1,"Drive":1,"Horsepower":82,"Sold":0,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1311973451,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"18.08.2026 14:03:25","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Лифтбек","Price":390000,"PhoneExist":0,"Source":2,"City":"Екатеринбург","Descr":"Lada Granta 2014 г.\n\nКуплен новым у официального дилера. Большую часть эксплуатации находился в сельской местности, использовался бережно.\n\nКомплектация Люкс:\n\n* кондиционер;\n* ABS;\n* подогрев передних сидений;\n* электростеклоподъемники;\n* электрозеркала;\n* центральный замок.\n\nВ последнее ТО:\n\n* установлена сигнализация StarLine с автозапуском;\n* новый аккумулятор;\n* заменены задние тормозные магистрали;\n* новые тормозные колодки;\n* заменены масла в двигателе и КПП.\n\nВ комплекте:\n\n* два комплекта колес (лето/зима);\n* три ключа;\n* документы по обслуживанию;\n* запас расходных материалов.\n\nИз недостатков:\n\n* имеются локальные очаги коррозии на кромке капота и в местах крепления защиты (см. фото).\n\nДокументы в полном порядке, автомобиль готов к переоформлению.","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":126000,"View":0,"FotoExist":1,"IsFakePhone":1,"Delta":9026,"Vin":"","Engine":1,"Drive":1,"Horsepower":106,"Sold":1,"Generation":"I"},{"Title":"LADA (ВАЗ) Granta","ID":1311916543,"Year":2014,"Marka":"VAZ","Model":"Granta","Credate":"18.08.2026 8:30:13","Kpp":1,"Volume":"1.6, пер., бен.","Body":"Лифтбек","Price":340000,"PhoneExist":0,"Source":2,"City":"Ульяновск","Descr":"Машина в хорошем состоянии, по кузову есть вмятины и рыжики, по технической части нареканий нет, комплектация без ЭУРа и ABS. Комплект зимней резины в подарок (вся резина отьездила по 1 сезону). Продажа от собственника, торг только при осмотре. Обмен не интересует, салоны просьба не звонить.","CountCar":0,"CountOffer":0,"Wheel":1,"Remont":0,"Distance":99000,"View":0,"FotoExist":1,"IsFakePhone":1,"Delta":10614,"Vin":"","Engine":1,"Drive":1,"Horsepower":87,"Sold":0,"Generation":"I"}]}}
    // ';

    private const OFFERS2 = '
        {"start":"21.08.2026 10:00:38","end":"21.08.2026 10:00:39","command":"data","listid":"1312313079,1312312873,1312299329,1312293440,1312261005,1312227408,1312219357,1312207196,1312165647,1312150237,1312133549,1312120828,1312051911,1312045124,1312040552,1312010659,1312005598,1311989544,1311973451,1311916543","error":false,"result":{"ListContact":[{"UID":1312313079,"Credate":"20.08.2026 12:56:57","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133835140-52fda118/","Phone":"","Contactface":"Иванов Виталий","Images":"https://avatars.avto.ru/get-autoru-vos/20346480/9995885ba42d296818df0f91296c8c6f/1200x900,https://avatars.avto.ru/get-autoru-vos/6214220/fb468977befbf116f7125e3e05fa3b49/1200x900,https://avatars.avto.ru/get-autoru-vos/11719605/ef056e95c49214064b1370d4a5bbe523/1200x900,https://avatars.avto.ru/get-autoru-vos/17637180/57faa7bafa74d740ee0966b8191dcd9f/1200x900"},{"UID":1312312873,"Credate":"20.08.2026 12:55:50","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133835264-64afdf90/","Phone":"","Contactface":"Андрей Е.","Images":"https://avatars.avto.ru/get-autoru-vos/19815194/c98ff8ea6ba403d61cefbd4878b4c030/1200x900,https://avatars.avto.ru/get-autoru-vos/2156362/22d8dd26738f28406ac90a20d2127289/1200x900,https://avatars.avto.ru/get-autoru-vos/4575350/331078acb82e54bd80a50e9bba121b13/1200x900,https://avatars.avto.ru/get-autoru-vos/1728862/3c38741b3d57f9f4194f22b25f4febdf/1200x900,https://avatars.avto.ru/get-autoru-vos/17697421/1402bf18a02e1148d7f3da13c8c16446/1200x900,https://avatars.avto.ru/get-autoru-vos/1728862/3e1963dbbf047cc88fc27369b71feed0/1200x900,https://avatars.avto.ru/get-autoru-vos/18101889/db176b2ad15d5a6548c0614e77b4e6a7/1200x900,https://avatars.avto.ru/get-autoru-vos/18536126/77d45cf8d9b7414fc286d140c59b362e/1200x900,https://avatars.avto.ru/get-autoru-vos/20849947/5db2f0dd229f7acd8928031456432bdf/1200x900,https://avatars.avto.ru/get-autoru-vos/20845579/cd68995bac735ab0b9ce94bc238a4bcf/1200x900,https://avatars.avto.ru/get-autoru-vos/18536126/c6b938381defed8f6f63ae4db9afd656/1200x900,https://avatars.avto.ru/get-autoru-vos/17971448/e4218a8c17b12def99477d157ba75f07/1200x900,https://avatars.avto.ru/get-autoru-vos/2094527/c196e9806836d28cec92c550a0aaa792/1200x900,https://avatars.avto.ru/get-autoru-vos/16673081/50a6564d8b4a12dee5078d102763057e/1200x900,https://avatars.avto.ru/get-autoru-vos/19811519/84a9a12c38ec9ca078195890af89587e/1200x900,https://avatars.avto.ru/get-autoru-vos/2087778/9bbc886923c059d04339f7d8b8baa7af/1200x900,https://avatars.avto.ru/get-autoru-vos/4612068/03d5c744f22020ed92049bd6bc4bd2e0/1200x900,https://avatars.avto.ru/get-autoru-vos/4612068/61eaa6150b4124efae96b2b5d1e9063e/1200x900,https://avatars.avto.ru/get-autoru-vos/16793145/00efbf3b38f5234ed33575a3a22dbdad/1200x900,https://avatars.avto.ru/get-autoru-vos/2181950/c4a92a7f145ce5e540ace6b40de0cdf7/1200x900,https://avatars.avto.ru/get-autoru-vos/17435526/099af71a9343185402746cb9c97fb1c3/1200x900,https://avatars.avto.ru/get-autoru-vos/17999629/71cc1829fb652ce629c60142c7487467/1200x900,https://avatars.avto.ru/get-autoru-vos/19063053/23b3c1dca914da98ed1c1db88ff3593e/1200x900,https://avatars.avto.ru/get-autoru-vos/18005086/2d2e556948ee1c052fcf7e65984ddbe8/1200x900,https://avatars.avto.ru/get-autoru-vos/17645331/e7d7674e093e0ba33c7e455e5ac035e0/1200x900,https://avatars.avto.ru/get-autoru-vos/18154113/fc194370e3230a4488dd2bb4c2c43ffa/1200x900"},{"UID":1312299329,"Credate":"20.08.2026 11:43:07","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133834011-e46f38b2/","Phone":"","Contactface":"Машалбек Тошматов","Images":"https://photo.auto.ru/photo/get-autoru-vos/20390804/l_pSrptRSnWx2cFM9pzMiFERy5lQqqmazOcTnAvqt8oLJargt31bpN2bQCqWreMuS03uK3dFIA6gjUH7YQYkkm9b3k2A3gj2rVWTiIR2vZBS8H645ytNEC8Gi6FlNx9b6MppOXlxGUiLyDOeZ1je-AZaQKaP4YBbmV-4nUO7nvh5ZrA/1200x900,https://photo.auto.ru/photo/get-autoru-vos/17973312/EWpI1yjNPlIBbyAYguFWuC_IxxgdEB8AbuetwNd4KSL_W7BD5edkCVDmDyYmuGIGeerjpd_-wDFbdwS3J-eu1CnwcSERm5mv9vdGCXsSzlAzKx2mOpsCdqgvwyB9-fWxvam6i2iK1QqCIK_SYR052VBg2L7Djd7yquP90--BzkBtF5U/1200x900,https://photo.auto.ru/photo/get-autoru-vos/9729495/Tz3PcN8tFMCPtaqxlSIhrHdizvFZjQTTPqtjQhSMY2Dm2gjJUpSCFPkNRjGd4ElPlOr8yUJenlzynXWMxVwt5Y8MTdl6iXbfF05jPEztCST_j1SjDiHCk7jOFjM3mN2CEdr3iOuVB7_jGxh1xJnXRcjmk8mvc3kPpxH6XcT5R8G8VR4/1200x900,https://photo.auto.ru/photo/get-autoru-vos/4946962/rC7nRp1XG6_MdboMoQZrdxvaD4hFBVqbOQDGVyyPVyesZPdfD68-hOYfmcE7HREbqF9xE13wSdXCTxZosogeq9eRxCBjq-0hiwl063732wuHLg5deNPpu2GwToAaHOZNOKFgaKFiwtIHdWDsehSIjQ6vjz6E8Xm2i6cRqjqymgP8_VQ/1200x900"},{"UID":1312293440,"Credate":"20.08.2026 11:15:36","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133833567-d0d3e496/","Phone":"","Contactface":"Gevorg","Images":"https://avatars.avto.ru/get-autoru-vos/17698338/f3292083366f1a10fd09eff204504c29/1200x900,https://avatars.avto.ru/get-autoru-vos/4575350/8a23d4d6db7643f5acdfe931dc545f4d/1200x900,https://avatars.avto.ru/get-autoru-vos/20446679/1d4c79b9616426f2de5d5dc2892b5e5f/1200x900,https://avatars.avto.ru/get-autoru-vos/4219430/3ea0f888df7337f55fbac513588d951a/1200x900,https://avatars.avto.ru/get-autoru-vos/18006848/76e3f6dcb687c81ddc43c2d80134b1a4/1200x900,https://avatars.avto.ru/get-autoru-vos/19700919/dc39c41ece217c7832fc1b70244bb49d/1200x900,https://avatars.avto.ru/get-autoru-vos/4946962/f8482c826474808a74382d8be1878af4/1200x900,https://avatars.avto.ru/get-autoru-vos/20193191/f6f320407ceb7e381dff4f6d54f7b3d2/1200x900,https://avatars.avto.ru/get-autoru-vos/18068962/fde866279c583339f0c47a90a7f1b6ef/1200x900,https://avatars.avto.ru/get-autoru-vos/7067970/170f4a9a636e0c4d9cc9a131ef52e50d/1200x900,https://avatars.avto.ru/get-autoru-vos/15584432/69c3a0d92f2e178897268fc212152745/1200x900,https://avatars.avto.ru/get-autoru-vos/5489899/e8beb4d6ad29cb6624b1e492cd9ba0e6/1200x900,https://avatars.avto.ru/get-autoru-vos/16976417/99ca856fcdcf2f10d76b51c0ffd11e6e/1200x900,https://avatars.avto.ru/get-autoru-vos/16966486/ce462d0ae8680af92ce02c6bd2e8feae/1200x900,https://avatars.avto.ru/get-autoru-vos/15628906/eb14e2d63639ed1acfbe3edd97612274/1200x900,https://avatars.avto.ru/get-autoru-vos/20191207/d66c278bb41289548aac470e2055da68/1200x900,https://avatars.avto.ru/get-autoru-vos/17999629/ac2c17c8b79348bfb378f27deb42ef2e/1200x900,https://avatars.avto.ru/get-autoru-vos/16793145/3007c473f7eb3a22b154de86ff47cce9/1200x900,https://avatars.avto.ru/get-autoru-vos/2057171/4ee4b7218e06f48d44109ecf435e6d64/1200x900,https://avatars.avto.ru/get-autoru-vos/5156061/fd4aca2f2cbed847bad77f71b1457d0b/1200x900,https://avatars.avto.ru/get-autoru-vos/17709739/7523ce18306ebf24fde63f0f001c09fa/1200x900"},{"UID":1312261005,"Credate":"20.08.2026 7:07:31","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133830631-bfc83897/","Phone":"","Contactface":"Davit Muradov","Images":"https://avatars.avto.ru/get-autoru-vos/17833075/6b4d73a2d836af208a14df534a5803d9/1200x900,https://avatars.avto.ru/get-autoru-vos/4809695/51c416dbb18914ce45719cfb4a674b09/1200x900"},{"UID":1312227408,"Credate":"19.08.2026 21:28:21","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133829224-9586c190/","Phone":"","Contactface":"Максим","Images":"https://avatars.avto.ru/get-autoru-vos/16995928/6928f9b4ba75996d110f8a1c31be8548/1200x900,https://avatars.avto.ru/get-autoru-vos/4829839/b1cb1e7c7c0bf8a5fc43f04d7998ef1f/1200x900,https://avatars.avto.ru/get-autoru-vos/4254438/c079b13e5fafa30b9e343f50df58e3fe/1200x900,https://avatars.avto.ru/get-autoru-vos/20036242/f80caf003c9ae904eb515f2f7b7277b3/1200x900,https://avatars.avto.ru/get-autoru-vos/18621059/5b368bc9eb06cc7d8c3d303ff8a823c4/1200x900,https://avatars.avto.ru/get-autoru-vos/17768325/5377d4b06c2cfaa0057d613ad58fa2c7/1200x900,https://avatars.avto.ru/get-autoru-vos/15268379/4cf93b289c1f2b6ff4a5efb30da04f31/1200x900,https://avatars.avto.ru/get-autoru-vos/17833075/df714ddabdf2394a4ca9c62430ce9f0d/1200x900,https://avatars.avto.ru/get-autoru-vos/21092119/dad4b1d0d61879108461f24567cca567/1200x900,https://avatars.avto.ru/get-autoru-vos/2057538/14def15a31ad98bab566780560246f34/1200x900"},{"UID":1312219357,"Credate":"19.08.2026 20:29:06","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133828707-e0b632d6/","Phone":"","Contactface":"Владимир","Images":"https://photo.auto.ru/photo/get-autoru-vos/20223974/_208Y3GZUNN5W6RAXDvo9c8znD4d9X94jEIcicVrMpogqt8ND1IWXryolktVg9mUXl0XO8eVJaoRup2nIcaW-IfMWMilvFyaQlxDV_cJNztHVW2gd9XspIGwlCjLtiV93Vr4dcSvXnt80mQnk2OghMKHMH3PmYWV0RycGbhviW1yww/1200x900,https://photo.auto.ru/photo/get-autoru-vos/2094527/WdF_uFoVQKc38EI468laVBMs_WacMqFLTbuxGlS3SU9OSbLGyZyRg0V6zt8d1cNMDk5xz_GbgSSGJnJqaWMVMSFfX4ohOvFLW7JyAF92ebRMQDuFpEb8nZ5INzo3QB1AitaA0cT--QP6GNSw51k0fgXRiSOY9v2D5O2sKp6BgJntiw/1200x900,https://photo.auto.ru/photo/get-autoru-vos/16976417/nAfaF7LuSmrCUJKi3PlORz14fx9ZhDODcO9gsjobTSsOMxFdScXXhAkpt8Pr3dDZOfA60IzzJa_30NJ9THf6tgT42Ue3cD6Ye0pfzxXi8arvNIy7MeDGDFF8_-h0EvYYpsG4yqvY-jqwvfrw-phDnyXra77Lq5OKt5NayDOLDDUXmg/1200x900,https://photo.auto.ru/photo/get-autoru-vos/18105905/20_OxqOHtvSpie7RDMs4cBrJ7JnFj9LrbnMP3E7TKTpu8H1eMBqedZaYYKipoXn-QLDyuM-OYEHSVS3-2BuEJb3UYAWum7-OCeBERVUst6TF1QuJkSMtxunGwpQToBDy94JiUtfLf3HuvMjqx5p9ekMcfMR9NEvK3d3zPUldGI6tFA/1200x900,https://photo.auto.ru/photo/get-autoru-vos/20223974/xJBSwiw2dDtlk8accgDrTF-id_upQ7pgwQ5N-RF0aPn78BcMzIKAbiqULuQ1H0ciH9HG7XcWLpZOu1ipNlsjbjvI1_NtpiM9HTDQMuaLBpt_zH1oh_KErLZGUZKKLu6274E89mRbY_EFDsmFT1y-q7jv0HcK5fJjNlK8sz7Dob312g/1200x900,https://photo.auto.ru/photo/get-autoru-vos/20335149/KRNEzYtv_LutGu9NeyFRXnHw_eKM5DwUvI7ra_B0HW7LqguU5pOBHR9VTwhiph-b1geRZjUrdrj1Xi7wOjkhMOv4Q3py9z48iJYSKx4N4CgR8NdIYrX2tG_Rn6e029_KqgbS8JJXT7dztJmnWZrttZZxLyFUqCtes3OiJoJQRjL3BQ/1200x900,https://photo.auto.ru/photo/get-autoru-vos/7067970/FKq-KuuH5nxtCi5hqtheFYsdwFRg_whwsamOs_OgYhvqY_5D7kEBrn-D2tQeI60SYcNfp5fGZSJzC5SuqsmVISqxuzJN3DnmzLpUcgk0v7fIM-IIZlB2F5eI0OoJFAhpyuqv3wkzRmF4BCvkIwTjyBXRPHlQ_KvrzL6cySoQlN0NlA/1200x900,https://photo.auto.ru/photo/get-autoru-vos/20390804/SHQqNfiUoi07CT2Fw0v2YugvxRScLc6IcF5xRSdsuGHsFt6a1lfzgYhSVDWR0nWZPFOy35sTtu_YhXIbopKTIbepD_py0VQsoDSkdUkR_g-mHKPMrzHzCu-Bxu3erOOT0cHFjPBAXzzCm900xhPgctpw9M48TJbVYqalArExwaIPyA/1200x900,https://photo.auto.ru/photo/get-autoru-vos/16813311/QdvYFEs5Q6IjJdYnk7JQmqv46_I0Q079tClrr06B-X53F_mFypRbxXtCKjD4e-_LbIQatJZ-RZnc4q1NAhG-6v91HSudOudtYa3l7xPdlXVkD4ris66eHmUSV0BetQTNkf5bF9FoW3u4hVYq7SAGHQoxGQD6oBkto2Mj2nGdGmJR0Q/1200x900,https://photo.auto.ru/photo/get-autoru-vos/5236428/4mEoJ4JIbPnls1xMpHYzq1BjqWYa1k4C7HbAkMvuLUCET5nj-iMaNOZaXRMIfgFL2ODmxzK8W3y41vpc6VC-z1Ir3QXg8DIbUkRDleS-pji4BKsyeQlX4HWjpZKbgVQCDS-YliV8pde530xStad2hxaA7TgJtn9yCQWWiphbRa_90A/1200x900,https://photo.auto.ru/photo/get-autoru-vos/18143138/nTW50GyQ-45HD43ighSN8SSCafy-XG1-ffCzTQE-4oF8x6M4g1OCVsMIpm_mVo-aqyfKCtxdwQZa_pAhyOFpsuAz9FEifYQjwz2WKyIrL8eVx4wRG_1X6l1_dRPb-qamhrSEgrZi7vi-8b2_9J_r_8SrCyzbBAzPhBU3RRvTcLA0fg/1200x900,https://photo.auto.ru/photo/get-autoru-vos/20358835/Wr1r_N2QA_5eoqY3jBfplFbn8JeZoe7eGU1OfMerrjf5LuCm32HuqdGI0giF8FW88yltiqmckHt6BbBWg08OakqnJmtQ0ELyYGT-ynF7Px_gqc7cNAJRDJoP2aIUendlFMDskugPwRjs3AAMtKKFieK_bcoluInVFpYAtriq_tRrvA/1200x900,https://photo.auto.ru/photo/get-autoru-vos/16569097/8fxDXKGg0Umwit0Jbo2v4QG2O9lgdmIHn4_iEt36szy_wQFyUFEiRRh4T3ekbPAmVGkeD5UDdZ2fmeBG3woPJvAiIMojVIXwszSWDGvdAlJahkNHaQo-1kTlLcgOyVxrWPw2_up0YlONxmFDmYp4Eu0Jk9fxyN2vhVP4u_IKfon0AA/1200x900,https://photo.auto.ru/photo/get-autoru-vos/16995928/xeu5KC1-aIOCBl9ecsMhTPUZhkT3Lbb97DeGhN4BcQlTItVGwaQbQY_xmmLbsEQmCerT8QoPN3UVC5ICbyTK44d3cyFwIjHjeC_tPrkibZ3yqeytu-toEi6Gu92WwDsxbIZx6iT1SMC32waKpjzNLkOGffs-qOmjEAVrW7uOxKncow/1200x900,https://photo.auto.ru/photo/get-autoru-vos/15548469/EqtmQYPOThuQ8KJtTbkNWkcpQFzvOwgCVWb8zGLu8t4ZjGlwy5sjvnnSsiHQPnd6VdMonUnv2D1IxiGuR-pczF3IF1u9CzWHHHqOJ2Z0NWRH1uRV4z57XLraX-5bZfik-uNAMcxMt9BR7eyCfBt2YPuvu0UUIsL54oo0XknTVx4P5w/1200x900,https://photo.auto.ru/photo/get-autoru-vos/18152297/ZJAqOoV8r-B5_-VQqD-ej3i0HYWLn8ZzmQ3ccP6qd6TbXwMcxYblJ1OgWkr5csfkh_6i6K1klDC63qye46lt_ZAIk9D-6gP_hb8k0bgdVc2jxevQqmwDPfP4q7xNzEgNwoBNmDIXc4ZPgObj59PnoG20W0-KxWVr6JT2Ok8Hh9PXXw/1200x900,https://photo.auto.ru/photo/get-autoru-vos/20657148/dWJo52DJSNtzmBn3bNyXs7_QeXBhYmhiSZz3R87tfK80QX7vA_HtFQKZHowxivwYxDn3A0A_6MTfWOTi9GPNmXkx5vgPfDbcHOKVE4K6P92NwxKRhkAjQhXJ3ptbgY50jCZ0DEvCO6iTDk4jpEfeqWxdde4FwHc8wii6t0B5KNdWFQ/1200x900"},{"UID":1312207196,"Credate":"19.08.2026 19:22:34","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133827705-0df7f999/","Phone":"","Contactface":"Частное лицо","Images":"https://photo.auto.ru/photo/get-autoru-vos/17707957/mcxWnmP0kaUAtZquPE9HQWof2Dgp842hQkbbbNX0LlLTJOWFxMp7jWGnuhQHurMqYUkfI2VhUl2bbq26QkLWPqwxypGVcp7RW-qZ9B6x02_MTXluZVpdzTsRhG_fgekoZZW13Or92rqIXr0lQpt-AGufskYyhjFDn7mIck467SmCPLM/1200x900,https://photo.auto.ru/photo/get-autoru-vos/20369579/PxgugjaS9aGvWfncshR3fsxnt156DqRaX2Vea5iXK77efBDEi9ivQ1uUEjX2yWx3Pi9CGZW7kRenUZm6H2g84dmO5gLhdELE6_2qDKBsfvtaWXYcFI6_IISOABvS79Sm5KmfACihiQ9VUkMbLICVLu9pxZzegXVLYQFrkmR_Yo27nWw/1200x900,https://photo.auto.ru/photo/get-autoru-vos/20606978/LMhfrtbrF-zoSxtStNXaKNby9EA1E_3l96b7jWHvp19DnHEm0aJweQQmN19SjhcJQRAn4Ee7tXoSA6BbrmA9K7p0bl9hjU82cpFsbMDHIwkSzaf5uJmQTgIHTF_3NqLNKWoOlgX5zYEDJw7tvZaX0U4HBJKrSD5ZvG33P_99mHnwFuo/1200x900,https://photo.auto.ru/photo/get-autoru-vos/18008089/751xJI9-Qfn6PU-SxmVVcW6wJWbuWT5pU1eAWvcg5TttTYgRbeo2OJ2fAHqCEJBclJKhxxjLwc1uy1le8euZyiKwe2g5Cd0eBCEexUgnWZTWhx6XpjlDk7FonWE8RfDTsOkOSubHZU2LQJfXabs8pefdtyxqN27-4-3aINIzZKImnV0/1200x900,https://photo.auto.ru/photo/get-autoru-vos/5555838/eHL7bKfnfDd-8T3PwGoSlL0KcuFxo9H-HXK_7YtsOyWvtbxzN6JHP9yMBI5yIZdmIus02pR2E6Sn39hyYvjBpN1A62VOgNxrROIcKaZhPsSZ_MoV2BXILEMRvFRnLF7kXS8JmqYmfHJ4Qbe3E0tQ6-CDi4wqLuABse-SpbYJvmKL7ig/1200x900"},{"UID":1312165647,"Credate":"19.08.2026 15:32:29","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133824080-5c2c11f2/","Phone":"","Contactface":"Александр","Images":"https://avatars.avto.ru/get-autoru-vos/18866657/9a4eb570a547314a2bf9b84f3decd8b2/1200x900,https://avatars.avto.ru/get-autoru-vos/16757019/6bfa4cdfa17e400b4f66f94e27cedd48/1200x900,https://avatars.avto.ru/get-autoru-vos/20673651/d77e86b8d1e214768d02bcbffc196633/1200x900,https://avatars.avto.ru/get-autoru-vos/6394190/ac6a377246f92869a71bbe35c4b5fa8a/1200x900,https://avatars.avto.ru/get-autoru-vos/17783177/56703f2acd333b03409678061c4dc4df/1200x900,https://avatars.avto.ru/get-autoru-vos/19785726/c5a01ec3a117ade32540b96bf85061f1/1200x900,https://avatars.avto.ru/get-autoru-vos/16569097/e4ba96101988bbdc9a892d1019803761/1200x900,https://avatars.avto.ru/get-autoru-vos/18621059/65927a1ccc543d631255a06518bde73d/1200x900,https://avatars.avto.ru/get-autoru-vos/20671634/03d91aca7de9fa456f083dd56cef5928/1200x900,https://avatars.avto.ru/get-autoru-vos/17709910/ee36932010c2086a616a00de4722cfa9/1200x900,https://avatars.avto.ru/get-autoru-vos/5479518/7959ed12221104b4b050c353cbb3d676/1200x900,https://avatars.avto.ru/get-autoru-vos/6376310/2547a91af6a65f5ca3a23a93390ce375/1200x900,https://avatars.avto.ru/get-autoru-vos/1705366/6ad7d75e20dc4d78ea2836fe8129a016/1200x900,https://avatars.avto.ru/get-autoru-vos/19793268/a7efba1dc1a3db6a73fbc7978b943fba/1200x900,https://avatars.avto.ru/get-autoru-vos/19063053/54e2a634c89c0a60560678df7aed25cc/1200x900"},{"UID":1312150237,"Credate":"19.08.2026 14:13:49","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133822472-e2cb69bd/","Phone":"","Contactface":"Артём Морозов","Images":"https://avatars.avto.ru/get-autoru-vos/5551788/614836fde0d4c7497cc9e9d877da47a6/1200x900,https://avatars.avto.ru/get-autoru-vos/16757019/9dd08b3c999cb91ce8026ac52c81e75e/1200x900,https://avatars.avto.ru/get-autoru-vos/18720861/12e1aac4d987dc83c98a3b162f2a78d6/1200x900,https://avatars.avto.ru/get-autoru-vos/16124386/f2b23b5f37c7e198f94cd9f193ad8b22/1200x900,https://avatars.avto.ru/get-autoru-vos/5232198/75af475a7c9bec8140575ae39f6d0b4b/1200x900,https://avatars.avto.ru/get-autoru-vos/20390804/64418e0f257145ff015783c8a4fb6937/1200x900,https://avatars.avto.ru/get-autoru-vos/5910495/98cf895e0a1ed01ff6ffc89c6508ea9b/1200x900,https://avatars.avto.ru/get-autoru-vos/18375018/007dcc5310357fae2dfb81b0023fce61/1200x900,https://avatars.avto.ru/get-autoru-vos/15611445/810724222b92b10d8a91a4c2bd7522dd/1200x900,https://avatars.avto.ru/get-autoru-vos/20392446/8cacd6125819202e9600e2df8c9dee8e/1200x900,https://avatars.avto.ru/get-autoru-vos/20193191/3f04ac626222e5e4a462760987388b6d/1200x900,https://avatars.avto.ru/get-autoru-vos/17636325/c7f22c055a9bcdba15439017664b24c7/1200x900,https://avatars.avto.ru/get-autoru-vos/17906453/c0a7821dd72071bfb2fd04c30b87f04e/1200x900"},{"UID":1312133549,"Credate":"19.08.2026 12:47:45","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133820639-423bf6c9/","Phone":"","Contactface":"Андрей Г.","Images":"https://avatars.avto.ru/get-autoru-vos/6034272/40698477d323468836772bdc21c95552/1200x900,https://avatars.avto.ru/get-autoru-vos/16483913/e8c853ff59d352cbb62086c9b7ca30d1/1200x900,https://avatars.avto.ru/get-autoru-vos/18152297/10ba4354dd06c425c517399dc69b3c45/1200x900,https://avatars.avto.ru/get-autoru-vos/18866657/3f08eb6e52dfe10f18b7f32631c214cf/1200x900,https://avatars.avto.ru/get-autoru-vos/17636325/a47168c0244b1a15b2508deef1280724/1200x900,https://avatars.avto.ru/get-autoru-vos/3690741/2e9275202f9677b127a81e0f6a6ae67c/1200x900,https://avatars.avto.ru/get-autoru-vos/20265681/32e101f698a8612bfa8ab223b3ee8742/1200x900,https://avatars.avto.ru/get-autoru-vos/17660749/a3f0306275516c93105ba9f0c885e011/1200x900,https://avatars.avto.ru/get-autoru-vos/18408326/090958cf2cd4f0cf82e68c0bfefa3cf3/1200x900,https://avatars.avto.ru/get-autoru-vos/20358835/bd3e986d07a59dd2c601c211ca5899f1/1200x900,https://avatars.avto.ru/get-autoru-vos/17660749/5e391a758e7e32a70a712e3740848f91/1200x900,https://avatars.avto.ru/get-autoru-vos/20951031/e14bb88fc5bb35eb6facb311f67be39c/1200x900"},{"UID":1312120828,"Credate":"19.08.2026 11:36:18","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133819338-6df3f2cb/","Phone":"","Contactface":"Григорий Иванов","Images":"https://avatars.avto.ru/get-autoru-vos/20657148/2bb2e792500ffc7d0b306773b20efd30/1200x900"},{"UID":1312051911,"Credate":"18.08.2026 21:06:49","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133814867-755cbfc2/","Phone":"","Contactface":"Юрий Жадаев","Images":"https://avatars.avto.ru/get-verba/1030388/2a000001609d713fe4380ce1d36fac8ab0eb/1200x900"},{"UID":1312045124,"Credate":"18.08.2026 20:04:10","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133813927-caaf74d1/","Phone":"","Contactface":"Vadim","Images":"https://avatars.avto.ru/get-autoru-vos/17024389/700ccab515bbc2d4de850eb9904fb65c/1200x900,https://avatars.avto.ru/get-autoru-vos/1655347/fad48c9d762fb1628c6be0ffa6ef9f0f/1200x900,https://avatars.avto.ru/get-autoru-vos/4373680/68b4dd5c5a2eedead26a82a893174696/1200x900,https://avatars.avto.ru/get-autoru-vos/20055049/678d6ad594a16d8234394c770e5b0440/1200x900,https://avatars.avto.ru/get-autoru-vos/20369579/cdfcd25f7c74101e14ae2d5bbd8c7489/1200x900,https://avatars.avto.ru/get-autoru-vos/16503020/2b915ac185e13a65c02773f5f7271949/1200x900,https://avatars.avto.ru/get-autoru-vos/2142377/4a610ce76ae35de9faa75a1151212e06/1200x900,https://avatars.avto.ru/get-autoru-vos/20444157/db974aac6d6df9ed91161c9bc5618826/1200x900,https://avatars.avto.ru/get-autoru-vos/20556383/d4b5a95312380e7794ca3d42f84ca870/1200x900,https://avatars.avto.ru/get-autoru-vos/20446679/f896771b70876a8ed6b7319c4e06e940/1200x900"},{"UID":1312040552,"Credate":"18.08.2026 19:33:40","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133813480-19c3fe8a/","Phone":"","Contactface":"user21022018","Images":"https://avatars.avto.ru/get-autoru-vos/16124386/cc5894ead31a513ef29211a7946b9d65/1200x900,https://avatars.avto.ru/get-autoru-vos/17711405/247fd47aa44267645e33085c38e36de6/1200x900,https://avatars.avto.ru/get-autoru-vos/19815194/21037945d81f0d6d5563e3e1c83ddf9f/1200x900,https://avatars.avto.ru/get-autoru-vos/17755365/2b33c54813b79460b5484291461eeb27/1200x900,https://avatars.avto.ru/get-autoru-vos/20939166/7e89664ab1e9b8f87bd98710d32c7d58/1200x900,https://avatars.avto.ru/get-autoru-vos/16149001/99cffb886126766e21f86a146c399f60/1200x900"},{"UID":1312010659,"Credate":"18.08.2026 16:58:18","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133810900-b03b1172/","Phone":"","Contactface":"кирилл б.","Images":"https://photo.auto.ru/photo/get-autoru-vos/17674678/oRi9y6FN-uD5luNLoVJ01Jgx-jj5qfJIAPjrIGN4aET3bolf9aMWR_AosodhQGYJuXmZXakrb4IQGYGiU2l3ktbnZshBWicPd7oHt_qggkc3hIr5jMn5rMOEp_89duxFxtGmX42mUzwywr54qdUVr_zOU6S_VLAghl_MmAk39dxL0g/1200x900,https://photo.auto.ru/photo/get-autoru-vos/5910495/22LFrhDB6oTZPERDIQCxjOE22CJUEEf8Ts68vqxcmfEJGLAD2WEaD2sb32A7HzcvDsbJExrMM7ekC8FvglKGYCrIk-43Vn9rTwcuUDeDYVhIAwpVlDchS0JdvZvc6C3S29MEv-P4IFOgtFwyd9M21Zg5vMAwwofqBlvB6wHxFgbLmQ/1200x900,https://photo.auto.ru/photo/get-autoru-vos/18129838/o0QbWcmsINNANOznkYM_pMvAZhDhv0BtEWa1OdLLwBpveFMTnRAzGYXj5w9j5UvzcCrwxkopsgut41pOQnjyYS10x2JkvqHFKp3H1WMloJvp9UK-05iQUXImj2Tix-aGtTjpiNwKPO0XgRkWKzq_fvs5axTd81TQwlUR8suvB4nATg/1200x900,https://photo.auto.ru/photo/get-autoru-vos/16308788/eG_66vdOa4c5zMZDuqytxQhQIaZE2ZNeHFg-rDJ1q1akjoZZFWEYsIp9oYYBUkfIm8MJ6c4xWEsDWR75mLmP2kKZrwLZlrw8ScOpi8CFrLYklAPYH5DbXKggP-UHxI7lMEZcLAwHJdZK6mAmmZuSLfh8J91Qr9mtF4Q7FwLHsFQGrA/1200x900,https://photo.auto.ru/photo/get-autoru-vos/17640555/jGNFK5SdvkReSFJPpCcX3lrk0OmPmzXoL5pUePFd5DL9q-arTwnOPHxXAjsBa2wz6qRJAv2Fj5rvZVVjXZQ_vM_ljkDyjVuKwT_ZLY_X2m5Scw2VEIHOLq9g9RpLLvtG9pIxjNhRNcrhM6U9xQ15Ca5TUN3_FoBS_EykyVbdpHAsXA/1200x900,https://photo.auto.ru/photo/get-autoru-vos/2181439/bgLitvhSBwUSC8--KEm_4WY_dJ1AZdcMNz12SWCLxv7iSnBCsk9oEcTHehROqPkDEhMxevlc9MbZbZg_TY_Awk7zgMzBxiNBRv8tnCPEcGDwsUhTjubKlHJskq7b-rXayDaHxWrUV1vpozbLhzlZ-HT3QnN0q9XWuXnAQ15TNfExlA/1200x900,https://photo.auto.ru/photo/get-autoru-vos/6219917/mxveo3JmdKctUj4l8WvR14sVjmAY7CehA3ZdkrKO3hoitD26R8dBs9_6lzVea8GULgNSTRaEtKT9xNHfYI8sQovaqQCf1CLs4U7EUOfZueIo_kYcXacyMKyg8-Ylso_JGuDUhlhReOS_yW8hmqV25cZ6tOakaiNDsM3bTRetohNV2A/1200x900,https://photo.auto.ru/photo/get-autoru-vos/20390804/ajwxZc8v9dRS17ejSz1KBhxQ1uuOSPY45uzsMaTPKY-1rBA2lath0ByGhToiHGIkHj7GikB39Sr3_xvcZzP7cD5FGKcvf2Okwd__8yWutVKRuSN7Is2DgxG3TNdvfJ1E2M-qUd-73Lm__PeY8JH0MO23S6AK3amgKOnifuza8T_Yqg/1200x900,https://photo.auto.ru/photo/get-autoru-vos/4698679/jZON9wpXPGfVd_ObFktRVFhRARbEbxMMxY8ga8tRA-E6LnzUTVKyRgJwxJ1-9eT-DvkOyG9f8FyPDenRvqmO3_1-ZzaO7ywoWcWKL_ZKilZjrEeIPxv_hK6Rugd0TjrXf0zYYQTWd5jWjavAGh9vXmJkaGc3lUSFMkJ-71nZLyZ0bw/1200x900,https://photo.auto.ru/photo/get-autoru-vos/19298886/V7Qsj_7RYK9r_5UfLJ_yVJKFZ9gB8cLbEYlKg-qDe08R_fGdZFZQfGZiniVd4Sp7DaJXFjiLlHCLEST-n13gKN15mlV8UXWBvg4yNU9HgT4C31sFQB1xIoI1CweikGAvuK4F4J3KBswq1XakNvawN5M1blE2g664jOpMwi7NXZV0sw/1200x900"},{"UID":1312005598,"Credate":"18.08.2026 16:33:27","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133810509-eb626c94/","Phone":"","Contactface":"Рома Дровнев","Images":"https://photo.auto.ru/photo/get-autoru-vos/18306378/XX7GoZ7fmFuhD1Ep3CyIHq7-yweYcWStbiG0AZmiv839BpJlyIza1Wo5FNwT4HRS64iw6ckfnDOpeSvpVjGaD9m2aH_wod9o8zbb6rjoR3r0hNU9eIyDkaCmjHwIGsEcn3sc8Im6UpPBGFTPtAygjYlGQARMnvTCmyU4a6NCK_OuF4k/1200x900,https://photo.auto.ru/photo/get-autoru-vos/20140694/8sSOw22m-pMN7IQ2UXSO0HdpGRy0_ZCjLu1dg36FqyBtkFeRo3CoD6Onatdkb0G7hVVoXFjni8RYS_OiGksoeF2Vj6ejvRlYEXrhNdGAwyiR4h0UcRVSYqylbUeTt-RDVs-7dKwlf85mB1bVbq37eiZJNRZpNqAFvqFlOpGuEwf6MVU/1200x900"},{"UID":1311989544,"Credate":"18.08.2026 15:15:24","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133809228-896ff17b/","Phone":"","Contactface":"Частное лицо","Images":"https://avatars.avto.ru/get-autoru-vos/20055049/1f774e50a9698d86072bdf747a660b80/1200x900,https://avatars.avto.ru/get-autoru-vos/20193191/7b37c6a6b3f50605b7da5930526d8df1/1200x900,https://avatars.avto.ru/get-autoru-vos/18154113/0efe3124d08606810b9ab60cb54459e2/1200x900,https://avatars.avto.ru/get-autoru-vos/17786981/2ef7db19f2397a8b32d0c0cf775c04f5/1200x900,https://avatars.avto.ru/get-autoru-vos/6219917/968ff134fa3c1397a59d8f5d9c0ecce2/1200x900,https://avatars.avto.ru/get-autoru-vos/21151957/c0342f012f32724cc4aab569f9e7f205/1200x900,https://avatars.avto.ru/get-autoru-vos/5236428/4163cfaef78184667ca9187cf4d1ae60/1200x900"},{"UID":1311973451,"Credate":"18.08.2026 14:03:25","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133807125-3d9d336a/","Phone":"","Contactface":"Роман Кабаков","Images":"https://avatars.avto.ru/get-verba/216201/2a000001609b5fd0bf4cfe06ce1e5f68185e/1200x900"},{"UID":1311916543,"Credate":"18.08.2026 8:30:13","Url":"https://auto.ru/cars/used/sale/vaz/granta/1133800583-8eef58b9/","Phone":"","Contactface":"Глеб","Images":"https://avatars.avto.ru/get-autoru-vos/17712067/ade6afbb10481657a5b1d30f08c3f781/1200x900,https://avatars.avto.ru/get-autoru-vos/19873021/939a88bbfdbdf12d6885d547a5bb7db5/1200x900,https://avatars.avto.ru/get-autoru-vos/21068092/c0cd9b88e3191856962be4b066740004/1200x900,https://avatars.avto.ru/get-autoru-vos/19700919/085b1d1b7de3b88270b66af77be0e1db/1200x900,https://avatars.avto.ru/get-autoru-vos/18536126/670a90c92aedbd2203f0071ccb1cbaf8/1200x900,https://avatars.avto.ru/get-autoru-vos/6394190/4dd6bb88dfe8a6e7fdc80edd4cc639c8/1200x900,https://avatars.avto.ru/get-autoru-vos/2158987/6332b5addb998e6965a5c07b648dddc4/1200x900,https://avatars.avto.ru/get-autoru-vos/2039028/8e74ffdcf3198253673ebafbf4204e2e/1200x900,https://avatars.avto.ru/get-autoru-vos/20507677/1f9c771884e9e6414b38a161523daf6e/1200x900,https://avatars.avto.ru/get-autoru-vos/15049662/c420f07b5c240edabd808c26d0ec66b9/1200x900,https://avatars.avto.ru/get-autoru-vos/19700919/67a2762121bc15659604840dd0afeb66/1200x900,https://avatars.avto.ru/get-autoru-vos/16277572/f280360fee0d2c7879595265c1d9dd2f/1200x900,https://avatars.avto.ru/get-autoru-vos/18105905/79187657599e3d3657c01ff187e1b0cf/1200x900,https://avatars.avto.ru/get-autoru-vos/9729495/bdfd1fe7dd5c94c1ed43c919a7139d14/1200x900,https://avatars.avto.ru/get-autoru-vos/20360782/2f69a7653ae39441aa35950f0ee413ac/1200x900,https://avatars.avto.ru/get-autoru-vos/17714294/05217ae722d698a0f72e90a1df00b5ba/1200x900,https://avatars.avto.ru/get-autoru-vos/5736406/77b8b3b2f84abc0f1d916694c8ae7dab/1200x900"}]}}

    ';
}

