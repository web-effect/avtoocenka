<?php
namespace App\Services;

class EvaluationService
{
    private static $api_key = 'cheb0153-33e76efcc615deec53fda63a9e0a7479a612a99d';
    

    public static function getCrumbs()
    {
        $url = 'https://apiauto.ru/1.0/search/cars/breadcrumbs?bc_lookup=BMW';

        $headers = [
            'x-authorization: cheb0153-33e76efcc615deec53fda63a9e0a7479a612a99d',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Ошибка cURL: ' . curl_error($ch);
        }

        curl_close($ch);

        $data = json_decode($response, true);

        return $data;
    }

    public static function getCity(string $city)
    {
        $file = MODX_BASE_PATH . 'assets/components/cities/rid.json';
        
        if (!file_exists($file)) {
            return [];
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        if (!is_array($data)) {
            return [];
        }
        
        $index = [];
        
        foreach ($data as $item) {
            $index[$item['id']] = $item;
        }
        
        $result = [];
        $search = mb_strtolower(trim($city));
        
        foreach ($data as $item) {
            // Ищем только точное совпадение названия
            if (mb_strtolower($item['name']) !== $search) {
                continue;
            }
            
            $path = [];
            $ids = array_map('trim', explode(',', $item['path']));
            
            foreach ($ids as $id) {
                if (isset($index[$id])) {
                    $path[] = $index[$id]['name'];
                }
            }
            
            if (!in_array($item['id'], $ids, true)) {
                $path[] = $item['name'];
            }
            
            // Если в пути меньше 4 уровней — это не город
            // Россия → округ → область → город
            if (count($path) < 4) {
                continue;
            }
            
            $result[] = [
                'id'   => $item['id'],
                'name' => $item['name'],
                'path' => implode(', ', $path)
            ];
        }
        
        return $result;
    }
    
    public static function getModels()
    {
        $client = new Client();
        
        $response = $client->get('https://apiauto.ru/1.0/search/cars/breadcrumbs', [
            'headers' => [
                'x-authorization' => self::$api_key,
            ],
        ]);
        
        $data = json_decode($response->getBody()->getContents(), true);
        
        $entities = [];
        
        foreach ($data['breadcrumbs'] as $breadcrumb) {
            if (($breadcrumb['meta_level'] ?? null) === 'MARK_LEVEL') {
                $entities = array_map(function ($entity) {
                    return [
                        'id'   => $entity['id'],
                        'name' => $entity['name'],
                    ];
                }, $breadcrumb['entities']);
        
                break;
            }
        }
        
        return $entities;
    }
}