{$site_name = 1|field:'site_name'}
{$logo = 1|field:'logo'|src}
{$email = 16|field:'contacts.email'}
{$phone = 16|field:'contacts.phone'}
{$phone = explode('||', $phone)}
{$phone = $phone[0]}
{$addr = 16|field:'contacts.address'}

{$soc = ''}
{foreach json_decode(16|field:'contacts.soc', true) as $item}
    {if $item.on}
        {if $soc != ''}
            {$soc = $soc|cat:', '}
        {/if}
        {$soc = $soc|cat:'"'|cat:$item.link|cat:'"'}
    {/if}
{/foreach}





<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "{$site_name}",
    "url": "{'site_url'|config}",
    "logo": "{'site_url'|config}{$logo}",
    "telephone": "{$phone}",
    "email": "{$email}",
    "address": {
        "@type": "PostalAddress",
        "streetAddress": "{$addr}",
        "addressLocality": "Ижевск",
        "postalCode": "426000",
        "addressCountry": "RU"
    },
    "sameAs": [{$soc}]
}
</script>
{if 'template'|resource==2}

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebSite", 
    "name": "{$site_name}", 
    "url": "{'site_url'|config}",
    "potentialAction": {  
    "@type": "SearchAction", 
    "target": "{'site_url'|config}{literal}/search?q={search_term_string}{/literal}",  
    "query-input": "required name=search_term_string" 
    }
}
</script>
{/if}


{if 'template'|resource==17}
<script type="application/ld+json">
{  
    "@context": "https://schema.org",
    "@type": "ContactPage",
    "url": "{'site_url'|config}{'alias'|resource}",
    "mainEntity": {    
        "@type": "Organization", 
        "name": "{$site_name}",  
        "telephone": "{$phone}", 
        "email": "{$email}", 
        "address": {  
            "@type": "PostalAddress",  
            "streetAddress": "{$addr}", 
            "addressLocality": "Ижевск",   
            "postalCode": "426000",      
             "addressCountry": "RU" 
        }
    }
}
</script>
{/if}






{if 'template'|resource==32}
    
    {$gallery = '@FILE ms_gallery'|snippet}

    
    {if 'longtitle'|resource}
        {$title='longtitle'|resource}
    {else}
        {$title= 'pagetitle'|resource}
    {/if}
    
    {if 'description'|resource}
        {$description='description'|resource}
    {else}
        {$description=strip_tags('content'|resource)|truncate:'100'}
    {/if}
    
    {if 'id'|resource|field:'image'}
        {$image = 'id'|resource|field:'image'|src}
    {else if $gallery}
        {$image = $gallery[0].url}
        {$siteurl = substr('site_url'|config, 0, -1)}
    {else}
        {$image = $logo}
    {/if}

    <script type="application/ld+json">
    { 
        "@context": "https://schema.org", 
        "@type": "Service", 
        "name": "{$title}",
            "description": "{$description}", 
            "image": "{$siteurl}{$image}",
            "provider": {   
                "@type": "Organization",   
                "name": "{$site_name}",  
                "url": "{'site_url'|config}"  
            },  
            "areaServed": { "@type": "City","name": "Ижевск"}
    }
    </script>
{/if}

{if  'template'|resource==7}

    {$gallery = '@FILE ms_gallery'|snippet}

    
    {if 'longtitle'|resource}
        {$title='longtitle'|resource}
    {else}
        {$title= 'pagetitle'|resource}
    {/if}
    
    {if 'description'|resource}
        {$description='description'|resource}
    {else}
        {$description=strip_tags('content'|resource)|truncate:'100'}
    {/if}
    
    {if 'id'|resource|field:'image'}
        {$image = 'id'|resource|field:'image'|src}
    {else if $gallery}
        {$image = $gallery[0].url}
        {$siteurl = substr('site_url'|config, 0, -1)}
    {else}
        {$image = $logo}
    {/if}


    <script type="application/ld+json">
    {
      "@context": "https://schema.org/",
      "@type": "Product",
      "name": "{$title}",
      "description": "{$description}",
      "image": "{$siteurl}{$image}",
      "offers": {
        "@type": "Offer",
        "availability": "https://schema.org/InStock",
        "priceSpecification": {
          "@type": "UnitPriceSpecification",
          "price": null,
          "priceCurrency": "RUB",
          "description": "Цена по запросу"
        }
      }
    }
{/if}
</script>