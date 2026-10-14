{if 'modules_favorites'|config}
    <label x-data class="like-favorite">
        {$hasFavorit}
        {foreach explode(",", $favorites) as $i}
            {if $i == $id}
                {$hasFavorit = true}
            {/if}
        {/foreach}

        <input @click="addInFavorites({$id}, $event.target)" {($hasFavorit)?'checked':''}  type="checkbox" name="">
        {'/app/libs/bi/heart.svg'|svg}
        {'/app/libs/bi/heart-fill.svg'|svg}
    </label>
{/if}