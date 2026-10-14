<?php
$report_stage = "АМТС";

$report = $modx->getObject('modResource', $report_id);

if($report){
    // АМТС
    $car_name = $report->getTVValue('car_name');
    $car_vin = $report->getTVValue('car_vin');
    if($car_vin && $car_name){
        $report_stage = "Оценка";
    }
    
    // Оценка
    $car_discount = $report->getTVValue('car_discount');
    if($car_discount){
        $report_stage = "Заказчик";
    }
    
    // Заказчик
    $client_name = $report->getTVValue('client_name');
    if($client_name){
        $report_stage = "Отчёт";
    }
    
    // Отчёт
    $report_date = $report->getTVValue('report_date');
    $report_summary = $report->getTVValue('report_summary');
    if($report_date && $report_summary){
        $report_stage = "Документы";
    }
}

return $report_stage;