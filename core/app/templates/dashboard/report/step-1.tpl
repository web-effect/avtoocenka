<div class="">

<div class="tabs | mb-40">
    <label>
        <input type="radio" value="auto" x-model="type">
        Автоматический поиск
    </label>
    <label>
        <input type="radio" value="manual" x-model="type">
        Ручной ввод
    </label>
</div>

<div class="report__wrapper">
    <main class="">
        <!-- Форма с VIN (всегда видна, кроме случая manual) -->

        <template x-if="!(type === 'manual')">
            <div>
                <template x-if="type == 'auto'">
                    <form action="" @submit.prevent="decodeVin()">
                        <section class="report__block">
                            <h2 class="report__block-title">Сведения об АМТС</h2>
                            <div class="report__block-inner"
                                :title="report.vehicle?.vin_decoded === true ? 'VIN используется в текущей оценке. Для поиска нового автомобиля, пожалуйста, создайте новую оценку' : ''"
                            >
                                {* <div class="grid lg:grid-cols-3 gap-20"> *}
                                {$params = [
                                    'name' => 'vin',
                                    'label' => 'VIN',
                                    'attrs' => '
                                        required
                                        x-model="vin"
                                        :readonly="report.vehicle?.vin_decoded === true"
                                    '
                                ]}
                                {include 'components/form-field' params=$params}
                        </section>

                        <div class="mt-20 text-center"
                            x-show="report.vehicle?.vin_decoded !== true"
                        >
                            <button class="button" type="submit">
                                Поиск по базе
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15.7895 17L20 12.5L15.7895 8M4 12.5H19.1579" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </template>

                <!-- Сообщения об успехе/ошибке -->
                <div class="flex items-center justify-center gap-8 mt-20 text-15 text-success"
                    x-show="report.vehicle?.vin_decoded === true"
                >
                    <svg fill="none" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><g stroke="#2c6e49" stroke-width="1.5"><path d="m22 12c0-5.52285-4.4772-10-10-10-5.52285 0-10 4.47715-10 10 0 5.5228 4.47715 10 10 10 5.5228 0 10-4.4772 10-10z"/><path d="m8 12.75s1.6.9125 2.4 2.25c0 0 2.4-5.25 5.6-7" stroke-linecap="round" stroke-linejoin="round"/></g></svg>
                    Автомобиль найден
                </div>

                <div class="flex items-center justify-center gap-8 mt-20 text-15 text-danger" x-show="report.vehicle?.vin_decoded === false">
                    Автомобиль не найден. Проверьте правильность ввода или
                    <button class="report__manual-button" @click.prevent="type = 'manual'">введите данные вручную</button>
                </div>
            </div>
        </template>

        <!-- Блок vehicle-params (включается ОДИН раз) -->
        <template x-if="type === 'manual' || report.vehicle?.vin_decoded === true">
            <div class="mt-20" >
                {include 'dashboard/report/vehicle-params'}
            </div>
        </template>

        <!-- Кнопки навигации -->
        {nav}
    </main>

    <aside class="report__right">
        <div class="report__alert">
            {'/app/img/icons/info-icon.svg'|svg}
            <span>
                В <b>VIN</b> разрешено использовать только латинские буквы (кроме I, o, q) и арабские цифры.
            </span>
        </div>
        {* <div class="report__alert">
            {'/app/img/icons/info-icon.svg'|svg}
            <span>
                В гос.номере допустимы только 12 русских букв: А, В, Е, К, М, Н, О, Р, С, Т, У, Х.
            </span>
        </div> *}
        <div class="report__alert">
            {'/app/img/icons/info-icon.svg'|svg}
            <span>
                <b>Кузов №</b> — указывается только при отсутствии VIN, либо при оценке автомобилей с правым расположением руля.
            </span>
        </div>
    </aside>
</div>
</div>