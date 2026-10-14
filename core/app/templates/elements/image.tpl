<div class="image {if $hasbg}has_bg{/if}" style="{if $hasbg && $image}background: url('{$image|phpthumbon:'f=webp'}'); background-position: center;{/if}">
    
    <img src="{$image|phpthumbon:$phpthumbon|default:'f=webp'}" alt="{$alt}" {if $h} style="height:{$h}px;"{/if} loading="lazy">                   
</div>