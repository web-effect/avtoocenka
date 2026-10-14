<form class="report__third"
    @submit.prevent
    @change="
        if (!$event.target.matches('input, select, textarea')) return;
        change($event.target.name, $event.target.value);
    "
>
    <div class="report__title">Сведения о заказчике</div>
    <div class="report__block">
        <div class="report__block-title">Сведения о заказчике оценки (наследнике)</div>
        <div class="report__block-inner">
            {$params = [
                'name' => 'customer_fullname',
                'label' => 'ФИО',
                'placeholder' => 'Иванов Иван Иванович',
                'attrs' => 'required x-model.lazy="report.customer.fullname"'
            ]}
            {include 'components/form-field' params=$params}
            
            <div class="grid md:grid-cols-2 gap-20">
                {$params = [
                    'name' => 'customer_registration_address',
                    'label' => 'Адрес регистрации',
                    'placeholder' => 'г. Город, ул. Улица, д. 1, кв. 1',
                    'description' => 'Адрес по месту регистрации (прописки)',
                    'attrs' => 'required x-model.lazy="report.customer.registration_address"'
                ]}
                {include 'components/form-field' params=$params}

                {$params = [
                    'name' => 'customer_birthdate',
                    'label' => 'Дата рождения',
                    'type' => 'date',
                    'attrs' => 'x-model.lazy="report.customer.birthdate"'
                ]}
                {include 'components/form-field' params=$params}
                
                {$params = [
                    'name' => 'customer_birth_city',
                    'label' => 'Место рождения',
                    'placeholder' => 'г. Город',
                    'attrs' => 'x-model.lazy="report.customer.birth_city"'
                ]}
                {include 'components/form-field' params=$params}

                {$params = [
                    'name' => 'customer_passport_series',
                    'label' => 'Серия паспорта',
                    'placeholder' => '4520',
                    'mask' => 'passport_series',
                    'attrs' => 'x-model.lazy="report.customer.passport_series"'
                ]}
                {include 'components/form-field' params=$params}
                
                {$params = [
                    'name' => 'customer_passport_number',
                    'label' => 'Номер паспорта',
                    'placeholder' => '123456',
                    'mask' => 'passport_number',
                    'attrs' => 'x-model.lazy="report.customer.passport_number"'
                ]}
                {include 'components/form-field' params=$params}

                {$params = [
                    'name' => 'customer_passport_department_code',
                    'label' => 'Код подразделения',
                    'placeholder' => '770-001',
                    'mask' => 'passport_department_code',
                    'attrs' => 'x-model.lazy="report.customer.passport_department_code"'
                ]}
                {include 'components/form-field' params=$params}
                
                {$params = [
                    'name' => 'customer_passport_issued_at',
                    'label' => 'Дата выдачи паспорта',
                    'type' => 'date',
                    'attrs' => 'x-model.lazy="report.customer.passport_issued_at"'
                ]}
                {include 'components/form-field' params=$params}

                {$params = [
                    'name' => 'customer_passport_issued_by',
                    'label' => 'Кем выдан паспорт',
                    'placeholder' => 'УФМС России',
                    'attrs' => 'x-model.lazy="report.customer.passport_issued_by"'
                ]}
                {include 'components/form-field' params=$params}

                {$params = [
                    'name' => 'customer_email',
                    'label' => 'E-mail',
                    'placeholder' => 'example@mail.ru',
                    'type' => 'email',
                    'attrs' => 'x-model.lazy="report.customer.email"'
                ]}
                {include 'components/form-field' params=$params}
                
                {$params = [
                    'name' => 'customer_phone',
                    'label' => 'Телефон',
                    'placeholder' => '+7 (900) 000-00-00',
                    'type' => 'tel',
                    'attrs' => 'x-model.lazy="report.customer.phone"'
                ]}
                {include 'components/form-field' params=$params}
            </div>
        </div>
    </div>
    
    <div class="report__block report__block">
        <div class="report__block-title">Сведение о собственнике АМТС (наследодателе)</div>
        <div class="report__block-inner">
            {$params = [
                'name' => 'owner_fullname',
                'label' => 'ФИО',
                'placeholder' => 'Иванов Иван Иванович',
                'attrs' => 'required x-model.lazy="report.owner.fullname"'
            ]}
            {include 'components/form-field' params=$params}
            
            <div class="grid md:grid-cols-2 gap-20">
                {$params = [
                    'name' => 'owner_deathdate',
                    'label' => 'Дата смерти (Дата оценки)',
                    'type' => 'date',
                    'attrs' => 'required x-model.lazy="report.owner.deathdate"'
                ]}
                {include 'components/form-field' params=$params}

                {$params = [
                    'name' => 'owner_death_certificate_number',
                    'label' => 'Номер свидетельства о смерти',
                    'attrs' => 'required x-model.lazy="report.owner.death_certificate_number"'
                ]}
                {include 'components/form-field' params=$params}
                
                {$params = [
                    'name' => 'owner_death_certificate_issued_at',
                    'label' => 'Дата выдачи свидетельства о смерти',
                    'type' => 'date',
                    'attrs' => 'required x-model.lazy="report.owner.death_certificate_issued_at"'
                ]}
                {include 'components/form-field' params=$params}

                {$params = [
                    'name' => 'owner_birthdate',
                    'label' => 'Дата рождения',
                    'type' => 'date',
                    'attrs' => 'x-model.lazy="report.owner.birthdate"'
                ]}
                {include 'components/form-field' params=$params}
            </div>
        </div>
    </div>

    {nav}
        
    {* <div class="report__buttons">
        <button class="report__prev report__btn" @click="changeStage('Оценка')">{'/app/img/icons/arrow-right-thin.svg'|svg} Назад</button>
        <button class="report__next report__btn" @click="changeStage('Отчёт')">Продолжить {'/app/img/icons/arrow-right-thin.svg'|svg}</button>
    </div> *}
</form>