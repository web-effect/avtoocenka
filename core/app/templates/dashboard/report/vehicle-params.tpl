{function rowAttrs field=''} 
    {$apiName = $field}
    {if $field == 'brand'}
        {$apiName = 'mark'}
    {/if}
    x-data="{ 
        show: false,
        initialized: false,
        {if $field == 'brand' || $field == 'model'}
            search: '',
            filter(items) {
                {if $field == 'brand'}
                    if (!this.search) {
                        return items.filter(item => item.is_popular && item.is_in_top);
                    }
                {/if}
                const search = this.search.toLowerCase();
                return items.filter(item =>
                    item.name?.toLowerCase().startsWith(search) ||
                    item.id?.toLowerCase().startsWith(search) ||
                    item.{$apiName}?.cyrillic_name?.toLowerCase().startsWith(search)
                );
            }
        {/if}
    }"
    x-effect="
        if (!initialized && report.vehicle) {
            show = !report.vehicle?.{$field} && !report.vehicle?.{$field}_name;
            initialized = true;
        }
    "
{/function}

{function expandButton field='' label='Не указан' editable=true}
    {if $field == 'engine_type'} 
        {$text = "Dict.engineTypes[report.vehicle?.engine_type]?.name ?? report.vehicle?.{$field}"}
    {else if $field == 'gear_type'} 
        {$text = "Dict.gearTypes[report.vehicle?.gear_type]?.name ?? report.vehicle?.{$field}"}
    {else if $field == 'transmission'} 
        {$text = "Dict.transmissions[report.vehicle?.transmission]?.name ?? report.vehicle?.{$field}"}
    {else if $field == 'steering_wheel'} 
        {$text = "Dict.steeringWheels[report.vehicle?.steering_wheel]?.name ?? report.vehicle?.{$field}"}
    {else if $field == 'operation_start_date'} 
        {$text = "\$dateFormat(report.vehicle?.{$field})"}
    {else} 
        {$text = "report.vehicle?.{$field}"}
    {/if}

    <button
        class="report__table-expand {($editable) ? '_editable' : ''}"
        {if $editable}
            :class="{ '_editable': isFieldEditable('{$field}') }"
        {/if}
        @click.prevent="show = !show"
    >
        <span class="text-muted" x-show="!report.vehicle?.{$field}">{$label}</span>

        <span class="font-bold" x-show="report.vehicle?.{$field}" x-text="{$text}"></span>
        {'/app/img/icons/chevron-down-20.svg'|svg}
    </button>
{* 
    <div class="" x-text="!(report.vehicle?.vin_decoded_fields ?? []).includes('{$field}')"></div>
    <div class="">{$field}</div> *}
{/function}

