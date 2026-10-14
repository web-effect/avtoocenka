Fancybox.bind("[data-fancybox]", {
    on: {
        init: (fancybox, slide) => {
            if (
                !(
                    fancybox.options.$trigger.href && 
                    fancybox.options.$trigger.href.includes("assets/")
                )
            ) {
                fancybox.options.dragToClose = false;
                fancybox.options.ScrollLock = false;
                fancybox.options.autoFocus = false;
            }
            if (fancybox.options.$trigger.dataset.fancyboxClass) {
                fancybox.options.mainClass = fancybox.options.$trigger.dataset.fancyboxClass;
            }
        },
    },
});

// открытие модалки с параметрами через ajax
Fancybox.bind('[data-fancybox][data-ajax]', {
    dragToClose: false,
    on: {
        "reveal": (fancyboxRef, carouselRef, slide) => {
            if (carouselRef.$trigger?.dataset?.ajax) {
                const dataset = carouselRef.$trigger?.dataset;
                const target = dataset.src + ' .fancybox-ajax-content';
                const values = dataset.values ? JSON.parse(dataset.values) : {};
                console.log(dataset, target, values);
                document.querySelector(target).innerHTML = '';
                htmx.ajax('POST', dataset.ajax, { target, values });
            }
        },
        // для новых версии fancybox
        // "Carousel.contentReady": (fancyboxRef, carouselRef, slide) => {
        //     if (slide.triggerEl?.dataset?.ajax) {
        //         const dataset = slide.triggerEl?.dataset;
        //         const target = dataset.src + ' .fancybox-ajax-content';
        //         const values = dataset.values ? JSON.parse(dataset.values) : {};
        //         document.querySelector(target).innerHTML = '';
        //         htmx.ajax('POST', dataset.ajax, { target, values });
        //     }
        // },
    },
});


const $numberFormat = function(number, symbol = '') {
    const formatted = (+number).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    if (symbol && symbol != '%') {
        symbol = ` ${symbol}`
    }
    return `${formatted}${symbol}`;
}

/** анимация чисел */
// const runNumber = (target, number, step = 50, delay = 100, format = false) => {
//   let i = 0;
//   let interval = setInterval(() => {
//     if (number + step > i) {
//       target.innerHTML = format ? numFormat(i) : i;
//       i += step;
//     } else {
//       target.innerHTML = format ? numFormat(number) : number;
//       clearInterval(interval);
//     }
//   }, delay);
// };

/** спойлеры */
document.addEventListener(
  "click",
  (e) => {
    if (e.target.closest(".spoiler-title")) {
      e.target.closest(".spoiler").classList.toggle("is-open");
    }
  },
  true
);

/** для плавного раскрытия */
const accordions = [].map.call(
  document.querySelectorAll(".js-max-height, .spoiler-content"),
  (el) => el
);
["load", "resize"].map((event) =>
  window.addEventListener(event, () => {
    accordions.map((el) =>
      el.style.setProperty("--max-height", `${el.scrollHeight}px`)
    );
  })
);


/** $on */
const $on = function (event, target, callback) {
  document.addEventListener(
    event,
    (e) => {
      const el = e.target.closest(target);
      if (el) {
        e.preventDefault();
        callback(el);
      }
    },
    true
  );
};

const $dateFormat = function (date, format = 'DD.MM.YYYY') {
    // Защита от пустых и невалидных значений
    if (!date) return '';

    let d;

    // Если строка в формате YYYY-MM-DD (стандартный формат input type="date")
    if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
        const [year, month, day] = date.split('-').map(Number);
        d = new Date(year, month - 1, day); // Парсим как локальную дату, без сдвига UTC
    } else {
        d = new Date(date);
    }

    // Проверяем валидность даты
    if (isNaN(d.getTime())) return '';

    const parts = {
        DD: String(d.getDate()).padStart(2, '0'),
        MM: String(d.getMonth() + 1).padStart(2, '0'),
        YYYY: d.getFullYear(),
        YY: String(d.getFullYear()).slice(-2),
        HH: String(d.getHours()).padStart(2, '0'),
        mm: String(d.getMinutes()).padStart(2, '0'),
        ss: String(d.getSeconds()).padStart(2, '0'),
    };

    return format.replace(
        /YYYY|YY|DD|MM|HH|mm|ss/g,
        match => parts[match]
    );
};

/** $request */
async function $request(url, data = {}) {
    const hasFiles = Object.values(data).some(value =>
        value instanceof File ||
        value instanceof Blob ||
        (Array.isArray(value) && value.some(v => v instanceof File || v instanceof Blob))
    );

    const params = {
        method: "POST"
    };

    if (hasFiles) {
        const formData = new FormData();

        for (const [key, value] of Object.entries(data)) {
            if (Array.isArray(value)) {
                value.forEach(v => formData.append(`${key}[]`, v));
            } else {
                formData.append(key, value);
            }
        }

        params.body = formData;
        
    } else {
        params.headers = {
            "Content-Type": "application/json"
        };
        params.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, params);
        const text = await response.text();
        let json;

        try {
            json = JSON.parse(text);
        } catch {
            throw new Error("Некорректный JSON от сервера: " + text);
        }

        if (!response.ok) {
            console.log(json)
            const message = json?.errors?.message 
                ? `Ошибка ${response.status}: ${json.errors.message}`
                : `Ошибка ${response.status}: неизвестная ошибка`;
            throw new Error(message);
        }

        return json;

    } catch(error) {
        alert(error.message);
        throw error;
    }
}






