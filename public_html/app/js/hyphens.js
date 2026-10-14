/*
 * Расстановка переносов
 * @todo: не всегда убираются переносы при ресайзе
 * @todo: таймер при ресайзе
 */

/*
 * Улучшенная расстановка переносов с:
 * - Игнорированием элементов с видео
 * - Оптимизацией ресайза через debounce
 * - Полным удалением всех переносов при unhyphenate
 * - Более точной проверкой переполнения
 */

const Hyphens = {
    selectors: 'h1,h2,h3,h4,h5,h6, .content p:not(:has(video)), .content li:not(:has(video)), .title:not(:has(video)), .hyphenable:not(:has(video))',
    resizeTimer: null,
    debounceDelay: 200,

    // Проверяет, содержит ли элемент тег video (используется как fallback)
    containsVideo(el) {
        return el.querySelector('video') !== null;
    },

    hyphenate(el) {
        // Дополнительная проверка на video (на случай если селектор :not(:has()) не поддерживается)
        if (this.containsVideo(el)) return;

        const RusA = "[абвгдеёжзийклмнопрстуфхцчшщъыьэюя]";
        const RusV = "[аеёиоуыэюя]";
        const RusN = "[бвгджзклмнпрстфхцчшщ]";
        const RusX = "[йъь]";
        const Hyphen = "\xAD";
        
        const patterns = [
            [RusX, RusA + RusA],
            [RusV, RusV + RusA],
            [RusV + RusN, RusN + RusV],
            [RusN + RusV, RusN + RusV],
            [RusV + RusN, RusN + RusN + RusV],
            [RusV + RusN + RusN, RusN + RusN + RusV]
        ];

        let text = el.innerHTML;
        patterns.forEach(([left, right]) => {
            const re = new RegExp(`(${left})(${right})`, "ig");
            text = text.replace(re, `$1${Hyphen}$2`);
        });
        el.innerHTML = text;
    },

    unhyphenate(el) {
        // Дополнительная проверка на video
        if (this.containsVideo(el)) return;
        
        // Удаляем все мягкие переносы
        el.innerHTML = el.innerHTML.replace(/\xAD/g, '');
    },

    isOverflown(el) {
        // Дополнительная проверка на video
        if (this.containsVideo(el)) return false;
        
        // Более точная проверка переполнения
        const style = getComputedStyle(el);
        const padding = parseFloat(style.paddingLeft) + parseFloat(style.paddingRight);
        const border = parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth);
        
        return el.scrollWidth > el.clientWidth - padding - border;
    },

    start() {
        const els = document.querySelectorAll(this.selectors);
        els.forEach((el) => {
            // Двойная проверка на video для надежности
            if (this.containsVideo(el)) return;
            
            const style = getComputedStyle(el);
            this.isOverflown(el) || style.textAlign === 'justify'
                ? this.hyphenate(el)
                : this.unhyphenate(el);
        });
    },

    // Оптимизированный обработчик ресайза
    handleResize() {
        clearTimeout(this.resizeTimer);
        this.resizeTimer = setTimeout(() => {
            this.start();
        }, this.debounceDelay);
    }
};

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    Hyphens.start();
    
    // Оптимизированный обработчик ресайза
    window.addEventListener('resize', () => Hyphens.handleResize());
    
    // Для динамически загружаемого контента
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(() => Hyphens.start());
        observer.observe(document.body, {
            subtree: true,
            childList: true,
            characterData: true
        });
    }
});