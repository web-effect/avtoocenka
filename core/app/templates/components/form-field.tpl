{foreach $params|default:[] as $key => $val} 
    {assign $key $val}
{/foreach}

{$value = $value|default:$smarty.post[$name]|default:''}
{$placeholder = $placeholder|default:' '}

<label class="field">
    {if $type == 'select'}
        <div class="input__select">
            {'/app/img/icons/arrow-down-dark.svg'|svg}
            <select name="salary_period" class="input">
                <option value="">Выберите...</option>
                {foreach $options as $opt}
                    <option value="{$opt}">{$opt}</option>
                {/foreach}
            </select>
        </div>
        {if $label}
            <div class="field__label">{$label}</div>
        {/if}
    {elseif $type == "file"}

    {else}
        {if "fi.error.$name"|ph}
            <div class="field__error">{"fi.error.$name"|ph}</div>
        {/if}
    
        {if $caption} 
            <div class="field__caption">{$caption}</div>
        {/if}
        
        {if $type == 'textarea'}
            <textarea class="input{if $classes} {$classes}{/if}" name="{$name}" placeholder="{$placeholder}" {$attrs}>{$value}</textarea>            
        {else}
            <div class="field__wrapper">
                {* {if $params.type == 'date'} 
                    <input class="js-air-datepicker" type="text">
                {/if} *}
                <input 
                    class="input{if $classes} {$classes}{/if}"
                    type="{$type|default:'text'}"
                    name="{$name}"
                    value="{$value}"
                    placeholder="{$placeholder}"
                    {$attrs}
                    {if $mask == 'passport_series'} 
                        {literal}pattern="[0-9]{4}"{/literal}
                        data-maska="####"
                    {/if}
                    {if $mask == 'passport_number'} 
                        {literal}pattern="[0-9]{6}"{/literal}
                        data-maska="######"
                    {/if}
                    {if $mask == 'passport_department_code'} 
                        {literal}pattern="[0-9\-]{7}"{/literal}
                        data-maska="###-###"
                    {/if}
                    
                >
            </div>
        {/if}
        
        {if $label}
            <div class="field__label">{$label}</div>
        {/if}
        
        {if $description}
            <div class="field__description">{$description}</div>
        {/if}
    {/if}
</label>