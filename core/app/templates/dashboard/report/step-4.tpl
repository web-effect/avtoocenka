<div class="report__fourth">
    <div class="report__title">Сведения об отчёте</div>

    <div class="report__wrapper">
        <div class="">
            <section class="report__block">
                <h2 class="report__block-title">
                    Контрольный список данных отчёта
                </h2>
                <div class="">
                    {$list = [
                        [
                            "name" => "Идентификационный номер (VIN)",
                            "field" => "vin"
                        ], 
                        [
                            "name" => "Марка",
                            "field" => "brand_name"
                        ], 
                        [
                            "name" => "Модель",
                            "field" => "model_name"
                        ], 
                        [
                            "name" => "Год выпуска",
                            "field" => "year"
                        ], 
                        [
                            "name" => "Тип кузова",
                            "field" => "configuration_name"
                        ], 
                        [
                            "name" => "Двигатель",
                            "field" => "engine_type"
                        ], 
                        [
                            "name" => "Объём двигателя",
                            "field" => "engine_capacity"
                        ], 
                        [
                            "name" => "Привод",
                            "field" => "gear_type"
                        ], 
                        [
                            "name" => "Коробка передач",
                            "field" => "transmission"
                        ], 
                        [
                            "name" => "Модификация",
                            "field" => "modification_name"
                        ], 
                        [
                            "name" => "Положение руля",
                            "field" => "steering_wheel"
                        ], 
                        [
                            "name" => "Номер кузова (кабины)",
                            "field" => "body_number"
                        ], 
                        [
                            "name" => "Номер двигателя",
                            "field" => "engine_number"
                        ], 
                        [
                            "name" => "Пробег",
                            "field" => "mileage"
                        ], 
                        [
                            "name" => "Регион продажи",
                            "field" => "region"
                        ], 
                        [
                            "name" => "Техническое состояние",
                            "field" => "technical_condition"
                        ], 
                        [
                            "name" => "Возраст",
                            "field" => "age"
                        ], 
                        [
                            "name" => "Государственный регистрационный знак(?)",
                            "field" => "registration_certificate"
                        ], 
                        [
                            "name" => "Владелец (Собственник)",
                            "field" => "owner"
                        ]
                    ]}
                    
                    {* {foreach $list as $el}
                        <div class="details__block-row">
                            <div class="details__block-name">{$el['name']}</div>
                            <div class="details__block-value"
                                x-show="report.vehicle?.{$el.value}"
                                x-text="report.vehicle?.{$el.value}"
                            ></div>
                            <div class="details__block-value-empty" x-show="!report.vehicle?.{$el.value}">Не указано</div>
                        </div>
                    {/foreach} *}

                    <ul class="report__table">
                        {foreach $list as $el}
                            {$value = "report.vehicle?.{$el.field}"}
                            {if $el.field == 'owner'} 
                                {$value = "report.owner?.fullname"}
                            {else if $el.field == 'age'} 
                                {$value = "vehicleAge"}
                            {else if $el.field == 'engine_type'} 
                                {$value = "Dict.engineTypes[report.vehicle?.engine_type]?.name ?? report.vehicle?.{$el.field}"}
                            {else if $el.field == 'gear_type'} 
                                {$value = "Dict.gearTypes[report.vehicle?.gear_type]?.name ?? report.vehicle?.{$el.field}"}
                            {else if $el.field == 'transmission'} 
                                {$value = "Dict.transmissions[report.vehicle?.transmission]?.name ?? report.vehicle?.{$el.field}"}
                            {else if $el.field == 'steering_wheel'} 
                                {$value = "Dict.steeringWheels[report.vehicle?.steering_wheel]?.name ?? report.vehicle?.{$el.field}"}
                            {/if}

                            <li class="report__table-item">
                                <div class="report__table-row">
                                    <div>{$el['name']}:</div>

                                    <div class="">
                                        <b class="" x-show="{$value}" x-text="{$value}"></b>
                                        <span class="text-muted" x-show="!{$value}">Не указано</span>
                                    </div>
                                </div>
                            </li>
                        {/foreach}
                    </ul>
                </div>
            </section>
            
            <section class="report__block">
                <h2 class="report__block-title">Реквизиты отчёта</h2>
                <form class="report__block-inner">
                    <div class="details__description">
                        <div class="details__description-name">
                            Дата оценки
                        </div>
                        <div class="details__description-value">
                            <span class="" x-text="$dateFormat(report.evaluation_date)"></span>
                        </div>
                    </div>
                    <div class="report__row report__row2">
                        {* <div class="field">
                            <span class="input" x-text="$dateFormat(report.evaluation_date)" readonly required></span>
                            <div class="field__label">Дата оценки</div>
                            <div class="field__description">Дата, на которую проводится оценка</div>
                        </div> *}

                        {* {$params = [
                            'name' => 'report_date',
                            'label' => 'Дата составления отчёта',
                            'placeholder' => '',
                            'type' => 'date',
                            'attrs' => 'required x-model="report.report_date" @change="change(\'report_date\', $event.target.value)"'
                        ]}
                        {include 'components/form-field' params=$params} *}

                        {$params = [
                            'name' => 'agreement_date',
                            'label' => 'Дата договора',
                            'placeholder' => '',
                            'type' => 'date',
                            'attrs' => 'required x-model="report.agreement_date" @change="change(\'agreement_date\', $event.target.value)"'
                        ]}
                        {include 'components/form-field' params=$params}

                        {$params = [
                            'name' => 'agreement_number',
                            'label' => 'Договор',
                            'placeholder' => '',
                            'attrs' => 'required x-model="report.agreement_number" @change="change(\'agreement_number\', $event.target.value)"'
                        ]}
                        {include 'components/form-field' params=$params}

                        {$params = [
                            'name' => 'report_number',
                            'label' => 'Номер отчёта',
                            'placeholder' => '',
                            'attrs' => 'required x-model="report.report_number" @change="change(\'report_number\', $event.target.value)"'
                        ]}
                        {include 'components/form-field' params=$params}

                        {$params = [
                            'name' => 'evaluation_price',
                            'label' => 'Стоимость проведения оценки',
                            'placeholder' => '0',
                            'type' => 'number',
                            'classes' => '_price',
                            'attrs' => 'required x-model="report.evaluation_price" @change="change(\'evaluation_price\', $event.target.value)"'
                        ]}
                        {include 'components/form-field' params=$params}
                    </div>
                </form>
            </section>
            {nav}
            {* <div class="report__buttons details__buttons">
                <button class="report__prev report__btn" @click="changeStage('Заказчик')">{'/app/img/icons/arrow-right-thin.svg'|svg} Назад</button>
                <button class="report__form-report report__btn" @click="changeStage('Документы')">{'/app/img/icons/folder-download.svg'|svg} Сформировать отчёт</button>
            </div> *}
        </div>

        <div class="report__right">
            <div class="report__alert">
                {'/app/img/icons/info-icon.svg'|svg}
                <span>
                    Проверьте соответствие данных Контрольного списка идентификационным данным,
                    указанным в <b>СТС</b> или <b>ПТС</b>.
                </span>
            </div>
            {if !$profile.is_complete}
                <div class="report__alert _warning">
                    {'/app/img/icons/alert.svg'|svg}
                    <span>
                        Обязательные поля в <b>сведениях о ЧПО</b> либо сведениях об оценщике и юридическом лице не заполнены. Возможно некорректное отображение данных в отчёте об оценке.
                    </span>
                </div>
            {/if}
        
            <div class="details__result">
                <div class="details__result-title">Итог по расчёту</div>
                <div class="details__result-list">
                    <div class="details__result-row">
                        <div class="details__result-name">Средняя цена</div>
                        <div class="details__result-value">
                            <span x-text="$numberFormat(report.price, '₽')"></span>
                        </div>
                    </div>
                    <div class="details__result-row">
                        <div class="details__result-name">
                            Корректировка
                            (<span x-text="$numberFormat(report.discount * -1, '%')"></span>)
                        </div>
                        <div class="details__result-value">
                            <span x-text="$numberFormat(report.discount_price * -1, '₽')"></span>
                        </div>
                    </div>
                </div>
                <div class="details__result-cost">
                    <div class="details__result-label">Итоговая стоимость</div>
                    <div class="details__result-price">
                        <span x-text="$numberFormat(report.price - report.discount_price, '₽')"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>