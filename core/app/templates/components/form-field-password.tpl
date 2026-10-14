{$name = $name|default:'password'}

<label class="field" x-data="{ show: true }">
    {if "fi.error.$name"|ph}
        <div class="field-error">{"fi.error.$name"|ph}</div>
    {/if}

    <div class="field__password">
        <div class="field-icon" @click="show = !show">
            <template x-if="show">{'app/libs/bi/eye.svg'|svg}</template>
            <template x-if="!show">{'app/libs/bi/eye-slash.svg'|svg}</template>
        </div>
        
        <input class="input" type="password" name="{$name}" value="{$smarty.post[$name]}" placeholder=" " {$attrs}
            :type="show ? 'password' : 'text'"
        >
    </div>
    
    <div class="field__label">{$label|default:'Пароль'}</div>
</label>