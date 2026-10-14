{extends 'parts/section.tpl'}

{block 'section-main'}
    <div class="columns">
        <form action="" class="b-form__form | form" hx-post="ajax/form-main">
            {ajax key='form-main'}
            {$form = '@FILE form'|snippet:[
                'form_title' => 'Форма связи',
                'form_ya_target' => 'write'
            ]}
            {if $form.success}
                {$form.successMessage}
            {else}
                {include 'components/form-field' name='fullname' label='Ваше имя' attrs='required'}
                {include 'components/form-field' name='email' label='Ваш email' type='email' attrs='required'}
                {include 'components/form-field' name='phone' label='Ваш телефон' type='tel' attrs='required'}
                {include 'components/form-field' name='message' label='Сообщение' type='text'}
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
                    <button class="button is-fullwidth" type="submit">Отправить</button>
                </div>
                
            {/if}
            {/ajax}
        </form>

        <div class="b-form__image">
            <img src="{$block.image|src|phpthumbon:'w=300&f=webp'}" alt="{$block.alt|default:$block.title}">
        </div>
    </div>
{/block}