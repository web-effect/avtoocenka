{extends 'dashboard.tpl'}
{block 'header'}{/block}
{block 'page_content'}
    {$required = \App\Services\UserService::REQUIRED_EXT_FIELDS}

    {function field params=[]}
        <label class="field">
            <div class="field__wrapper">
                {if $params.type == 'select'}
                    <select name="{$params.name}" class="input"
                        x-model.lazy="profile.{$params.name}"
                        @change="change()"
                        :required="required[appraiserType]?.includes('{$params.name}')"
                    >
                        <option value="">Выберите...</option>
                        {foreach $params.values as $opt} 
                            <option value="{$opt}">{$opt}</option>
                        {/foreach}
                    </select>
                {else} 
                    {* {if $params.type == 'date'} 
                        <input class="js-air-datepicker" type="text">
                    {/if} *}
                    <input
                        class="input"
                        type="{$params.type|default:'text'}"
                        name="{$params.name}"
                        value="{$profile[$params.name]}"
                        placeholder="{$params.placeholder}"
                        x-model.lazy="profile.{$params.name}"
                        @change="change()"
                        :required="required[appraiserType]?.includes('{$params.name}')"
                        {$params.attrs}
                        {if $params.mask == 'inn'} 
                            {literal}pattern=".{10,}"{/literal}
                            data-maska="##########00" data-maska-tokens="0:[0-9]:optional"
                        {/if}
                        {if $params.mask == 'inn-ogrn'} 
                            {literal}pattern=".{10,}"{/literal}
                            data-maska="##########000" data-maska-tokens="0:[0-9]:optional"
                        {/if}
                    >
                {/if}
            </div>
            <div class="field__label">{$params.label}</div>
        </label>
    {/function}

    {function file label='' fieldName=''}
        <label
            class="field"
            @dragover.prevent="drag = true"
            @dragleave.prevent="drag = false"
            @drop.prevent="dropFile($event, '{$fieldName}')"
        >
            <div class="field__wrapper _file" :class="{ '_uploaded': profile.{$fieldName} }">
                <input
                    class="input"
                    type="text"
                    placeholder="Загрузить файл..."
                    {* @input="$event.target.value = profile.{$fieldName} || ''" *}
                    @focus="$event.target.blur()"
                    @click="$el.closest('.field').querySelector('[type=file]').click()"
                    :required="required[appraiserType]?.includes('{$fieldName}')"
                    :value="profile.{$fieldName} || ''"
                >
                <input
                    type="file"
                    accept=".jpg, .jpeg, .png"
                    hidden
                    @change="selectFile($event, '{$fieldName}')"
                >
                <span ></span>
                <button tabindex="-1"
                    x-show="profile.{$fieldName}"
                    @click.prevent="deleteFile('{$fieldName}')"
                >
                    <svg fill="none" height="20" viewBox="0 0 20 20" width="20" xmlns="http://www.w3.org/2000/svg"><g stroke="#5b6375" stroke-linecap="round" stroke-width="1.5"><path d="m16.25 4.58337-.3348 5.42183m-12.1652-5.42183.50384 8.35393c.12904 2.1396.19356 3.2093.7284 3.9786.26442.3803.60509.7014 1.0003.9426.55941.3416 1.25169.4441 2.35079.4749"/><path d="m16.6667 12.5-5.8334 5.8329m5.8334.0004-5.8334-5.8329" stroke-linejoin="round"/><path d="m2.5 4.58329h15m-4.1202 0-.5689-1.17356c-.3779-.77956-.5669-1.16934-.8928-1.41243-.0723-.05392-.1489-.10189-.2289-.14342-.3609-.18725-.7941-.18725-1.6604-.18725-.88811 0-1.33215 0-1.69906.1951-.08132.04324-.15892.09314-.232.1492-.32971.25295-.51388.65699-.88224 1.46508l-.50473 1.10728"/></g></svg>
                </button>
            </div>
            <div class="field__label">{$label}</div>
        </label>
    {/function}

    <form class="appraiser"
        x-data='appraiser({$profile|json_encode|escape}, {$required|json_encode})'
        {* @submit.prevent="save()" *}
        {* @change="
            if (!$event.target.matches('input, select, textarea') || $event.target.matches('[type=file]')) return;
            change($event.target.name, $event.target.value);
        " *}
    >

        <div class="appraiser__top">
            <div class="tabs">
                {* <label>
                    <input type="radio" name="appraiser_type" value="hired" x-model="profile.appraiser_type"
                        @change="appraiserType = 'hired'; change($event.target.name, $event.target.value)";
                    >
                    Оценщик по найму
                </label> *}
                <label>
                    <input type="radio" name="appraiser_type" value="private" x-model="profile.appraiser_type"
                        @change="appraiserType = 'private'; change($event.target.name, $event.target.value)";
                    >
                    Частнопрактикующий оценщик
                </label>
            </div>

            <div class="appraiser__notification">
                <div class="appraiser__notify">
                    {'/app/img/icons/info-icon.svg'|svg}
                    <span>Заполните сведения — они будут автоматически подставляться в отчёты об оценке.</span>
                </div>
                <div class="appraiser__notify" x-show="appraiserType == 'hired'">
                    {'/app/img/icons/info-icon.svg'|svg}
                    <span>Укажите необходимые данные, если осуществляете профессиональную деятельность на основании трудового договора с оценочной компанией.</span>
                </div>
                <div class="appraiser__alert" x-show="!profile.is_complete">
                    {'/app/img/icons/alert.svg'|svg}
                    <span>Обязательные поля не заполнены! Возможно некорректное отображение данных в отчёте об оценке.</span>
                </div>
            </div>
        </div>

        <div class="appraiser__forms">
            <template x-if="appraiserType == 'hired'">
            <div class="appraiser__form">
                <div class="appraiser__block">
                    <div class="appraiser__block-title">Сведения о юридическом лице</div>
                    <div class="appraiser__block-inner">
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'company_inn_ogrn',
                                'label' => 'ИНН или ОГРН',
                                'placeholder' => 'ОГРН или ИНН оценочной компании',
                                'mask' => 'inn-ogrn'
                            ]}
                            {field params=$params}
                            {* {field params=$params} *}
                            
                            {$params = [
                                'name' => 'company_director_name',
                                'label' => 'Руководитель организации, ФИО:',
                                'placeholder' => 'Иванов Иван Иванович'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'company_director_position',
                                'label' => 'Должность руководителя:',
                                'placeholder' => 'Генеральный директор',
                                'attrs' => 'required'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'company_legal_address',
                                'label' => 'Юридический адрес организации:',
                                'placeholder' => 'г. Город, ул. Улица, д. 1'
                            ]}
                            {field params=$params}
                        </div>
                        {* {$params = [
                            'name' => 'company_actual_address',
                            'label' => 'Фактический адрес организации:',
                            'placeholder' => 'г. Город, ул. Улица, д. 1',
                            'attrs' => 'required'
                        ]}
                        {field params=$params} *}

                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'company_phone',
                                'label' => 'Телефон организации:',
                                'placeholder' => '+7 (___) ___ - __ - __',
                                'type' => 'tel',
                                'attrs' => 'required'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'company_email',
                                'label' => 'E-mail организации:',
                                'placeholder' => 'org@mail.ru',
                                'attrs' => 'required'
                            ]}
                            {field params=$params}

                        </div>
                        {* <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'company_website',
                                'label' => 'Веб-сайт организации:',
                                'placeholder' => 'http://'
                            ]}
                            {field params=$params}

                            {file fieldName='company_logo' label='Логотип организации (используется в отчёте об оценке)'}
                        </div> *}
                        <div class="appraiser__row appraiser__row2">
                            {file fieldName='company_registration_certificate' label='Скан свидетельства о гос. регистрации юр. лица:'}
                            
                            {* {$params = [
                                'name' => 'company_director_ecp_certificate_number',
                                'label' => 'Номер сертификата ЭЦП руководителя',
                                'placeholder' => 'Номер сертификата'
                            ]}
                            {field params=$params} *}
                        </div>
                        {* <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'company_director_ecp_valid_from',
                                'label' => 'Действителен с',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'company_director_ecp_valid_to',
                                'label' => 'Действителен до',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                        </div> *}
                    </div>
                </div>

                <div class="appraiser__block">
                    <div class="appraiser__block-title">Сведения о страховании ответственности юридического лица</div>
                    <div class="appraiser__block-inner">
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'company_insurer_name',
                                'label' => 'Наименование страховщика юр. лица',
                                'placeholder' => 'Наименование страховой компании'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'company_insurance_policy_number',
                                'label' => 'Номер договора (полиса) страхования ответственности юр.лица:',
                                'placeholder' => 'Номер договора'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row4">
                            {$params = [
                                'name' => 'company_insurance_contract_date',
                                'label' => 'Дата заключения договора страхования',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {file fieldName='company_insurance_scan' label='Скан договора (полиса) страхования юр. лица'}
                            {file fieldName='company_insurance_scan_page2' label='Скан договора страхования юр. лица (стр. 2 при наличии)'}
                        </div>
                        <div class="appraiser__row appraiser__row4">
                            {$params = [
                                'name' => 'company_insurance_amount',
                                'type' => 'number',
                                'label' => 'Страховая сумма',
                                'placeholder' => '0,00 руб.'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'company_insurance_valid_from',
                                'label' => 'Срок действия договора (полиса) с',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'company_insurance_valid_to',
                                'label' => 'Срок действия договора (полиса) до',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                        </div>
                    </div>
                </div>

                <div class="appraiser__block">
                    <div class="appraiser__block-title">Сведения об оценщике</div>
                    <div class="appraiser__block-inner">
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_name',
                                'label' => 'ФИО оценщика',
                                'placeholder' => 'Иванов Иван Иванович'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'appraiser_sro',
                                'label' => 'Информация о членстве в СРО)',
                                'type' => 'select',
                                'values' => [
                                    'Некоммерческое партнерство «САМОРЕГУЛИРУЕМАЯ ОРГАНИЗАЦИЯ АССОЦИАЦИИ РОССИЙСКИХ МАГИСТРОВ ОЦЕНКИ»',
                                    'Некоммерческое партнерство «Саморегулируемая организация Деловой Союз Оценщиков»',
                                    'Ассоциация «Межрегиональный Союз Оценщиков»',
                                    'Ассоциация саморегулируемая организация «Национальная коллегия специалистов-оценщиков»',
                                    'Межрегиональная саморегулируемая некоммерческая организация — Некоммерческое партнерство «Общество профессиональных экспертов и оценщиков»',
                                    'Саморегулируемая организация Региональная ассоциация оценщиков',
                                    'Ассоциация саморегулируемая организация оценщиков «Свободный Оценочный Департамент»',
                                    'Ассоциация Саморегулируемая организация «СОЮЗ»',
                                    'Саморегулируемая межрегиональная ассоциация оценщиков',
                                    'Саморегулируемая организация Ассоциация оценщиков «Сообщество профессионалов оценки»',
                                    'Саморегулируемая организация «Союз «Федерация Специалистов Оценщиков»',
                                    'Ассоциация «Саморегулируемая организация оценщиков «Экспертный совет»',
                                    'Ассоциация «РУССКОЕ ОБЩЕСТВО ОЦЕНЩИКОВ»'
                                ]
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_sro_registry_number',
                                'label' => 'Регистрационный номер в реестре СРО',
                                'placeholder' => '00000'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'appraiser_sro_certificate_number',
                                'label' => 'Номер свидетельства СРО',
                                'placeholder' => 'Номер свидетельства'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_sro_certificate_date',
                                'label' => 'Дата выдачи свидетельства СРО',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                            
                            {file fieldName='appraiser_sro_certificate_scan' label='Скан-копия свидетельства СРО'}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {file fieldName='appraiser_sro_extract_scan' label='Скан-копия выписки из свидетельства СРО'}
                            {* 
                            {$params = [
                                'name' => 'appraiser_ecp_certificate_number',
                                'label' => 'Номер сертификата ЭЦП оценщика',
                                'placeholder' => 'Номер сертификата'
                            ]}
                            {field params=$params}
                            *}
                        </div> 
                        {* <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_ecp_valid_from',
                                'label' => 'Действителен с',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'appraiser_ecp_valid_to',
                                'label' => 'Действителен до',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                        </div> *}
                    </div>
                </div>

                <div class="appraiser__block">
                    <div class="appraiser__block-title">Документы о профессиональных знаниях в области оценочной деятельности</div>
                    <div class="appraiser__block-inner">
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'education_institution_name',
                                'label' => 'Наименование учебного заведения, выдавшего докуент об образовании (квалификации)',
                                'placeholder' => 'Название учебного заведения'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'diploma_series_number',
                                'label' => 'Серия и номер диплома',
                                'placeholder' => 'Серия и номер'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'diploma_registration_number',
                                'label' => 'Регистрационный номер диплома',
                                'placeholder' => 'Регистрационный номер'
                            ]}
                            {field params=$params}

                            {file fieldName='diploma_scan' label='Скан-копия диплома об образовании (квалификации)'}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'qualification_certificate_number',
                                'label' => 'Номер квалификационного аттестата в области оценочной деятельности',
                                'placeholder' => 'Номер аттестата'
                            ]}
                            {field params=$params}
                            
                            {file fieldName='qualification_certificate_scan' label='Скан-копия квалификационного аттестата в области оценочной деятельности'}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'qualification_certificate_date',
                                'label' => 'Дата выдачи квалификационного аттестата',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                            
                            
                            {$params = [
                                'name' => 'appraisal_experience_start',
                                'label' => 'Начало оценочной деятельности',
                                'type' => 'number',
                                'attrs' => '
                                    pattern = "[0-9]{4}"
                                    x-mask="9999"
                                    :min="1970"
                                    :max="new Date().getFullYear()"
                                '
                            ]}
                            {field params=$params}
                            {* {$params = [
                                'name' => 'appraisal_experience',
                                'label' => 'Стаж работы в оценочной деятельности',
                                'type' => 'select',
                                'values' => ['3 года', '4 года', 'Более 5 лет']
                            ]}
                            {field params=$params} *}
                        </div>
                    </div>
                </div>

                <div class="appraiser__block">
                    <div class="appraiser__block-title">Сведения о страховании ответственности оценщика</div>
                    <div class="appraiser__block-inner">
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_insurer_name',
                                'label' => 'Наименование страховщика оценщика',
                                'placeholder' => 'Наименование страховой компании'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'appraiser_insurance_policy_number',
                                'label' => 'Номер договора (полиса) страхования ответственности оценщика',
                                'placeholder' => 'Номер договора'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row4">
                            {$params = [
                                'name' => 'appraiser_insurance_contract_date',
                                'label' => 'Дата заключения договора страхования',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {file fieldName='appraiser_insurance_scan' label='Скан договора (полиса) страхования ответственности оценщика'}
                            
                            {file fieldName='appraiser_insurance_scan_page2' label='Скан договора страхования оценщика (стр. 2 при наличии)'}
                        </div>
                        <div class="appraiser__row appraiser__row4">
                            {$params = [
                                'name' => 'appraiser_insurance_amount',
                                'label' => 'Страховая сумма',
                                'type' => 'number',
                                'placeholder' => '0,00 руб.'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_insurance_valid_from',
                                'label' => 'Срок действия договора (полиса) с',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'appraiser_insurance_valid_to',
                                'label' => 'Срок действия договора (полиса) до',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                        </div>
                    </div>
                </div>

                {* <div class="appraiser__block">
                    <div class="appraiser__block-title">Сведения о страховании ответственности оценщика</div>
                    <div class="appraiser__block-inner">
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'employment_contract_number',
                                'label' => 'Номер трудового договора',
                                'placeholder' => 'Номер договора'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'employment_contract_date',
                                'label' => 'Дата заключения трудового договора',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                        </div>
                    </div>
                </div> *}
            </div>
            </template>
            
            <template x-if="appraiserType == 'private'">
            <div action="" class="appraiser__form">
                <div class="appraiser__block">
                    <div class="appraiser__block-title">Сведения о ЧПО</div>
                    <div class="appraiser__block-inner">
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_name',
                                'label' => 'ФИО оценщика',
                                'placeholder' => 'Иванов Иван Иванович',
                                'attrs' => 'required'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'appraiser_inn',
                                'label' => 'ИНН',
                                'placeholder' => 'ХХХХХХХХХХХХХХХХ',
                                'mask' => 'inn'
                            ]}
                            {field params=$params}
                        </div>
                        {$params = [
                            'name' => 'appraiser_address',
                            'label' => 'Адрес:',
                            'placeholder' => 'г. Город, ул. Улица, д. 1',
                            'attrs' => 'required'
                        ]}
                        {field params=$params}

                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_phone',
                                'label' => 'Телефон:',
                                'placeholder' => '+7 (___) ___ - __ - __',
                                'type' => 'tel',
                                'attrs' => 'required'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'appraiser_email',
                                'label' => 'E-mail:',
                                'placeholder' => 'org@mail.ru',
                                'type' => 'email',
                                'attrs' => 'required'
                            ]}
                            {field params=$params}
                        </div>
                        {* <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_website',
                                'label' => 'Веб-сайт ЧПО',
                                'placeholder' => 'http://'
                            ]}
                            {field params=$params}
                            
                            {file fieldName='appraiser_logo' label='Логотип ЧПО'}
                        </div> *}
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_sro',
                                'label' => 'Информация о членстве в СРО',
                                'type' => 'select',
                                'values' => [
                                    'Некоммерческое партнерство «САМОРЕГУЛИРУЕМАЯ ОРГАНИЗАЦИЯ АССОЦИАЦИИ РОССИЙСКИХ МАГИСТРОВ ОЦЕНКИ»',
                                    'Некоммерческое партнерство «Саморегулируемая организация Деловой Союз Оценщиков»',
                                    'Ассоциация «Межрегиональный Союз Оценщиков»',
                                    'Ассоциация саморегулируемая организация «Национальная коллегия специалистов-оценщиков»',
                                    'Межрегиональная саморегулируемая некоммерческая организация — Некоммерческое партнерство «Общество профессиональных экспертов и оценщиков»',
                                    'Саморегулируемая организация Региональная ассоциация оценщиков',
                                    'Ассоциация саморегулируемая организация оценщиков «Свободный Оценочный Департамент»',
                                    'Ассоциация Саморегулируемая организация «СОЮЗ»',
                                    'Саморегулируемая межрегиональная ассоциация оценщиков',
                                    'Саморегулируемая организация Ассоциация оценщиков «Сообщество профессионалов оценки»',
                                    'Саморегулируемая организация «Союз «Федерация Специалистов Оценщиков»',
                                    'Ассоциация «Саморегулируемая организация оценщиков «Экспертный совет»',
                                    'Ассоциация «РУССКОЕ ОБЩЕСТВО ОЦЕНЩИКОВ»'
                                ]
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'appraiser_sro_registry_number',
                                'label' => 'Регистрационный номер в реестре СРО',
                                'placeholder' => '00000'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_sro_certificate_number',
                                'label' => 'Номер свидетельства СРО',
                                'placeholder' => 'Номер свидетельства'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'appraiser_sro_certificate_date',
                                'label' => 'Дата выдачи свидетельства СРО',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {file fieldName='appraiser_sro_certificate_scan' label='Скан-копия свидетельства СРО'}
                            {file fieldName='appraiser_sro_extract_scan' label='Скан-копия  выписки из свидетельства СРО'}
                        </div>
                        {* <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_ecp_certificate_number',
                                'label' => 'Номер сертификата ЭЦП оценщика',
                                'placeholder' => 'Номер сертификата'
                            ]}
                            {field params=$params}
                        </div> *}
                        {* <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_ecp_valid_from',
                                'label' => 'Действителен с',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'appraiser_ecp_valid_to',
                                'label' => 'Действителен до',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                        </div> *}
                    </div>
                </div>

                <div class="appraiser__block">
                    <div class="appraiser__block-title">Документы о профессиональных знаниях в области оценочной деятельности</div>
                    <div class="appraiser__block-inner">
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'education_institution_name',
                                'label' => 'Наименование учебного заведения, выдавшего докуент об образовании (квалификации)',
                                'placeholder' => 'Название учебного заведения'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'diploma_series_number',
                                'label' => 'Серия и номер диплома',
                                'placeholder' => 'Серия и номер'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'diploma_registration_number',
                                'label' => 'Регистрационный номер диплома',
                                'placeholder' => 'Регистрационный номер'
                            ]}
                            {field params=$params}
                            
                            {file fieldName='diploma_scan' label='Скан-копия диплома об образовании (квалификации)'}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'qualification_certificate_number',
                                'label' => 'Номер квалификационного аттестата в области оценочной деятельности',
                                'placeholder' => 'Номер аттестата'
                            ]}
                            {field params=$params}
                            
                            {file fieldName='qualification_certificate_scan' label='Скан-копия квалификационного аттестата в области оценочной деятельности'}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'qualification_certificate_date',
                                'label' => 'Дата выдачи квалификационного аттестата',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                            
                            {* {$params = [
                                'name' => 'appraisal_experience',
                                'label' => 'Стаж работы в оценочной деятельности',
                                'type' => 'select',
                                'values' => ['3 года', '4 года', 'Более 5 лет']
                            ]}
                            {field params=$params} *}

                            {$params = [
                                'name' => 'appraisal_experience_start',
                                'label' => 'Начало оценочной деятельности',
                                'attrs' => '
                                    pattern = "[0-9]{4}"
                                    x-mask="9999"
                                    :min="1970"
                                    :max="new Date().getFullYear()"
                                '
                            ]}
                            {field params=$params}
                        </div>
                    </div>
                </div>
                <div class="appraiser__block">
                    <div class="appraiser__block-title">Сведения о страховании ответственности оценщика</div>
                    <div class="appraiser__block-inner">
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_insurer_name',
                                'label' => 'Наименование страховщика оценщика',
                                'placeholder' => 'Наименование страховой компании'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'appraiser_insurance_policy_number',
                                'label' => 'Номер договора (полиса) страхования ответственности оценщика',
                                'placeholder' => 'Номер договора'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row4">
                            {$params = [
                                'name' => 'appraiser_insurance_contract_date',
                                'label' => 'Дата заключения договора страхования',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {file fieldName='appraiser_insurance_scan' label='Скан договора (полиса) страхования ответственности оценщика'}
                            {file fieldName='appraiser_insurance_scan_page2' label='Скан договора страхования оценщика (стр. 2 при наличии)'}
                        </div>
                        <div class="appraiser__row appraiser__row4">
                            {$params = [
                                'name' => 'appraiser_insurance_amount',
                                'label' => 'Страховая сумма',
                                'type' => 'number',
                                'placeholder' => '0,00 руб.'
                            ]}
                            {field params=$params}
                        </div>
                        <div class="appraiser__row appraiser__row2">
                            {$params = [
                                'name' => 'appraiser_insurance_valid_from',
                                'label' => 'Срок действия договора (полиса) с',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                            
                            {$params = [
                                'name' => 'appraiser_insurance_valid_to',
                                'label' => 'Срок действия договора (полиса) до',
                                'type' => 'date'
                            ]}
                            {field params=$params}
                        </div>
                    </div>
                </div>
            </div>
            </template>

            <div class="appraiser__bottom">
                {* <button class="appraiser__cancel">Отмена</button> *}
                <button class="button" type="submit" @click.prevent="save()">Сохранить</button>
            </div>
        </div>
    </form>
{/block}
{block 'blocks'}{/block}