<div class="enable-cookies" x-data="{ show: false }" x-init="if (!Cookies.get('accept_cookies')) setTimeout(()=>{ show=true }, 3000)" x-show="show" x-transition x-cloak>
    <div>Продолжая работать с этим сайтом, Вы даёте <a target="_blank" href="{9|url}">согласие</a> на сбор cookie-файлов посредством использования интернет-сервиса "Яндекс.Метрика". Если Вы не согласны, пожалуйста, покиньте сайт</div>
    <div @click="Cookies.set('accept_cookies', true, { expires:null }); show=false" class="button">Принять</div>
</div>
