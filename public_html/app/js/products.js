const Catalog = {
    timer: null,
    setFilter(values = {}, swap = 'innerHTML', timeout = 500) {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => {
            document.querySelectorAll(".js-catalog-filter").forEach(el => {
                console.log(htmx.values(el))
                values = Object.assign(values, htmx.values(el));
            })

            const queryString = {};
            for (let i in values) {
                if (('' + values[i]).length) {
                    queryString[i.replace('[]', '')] = values[i];
                } else {
                    delete values[i];
                }
            }
            const itemsEl = document.querySelector('.js-catalog-items');
            const addr = itemsEl.dataset?.ajaxKey || 'catalog';

            htmx.ajax('POST', 'ajax/' + addr, { target: '.js-catalog-items', values, swap });
            history.replaceState(null, null, window.location.pathname + '?' + new URLSearchParams(queryString));
        }, timeout)
    },
};

// фильтр
$on('change', '.js-catalog-filter', el => Catalog.setFilter());

// пагинация
$on('click', '.js-catalog-pagination [data-page]', el => {
    const page = el.dataset.page;
    Catalog.setFilter(page && page > 1 ? { page } : {}, 'innerHTML', 0)
    window.scrollTo({ top: 0, behavior: 'smooth' });
    document.querySelector('.js-catalog-filter-expand')?.parentNode.classList.remove('is-expanded');
});
$on('click', '.js-catalog-pagination [data-page-next]', el => {
    Catalog.setFilter({ page: el.dataset.pageNext }, 'beforeend', 0);
});

// сброс
$on('reset', '.js-catalog-filter', el => {
    el.querySelectorAll(':checked').forEach(el => el.checked = false);
    window.noUiSlider && el.querySelectorAll('.js-catalog-filter-slider').forEach(el => el.noUiSlider.set([0, Infinity]));
    Catalog.setFilter();
}, true);

// развернуть
$on('click', '.js-catalog-filter-expand', el => {
    const cl = el.parentNode.classList;
    cl.contains('is-expanded') ? cl.remove('is-expanded') : cl.add('is-expanded');
});

// nouislider
window.noUiSlider && document.addEventListener('DOMContentLoaded', () => {
    for (let el of document.querySelectorAll('.js-catalog-filter-slider')) {
        let minEl = el.parentNode.querySelector('[name*="min_"]');
        let maxEl = el.parentNode.querySelector('[name*="max_"]');
        let ranger = noUiSlider.create(el, {
            start: [+minEl.value || +minEl.min, +maxEl.value || +maxEl.max],
            connect: true,
            step: 1,
            range: { 'min': +minEl.min, 'max': +maxEl.max },
            format: {
                to: (val) => parseInt(val),
                from: (val) => val
            }
        });
        ranger.on('update', (values, handle) => {
            minEl.value = +minEl.min == values[0] ? null : values[0];
            maxEl.value = +maxEl.max == values[1] ? null : values[1];
        });
        ranger.on('set', () => el.closest('form').dispatchEvent(new Event('change', {'bubbles': true})))
        document.addEventListener('change', (e) => {
            e.target == minEl && ranger.set([e.target.value, null]);
            e.target == maxEl && ranger.set([null, e.target.value]);
        });
    }


    // Изменение значения слайдера при обновления фасетного фильтра
    const container = document.querySelector(".data-container");

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.addedNodes.length > 0 && mutation.target.firstElementChild === mutation.addedNodes[0]) {
                for (let el of document.querySelectorAll('.js-catalog-filter-slider')) {
                    let minEl = el.parentNode.querySelector('[name*="min_"]');
                    let maxEl = el.parentNode.querySelector('[name*="max_"]');
                    let ranger = noUiSlider.create(el, {
                        start: [+minEl.value || +minEl.min, +maxEl.value || +maxEl.max],
                        connect: true,
                        step: 1,
                        range: { 'min': +minEl.min, 'max': +maxEl.max },
                        format: {
                            to: (val) => parseInt(val),
                            from: (val) => val
                        }
                    });
                    ranger.on('update', (values, handle) => {
                        minEl.value = +minEl.min == values[0] ? null : values[0];
                        maxEl.value = +maxEl.max == values[1] ? null : values[1];
                    });
                    ranger.on('set', () => el.closest('form').dispatchEvent(new Event('change', {'bubbles': true})))
                    document.addEventListener('change', (e) => {
                        e.target == minEl && ranger.set([e.target.value, null]);
                        e.target == maxEl && ranger.set([null, e.target.value]);
                    });
                }
            }
        });
    });
    
    if(container){
        observer.observe(container, {
            childList: true, 
            subtree: false   
        });
    }
    
});






// filter_facet
const CatalogFaced = {
    timer: null,
    setFilter(values = {}, swap = 'innerHTML', timeout = 500, newPage = false) {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => {
            document.querySelectorAll(".js-catalog-filter-faced").forEach(el => {
                values = Object.assign(values, htmx.values(el));
            })

            const queryString = {};
            console.log(values)
            for (let i in values) {
                if(i != 'loadmore'){
                    if (('' + values[i]).length) {
                        queryString[i.replace('[]', '')] = values[i];
                    } else {
                        delete values[i];
                    }
                }
            }

            if(values?.loadmore){
                // Показать еще
                htmx.ajax('POST', 'ajax/catalog', { target: '.js-catalog-items', values, swap });
            }else{
                htmx.ajax('POST', 'ajax/catalog', { target: '.data-container', values, swap });
            }
            history.replaceState(null, null, window.location.pathname + '?' + new URLSearchParams(queryString));
        }, timeout)
    },
};

// фильтр
$on('change', '.js-catalog-filter-faced', el => CatalogFaced.setFilter());

/* Каталог работающий через сниппет ms_products_facet
$on('click', '.js-catalog-pagination-facet [data-page]', el => { 
    const page = el.dataset.page;
    CatalogFaced.setFilter(page && page > 1 ? { page } : {}, 'innerHTML', 0)
    window.scrollTo({ top: 0, behavior: 'smooth' });
    document.querySelector('.js-catalog-filter-expand')?.parentNode.classList.remove('is-expanded');
});

$on('click', '.js-catalog-pagination-facet [data-page-loadmore]', el => {
    CatalogFaced.setFilter(el.dataset.pageLoadmorePage && el.dataset.pageLoadmorePage > 1 ? { page: el.dataset.pageLoadmorePage, loadmore: el.dataset.pageLoadmore } : {loadmore: el.dataset.pageLoadmore}, 'beforeend', 0);
});
*/


// Каталог работающий через сниппет ms_products
$on('click', '.js-catalog-pagination-facet-v2 [data-page]', el => {
    const page = el.dataset.page;
    CatalogFaced.setFilter(page && page > 1 ? { page } : {}, 'innerHTML', 0)
    window.scrollTo({ top: 0, behavior: 'smooth' });
    document.querySelector('.js-catalog-filter-expand')?.parentNode.classList.remove('is-expanded');
});

$on('click', '.js-catalog-pagination-facet-v2 [data-page-next]', el => {
    CatalogFaced.setFilter({ page: el.dataset.pageNext, loadmore: true }, 'beforeend', 0);
});

// Сброс
$on('reset', '.js-catalog-filter-faced', el => {
    el.querySelectorAll(':checked').forEach(el => el.checked = false);
    window.noUiSlider && el.querySelectorAll('.js-catalog-filter-slider').forEach(el => el.noUiSlider.set([0, Infinity]));
    CatalogFaced.setFilter();
}, true);