// VIN маска // перенесено в  forms
// const vinInput = document.getElementById('vinInput');
// if (vinInput) {
//     vinInput.addEventListener('input', function (e) {
//         let raw = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
//         raw = raw.replace(/[IOQ]/g, '');
//         if (raw.length > 17) raw = raw.slice(0, 17);

//         let formatted = '';
//         for (let i = 0; i < raw.length; i++) {
//             if (i > 0 && i % 3 === 0 && i < 15) {
//                 formatted += '-';
//             }
//             formatted += raw[i];
//         }
//         this.value = formatted;
//     });
// }

// Маска для номера кузова
const bodyInput = document.getElementById('bodyNumber');
if (bodyInput) {
    bodyInput.addEventListener('input', function (e) {
        let raw = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
        if (raw.length > 20) raw = raw.slice(0, 20);
        this.value = raw;
    });
}

// Маска для российского автомобильного номера (формат A000AA)
const carNumberInput = document.getElementById('carNumber');
if (carNumberInput) {
    carNumberInput.addEventListener('input', function (e) {
        const validLetters = 'АВЕКМНОРСТУХ';
        let raw = this.value.toUpperCase().replace(/[^АВЕКМНОРСТУХ0-9]/g, '');
        
        let result = '';
        for (let i = 0; i < raw.length && i < 6; i++) {
            const char = raw[i];
            
            if (i === 0 && validLetters.includes(char)) {
                result += char;
            }
            else if (i >= 1 && i <= 3 && /[0-9]/.test(char)) {
                result += char;
            }
            else if (i >= 4 && i <= 5 && validLetters.includes(char)) {
                result += char;
            }
            else {
                break;
            }
        }
        
        this.value = result;
        
        const plateLeft = document.querySelector('.valuation__plate-left');
        if (plateLeft) {
            const letter1 = result[0] || 'А';
            
            const digit1 = result[1] || '0';
            const digit2 = result[2] || '0';
            const digit3 = result[3] || '0';
            const digits = digit1 + digit2 + digit3;
            
            const letter2 = result[4] || 'А';
            const letter3 = result[5] || 'А';
            
            plateLeft.innerHTML = letter1 + '<span>' + digits + '</span>' + letter2 + letter3;
        }
    });
}

// Маска регион
const regionInput = document.getElementById('region');
if (regionInput) {
    regionInput.addEventListener('input', function (e) {
        let raw = this.value.replace(/[^0-9]/g, '');
        if (raw.length > 3) raw = raw.slice(0, 3);
        this.value = raw;
        
        // Обновляем верстку
        const plateRegion = document.querySelector('.valuation__plate-region');
        if (plateRegion) {
            plateRegion.textContent = raw || '0';
        }
    });
}

// Номер двигателя
const engineInput = document.getElementById('engineNumber');
if (engineInput) {
    engineInput.addEventListener('input', function (e) {
        let raw = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (raw.length > 9) raw = raw.slice(0, 9); // 9 символов
        this.value = raw;
    });
}

// Номер СТС
const stsInput = document.getElementById('stsNumber');
if (stsInput) {
    stsInput.addEventListener('input', function (e) {
        // 1. Разрешаем: цифры, буквы латиницы и кириллицы. Приводим к верхнему регистру.
        let raw = this.value.toUpperCase().replace(/[^A-ZА-Я0-9]/g, '');
        
        // 2. Ограничиваем до 10 символов
        if (raw.length > 10) raw = raw.slice(0, 10);
        
        // 3. Автоматическая вставка пробелов для удобства чтения (опционально)
        let formatted = '';
        for (let i = 0; i < raw.length; i++) {
            // Вставляем пробел после 4-го и 6-го символа
            if (i === 4 || i === 6) {
                formatted += ' ';
            }
            formatted += raw[i];
        }
        
        this.value = formatted;
    });
}

// function initPassportSeriesMask(selector = '.passport-series') {
//     const elements = document.querySelectorAll(selector);
//     if (!elements.length) return;
    
//     elements.forEach(element => {
//         if (element.dataset.passportSeriesMasked) return;
//         element.dataset.passportSeriesMasked = 'true';
        
//         element.addEventListener('input', function (e) {
//             let raw = this.value.replace(/[^0-9]/g, '');
//             if (raw.length > 4) raw = raw.slice(0, 4);
//             this.value = raw;
//         });
//     });
// }

// Серия пасспорта
// initPassportSeriesMask('.passport-series');

