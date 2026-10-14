<div class="report__second">
    <div class="report__title">
        Оценка ТС:
        <span x-text="report.vehicle.brand_name"></span>
        <span x-text="report.vehicle.model_name"></span>
        <span x-text="report.vehicle.year"></span>
    </div>
    <div class="">
        <div class="grade__items">
            <div class="grade__top">
                <div class="grade__assessment">
                    <div class="grade__assessment-header">
                        <div class="grade__assessment-title">Расчёт стоимости</div>
                        {* <div class="grade__assessment-description">На основе 5 аналогов</div> *}
                    </div>
                    <div class="grade__assessment-inner">
                        <div class="grade__assessment-row">
                            <div class="grade__assessment-row-name">Средняя цена аналогов</div>
                            <div class="grade__assessment-row-value grade__assessment-row-first">
                                <span x-text="$numberFormat(report.price, '₽')"></span>
                            </div>
                        </div>
                        <div class="grade__assessment-row">
                            <div class="grade__assessment-row-name">Корректировка на торг
                                <span class="grade__assessment-row-percent">
                                (<span x-text="$numberFormat(report.discount * -1, '%')"></span>)
                                </span>
                            </div>
                            <div class="grade__assessment-row-value grade__assessment-row-value-yellow grade__assessment-row-first">
                                <span x-text="$numberFormat(report.discount_price * -1, '₽')"></span>
                            </div>
                        </div>
                        <div class="grade__assessment-result">
                            <div class="grade__assessment-result-name">Итоговая стоимость АМТС</div>
                            <div class="grade__assessment-result-value grade__assessment-result-final">
                                <span x-text="$numberFormat(report.price - report.discount_price, '₽')"></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="grade__correction">
                    <div class="grade__correction-header">
                        <div class="grade__correction-title">Корректировка на снижение цены в процессе торга</div>
                        <div class="grade__correction-description">Применяется к средней цене аналогов. Типичное значение для рынка б/у авто: -5%  _ -10%</div>
                    </div>
                    <div class="grade__list">
                        {$arr = [0, 5, 10, 15, 20, 30, 40]}
                        {foreach $arr as $item}
                            <label class="grade__radio">
                                <input type="radio" name="radio_discount" value="{$item}" x-model="discount"
                                    @change="change('discount', $event.target.value)"
                                >
                                <div class="grade__radio-value">
                                    {if $item}−{/if}{$item}%
                                </div>
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
                            Найдено объявлений: <span x-text="report.api_offers.length"></span> 
                        </div>
                        {* <div class="grade__cars-found">
                            Используется в расчёте: <span>5</span>
                            (убрать?)
                        </div> *}
                    </div>
                </div>

                <div class="report__alert mb-20">
                    {'/app/img/icons/info-icon.svg'|svg}
                    <span>
                        Если автомобили в выбранном регионе отсутствуют, выводятся все автомобили из РФ
                    </span>
                </div>

                {* <div class="" x-text="JSON.stringify(
                    report.api_offers ?? [])
                            .filter(item => item.selected)
                            .sort((a, b) => a.selected - b.selected
                )"></div> *}

                <ul class="grade__cars-items">
                    <template
                        x-for="item in displayedOffers"
                        :key="String(item.id)"
                    >
                    <li class="grade__cars-item">
                        <div class="grade__cars-image">
                            <template x-if="item.images[0]">
                                <img :src="item.images[0]" alt="">
                            </template>
                            {* <template x-if="item.images[1]">
                                <img :src="item.images[1]" alt="">
                            </template> *}
                            <template x-if="!item.images[0]">
                                <img src="/app/img/icons/car-image.png" alt="">
                            </template>
                            {* <img :src="item.images[1]" alt=""> *}
                        </div>
                        <div class="grade__cars-item-wrapper">
                            <div class="grade__cars-content">
                                <div class="grade__cars-top" >
                                    <div class="grade__cars-iteration" x-text="item.selected"
                                    ></div>
                                    <div class="grade__cars-wrapper">
                                        <div class="grade__cars-name">
                                            <span x-text="item.title"></span>
                                            {* <span x-text="item.model"></span> *}
                                        </div>
                                        <div class="grade__cars-year" x-text="item.year"></div>
                                    </div>
                                    <div class="grade__cars-cost" x-text="$numberFormat(item.price, '₽')"></div>
                                    <div class="">
                                        <a :href="item.url" target="_blank">Ссылка</a>
                                    </div>
                                </div>
                                <div class="grade__cars-options">
                                    {* <div class="grade__cars-option">
                                        <div class="grade__cars-option-name">Цвет</div>
                                        <div class="grade__cars-option-value">—</div>
                                    </div> *}
                                    <div class="grade__cars-option">
                                        <div class="grade__cars-option-name">Мощность</div>
                                        <div class="grade__cars-option-value" x-text="item.power + ' л.с.'"></div>
                                    </div>
                                    <div class="grade__cars-option">
                                        <div class="grade__cars-option-name">Трансмиссия</div>
                                        <div class="grade__cars-option-value" x-text="item.transmission"></div>
                                    </div>
                                    <div class="grade__cars-option">
                                        <div class="grade__cars-option-name">Кузов</div>
                                        <div class="grade__cars-option-value" x-text="item.body_type"></div>
                                    </div>
                                    <div class="grade__cars-option">
                                        <div class="grade__cars-option-name">Двигатель</div>
                                        <div class="grade__cars-option-value" x-text="item.engine_type"></div>
                                    </div>
                                    <div class="grade__cars-option">
                                        <div class="grade__cars-option-name">Привод</div>
                                        <div class="grade__cars-option-value" x-text="item.gear_type"></div>
                                    </div>
                                    <div class="grade__cars-option">
                                        <div class="grade__cars-option-name">Пробег</div>
                                        <div class="grade__cars-option-value" x-text="$numberFormat(item.mileage, 'км')"></div>
                                    </div>
                                </div>
                                <div class="grade__cars-location">
                                    {'/app/img/icons/location.svg'|svg}
                                    <span x-text="item.region"></span>
                                </div>
                                <div class="text-12 text-muted" x-text="$dateFormat(item.created)"></div>
                                {* <div class="" x-text="item.Id"></div> *}
                            </div>
                            <button class="grade__cars-update" @click="replaceOffer(item.id)">
                                {'/app/img/icons/arrow-reload-horizontal-round.svg'|svg}
                            </button>
                        </div>
                    </li>
                    </template>
                </ul>
            </div>

            {nav}
            
            {* <div class="report__buttons"> 
                <button class="report__prev report__btn" @click="changeStage('АМТС')">{'/app/img/icons/arrow-right-thin.svg'|svg} Назад</button>
                <button class="report__next report__btn" @click="changeStage('Заказчик')">Продолжить {'/app/img/icons/arrow-right-thin.svg'|svg}</button>
            </div> *}
        </div>
        <div class="grade__notification">
            {* <div class="report__alert">
                {'/app/img/icons/info-icon.svg'|svg}
                <span>
                    Цена АМТС определена как <b>среднее арифметическое</b> из включённых аналогов.
                </span>
            </div> *}
            {* <div class="report__alert _warning">
                {'/app/img/icons/alert.svg'|svg}
                <span>
                    Поиск аналогов выполняется по регионам: <b>Кировская обл., Татарстан, Марий Эл, Пермский край, Удмуртия.</b>
                </span>
            </div> *}
        </div>
    </div>
</div>