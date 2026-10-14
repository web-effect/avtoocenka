<?php
// 1. Город
// 2. Марка

if (!function_exists('getCityData')) {
    function getCityData(string $city)
    {
        $data = null;
        $index = null;
        
        if ($data === null) {
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
        }
        
        $result = [];
        
        foreach ($data as $item) {
            if (mb_strtolower($item['name']) !== mb_strtolower($city)) {
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
            
            $result[] = [
                'id'   => $item['id'],
                'name' => $item['name'],
                'path' => $path,
                'data' => $item,
            ];
        }
        
        return $result;
    }

}

if($_POST['type'] == "manual"){
    return [
        'step' => 'manual'
    ];
}


if($_POST['type'] == "vin"){
    return [
        'step' => 'vin'
    ];
}

return $_POST;