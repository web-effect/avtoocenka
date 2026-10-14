{extends 'block_base.tpl'}
{block 'header'}
{/block}
{block 'page_content'}
    {'AuthCheck'|snippet nocache}

    <div class="valuation" x-data="valuation()">
        <div class="valuation__steps">
            <template x-for="item in stages" :key="item">
                <div
                    class="valuation__step"
                    :class="{ active: stage === item }"
                >
                    <span x-text="'Шаг ' + (stages.indexOf(item) + 1) + ' —'"></span>
                    <span x-text="item"></span>
                </div>
            </template>
        </div>
        
        <div class="valuation__steps-mobile">
            <div class="valuation__steps-mobile-items">
                <template x-for="(item, index) in stages" :key="item">
                    <div
                        class="valuation__steps-mobile-item"
                        :class="{ active: index <= currentStep }"
                    ></div>
                </template>
            </div>
            <div class="valuation__steps-mobile-wrapper">
                <div
                    class="valuation__steps-mobile-progress"
                    x-text="'Шаг ' + (currentStep + 1) + ' из ' + stages.length"
                ></div>
                <div
                    class="valuation__steps-mobile-title"
                    x-text="stage"
                ></div>
            </div>
        </div>
        
        <div class="valuation__blocks"> {*убрать?*}
            <div class="valuation__stage"> {*убрать?*}
                <div class="valuation__first" x-show="stage == 'АМТС'">
                    <div class="valuation__top">
                        <div class="valuation__wrapper">
                            <div class="valuation__block"> 
                                <div class="valuation__block-title">Сведения об АМТС</div>
                                <div class="valuation__block-inner">
                                    <div class="valuation__row valuation__row2">
                                        {$params = [
                                            'name' => 'name',
                                            'label' => 'Марка'
                                        ]}
                                        {include 'components/form-field' params=$params}
                                        
                                        {$params = [
                                            'name' => 'name',
                                            'label' => 'Модель'
                                        ]}
                                        {include 'components/form-field' params=$params}
                                    </div>
                                    <div class="valuation__row valuation__row2">
                                        {$params = [
                                            'name' => 'name',
                                            'label' => 'Год'
                                        ]}
                                        {include 'components/form-field' params=$params}
                                        
                                        {$params = [
                                            'name' => 'name',
                                            'label' => 'Модификация (необязательно)',
                                            'placeholder' => 'Седан, хэтчбек, универсал...'
                                        ]}
                                        {include 'components/form-field' params=$params}
                                    </div>
                                    <div class="valuation__row valuation__row2">
                                        <div class="valuation__column">
                                            {$params = [
                                                'name' => 'name',
                                                'label' => 'VIN',
                                                'placeholder' => 'Не указывается',
                                                'id' => 'vinInput'
                                            ]}
                                            {include 'components/form-field' params=$params}
                                            
                                            <label class="valuation__checkbox">
                                                <div class="valuation__checkbox-wrapper">
                                                    <input class="valuation__checkbox-input" name="vin" type="checkbox"/>
                                                    <span class="valuation__checkbox-check"></span>
                                                </div>
                                                <div class="valuation__checkbox-text">
                                                    Нет VIN — указать Кузов №
                                                </div>
                                            </label>
                                        </div>
                                        
                                        {$params = [
                                            'name' => 'name',
                                            'label' => 'Кузов №',
                                            'placeholder' => 'SB1234567',
                                            'description' => 'Серия + номер кузова по ПТС/СТС',
                                            'id' => 'bodyNumber'
                                        ]}
                                        {include 'components/form-field' params=$params}
                                    </div>
                                    <div class="valuation__carnumber">
                                        <div class="valuation__carnumber-left">
                                            {$params = [
                                                'name' => 'name',
                                                'label' => 'Номер',
                                                'placeholder' => 'А000АА',
                                                'description' => 'Допустимые буквы: А,В, Е, К, М, Н, О, Р, С, Т, У, Х.',
                                                'id' => 'carNumber'
                                            ]}
                                            {include 'components/form-field' params=$params}
                                            
                                            {$params = [
                                                'name' => 'name',
                                                'label' => 'Регион',
                                                'placeholder' => '77',
                                                'id' => 'region'
                                            ]}
                                            {include 'components/form-field' params=$params}
                                        </div>
                                        
                                        <div class="valuation__carnumber-plate">
                                            <div class="valuation__plate-left">
                                                А<span>000</span>AA
                                            </div>
                                            <div class="valuation__plate-right">
                                                <div class="valuation__plate-region">77</div>
                                                <div class="valuation__plate-flag">
                                                    <div class="valuation__plate-country">RUS</div>
                                                    <div class="valuation__plate-img">
                                                        {'/app/img/icons/flag-russia.svg'|svg}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="valuation__block-bottom">
                                <button class="valuation__search button">Поиск по базе</button>
                                <div class="valuation__found">
                                    {'/app/img/icons/checkmark-circle.svg'|svg}
                                    <span>Автомобиль найден</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="valuation__notification">
                            <div class="valuation__notify">
                                {'/app/img/icons/info-icon.svg'|svg}
                                <span>
                                    <b>Кузов №</b> — указывается только при отсутствии VIN, либо при оценке автомобилей с правым расположением руля.
                                </span>
                            </div>
                            <div class="valuation__notify">
                                {'/app/img/icons/info-icon.svg'|svg}
                                <span>
                                    В <b>VIN</b> разрешено использовать только латинские буквы (кроме I, o, q) и арабские цифры.
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="valuation__specification">
                        <div class="valuation__title">Уточнение характеристик</div>
                        <div class="valuation__items">
                            <div class="valuation__wrapper">
                                <div class="valuation__block">
                                    <div class="valuation__block-title">Сведения об АМТС</div>
                                    <div class="valuation__block-inner valuation__list">
                                        {$arr = ['Механика', 'Автомат', 'Вариатор', 'Робот']}
                                        {foreach $arr as $item}
                                            <label class="valuation__radio">
                                                <input type="radio" name="radio" value="{$item}">
                                                <div class="valuation__radio-value">{$item}</div>
                                            </label>
                                        {/foreach}
                                    </div>
                                </div>
                                <div class="valuation__block">
                                    <div class="valuation__block-title">Номер двигателя</div>
                                    <div class="valuation__block-inner">
                                        <div class="valuation__row valuation__row2">
                                            {$params = [
                                                'name' => 'name',
                                                'label' => 'Номер',
                                                'placeholder' => '3AR - FE23456',
                                                'description' => 'Необязательно — указывается, если есть в документах',
                                                'id' => 'engineNumber'
                                            ]}
                                            {include 'components/form-field' params=$params}
                                        </div>
                                    </div>
                                </div>
                                <div class="valuation__block">
                                    <div class="valuation__block-title">Привод</div>
                                    <div class="valuation__block-inner valuation__list">
                                        {$arr = ['Передний', 'Задний', 'Полный']}
                                        {foreach $arr as $item}
                                            <label class="valuation__radio">
                                                <input type="radio" name="radio" value="{$item}">
                                                <div class="valuation__radio-value">{$item}</div>
                                            </label>
                                        {/foreach}
                                    </div>
                                </div>
                                <div class="valuation__block">
                                    <div class="valuation__block-title">Рабочий объём, см3</div>
                                    <div class="valuation__block-inner valuation__list valuation__list-alt">
                                        {$arr = [1000, 1200, 1400, 1500, 1600, 1800, 2000, 2200, 2400, 2500, 3000, 3500, 4000, 4500, 5000]}
                                        {foreach $arr as $item}
                                            <label class="valuation__radio">
                                                <input type="radio" name="radio" value="{$item}">
                                                <div class="valuation__radio-value">{$item}</div>
                                            </label>
                                        {/foreach}
                                    </div>
                                </div>
                                <div class="valuation__block">
                                    <div class="valuation__block-title">Мощность, л.с.</div>
                                    <div class="valuation__block-inner valuation__list-alt">
                                        {$arr = [60, 75, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 220, 249, 250, 300, 350, 400]}
                                        {foreach $arr as $item}
                                            <label class="valuation__radio">
                                                <input type="radio" name="radio" value="{$item}">
                                                <div class="valuation__radio-value">{$item}</div>
                                            </label>
                                        {/foreach}
                                    </div>
                                </div>
                                <div class="valuation__block">
                                    <div class="valuation__block-title">Техническое состояние</div>
                                    <div class="valuation__block-inner valuation__list">
                                        {$arr = ['Новое', 'Отличное', 'Хорошее', 'Удовлетворительное', 'Неудовлетворительное']}
                                        {foreach $arr as $item}
                                            <label class="valuation__radio">
                                                <input type="radio" name="radio" value="{$item}">
                                                <div class="valuation__radio-value">{$item}</div>
                                            </label>
                                        {/foreach}
                                    </div>
                                </div>
                                <div class="valuation__block">
                                    <div class="valuation__block-title">Возраст</div>
                                    <div class="valuation__block-inner">
                                        <div class="valuation__row valuation__row2 valuation__row-age">
                                            {$params = [
                                                'name' => 'name',
                                                'label' => 'Дата начала эксплуатации',
                                                'type' => 'date'
                                            ]}
                                            {include 'components/form-field' params=$params}
                                            
                                            <div class="valuation__age">
                                                <div class="valuation__age-wrapper">
                                                    <div class="valuation__assessment">
                                                        <div class="valuation__assessment-title">Дата оценки:</div>
                                                        <div class="valuation__assessment-value">15 июня 2026 г.</div>
                                                    </div>
                                                    <div class="valuation__assessment">
                                                        <div class="valuation__assessment-title">Дата начала эксплуатации:</div>
                                                        <div class="valuation__assessment-value">1 января 2025 г.</div>
                                                    </div>
                                                </div>
                                                <div class="valuation__age-result">
                                                    <div class="valuation__age-title">Возраст на дату оценки:</div>
                                                    <div class="valuation__age-value">1 год 5 месяцев</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="valuation__block valuation__block-margin">
                                    <div class="valuation__block-title">Документы, идентифицирующие АМТС</div>
                                    <div class="valuation__block-inner">
                                        <div class="valuation__row valuation__row2">
                                            {$params = [
                                                'name' => 'name',
                                                'label' => 'Свидетельство о регистрации АМТС (СТС)',
                                                'placeholder' => '77АА 123456',
                                                'description' => 'Серия и номер, например: 77АА 024506',
                                                'id' => 'stsNumber'
                                            ]}
                                            {include 'components/form-field' params=$params}
                                        </div>
                                    </div>
                                </div>
                                <div class="valuation__buttons">
                                    <button class="valuation__prev valuation__btn">{'/app/img/icons/arrow-right-thin.svg'|svg} Назад</button>
                                    <div class="valuation__description">Заполните все обязательные поля</div>
                                    <button class="valuation__next valuation__btn" @click="changeStage('Оценка')">Продолжить {'/app/img/icons/arrow-right-thin.svg'|svg}</button>
                                </div>
                            </div>
                            
                            <div class="valuation__notification">
                                <div class="valuation__notify">
                                    {'/app/img/icons/info-icon.svg'|svg}
                                    <span>
                                        <b>Кузов №</b> — указывается только при отсутствии VIN, либо при оценке автомобилей с правым расположением руля.
                                    </span>
                                </div>
                                <div class="valuation__notify">
                                    {'/app/img/icons/info-icon.svg'|svg}
                                    <span>
                                        В <b>VIN</b> разрешено использовать только латинские буквы (кроме I, o, q) и арабские цифры.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="valuation__second" x-show="stage == 'Оценка'">
                    <div class="grade__title">Оценка ТС: Toyota Camry 2020</div>
                    <div class="grade__wrapper">
                        <div class="grade__items">
                            <div class="grade__top">
                                <div class="grade__assessment">
                                    <div class="grade__assessment-header">
                                        <div class="grade__assessment-title">Расчёт стоимости</div>
                                        <div class="grade__assessment-description">На основе 5 аналогов</div>
                                    </div>
                                    <div class="grade__assessment-inner">
                                        <div class="grade__assessment-row">
                                            <div class="grade__assessment-row-name">Средняя цена аналогов</div>
                                            <div class="grade__assessment-row-value grade__assessment-row-first">744 800 ₽</div>
                                        </div>
                                        <div class="grade__assessment-row">
                                            <div class="grade__assessment-row-name">Корректировка на торг <span class="grade__assessment-row-percent">(-10%)</span></div>
                                            <div class="grade__assessment-row-value grade__assessment-row-value-yellow grade__assessment-row-first">
                                                -74 480 ₽
                                            </div>
                                        </div>
                                        <div class="grade__assessment-result">
                                            <div class="grade__assessment-result-name">Итоговая стоимость АМТС</div>
                                            <div class="grade__assessment-result-value grade__assessment-result-final">670 320 ₽</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="grade__correction">
                                    <div class="grade__correction-header">
                                        <div class="grade__correction-title">Корректировка на снижение цены в процессе торга</div>
                                        <div class="grade__correction-description">Применяется к средней цене аналогов. Типичное значение для рынка б/у авто: -5%  _ -10%</div>
                                    </div>
                                    <div class="grade__list">
                                        {$arr = ['0%', '-5%', '-10%', '-15%', '-20%', '-25%', '-30%']}
                                        {foreach $arr as $item}
                                            <label class="grade__radio">
                                                <input type="radio" name="radio_discount" value="{$item}" {if $item@iteration == 1}checked{/if}>
                                                <div class="grade__radio-value">{$item}</div>
                                            </label>
                                        {/foreach}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grade__cars">
                                <div class="grade__cars-header">
                                    <div class="grade__cars-title">Выборка идентичных, технически исправных АМТС</div>
                                    <div class="grade__cars-description">
                                        <div class="grade__cars-found">
                                            Найдено объявлений: <span>50</span> 
                                        </div>
                                        <div class="grade__cars-found">
                                            Используется в расчёте: <span>5</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="grade__cars-items">
                                    <div class="grade__cars-item">
                                        <div class="grade__cars-image">
                                            <img src="/app/img/icons/car-image.png" alt="">
                                        </div>
                                        <div class="grade__cars-item-wrapper">
                                            <div class="grade__cars-content">
                                                <div class="grade__cars-top">
                                                    <div class="grade__cars-iteration">1</div>
                                                    <div class="grade__cars-wrapper">
                                                        <div class="grade__cars-name">Toyota Camry</div>
                                                        <div class="grade__cars-year">2020</div>
                                                    </div>
                                                    <div class="grade__cars-cost">759 000 ₽</div>
                                                </div>
                                                <div class="grade__cars-options">
                                                    {$options = [
                                                        [
                                                            "name" => "Цвет",
                                                            "value" => "чёрный"
                                                        ],
                                                        [
                                                            "name" => "Мощность",
                                                            "value" => "181 л.с."
                                                        ],
                                                        [
                                                            "name" => "Трансмиссия",
                                                            "value" => "автомат"
                                                        ],
                                                        [
                                                            "name" => "Кузов",
                                                            "value" => "седан"
                                                        ],
                                                        [
                                                            "name" => "Двигатель",
                                                            "value" => "бензин"
                                                        ],
                                                        [
                                                            "name" => "Привод",
                                                            "value" => "передний"
                                                        ],
                                                        [
                                                            "name" => "Пробег",
                                                            "value" => "176 200 км"
                                                        ]
                                                    ]}
                                                    {foreach $options as $opt}
                                                        <div class="grade__cars-option">
                                                            <div class="grade__cars-option-name">{$opt['name']}</div>
                                                            <div class="grade__cars-option-value">{$opt['value']}</div>
                                                        </div>
                                                    {/foreach}
                                                </div>
                                                <div class="grade__cars-location">
                                                    {'/app/img/icons/location.svg'|svg}
                                                    <span>Кировская обл.</span>
                                                </div>
                                            </div>
                                            <button class="grade__cars-update">
                                                {'/app/img/icons/arrow-reload-horizontal-round.svg'|svg}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="grade__cars-item">
                                        <div class="grade__cars-image">
                                            <img src="/app/img/icons/car-image.png" alt="">
                                        </div>
                                        <div class="grade__cars-item-wrapper">
                                            <div class="grade__cars-content">
                                                <div class="grade__cars-top">
                                                    <div class="grade__cars-iteration">2</div>
                                                    <div class="grade__cars-wrapper">
                                                        <div class="grade__cars-name">Toyota Camry</div>
                                                        <div class="grade__cars-year">2020</div>
                                                    </div>
                                                    <div class="grade__cars-cost">759 000 ₽</div>
                                                </div>
                                                <div class="grade__cars-options">
                                                    {$options = [
                                                        [
                                                            "name" => "Цвет",
                                                            "value" => "чёрный"
                                                        ],
                                                        [
                                                            "name" => "Мощность",
                                                            "value" => "181 л.с."
                                                        ],
                                                        [
                                                            "name" => "Трансмиссия",
                                                            "value" => "автомат"
                                                        ],
                                                        [
                                                            "name" => "Кузов",
                                                            "value" => "седан"
                                                        ],
                                                        [
                                                            "name" => "Двигатель",
                                                            "value" => "бензин"
                                                        ],
                                                        [
                                                            "name" => "Привод",
                                                            "value" => "передний"
                                                        ],
                                                        [
                                                            "name" => "Пробег",
                                                            "value" => "176 200 км"
                                                        ]
                                                    ]}
                                                    {foreach $options as $opt}
                                                        <div class="grade__cars-option">
                                                            <div class="grade__cars-option-name">{$opt['name']}</div>
                                                            <div class="grade__cars-option-value">{$opt['value']}</div>
                                                        </div>
                                                    {/foreach}
                                                </div>
                                                <div class="grade__cars-location">
                                                    {'/app/img/icons/location.svg'|svg}
                                                    <span>Кировская обл.</span>
                                                </div>
                                            </div>
                                            <button class="grade__cars-update">
                                                {'/app/img/icons/arrow-reload-horizontal-round.svg'|svg}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="valuation__buttons"> 
                                <button class="valuation__prev valuation__btn" @click="changeStage('АМТС')">{'/app/img/icons/arrow-right-thin.svg'|svg} Назад</button>
                                <button class="valuation__next valuation__btn" @click="changeStage('Заказчик')">Продолжить {'/app/img/icons/arrow-right-thin.svg'|svg}</button>
                            </div>
                        </div>
                        <div class="grade__notification">
                            <div class="grade__notify">
                                {'/app/img/icons/info-icon.svg'|svg}
                                <span>
                                    Цена АМТС определена как <b>среднее арифметическое</b> из включённых аналогов.
                                </span>
                            </div>
                            <div class="grade__alert">
                                {'/app/img/icons/alert.svg'|svg}
                                <span>
                                    Поиск аналогов выполняется по регионам: <b>Кировская обл., Татарстан, Марий Эл, Пермский край, Удмуртия.</b>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="valuation__third" x-show="stage == 'Заказчик'">
                    <div class="report__title">Сведения о заказчике оценки (наследнике)</div>
                    <div class="valuation__block">
                        <div class="valuation__block-title">Номер двигателя</div>
                        <div class="valuation__block-inner">
                            {$params = [
                                'name' => 'name',
                                'label' => 'ФИО',
                                'placeholder' => 'Иванов Иван Иванович'
                            ]}
                            {include 'components/form-field' params=$params}
                            
                            <div class="valuation__row valuation__row2">
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Дата рождения',
                                    'type' => 'date'
                                ]}
                                {include 'components/form-field' params=$params}
                                
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Место рождения',
                                    'placeholder' => 'г. Город'
                                ]}
                                {include 'components/form-field' params=$params}
                            </div>
                            
                            <div class="valuation__row valuation__row2">
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Серия паспорта',
                                    'placeholder' => '4520',
                                    'classes' => 'passport-series'
                                ]}
                                {include 'components/form-field' params=$params}
                                
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Номер паспорта',
                                    'placeholder' => '123456',
                                    'classes' => 'passport-number'
                                ]}
                                {include 'components/form-field' params=$params}
                            </div>
                            
                            <div class="valuation__row valuation__row2">
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Код подразделения',
                                    'placeholder' => '770-001',
                                    'classes' => 'department-code'
                                ]}
                                {include 'components/form-field' params=$params}
                                
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Дата выдачи паспорта',
                                    'type' => 'date'
                                ]}
                                {include 'components/form-field' params=$params}
                            </div>
                            
                            <div class="valuation__row valuation__row2">
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Кем выдан паспорт',
                                    'placeholder' => 'УФМС России'
                                ]}
                                {include 'components/form-field' params=$params}
                                
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Адрес регистрации',
                                    'placeholder' => 'г. Город, ул. Улица, д. 1, кв. 1',
                                    'description' => 'Адрес по месту регистрации (прописки)'
                                ]}
                                {include 'components/form-field' params=$params}
                            </div>
                            
                            <div class="valuation__row valuation__row2">
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'E-mail',
                                    'placeholder' => 'example@mail.ru',
                                    'type' => 'email'
                                ]}
                                {include 'components/form-field' params=$params}
                                
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Телефон',
                                    'placeholder' => '+7 (900) 000-00-00',
                                    'type' => 'tel'
                                ]}
                                {include 'components/form-field' params=$params}
                            </div>
                        </div>
                    </div>
                    
                    <div class="valuation__block report__block">
                        <div class="valuation__block-title">Сведение о собственнике АМТС (наследодателе)</div>
                        <div class="valuation__block-inner">
                            {$params = [
                                'name' => 'name',
                                'label' => 'ФИО',
                                'placeholder' => 'Иванов Иван Иванович'
                            ]}
                            {include 'components/form-field' params=$params}
                            
                            <div class="valuation__row valuation__row2">
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Дата рождения',
                                    'type' => 'date'
                                ]}
                                {include 'components/form-field' params=$params}
                                
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Дата смерти',
                                    'type' => 'date'
                                ]}
                                {include 'components/form-field' params=$params}
                            </div>
                            
                            <div class="valuation__row valuation__row2">
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Серия паспорта',
                                    'placeholder' => '4520',
                                    'classes' => 'passport-series'
                                ]}
                                {include 'components/form-field' params=$params}
                                
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Номер паспорта',
                                    'placeholder' => '123456',
                                    'classes' => 'passport-number'
                                ]}
                                {include 'components/form-field' params=$params}
                            </div>
                            
                            <div class="valuation__row valuation__row2">
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Дата выдачи паспорта',
                                    'type' => 'date'
                                ]}
                                {include 'components/form-field' params=$params}
                                
                                {$params = [
                                    'name' => 'name',
                                    'label' => 'Кем выдан паспорт',
                                    'placeholder' => 'УФМС России'
                                ]}
                                {include 'components/form-field' params=$params}
                            </div>
                            
                            {$params = [
                                'name' => 'name',
                                'label' => 'Адрес регистрации',
                                'placeholder' => 'г. Город, ул. Улица, д. 1, кв. 1'
                            ]}
                            {include 'components/form-field' params=$params}
                        </div>
                    </div>
                        
                    <div class="valuation__buttons">
                        <button class="valuation__prev valuation__btn" @click="changeStage('Оценка')">{'/app/img/icons/arrow-right-thin.svg'|svg} Назад</button>
                        <button class="valuation__next valuation__btn" @click="changeStage('Отчёт')">Продолжить {'/app/img/icons/arrow-right-thin.svg'|svg}</button>
                    </div>
                </div>
                
                <div class="valuation__fourth" x-show="stage == 'Отчёт'">
                    <div class="details__title">Сведения об отчёте</div>
                    <div class="details__wrapper">
                        <div class="details__blocks">
                            <div class="details__block">
                                <div class="details__block-title">
                                    Контрольный список данных отчёта
                                </div>
                                <div class="details__block-inner">
                                    {$list = [
                                        [
                                            "name" => "Марка",
                                            "value" => "Toyota"
                                        ], 
                                        [
                                            "name" => "Модель",
                                            "value" => "Camry"
                                        ],
                                        [
                                            "name" => "Государственный регистрационный знак",
                                            "value" => "К547МА43"
                                        ],
                                        [
                                            "name" => "Год выпуска",
                                            "value" => "2020"
                                        ],
                                        [
                                            "name" => "Номер кузова (кабины):"
                                        ]
                                    ]}
                                    
                                    {foreach $list as $elem}
                                        <div class="details__block-row">
                                            <div class="details__block-name">{$elem['name']}</div>
                                            {if $elem['value']}
                                                <div class="details__block-value">{$elem['value']}</div>
                                            {else}
                                                <div class="details__block-value-empty">Не указан</div>
                                            {/if}
                                        </div>
                                    {/foreach}
                                </div>
                            </div>
                            
                            <div class="details__block">
                                <div class="details__block-title">Реквизиты отчёта</div>
                                <div class="details__block-payment">
                                    <div class="details__description">
                                        <div class="details__description-name">
                                            Дата формирования отчёта об оценки
                                        </div>
                                        <div class="details__description-value">
                                            5 июня 2026
                                        </div>
                                    </div>
                                    <div class="valuation__row valuation__row2">
                                        {$params = [
                                            'name' => 'name',
                                            'label' => 'Дата оценки',
                                            'placeholder' => '05.06.2026',
                                            'type' => 'date',
                                            'description' => 'Дата, на которую проводится оценка'
                                        ]}
                                        {include 'components/form-field' params=$params}
                                        
                                        {$params = [
                                            'name' => 'name',
                                            'label' => 'Стоимость проведения оценки',
                                            'placeholder' => '0',
                                            'type' => 'price'
                                        ]}
                                        {include 'components/form-field' params=$params}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="valuation__buttons details__buttons">
                                <button class="valuation__prev valuation__btn" @click="changeStage('Заказчик')">{'/app/img/icons/arrow-right-thin.svg'|svg} Назад</button>
                                <button class="valuation__form-report valuation__btn" @click="changeStage('Документы')">{'/app/img/icons/folder-download.svg'|svg} Сформировать отчёт</button>
                            </div>
                        </div>
                        <div class="details__right">
                            <div class="valuation__notification">
                                <div class="valuation__notify">
                                    {'/app/img/icons/info-icon.svg'|svg}
                                    <span>
                                        <b>Кузов №</b> — указывается только при отсутствии VIN, либо при оценке автомобилей с правым расположением руля.
                                    </span>
                                </div>
                                <div class="valuation__notify">
                                    {'/app/img/icons/info-icon.svg'|svg}
                                    <span>
                                        В <b>VIN</b> разрешено использовать только латинские буквы (кроме I, o, q) и арабские цифры.
                                    </span>
                                </div>
                            </div>
                            
                            <div class="details__result">
                                <div class="details__result-title">Итог по расчёту</div>
                                <div class="details__result-list">
                                    <div class="details__result-row">
                                        <div class="details__result-name">Средняя цена</div>
                                        <div class="details__result-value">744 800 ₽</div>
                                    </div>
                                    <div class="details__result-row">
                                        <div class="details__result-name">Корректировка (-5%)</div>
                                        <div class="details__result-value">-37 240 ₽</div>
                                    </div>
                                </div>
                                <div class="details__result-cost">
                                    <div class="details__result-label">Итоговая стоимость</div>
                                    <div class="details__result-price">707 560 ₽</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="valuation__fifth" x-show="stage == 'Документы'">
                    <div class="documents__title">Получение и отправка документов</div>
                    <div class="documents__wrapper">
                        <div class="documents__blocks">
                            <div class="documents__block">
                                <div class="documents__block-header">
                                    <div class="documents__block-left">
                                        <div class="documents__block-status">
                                            {'app/img/icons/checkmark-circle-fill.svg'|svg}
                                        </div>
                                        <div class="documents__block-title">
                                            Шаг 1 — Скачайте отчёт об оценке
                                        </div>
                                    </div>
                                    <div class="documents__block-header-description">Готов к скачиванию</div>
                                </div>
                                <div class="documents__block-wrapper">
                                    <div class="documents__block-document">
                                        <div class="documents__document-left">
                                            {'app/img/icons/documents.svg'|svg}
                                            <div class="documents__document-content">
                                                <div class="documents__document-title">Отчёт об оценке №47.docx</div>
                                                <div class="documents__document-description">Toyota Camry 2020 - 717 560 ₽ - 5 июня 2026</div>
                                            </div>
                                        </div>
                                        <button class="documents__document-button">
                                            Скачать DOCX
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="documents__block">
                                <div class="documents__block-header">
                                    <div class="documents__block-left">
                                        <div class="documents__block-status">
                                            {'app/img/icons/checkmark-circle-fill.svg'|svg}
                                        </div>
                                        <div class="documents__block-title">
                                            Шаг 2 — Подпишите и загрузите подписанный отчёт
                                        </div>
                                    </div>
                                    <div class="documents__block-header-description">Загружено</div>
                                </div>
                                <div class="documents__block-wrapper">
                                    <div class="documents__steps">
                                        <div class="documents__step">
                                            {'app/img/icons/checkmark-circle-2.svg'|svg}
                                            <div class="documents__step-content">
                                                <div class="documents__step-title">Скачайте DOCX</div>
                                                <div class="documents__step-description">Файл отчёта получен на вашем устройстве</div>
                                            </div>
                                        </div>
                                        
                                        <div class="documents__steps-divider"></div>
                                        
                                        <div class="documents__step">
                                            {'app/img/icons/checkmark-circle-2.svg'|svg}
                                            <div class="documents__step-content">
                                                <div class="documents__step-title">Скачайте DOCX</div>
                                                <div class="documents__step-description">Файл отчёта получен на вашем устройстве</div>
                                            </div>
                                        </div>
                                        
                                        <div class="documents__steps-divider"></div>
                                        
                                        <div class="documents__step">
                                            {'app/img/icons/checkmark-circle-2.svg'|svg}
                                            <div class="documents__step-content">
                                                <div class="documents__step-title">Скачайте DOCX</div>
                                                <div class="documents__step-description">Файл отчёта получен на вашем устройстве</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div
                                        class="documents__files"
                                        @dragover.prevent="drag = true"
                                        @dragleave.prevent="drag = false"
                                        @drop.prevent="dropFile($event)"
                                        @click="$refs.file.click()"
                                    >
                                        <input
                                            type="file"
                                            x-ref="file"
                                            class="hidden"
                                            accept=".pdf,.doc,.docx"
                                            @change="selectFile($event)"
                                            hidden
                                        >
                                        <div class="documents__files-list">
                                            <!-- Если файла нет -->
                                            <template x-if="!file">
                                                <div class="documents__file">
                                                    <div class="documents__file-name">
                                                        Файл ещё не загружен
                                                    </div>
                                                </div>
                                            </template>
                                    
                                            <!-- Если файл выбран -->
                                            <template x-if="file">
                                                <div class="documents__file">
                                                    <div class="documents__file-icon">
                                                        {'app/img/icons/files.svg'|svg}
                                                    </div>
                                    
                                                    <div
                                                        class="documents__file-name"
                                                        x-text="file.name"
                                                    ></div>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="documents__files-content">
                                            <div
                                                class="documents__files-title"
                                                x-show="file"
                                            >
                                                Нажмите, чтобы загрузить другой файл
                                            </div>
                                            <div
                                                class="documents__files-title"
                                                x-show="!file"
                                            >
                                                Перетащите файл или нажмите для выбора
                                            </div>
                                            <div class="documents__files-description">
                                                PDF или DOCX. До 20 МБ
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="documents__block">
                                <div class="documents__block-header">
                                    <div class="documents__block-left">
                                        <div class="documents__block-status">
                                            {'app/img/icons/checkmark-circle-fill.svg'|svg}
                                        </div>
                                        <div class="documents__block-title">
                                            Шаг 3 — Отправьте документы заказчику
                                        </div>
                                    </div>
                                </div>
                                <div class="documents__block-wrapper">
                                    <div class="documents__emails">
                                        <div class="documents__emails-label">
                                            Получатели
                                        </div>
                                        <div class="documents__emails-wrapper">
                                            <div class="documents__emails-list" x-show="selectedEmails">
                                                <template x-for="email in selectedEmails" :key="email">
                                                    <div class="documents__email">
                                                        <div
                                                            class="documents__email-name"
                                                            x-text="email"
                                                        ></div>
                                                        <button
                                                            class="documents__email-remove"
                                                            @click="removeSelected(email)"
                                                        >
                                                            {'app/img/icons/delete.svg'|svg}
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                            <input
                                                class="documents__email-input"
                                                x-model="newEmail"
                                                @keydown.enter.prevent="addEmail()"
                                                placeholder="Добавить e-mail..."
                                            >
                                        </div>
                                    </div>
                                    
                                    <div class="documents__email-inner">
                                        <div class="documents__email-recent">
                                            <div class="documents__recent-title">Было отправлено:</div>
                                            <div class="documents__recent-list">
                                                <template x-for="email in recentEmails" :key="email">
                                                    <div
                                                        class="documents__recent-email"
                                                        @click="addRecent(email)"
                                                    >
                                                        <div
                                                            class="documents__recent-email-name"
                                                            x-text="email"
                                                        ></div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="documents__email-description">
                                            Для повторного отправления выберете e-mail
                                        </div>
                                    </div>
                                    
                                    <button
                                        class="documents__send"
                                        @click="send()"
                                    >
                                        {'app/img/icons/sent-2.svg'|svg} Отправить документы
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="documents__right">
                            <div class="valuation__notify">
                                {'/app/img/icons/info-icon.svg'|svg}
                                <span>
                                    <b>Кузов №</b> — указывается только при отсутствии VIN, либо при оценке автомобилей с правым расположением руля.
                                </span>
                            </div>
                            <div class="valuation__notify">
                                {'/app/img/icons/info-icon.svg'|svg}
                                <span>
                                    В <b>VIN</b> разрешено использовать только латинские буквы (кроме I, o, q) и арабские цифры.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/block}
{block 'blocks'}{/block}