{*
 * $first - вывести только первый элемент
 *}

{if $type == 'addr'}
    {strip}
        {$addrs = explode('||', 16|field:'contacts.address')}
        {foreach $addrs as $addr}
            {if !$first || $addr@first}
                <span {if $class}class="{$class}" {/if}>
                    {$addr}
                </span>
                {if !$first && $addr@first && $addrs|count > 1}{$separator|default:'<br>'}{/if}
            {/if}
        {/foreach}
    {/strip}
{/if}

{if $type == 'email'}
    {$emails = explode('||', 16|field:'contacts.email')}
    {strip}
        {foreach $emails as $email}
            {if !$first || $email@first}
                <a class="{$class}" target="_blank" href="mailto:{$email}">
                    {$email}
                </a>
                {if !$first && $email@first && $emails|count > 1}{$separator|default:'<br>'}{/if}
            {/if}
        {/foreach}
    {/strip}
{/if}

{if $type == 'phone'}
    {$phones = explode('||', 16|field:'contacts.phone')}
    {strip}
        {foreach $phones as $phone}
            {if !$first || $phone@first}
                <a class="{$class}" target="_blank" href="tel:{$phone|regex_replace:'/[ \(\)\-]/':''}">
                    {$phone}
                </a>
                {if !$first && $phone@first && $phones|count > 1}{$separator|default:'<br>'}{/if}
            {/if}
        {/foreach}
    {/strip}
{/if}

{if $type == 'soc'}
    {foreach json_decode(16|field:'contacts.soc', true) as $item}
        {if $item.on}
            <a class="soc" href="{$item.link}" target="_blank" rel="nofollow noopener">
                {$item.icon|source|svg}
            </a>
        {/if}
    {/foreach}
{/if}