<div class="report__block">
    <h2 class="report__block-title">Список данных</h2>
    
    <template x-if="report.vehicle">
    <form>
    <ul class="report__table">
        {* <li class="report__table-item">
            <div class="report__table-row">
                <div class="">Идентификационный номер (VIN)</div>
                <div class="font-bold">JT2BG12KOLS123456</div>
            </div>
        </li> *}

        <li class="report__table-item"
            {rowAttrs field='brand'}
        >
            <div class="report__table-row">
                <div class="">Марка*</div>
                {expandButton field='brand_name' label='Не указана'}
            </div>

            <template x-if="type == 'manual' || !(report.vehicle?.vin_decoded_fields ?? []).includes('brand_name')">
            <div class="report__table-content" x-show="show" @invalid.capture="show = true">
                <label class="">
                    <input class="input" type="search" x-model="search" placeholder="Поиск">
                </label>

                <div class="grid xl:grid-cols-3 gap-20 mt-20">
                    <template x-for="item in filter(vehicleApiData?.brands ?? [])" :key="item.id">
                        <label class="report__radio">
                            <img alt="" loading="lazy" :src="item.mark?.logo?.sizes?.logo">
                            <input type="radio" name="brand" required
                                @change="change('vehicle_brand', { id: item.id, name: item.name }); show = false;"
                                :checked="item.id == report.vehicle?.brand_id"
                            >
                            <span x-text="item.name"></span>
                        </label>
                    </template>
                </div>
            </div>
            </template>
        </li>

        <template x-if="report.vehicle?.vin_decoded || vehicleApiData?.models?.length">
        <li class="report__table-item"
            {rowAttrs field='model'}
        >
            <div class="report__table-row">
                <div class="">Модель*</div>
                {expandButton field='model_name' label='Не указана'}
            </div>

            <template x-if="type == 'manual' || !(report.vehicle?.vin_decoded_fields ?? []).includes('model_name')">
            <div class="report__table-content" x-show="show" @invalid.capture="show = true">
                <label class="">
                    <input class="input" type="search" x-model="search" placeholder="Поиск">
                </label>

                <div class="grid xl:grid-cols-3 gap-20 mt-20">
                    <template x-for="item in filter(vehicleApiData?.models ?? [])" :key="item.id">
                        <label class="report__radio">
                            <input type="radio" name="model" required
                                @change="change('vehicle_model', { id: item.id, name: item.name }); show = false;"
                                :checked="item.id == report.vehicle?.model_id"
                            >
                            <span x-text="item.name"></span>
                        </label>
                    </template>
                </div>
            </div>
            </template>
        </li>
        </template>

        <template x-if="
            (report.vehicle?.vin_decoded || vehicleApiData?.generations?.length) &&
            !(report.vehicle?.vin_decoded === true &&
            !(report.vehicle?.vin_decoded_fields ?? []).includes('generation_name'))
        ">
        <li class="report__table-item"
            {rowAttrs field='generation'}
        >
            <div class="report__table-row">
                <div class="">Поколение*</div>
                {expandButton field='generation_name' label='Не указано'}
            </div>

            <template x-if="type == 'manual' || !(report.vehicle?.vin_decoded_fields ?? []).includes('generation_name')">
            <div class="report__table-content" x-show="show" @invalid.capture="show = true">
                <div class="grid xl:grid-cols-3 gap-20 mt-20">
                    <template x-for="item in vehicleApiData?.generations ?? []" :key="item.id">
                        <label class="report__radio">
                            <input type="radio" name="generation_name" required
                                @change="change('vehicle_generation', { id: item.id, name: item.name }); show = false;"
                                :checked="item.name == report.vehicle?.generation_name"
                            >
                            <div x-text="item.name"></div>
                            <div class="">
                                (<span x-text="item.super_gen?.year_from"></span> — <span x-text="item.super_gen?.year_to || 'н. в.'"></span>)
                            </div>
                        </label>
                    </template>
                </div>
            </div>
            </template>
        </li>
        </template>

        <template x-if="report.vehicle?.vin_decoded || vehicleApiData?.configurations?.length">
        <li class="report__table-item"
            {rowAttrs field='configuration'}
        >
            <div class="report__table-row">
                <div class="">Тип кузова*</div>
                {expandButton field='configuration_name'}
            </div>

            <template x-if="type == 'manual' || !(report.vehicle?.vin_decoded_fields ?? []).includes('configuration_name')">
            <div class="report__table-content" x-show="show" @invalid.capture="show = true">
                <div class="grid xl:grid-cols-3 gap-20 mt-20">
                    <template x-for="item in vehicleApiData?.configurations ?? []" :key="item.id">
                        <label class="report__radio">
                            <input type="radio" name="configuration" required
                                @change="change('vehicle_configuration', { id: item.id, name: item.name }); show = false;"
                                :checked="item.id == report.vehicle?.configuration_id"
                            >
                            <span x-text="item.name"></span>
                        </label>
                    </template>
                </div>
            </div>
            </template>
        </li>
        </template>

        <template x-if="report.vehicle?.vin_decoded || vehicleApiData?.modifications?.length">
        <li class="report__table-item"
            {rowAttrs field='modification'}
        >
            <div class="report__table-row">
                <div class="">Модификация*</div>
                {expandButton field='modification_name' label='Не указана'}
            </div>

            <template x-if="type == 'manual' || !(report.vehicle?.vin_decoded_fields ?? []).includes('modification_name')">
            <div class="report__table-content" x-show="show" @invalid.capture="show = true">
                <div class="grid xl:grid-cols-3 gap-20 mt-20">
                    <template x-for="item in vehicleApiData?.modifications ?? []" :key="item.id">
                        <label class="report__radio">
                            <input type="radio" name="modification" required
                                @change="change('vehicle_modification', item); show = false;"
                                :checked="item.id == report.vehicle?.modification_id"
                            >
                            <div x-text="item.tech_params?.human_name"></div>
                            <div class="">
                                (<span x-text="item.tech_params?.year_start"></span> — <span x-text="item.tech_params?.year_stop || 'н. в.'"></span>)
                            </div>
                        </label>
                    </template>
                </div>
            </div>
            </template>
        </li>
        </template>

        <li class="report__table-item" {rowAttrs field='year'}>
            <div class="report__table-row">
                <div class="">Год выпуска*</div>
                {expandButton field='year'}
            </div>

            <template x-if="(type == 'manual' && report.vehicle.year_from) || !(report.vehicle?.vin_decoded_fields ?? []).includes('year')">
            <div class="report__table-content" x-show="show" @invalid.capture="show = true">
                {$params = [
                    'name' => 'year',
                    'label' => 'Год выпуска',
                    'type' => 'number',
                    'attrs' => '
                        required
                        pattern = "[0-9]{4}"
                        x-mask="9999"
                        :min="report.vehicle.year_from ?? 1900"
                        :max="report.vehicle.year_to ?? new Date().getFullYear()"
                        :value="report.vehicle.year"
                        @change="change(\'vehicle_year\', $event.target.value); show = false;"
                    '
                ]}
                {include 'components/form-field' params=$params}
            </div>
            </template>
        </li>

        <template x-if="report.vehicle?.engine_type">
        <li class="report__table-item" {rowAttrs field='engine_type'}>
            <div class="report__table-row">
                <div class="">Двигатель*</div>
                {expandButton field='engine_type' editable=false}
            </div>
            {* <div class="report__table-content" x-show="show">
                <div class="grid xl:grid-cols-3 gap-20 mt-20">
                    <label class="report__radio">
                        <span x-text="vehicle.engine_type"></span>
                    </label>
                </div>
            </div> *}
        </li>
        </template>

        <template x-if="report.vehicle?.engine_capacity">
        <li class="report__table-item" {rowAttrs field='engine_capacity'}>
            <div class="report__table-row">
                <div class="">Объём двигателя*</div>
                {expandButton field='engine_capacity' editable=false}
            </div>
        </li>
        </template>

        <template x-if="report.vehicle?.gear_type">
        <li class="report__table-item" {rowAttrs field='gear_type'}>
            <div class="report__table-row">
                <div class="">Привод*</div>
                {expandButton field='gear_type' editable=false}
            </div>
            {* <div class="report__table-content" x-show="show">
                <div class="grid xl:grid-cols-3 gap-20 mt-20">
                    <label class="report__radio">
                        <span x-text="vehicle.gear_type"></span>
                    </label>
                </div>
            </div> *}
        </li>
        </template>

        <template x-if="report.vehicle?.transmission">
        <li class="report__table-item" {rowAttrs field='transmission'}>
            <div class="report__table-row">
                <div class="">Коробка передач*</div>
                {expandButton field='transmission' editable=false}
            </div>
            {* <div class="report__table-content" x-show="show">
                <div class="grid xl:grid-cols-3 gap-20 mt-20">
                    <label class="report__radio">
                        <span x-text="vehicle.transmission"></span>
                    </label>
                </div>
            </div> *}
        </li>
        </template>

        <li class="report__table-item" {rowAttrs field='steering_wheel'}>
            <div class="report__table-row">
                <div class="">Положение руля*</div>
                {expandButton field='steering_wheel' label='Не указано'}
            </div>

            <template x-if="type == 'manual' || !(report.vehicle?.vin_decoded_fields ?? []).includes('steering_wheel')">
            <div class="report__table-content" x-show="show" @invalid.capture="show = true">
                <div class="grid xl:grid-cols-2 gap-20">
                    <label class="report__radio">
                        <input type="radio" name="steering_wheel" required
                            @change="change('vehicle_steering_wheel', 'LEFT'); show = false;"
                            :checked="report.vehicle?.steering_wheel == 'LEFT'"
                        >
                        <span>Слева</span>
                    </label>
                    <label class="report__radio">
                        <input type="radio" name="steering_wheel" required
                            @change="change('vehicle_steering_wheel', 'RIGHT'); show = false;"
                            :checked="report.vehicle?.steering_wheel == 'RIGHT'"
                        >
                        <span>Справа</span>
                    </label>
                </div>
            </div>
            </template>
        </li>


        <li class="report__table-item" {rowAttrs field='body_number'}>
            <div class="report__table-row">
                <div class="">Номер кузова (кабины)</div>
                {expandButton field='body_number'}
            </div>

            <div class="report__table-content" x-show="show">
                {$params = [
                    'name' => 'body_number',
                    'label' => 'Кузов №',
                    'description' => 'Серия + номер кузова по ПТС/СТС',
                    'attrs' => ':value="report.vehicle.body_number"
                        @change="change(\'vehicle_body_number\', $event.target.value); show = false;"'
                ]}
                {include 'components/form-field' params=$params}
            </div>
        </li>

        <li class="report__table-item" {rowAttrs field='engine_number'}>
            <div class="report__table-row">
                <div class="">Номер двигателя</div>
                {expandButton field='engine_number'}
            </div>

            <div class="report__table-content" x-show="show">
                {$params = [
                    'name' => 'engine_number',
                    'label' => 'Номер',
                    'description' => 'Необязательно — указывается, если есть в документах',
                    'attrs' => ':value="report.vehicle.engine_number"
                        @change="change(\'vehicle_engine_number\', $event.target.value); show = false;"'
                ]}
                {include 'components/form-field' params=$params}
            </div>
        </li>

        <li class="report__table-item" {rowAttrs field='mileage'}>
            <div class="report__table-row">
                <div class="">Пробег</div>
                {expandButton field='mileage'}
            </div>

            <div class="report__table-content" x-show="show">
                {$params = [
                    'name' => 'mileage',
                    'label' => 'Пробег',
                    'attrs' => ':value="report.vehicle.mileage"
                        @change="change(\'vehicle_mileage\', $event.target.value); show = false;"'
                ]}
                {include 'components/form-field' params=$params}
            </div>
        </li>

        <li class="report__table-item" {rowAttrs field='region'}>
            <div class="report__table-row">
                <div class="">Регион*</div>
                {expandButton field='region'}
            </div>

            <div class="report__table-content" x-show="show">
                <label class="field">
                    <div class="field__wrapper">
                        <select class="input" name="region" required
                            :value="report.vehicle?.region"
                            @change="change('vehicle_region', $event.target.value); show = false;"
                        >
                            <option value="">Не выбран</option>

                            <template x-for="item in Dict.regions" :key="item">
                                <option
                                    :value="item"
                                    :selected="item == report.vehicle?.region"
                                    x-text="item"
                                ></option>
                            </template>
                        </select>
                    </div>
                </label>
            </div>
        </li>

        {* <li class="report__table-item" {rowAttrs field='technical_condition'}>
            <div class="report__table-row">
                <div class="">Техническое состояние*</div>
                {expandButton field='technical_condition' label='Не выбрано'}
            </div>

            <div class="report__table-content" x-show="show">
                <div class="report__table-radios">
                    {$arr = ['Новое', 'Очень хорошее', 'Хорошее', 'Удовлетворительное', 'Условно пригодное', 'Неудовлетворительное', 'Предельное']}

                    {foreach $arr as $item}
                        <label class="report__radio">
                            <input type="radio" name="technical_condition" value="{$item}" required
                                @change="change('vehicle_technical_condition', $event.target.value); show = false;"
                                :checked="report.vehicle?.technical_condition == '{$item}'"
                            >
                            <div class="report__radio-value">{$item}</div>
                        </label>
                    {/foreach}
                </div>
            </div>
        </li> *}

        <li class="report__table-item" {rowAttrs field='technical_condition'}>
            <div class="report__table-row">
                <div class="">Техническое состояние*</div>
                {expandButton field='technical_condition' label='Не выбрано'}
            </div>

            <div class="report__table-content" x-show="show">
                <div class="report__table-radios">
                    <template
                        x-for="(range, condition) in technicalConditions"
                        :key="condition"
                    >
                        <label class="report__radio">
                            <input
                                required
                                type="radio"
                                name="technical_condition"
                                :value="condition"
                                {* @change="setTechnicalCondition(condition)" *}
                                @change="change('vehicle_technical_condition', condition); show = false;"
                                :checked="report.vehicle?.technical_condition === condition"
                            >

                            <div
                                class="report__radio-value"
                                x-text="condition"
                            ></div>
                        </label>
                    </template>
                </div>

                {* <div class="mt-20" x-show="report.vehicle?.technical_condition">
                    <label class="field">
                        <input
                            class="input"
                            type="number"
                            name="wear"
                            required
                            :min="currentTechnicalCondition?.min"
                            :max="currentTechnicalCondition?.max"
                            step="1"
                            :value="report.vehicle?.wear ?? ''"
                            {literal}
                            :placeholder="currentTechnicalCondition
                                ? `${currentTechnicalCondition.min}–${currentTechnicalCondition.max}`
                                : ''"
                            {/literal}
                            @change="if (validateWear($event)) show = false"
                        >
                        <div class="field__label">Коэффициент износа, %</div>
                    </label>
                </div> *}
            </div>
        
        </li>

        <li class="report__table-item" {rowAttrs field='operation_start_date'}>
            <div class="report__table-row">
                <div class="">Дата начала эксплуатации</div>
                {expandButton field='operation_start_date' label='Не указана'}
                {* <div class="">
                    <b class="" x-show="vehicleAge" x-text="vehicleAge"></b>
                    <span class="text-muted" x-show="!vehicleAge">Не указан</span>
                </div> *}
            </div>

            <div class="report__table-content" x-show="show">
                <div class="grid xl:grid-cols-2 gap-20 items-end">
                    {$params = [
                        'name' => 'operation_start_date',
                        'type' => 'date',
                        'label' => 'Дата',
                        'attrs' => '
                            :value="report.vehicle.operation_start_date"
                            :max="new Date().toISOString().split(\'T\')[0]" 
                            @change="change(\'vehicle_operation_start_date\', $event.target.value); show = false;"'
                    ]}
                    {include 'components/form-field' params=$params}

                    {* <div class="" x-show="report.vehicle.operation_start_date">
                        <div class="report__table-row">
                            <div class="">Дата оценки:</div>
                            <span class="font-medium" x-text="formatDate()"></span>
                        </div>
                        <div class="report__table-row | mt-5">
                            <div class="">Дата начала эксплуатации:</div>
                            <span class="font-medium" x-text="formatDate(report.vehicle.operation_start_date)"></span>
                        </div>
                    </div> *}
                </div>
            </div>
        </li>

        <li class="report__table-item" {rowAttrs field='registration_certificate'}>
            <div class="report__table-row">
                <div class="">Документы, идентифицирующие АМТС</div>
                {expandButton field='registration_certificate'}
            </div>

            <div class="report__table-content" x-show="show">
                <div class="grid xl:grid-cols-2 gap-20">
                    {$params = [
                        'name' => 'registration_certificate',
                        'label' => 'Свидетельство о регистрации АМТС (СТС)',
                        'description' => 'Серия и номер, например: 77АА 024506',
                        'attrs' => 'required :value="report.vehicle.registration_certificate"
                            @change="change(\'vehicle_registration_certificate\', $event.target.value);"'
                    ]}
                    {include 'components/form-field' params=$params}

                    {$params = [
                        'type' => 'date',
                        'name' => 'registration_certificate_date',
                        'label' => 'Дата выдачи',
                        'attrs' => 'required :value="report.vehicle.registration_certificate_date"
                            @change="change(\'vehicle_registration_certificate_date\', $event.target.value);"'
                    ]}
                    {include 'components/form-field' params=$params}
                </div>
            </div>
        </li>

        <li class="report__table-item" {rowAttrs field='registration_number'}>
            <div class="report__table-row">
                <div class="">Госномер</div>
                {expandButton field='registration_number'}
            </div>
            
            <div class="report__table-content" x-show="show">
                {$params = [
                    'name' => 'registration_number',
                    'label' => 'Госномер',
                    'attrs' => ':value="report.vehicle.registration_number"
                        @change="change(\'vehicle_registration_number\', $event.target.value); show = false;"'
                ]}
                {include 'components/form-field' params=$params}
            </div>
        </li>

        <template x-if="type == 'manual'">
        <li class="report__table-item" {rowAttrs field='vin'}>
            <div class="report__table-row">
                <div class="">VIN</div>
                {expandButton field='vin'}
            </div>
            
            <div class="report__table-content" x-show="show">
                {$params = [
                    'name' => 'vin',
                    'label' => 'VIN',
                    'attrs' => 'required :value="report.vehicle.vin"
                        @change="change(\'vehicle_vin\', $event.target.value); show = false;"'
                ]}
                {include 'components/form-field' params=$params}
            </div>
        </li>
        </template>

    </ul>
    </form>
    </template>
</div>