{extends 'parts/section.tpl'}

{block 'section-main'}

    <div class="ya-map" data-map='{16|field:'contacts.map'}' {*data-map-offset="150"*} data-map-zoom="{16|field:'contacts.map_zoom'}">
       
        {if 16|field:'company_ref'}
            <a class="company_ref" target="_blank" href="{16|field:'company_ref'}">
        		<ymaps class="" title="Мы на Яндекс Картах">
        			<ymaps class="ymaps__container">
        				<ymaps class="ymaps__pin">{'/app/img/yametka.svg'|svg}</ymaps>
        				<ymaps class="ymaps__text-container">
        					<ymaps class="ymaps__text">Мы на Яндекс Картах</ymaps>
        				</ymaps>
        			</ymaps>
        		</ymaps>
        	</a>
	    {/if}
    </div>
{/block}
