{extends 'dashboard.tpl'}
{block 'header'}
{/block}
{block 'page_content'}
{nocache}
	<div class="profile">    
        <div class="appraiser__block">
            <div class="appraiser__block-title">Настройки профиля</div>
            <form class="appraiser__block-inner js-form" hx-post="ajax/user_update">
                {ajax key='user_update'}
                    {literal}
                        {'UpdateProfile'|snippet:[
                            'submitVar' => $ajax_key,
                            'reloadOnSuccess' => false,
                            'validate' => 'email:email:required,fullname:required',
                            'syncUsername' => 'email'
                        ] nocache}

                        {function field field='' label='' type='text' attrs=''}
                            <label class="field">
                                <div class="field-error">{"error.{$field}"|ph}</div>
                                <input class="input" type="{$type}" name="{$field}" value="{$field|ph}" placeholder=" " {$attrs}>
                                <div class="field-label">{$label}</div>
                            </label>
                        {/function}
                        
                        <input type="hidden" name="{$ajax_key}" value="1">
                        
                        {$params = [
                            'name' => 'email',
                            'label' => 'Логин (email)',
                            'type' => 'email',
                            'value' => 'email'|ph,
                            'attrs' => 'required readonly'
                        ]}
                        {include 'components/form-field' params=$params}

                        {$params = [
                            'name' => 'fullname',
                            'label' => 'ФИО',
                            'value' => 'fullname'|ph,
                            'attrs' => 'required'
                        ]}
                        {include 'components/form-field' params=$params}
                        {* <div class="profile__row3">
                            {$params = [
                                'name' => 'field_surname',
                                'label' => 'Фамилия',
                                'value' => {'field_surname'|ph}
                            ]}
                            {include 'components/form-field' params=$params}
                            {$params = [
                                'name' => 'field_name',
                                'label' => 'Имя',
                                'value' => {'field_name'|ph}
                            ]}
                            {include 'components/form-field' params=$params}
                            {$params = [
                                'name' => 'field_patronymic',
                                'label' => 'Отчество',
                                'value' => {'field_patronymic'|ph}
                            ]}
                            {include 'components/form-field' params=$params}
                        </div> *}
                        {$params = [
                            'name' => 'phone',
                            'label' => 'Телефон',
                            'type' => 'tel',
                            'value' => 'phone'|ph
                        ]}
                        {include 'components/form-field' params=$params}
                        
                        <button class="button _fullwidth" type="submit">Сохранить</button>

                        {if "login.update_success"|ph}
                            <div class="message _success _temporary">Изменения сохранены</div>
                        {/if}
                    {/literal}
                {/ajax}
            </form>
        </div>
        
        <div class="appraiser__block">
            <div class="appraiser__block-title">Изменить пароль</div>
            <form class="appraiser__block-inner js-form" hx-post="ajax/password">
                {ajax key='password'}
                    {'ChangePassword'|snippet:[
                        'submitVar' => $ajax_key,
                        'reloadOnSuccess' => false,
                        'clearFieldsOnSuccess' => 1,
                        'placeholderPrefix' => 'fi.'
                    ] nocache}
                    
                    <input type="hidden" name="{$ajax_key}" value="1">
                    
                    {include 'components/form-field-password' name='password_old' label='Старый пароль' attrs='required'}
                    {include 'components/form-field-password' name='password_new' label='Новый пароль' attrs='required'}
                    {include 'components/form-field-password' name='password_new_confirm' label='Подтвердите новый пароль' attrs='required'}
                    
                    <button class="button _fullwidth" type="submit">Сохранить</button>
                    
                    {if "fi.successMessage"|ph}
                        <div class="message _success _temporary">Пароль изменён</div>
                    {/if}
                {/ajax}
            </form>
        </div>
	</main>
{/nocache}
{/block}
{block 'blocks'}
{/block}