// function initPassportNumberMask(selector = '.passport-number') {
//     const elements = document.querySelectorAll(selector);
//     if (!elements.length) return;
    
//     elements.forEach(element => {
//         if (element.dataset.passportNumberMasked) return;
//         element.dataset.passportNumberMasked = 'true';
        
//         element.addEventListener('input', function (e) {
//             let raw = this.value.replace(/[^0-9]/g, '');
//             if (raw.length > 6) raw = raw.slice(0, 6);
//             this.value = raw;
//         });
//     });
// }

// Номер пасспорта
// initPassportNumberMask('.passport-number');

// function initDepartmentCodeMask(selector = '.department-code') {
//     const elements = document.querySelectorAll(selector);
//     if (!elements.length) return;
    
//     elements.forEach(element => {
//         if (element.dataset.departmentCodeMasked) return;
//         element.dataset.departmentCodeMasked = 'true';
        
//         element.addEventListener('input', function (e) {
//             let raw = this.value.replace(/[^0-9]/g, '');
//             if (raw.length > 6) raw = raw.slice(0, 6);
            
//             // Форматируем с дефисом после 3 цифр (123-456)
//             let formatted = '';
//             for (let i = 0; i < raw.length; i++) {
//                 if (i === 3) {
//                     formatted += '-';
//                 }
//                 formatted += raw[i];
//             }
            
//             this.value = formatted;
//         });
//     });
// }

// Код подразделения
// initDepartmentCodeMask('.department-code');

// function initINNMask(selector = '.inn-input') {
//     const elements = document.querySelectorAll(selector);
//     if (!elements.length) return;
    
//     elements.forEach(element => {
//         if (element.dataset.innMasked) return;
//         element.dataset.innMasked = 'true';
        
//         element.addEventListener('input', function (e) {
//             let raw = this.value.replace(/[^0-9]/g, '');
//             if (raw.length > 12) raw = raw.slice(0, 12);
//             this.value = raw;
//         });
//     });
// }

// ИНН
// initINNMask('.inn-input');

// // 1. Переключение табов
// function initTabs() {
//     const tabs = document.querySelectorAll('.appraiser__tab');
//     if (!tabs.length) return;
    
//     tabs.forEach(tab => {
//         tab.addEventListener('click', function(e) {
//             e.preventDefault();
            
//             const tabData = this.dataset.tab;
            
//             // Убираем active у всех табов
//             tabs.forEach(t => t.classList.remove('active'));
//             this.classList.add('active');
            
//             // Скрываем все формы
//             const forms = document.querySelectorAll('.appraiser__form');
//             forms.forEach(form => {
//                 form.style.display = 'none';
//                 form.classList.remove('active');
//             });
            
//             // Показываем нужную форму
//             const targetForm = document.querySelector('.appraiser__form[data-tab="' + tabData + '"]');
//             if (targetForm) {
//                 targetForm.style.display = '';
//                 targetForm.classList.add('active');
//             }
//         });
//     });
// }

// 2. Загрузка файла
// function initFileUpload() {
//     const fileInputs = document.querySelectorAll('.file-field__input');
//     if (!fileInputs.length) return;
    
//     fileInputs.forEach(input => {
//         input.addEventListener('change', function() {
//             const file = this.files[0];
//             const field = this.closest('.field');
            
//             if (!file || !field) return;
            
//             field.classList.add('has-file');
//             const nameElement = field.querySelector('.file-field__name');
//             if (nameElement) {
//                 nameElement.textContent = file.name;
//             }
//         });
//     });
// }

// 3. Удаление файла
// function initFileRemove() {
//     const removeButtons = document.querySelectorAll('.file-field__action');
//     if (!removeButtons.length) return;
    
//     removeButtons.forEach(button => {
//         button.addEventListener('click', function(e) {
//             const field = this.closest('.field');
            
//             if (!field || !field.classList.contains('has-file')) {
//                 return;
//             }
            
//             e.preventDefault();
//             e.stopPropagation();
            
//             const input = field.querySelector('.file-field__input');
//             if (input) {
//                 input.value = '';
//             }
            
//             field.classList.remove('has-file');
            
//             const nameElement = field.querySelector('.file-field__name');
//             if (nameElement) {
//                 nameElement.textContent = 'Загрузить файл';
//             }
//         });
//     });
// }

// Инициализация всех функций
// function init() {
//     initTabs();
//     initFileUpload();
//     initFileRemove();
// }

// // Запуск после загрузки DOM
// if (document.readyState === 'loading') {
//     document.addEventListener('DOMContentLoaded', init);
// } else {
//     init();
// }




const items = document.querySelectorAll('.replenshiment__item');

if (items.length) {
    items.forEach(item => {
        item.addEventListener('click', (e) => {
            e.stopPropagation();

            const isActive = item.classList.contains('active');

            // Закрываем все карточки
            items.forEach(el => el.classList.remove('active'));

            // Если текущая не была активна — открываем её
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });

    document.addEventListener('click', () => {
        items.forEach(item => item.classList.remove('active'));
    });
}