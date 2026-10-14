{extends 'block_base.tpl'}
{block 'header'}
{/block}
{block 'page_content'}
    {'AuthCheck'|snippet nocache}

    <div class="evaluation" x-data='evaluation({$report|json_encode})'>
        <div class="evaluation__steps">
            <template x-for="item in stages" :key="item">
                <div
                    class="evaluation__step"
                    :class="{ active: stage === item }"
                >
                    <span x-text="'Шаг ' + (stages.indexOf(item) + 1) + ' —'"></span>
                    <span x-text="item"></span>
                </div>
            </template>
        </div>
        
        <div class="evaluation__steps-mobile">
            <div class="evaluation__steps-mobile-items">
                <template x-for="(item, index) in stages" :key="item">
                    <div
                        class="evaluation__steps-mobile-item"
                        :class="{ active: index <= currentStep }"
                    ></div>
                </template>
            </div>
            <div class="evaluation__steps-mobile-wrapper">
                <div
                    class="evaluation__steps-mobile-progress"
                    x-text="'Шаг ' + (currentStep + 1) + ' из ' + stages.length"
                ></div>
                <div
                    class="evaluation__steps-mobile-title"
                    x-text="stage"
                ></div>
            </div>
        </div>

        <div class="evaluation__tabs | mb-40">
            <label>
                <input type="radio" value="auto" x-model="type">
                Автоматический поиск
            </label>
            <label>
                <input type="radio" value="manual" x-model="type">
                Ручной ввод
            </label>
        </div>

        <div class="evaluation__top">
            <template x-if ="type == 'auto'">
            <div class="evaluation__wrapper">
                
                <form action="">
                    <section class="evaluation__block">
                        <h2 class="evaluation__block-title">Сведения об АМТС</h2>
                        <div class="evaluation__block-inner">
                            <div class="grid lg:grid-cols-3 gap-20">
                                {$params = [
                                    'name' => 'vin',
                                    'label' => 'Госномер или VIN',
                                    'attrs' => 'required x-model="vin"'
                                ]}
                                {include 'components/form-field' params=$params}
                                
                                {$params = [
                                    'name' => 'model',
                                    'label' => 'Пробег, км',
                                    'attrs' => 'required x-model="model"'
                                ]}
                                {include 'components/form-field' params=$params}

                                {$params = [
                                    'name' => 'model',
                                    'label' => 'Город',
                                    'attrs' => 'required x-model="model"'
                                ]}
                                {include 'components/form-field' params=$params}

                            </div>
                        </div>
                    </section>

                    <div class="mt-20 text-center">
                        <button class="button" type="submit">
                            Поиск по базе
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15.7895 17L20 12.5L15.7895 8M4 12.5H19.1579" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </form>

                <div class="flex items-center justify-center gap-8 mt-20 text-15 text-success">
                    <svg fill="none" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><g stroke="#2c6e49" stroke-width="1.5"><path d="m22 12c0-5.52285-4.4772-10-10-10-5.52285 0-10 4.47715-10 10 0 5.5228 4.47715 10 10 10 5.5228 0 10-4.4772 10-10z"/><path d="m8 12.75s1.6.9125 2.4 2.25c0 0 2.4-5.25 5.6-7" stroke-linecap="round" stroke-linejoin="round"/></g></svg>
                    Автомобиль найден
                </div>

                <section class="evaluation__block mt-20">
                    <h2 class="evaluation__block-title">Список данных</h2>
                    <ul class="evaluation__table">
                        <li class="evaluation__table-item">
                            <div class="evaluation__table-row">
                                <div class="">Идентификационный номер (VIN)</div>
                                <div class="font-bold"><b>JT2BG12KOLS123456</b></div>
                            </div>
                        </li>
                        <li class="evaluation__table-item">
                            <div class="evaluation__table-row">
                                <div class="">Марка</div>
                                <div class="font-bold"><b>Toyota</b></div>
                            </div>
                        </li>

                        <li class="evaluation__table-item" x-data="{ show: true }">
                            <div class="evaluation__table-row">
                                <div class="">Номер кузова (кабины)</div>
                                <button class="flex gap-10" @click="show = !show">
                                    <span class="text-muted">Не указан</span>
                                    {'/app/img/icons/chevron-down-20.svg'|svg}
                                </button>
                            </div>

                            <div class="" x-show="show">
                                <div class="grid xl:grid-cols-2 gap-20">
                                    {$params = [
                                        'name' => '',
                                        'label' => 'Кузов №',
                                        'description' => 'Серия + номер кузова по ПТС/СТС',
                                        'attrs' => ''
                                    ]}
                                    {include 'components/form-field' params=$params}
                                </div>
                            </div>
                        </li>

                        <li class="evaluation__table-item" x-data="{ show: true }">
                            <div class="evaluation__table-row">
                                <div class="">Номер двигателя</div>
                                <button class="flex gap-10" @click="show = !show">
                                    <span class="text-muted">Не указан</span>
                                    {'/app/img/icons/chevron-down-20.svg'|svg}
                                </button>
                            </div>

                            <div class="" x-show="show">
                                <div class="grid xl:grid-cols-2 gap-20">
                                    {$params = [
                                        'name' => '',
                                        'label' => 'Номер',
                                        'description' => 'Необязательно — указывается, если есть в документах',
                                        'attrs' => ''
                                    ]}
                                    {include 'components/form-field' params=$params}
                                </div>
                            </div>
                        </li>

                        <li class="evaluation__table-item" x-data="{ show: true }">
                            <div class="evaluation__table-row">
                                <div class="">Техническое состояние</div>
                                <button class="flex gap-10" @click="show = !show">
                                    <span class="font-bold">Хорошее</span>
                                    {'/app/img/icons/chevron-down-20.svg'|svg}
                                </button>
                            </div>
                            <div class="evaluation__list" x-show="show">
                                {$arr = ['Новое', 'Отличное', 'Хорошее', 'Удовлетворительное', 'Неудовлетворительное']}
                                {foreach $arr as $item}
                                    <label class="evaluation__radio">
                                        <input type="radio" name="radio" value="{$item}">
                                        <div class="evaluation__radio-value">{$item}</div>
                                    </label>
                                {/foreach}
                            </div>
                        </li>

                        <li class="evaluation__table-item">
                            <div class="evaluation__table-row">
                                <div class="">Возраст</div>
                                <span class="font-bold">1 год 5 месяцев</span>
                            </div>
                            <div class="">
                                <div class="grid xl:grid-cols-2 gap-20 items-end">
                                    {$params = [
                                        'name' => '',
                                        'label' => 'Дата начала эксплуатации',
                                        'attrs' => ''
                                    ]}
                                    {include 'components/form-field' params=$params}

                                    <div class="">
                                        <div class="evaluation__table-row">
                                            <div class="">Дата оценки:</div>
                                            <span class="font-medium">15 июня 2026 г.</span>
                                        </div>
                                        <div class="evaluation__table-row | mt-5">
                                            <div class="">Дата начала эксплуатации:</div>
                                            <span class="font-medium">1 января 2025 г.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li class="evaluation__table-item">
                            <div class="evaluation__table-row">
                                <div class="">Документы, идентифицирующие АМТС</div>
                                <div class="">Не указан</div>
                            </div>
                            <div class="">
                                <div class="grid xl:grid-cols-2 gap-20 items-end">
                                    {$params = [
                                        'name' => '',
                                        'label' => 'Свидетельство о регистрации АМТС (СТС)',
                                        'description' => 'Серия и номер, например: 77АА 024506',
                                        'attrs' => ''
                                    ]}
                                    {include 'components/form-field' params=$params}

                                </div>
                            </div>
                        </li>


                    </ul>
                </section>

                <div class="evaluation__buttons">
                    <button class="evaluation__prev evaluation__btn">{'/app/img/icons/arrow-right-thin.svg'|svg} Назад</button>
                    {* <div class="evaluation__description">Заполните все обязательные поля</div> *}
                    <button class="evaluation__next evaluation__btn" @click="changeStage('Оценка')">Продолжить {'/app/img/icons/arrow-right-thin.svg'|svg}</button>
                </div>
            </div>
            </template>

            <template x-if ="type == 'manual'">
            <div class="evaluation__wrapper">
                    <section class="evaluation__block mt-20">
                    <h2 class="evaluation__block-title">Список данных</h2>
                    <ul class="evaluation__table">
                        <li class="evaluation__table-item">
                            <div class="evaluation__table-row">
                                <div class="">Идентификационный номер (VIN)</div>
                                <div class="font-bold"><b>JT2BG12KOLS123456</b></div>
                            </div>
                        </li>
                        <li class="evaluation__table-item">
                            <div class="evaluation__table-row">
                                <div class="">Марка</div>
                                <div class="font-bold"><b>Toyota</b></div>
                            </div>
                        </li>

                        <li class="evaluation__table-item" x-data="{ show: true }">
                            <div class="evaluation__table-row">
                                <div class="">Номер кузова (кабины)</div>
                                <button class="flex gap-10" @click="show = !show">
                                    <span class="text-muted">Не указан</span>
                                    {'/app/img/icons/chevron-down-20.svg'|svg}
                                </button>
                            </div>

                            <div class="" x-show="show">
                                <div class="grid xl:grid-cols-2 gap-20">
                                    {$params = [
                                        'name' => '',
                                        'label' => 'Кузов №',
                                        'description' => 'Серия + номер кузова по ПТС/СТС',
                                        'attrs' => ''
                                    ]}
                                    {include 'components/form-field' params=$params}
                                </div>
                            </div>
                        </li>

                        <li class="evaluation__table-item" x-data="{ show: true }">
                            <div class="evaluation__table-row">
                                <div class="">Номер двигателя</div>
                                <button class="flex gap-10" @click="show = !show">
                                    <span class="text-muted">Не указан</span>
                                    {'/app/img/icons/chevron-down-20.svg'|svg}
                                </button>
                            </div>

                            <div class="" x-show="show">
                                <div class="grid xl:grid-cols-2 gap-20">
                                    {$params = [
                                        'name' => '',
                                        'label' => 'Номер',
                                        'description' => 'Необязательно — указывается, если есть в документах',
                                        'attrs' => ''
                                    ]}
                                    {include 'components/form-field' params=$params}
                                </div>
                            </div>
                        </li>

                        <li class="evaluation__table-item" x-data="{ show: true }">
                            <div class="evaluation__table-row">
                                <div class="">Техническое состояние</div>
                                <button class="flex gap-10" @click="show = !show">
                                    <span class="font-bold">Хорошее</span>
                                    {'/app/img/icons/chevron-down-20.svg'|svg}
                                </button>
                            </div>
                            <div class="evaluation__list" x-show="show">
                                {$arr = ['Новое', 'Отличное', 'Хорошее', 'Удовлетворительное', 'Неудовлетворительное']}
                                {foreach $arr as $item}
                                    <label class="evaluation__radio">
                                        <input type="radio" name="radio" value="{$item}">
                                        <div class="evaluation__radio-value">{$item}</div>
                                    </label>
                                {/foreach}
                            </div>
                        </li>

                        <li class="evaluation__table-item">
                            <div class="evaluation__table-row">
                                <div class="">Возраст</div>
                                <span class="font-bold">1 год 5 месяцев</span>
                            </div>
                            <div class="">
                                <div class="grid xl:grid-cols-2 gap-20 items-end">
                                    {$params = [
                                        'name' => '',
                                        'label' => 'Дата начала эксплуатации',
                                        'attrs' => ''
                                    ]}
                                    {include 'components/form-field' params=$params}

                                    <div class="">
                                        <div class="evaluation__table-row">
                                            <div class="">Дата оценки:</div>
                                            <span class="font-medium">15 июня 2026 г.</span>
                                        </div>
                                        <div class="evaluation__table-row | mt-5">
                                            <div class="">Дата начала эксплуатации:</div>
                                            <span class="font-medium">1 января 2025 г.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li class="evaluation__table-item">
                            <div class="evaluation__table-row">
                                <div class="">Документы, идентифицирующие АМТС</div>
                                <div class="">Не указан</div>
                            </div>
                            <div class="">
                                <div class="grid xl:grid-cols-2 gap-20 items-end">
                                    {$params = [
                                        'name' => '',
                                        'label' => 'Свидетельство о регистрации АМТС (СТС)',
                                        'description' => 'Серия и номер, например: 77АА 024506',
                                        'attrs' => ''
                                    ]}
                                    {include 'components/form-field' params=$params}

                                </div>
                            </div>
                        </li>


                    </ul>
                </section>


                    


                <div class="evaluation__buttons">
                    <button class="evaluation__prev evaluation__btn">{'/app/img/icons/arrow-right-thin.svg'|svg} Назад</button>
                    {* <div class="evaluation__description">Заполните все обязательные поля</div> *}
                    <button class="evaluation__next evaluation__btn" @click="changeStage('Оценка')">Продолжить {'/app/img/icons/arrow-right-thin.svg'|svg}</button>
                </div>
            </div>
            </template>

            <div class="evaluation__notification">
                <div class="evaluation__notify">
                    {'/app/img/icons/info-icon.svg'|svg}
                    <span>
                        <b>Кузов №</b> — указывается только при отсутствии VIN, либо при оценке автомобилей с правым расположением руля.
                    </span>
                </div>
                <div class="evaluation__notify">
                    {'/app/img/icons/info-icon.svg'|svg}
                    <span>
                        В <b>VIN</b> разрешено использовать только латинские буквы (кроме I, o, q) и арабские цифры.
                    </span>
                </div>
            </div>
        </div>





        <div class="">
            <br><br><br><br>
            (старая вёрстка)
        </div>
        
        <div class="evaluation__blocks">
            <div class="evaluation__stage">
                <div class="evaluation__first" x-show="stage == 'АМТС'">
                    <div class="evaluation__top">
                        <div class="evaluation__wrapper">
                            <div class="evaluation__block">
                                <div class="evaluation__block-title">Сведения об АМТС</div>
                                <div class="evaluation__block-inner">
                                    <div class="evaluation__row evaluation__row2">
                                        {$params = [
                                            'name' => 'brand',
                                            'label' => 'Марка',
                                            'attrs' => 'x-model="brand"'
                                        ]}
                                        {include 'components/form-field' params=$params}
                                        
                                        {$params = [
                                            'name' => 'model',
                                            'label' => 'Модель',
                                            'attrs' => 'x-model="model"'
                                        ]}
                                        {include 'components/form-field' params=$params}
                                    </div>
                                    <div class="evaluation__row evaluation__row2">
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
                                    <div class="evaluation__row evaluation__row2">
                                        <div class="evaluation__column">
                                            {$params = [
                                                'name' => 'vin',
                                                'label' => 'VIN',
                                                'placeholder' => 'Не указывается',
                                                'id' => 'vinInput',
                                                'attrs' => 'x-model="vin"'
                                            ]}
                                            {include 'components/form-field' params=$params}
                                            
                                            <label class="evaluation__checkbox">
                                                <div class="evaluation__checkbox-wrapper">
                                                    <input class="evaluation__checkbox-input" name="vin" type="checkbox"/>
                                                    <span class="evaluation__checkbox-check"></span>
                                                </div>
                                                <div class="evaluation__checkbox-text">
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
                                    <div class="evaluation__carnumber">
                                        <div class="evaluation__carnumber-left">
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
                                        
                                        <div class="evaluation__carnumber-plate">
                                            <div class="evaluation__plate-left">
                                                А<span>000</span>AA
                                            </div>
                                            <div class="evaluation__plate-right">
                                                <div class="evaluation__plate-region">77</div>
                                                <div class="evaluation__plate-flag">
                                                    <div class="evaluation__plate-country">RUS</div>
                                                    <div class="evaluation__plate-img">
                                                        {'/app/img/icons/flag-russia.svg'|svg}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="evaluation__notification">
                            <div class="evaluation__notify">
                                {'/app/img/icons/info-icon.svg'|svg}
                                <span>
                                    <b>Кузов №</b> — указывается только при отсутствии VIN, либо при оценке автомобилей с правым расположением руля.
                                </span>
                            </div>
                            <div class="evaluation__notify">
                                {'/app/img/icons/info-icon.svg'|svg}
                                <span>
                                    В <b>VIN</b> разрешено использовать только латинские буквы (кроме I, o, q) и арабские цифры.
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="evaluation__specification">
                        <div class="evaluation__title">Уточнение характеристик</div>
                        <div class="evaluation__items">
                            <div class="evaluation__wrapper">
                                <div class="evaluation__block">
                                    <div class="evaluation__block-title">Сведения об АМТС</div>
                                    <div class="evaluation__block-inner evaluation__list">
                                        {$arr = ['Механика', 'Автомат', 'Вариатор', 'Робот']}
                                        {foreach $arr as $item}
                                            <label class="evaluation__radio">
                                                <input type="radio" name="radio" value="{$item}">
                                                <div class="evaluation__radio-value">{$item}</div>
                                            </label>
                                        {/foreach}
                                    </div>
                                </div>
                                <div class="evaluation__block">
                                    <div class="evaluation__block-title">Номер двигателя</div>
                                    <div class="evaluation__block-inner">
                                        <div class="evaluation__row evaluation__row2">
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
                                <div class="evaluation__block">
                                    <div class="evaluation__block-title">Привод</div>
                                    <div class="evaluation__block-inner evaluation__list">
                                        {$arr = ['Передний', 'Задний', 'Полный']}
                                        {foreach $arr as $item}
                                            <label class="evaluation__radio">
                                                <input type="radio" name="radio" value="{$item}">
                                                <div class="evaluation__radio-value">{$item}</div>
                                            </label>
                                        {/foreach}
                                    </div>
                                </div>
                                <div class="evaluation__block">
                                    <div class="evaluation__block-title">Рабочий объём, см3</div>
                                    <div class="evaluation__block-inner evaluation__list evaluation__list-alt">
                                        {$arr = [1000, 1200, 1400, 1500, 1600, 1800, 2000, 2200, 2400, 2500, 3000, 3500, 4000, 4500, 5000]}
                                        {foreach $arr as $item}
                                            <label class="evaluation__radio">
                                                <input type="radio" name="radio" value="{$item}">
                                                <div class="evaluation__radio-value">{$item}</div>
                                            </label>
                                        {/foreach}
                                    </div>
                                </div>
                                <div class="evaluation__block">
                                    <div class="evaluation__block-title">Мощность, л.с.</div>
                                    <div class="evaluation__block-inner evaluation__list-alt">
                                        {$arr = [60, 75, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200, 220, 249, 250, 300, 350, 400]}
                                        {foreach $arr as $item}
                                            <label class="evaluation__radio">
                                                <input type="radio" name="radio" value="{$item}">
                                                <div class="evaluation__radio-value">{$item}</div>
                                            </label>
                                        {/foreach}
                                    </div>
                                </div>
                                <div class="evaluation__block">
                                    <div class="evaluation__block-title">Техническое состояние</div>
                                    <div class="evaluation__block-inner evaluation__list">
                                        {$arr = ['Новое', 'Отличное', 'Хорошее', 'Удовлетворительное', 'Неудовлетворительное']}
                                        {foreach $arr as $item}
                                            <label class="evaluation__radio">
                                                <input type="radio" name="radio" value="{$item}">
                                                <div class="evaluation__radio-value">{$item}</div>
                                            </label>
                                        {/foreach}
                                    </div>
                                </div>
                                <div class="evaluation__block">
                                    <div class="evaluation__block-title">Возраст</div>
                                    <div class="evaluation__block-inner">
                                        <div class="evaluation__row evaluation__row2 evaluation__row-age">
                                            {$params = [
                                                'name' => 'name',
                                                'label' => 'Дата начала эксплуатации',
                                                'type' => 'date'
                                            ]}
                                            {include 'components/form-field' params=$params}
                                            
                                            <div class="evaluation__age">
                                                <div class="evaluation__age-wrapper">
                                                    <div class="evaluation__assessment">
                                                        <div class="evaluation__assessment-title">Дата оценки:</div>
                                                        <div class="evaluation__assessment-value">15 июня 2026 г.</div>
                                                    </div>
                                                    <div class="evaluation__assessment">
                                                        <div class="evaluation__assessment-title">Дата начала эксплуатации:</div>
                                                        <div class="evaluation__assessment-value">1 января 2025 г.</div>
                                                    </div>
                                                </div>
                                                <div class="evaluation__age-result">
                                                    <div class="evaluation__age-title">Возраст на дату оценки:</div>
                                                    <div class="evaluation__age-value">1 год 5 месяцев</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="evaluation__block evaluation__block-margin">
                                    <div class="evaluation__block-title">Документы, идентифицирующие АМТС</div>
                                    <div class="evaluation__block-inner">
                                        <div class="evaluation__row evaluation__row2">
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
                                <div class="evaluation__buttons">
                                    <button class="evaluation__prev evaluation__btn">{'/app/img/icons/arrow-right-thin.svg'|svg} Назад</button>
                                    <div class="evaluation__description">Заполните все обязательные поля</div>
                                    <button class="evaluation__next evaluation__btn" @click="changeStage('Оценка')">Продолжить {'/app/img/icons/arrow-right-thin.svg'|svg}</button>
                                </div>
                            </div>
                            
                            <div class="evaluation__notification">
                                <div class="evaluation__notify">
                                    {'/app/img/icons/info-icon.svg'|svg}
                                    <span>
                                        <b>Кузов №</b> — указывается только при отсутствии VIN, либо при оценке автомобилей с правым расположением руля.
                                    </span>
                                </div>
                                <div class="evaluation__notify">
                                    {'/app/img/icons/info-icon.svg'|svg}
                                    <span>
                                        В <b>VIN</b> разрешено использовать только латинские буквы (кроме I, o, q) и арабские цифры.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="evaluation__second" x-show="stage == 'Оценка'">
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
                                                <input type="radio" name="radio_discount" value="{$item}" x-model="discount" {if $item@iteration == 1}checked{/if}>
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
                            
                            <div class="evaluation__buttons"> 
                                <button class="evaluation__prev evaluation__btn" @click="changeStage('АМТС')">{'/app/img/icons/arrow-right-thin.svg'|svg} Назад</button>
                                <button class="evaluation__next evaluation__btn" @click="changeStage('Заказчик')">Продолжить {'/app/img/icons/arrow-right-thin.svg'|svg}</button>
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
                
                <div class="evaluation__third" x-show="stage == 'Заказчик'">
                    <div class="report__title">Сведения о заказчике</div>
                    <div class="evaluation__block">
                        <div class="evaluation__block-title">Сведения о заказчике оценки (наследнике)</div>
                        <div class="evaluation__block-inner">
                            {$params = [
                                'name' => 'name',
                                'label' => 'ФИО',
                                'placeholder' => 'Иванов Иван Иванович',
                                'attrs' => 'x-model="client_name"'
                            ]}
                            {include 'components/form-field' params=$params}
                            
                            <div class="evaluation__row evaluation__row2">
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
                            
                            <div class="evaluation__row evaluation__row2">
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
                            
                            <div class="evaluation__row evaluation__row2">
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
                            
                            <div class="evaluation__row evaluation__row2">
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
                            
                            <div class="evaluation__row evaluation__row2">
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
                    
                    <div class="evaluation__block report__block">
                        <div class="evaluation__block-title">Сведение о собственнике АМТС (наследодателе)</div>
                        <div class="evaluation__block-inner">
                            {$params = [
                                'name' => 'name',
                                'label' => 'ФИО',
                                'placeholder' => 'Иванов Иван Иванович'
                            ]}
                            {include 'components/form-field' params=$params}
                            
                            <div class="evaluation__row evaluation__row2">
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
                            
                            <div class="evaluation__row evaluation__row2">
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
                            
                            <div class="evaluation__row evaluation__row2">
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
                        
                    <div class="evaluation__buttons">
                        <button class="evaluation__prev evaluation__btn" @click="changeStage('Оценка')">{'/app/img/icons/arrow-right-thin.svg'|svg} Назад</button>
                        <button class="evaluation__next evaluation__btn" @click="changeStage('Отчёт')">Продолжить {'/app/img/icons/arrow-right-thin.svg'|svg}</button>
                    </div>
                </div>
                
                <div class="evaluation__fourth" x-show="stage == 'Отчёт'">
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
                                    <div class="evaluation__row evaluation__row2">
                                        {$params = [
                                            'name' => 'name',
                                            'label' => 'Дата оценки',
                                            'placeholder' => '05.06.2026',
                                            'type' => 'date',
                                            'description' => 'Дата, на которую проводится оценка',
                                            'attrs' => 'x-model="report_date"'
                                        ]}
                                        {include 'components/form-field' params=$params}
                                        
                                        {$params = [
                                            'name' => 'name',
                                            'label' => 'Стоимость проведения оценки',
                                            'placeholder' => '0',
                                            'type' => 'price',
                                            'attrs' => 'x-model="report_cost"'
                                        ]}
                                        {include 'components/form-field' params=$params}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="evaluation__buttons details__buttons">
                                <button class="evaluation__prev evaluation__btn" @click="changeStage('Заказчик')">{'/app/img/icons/arrow-right-thin.svg'|svg} Назад</button>
                                <button class="evaluation__form-report evaluation__btn" @click="changeStage('Документы')">{'/app/img/icons/folder-download.svg'|svg} Сформировать отчёт</button>
                            </div>
                        </div>
                        <div class="details__right">
                            <div class="evaluation__notification">
                                <div class="evaluation__notify">
                                    {'/app/img/icons/info-icon.svg'|svg}
                                    <span>
                                        <b>Кузов №</b> — указывается только при отсутствии VIN, либо при оценке автомобилей с правым расположением руля.
                                    </span>
                                </div>
                                <div class="evaluation__notify">
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
                
                <div class="evaluation__fifth" x-show="stage == 'Документы'">
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
                                        <button class="documents__document-button" @click="downloadDocument()">
                                            Скачать DOCX
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="documents__block" :class="{ active: is_download === true }">
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
                                        <div class="documents__emails-wrapper"  :class="{ 'validate': emailError }">
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
                                                @input="emailError = false"
                                                @keydown.enter.prevent="addEmail()"
                                                @keyup.space="addEmail()"
                                                @blur="addEmail()"
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
                            <div class="evaluation__notify">
                                {'/app/img/icons/info-icon.svg'|svg}
                                <span>
                                    <b>Кузов №</b> — указывается только при отсутствии VIN, либо при оценке автомобилей с правым расположением руля.
                                </span>
                            </div>
                            <div class="evaluation__notify">
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