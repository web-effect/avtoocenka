<?php
// ввести в Console: require_once MODX_CORE_PATH . 'app/db/migrations.php';
require_once MODX_CORE_PATH . "app/modules/eloquent/bootstrap.php";

/** Отчёты */
Table::create('app_reports', function ($table) {
    $table->increments('id');

    $table->unsignedInteger('user_id')->index();
    $table->unsignedInteger('step')->default(1);

    $table->json('vehicle')->nullable(); // Данные машины
    $table->json('customer')->nullable(); // Сведения о заказчике оценки
    $table->json('owner')->nullable(); // Сведения о собственнике АМТС

    //$table->unsignedInteger('price')->default(0); // Цена (с auto.ru)
    $table->unsignedInteger('discount')->default(0); // Корректировка цены

    $table->unsignedInteger('evaluation_price')->nullable(); // Цена оценки
    $table->date('evaluation_date')->nullable(); // Дата оценки

    $table->string('file')->nullable(); // Отчёт
    $table->json('sent_emails')->nullable(); // Кому отправлены письма

    $table->timestamps();
});

Table::addColumn('app_reports', 'file_downloaded', function ($table) {
    $table->boolean('file_downloaded')->default(false); // файл скачан
});

Table::addColumn('app_reports', 'api_prices', function ($table) {
    $table->json('api_prices')->nullable();
});

Table::addColumn('app_reports', 'api_offers', function ($table) {
    $table->json('api_offers')->nullable();
});

Table::addColumn('app_reports', 'report_date', function ($table) {
    $table->date('report_date')->nullable(); // Дата отчёта
});

Table::addColumn('app_reports', 'report_number', function ($table) {
    $table->string('report_number')->nullable(); // Номер отчёта
});

Table::addColumn('app_reports', 'agreement_date', function ($table) {
    $table->date('agreement_date')->nullable(); // Дата договора
});

Table::addColumn('app_reports', 'agreement_number', function ($table) {
    $table->string('agreement_number')->nullable(); // Номер договора
});