<div class="modal-form" id="modal-help" hidden>
    <form action="" class="form-box is-fullwidth | js-form" hx-post="ajax/modal-help">
        {ajax key='modal-help'}

        {$form = '@FILE form'|snippet:[
                'form_title' => 'Служба поддержки',
                'form_ya_target' => 'modal-help'
            ]}

        <div class="form-box-title h2 font-title">Служба поддержки</div>

        {if $form.success}
            {$form.successMessage}
        {else}
            {include 'components/form-field' name='fullname' label='Ваше имя' attrs='required'}
            {include 'components/form-field' name='phone' label='Ваш телефон' type='tel' attrs='required'}
            {include 'components/form-field' name='email' label='Ваш email' type='email' attrs=''}
            {include 'components/form-field' name='comment' label='Комментарий' type='textarea' attrs='required'}

            {$form.validation_error_message}

            <div class="form-privacy">
                <label class="checkbox privacy_check">
                    <input type="checkbox" name="privacy" required>
                    <span class="checkbox-check"></span>
                    <div class="">
                        Даю согласие на обработку персональных данных на условиях и для целей, определенных в указанной форме сбора персональных данных, а также подтверждаю ознакомление с
                        <a rel="noopener noreferrer nofollow" href="{9|url}" target="_blank" tabindex="0">текстом политики в отношении обработки персональных данных</a>.
                    </div>
                </label>
            </div>
            <div class="form-box-footer _center">
                <button class="button _fullwidth" type="submit">Отправить</button>
            </div>
            
        {/if}

        {/ajax}
    </form>
</div>
