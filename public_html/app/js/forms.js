"use strict";

/**
* @todo показ. валидацию только если button[type=submit]
*/

const Forms = {
    init() {
        document.body.addEventListener("htmx:beforeRequest", (e) => {
            // рекапча
            // const classes = e.detail.target.classList;
            // if (!classes.contains('js-form')) return;
            // console.log(e.detail);
            // this.recaptcha.isOn() && this.recaptcha.run(e);
        });
        
        document.addEventListener("input", (e) => {
            // email с маленькой буквы
            if (e.target.tagName == "INPUT" && e.target.type == "email") {
                e.target.value = e.target.value.toString().toLowerCase();
            }
        });
        
        document.addEventListener("input", (e) => {
            if (e.target.tagName == "SELECT") {
                e.target.classList[e.target.value ? 'add' : 'remove']("_selected");
            }
        });
        
        document.addEventListener(
            "blur",
            (e) => {
                if (e.target.closest(".field")) {
                    e.target.closest(".field").classList.add("show-validation");
                }
            },
            true
        );
        
        document.addEventListener(
            "click",
            (e) => {
                if (e.target.closest("[type=submit]") && e.target.closest("form")) {
                    e.target.closest("form").classList.add("show-validation");
                }
            },
            true
        );
        
        document.querySelectorAll("textarea").forEach(function(textarea) {
            textarea.style.height = textarea.scrollHeight + "px";
            textarea.style.overflowY = "hidden";
            
            textarea.addEventListener("input", function() {
                this.style.height = "auto";
                this.style.height = this.scrollHeight + "px";
            });
        });
        
        this.mask();
    },
    
    mask() {
        if (window.Maska) {
            const { MaskInput } = Maska;
            
            new MaskInput("[data-maska]");
            
            new MaskInput("[type=tel]", {
                mask: "+7 (9##) ###-##-##",
                tokens: {
                    9: { pattern: /9/ }
                },
            })
            document.querySelectorAll('[type=tel]').forEach((el) => {
                el.pattern = '\\+7\\s\\(9[0-9]{2}\\)\\s[0-9]{3}-[0-9]{2}-[0-9]{2}'
                el.title = 'Введите номер в формате +7 (9##) ###-##-##'
            });
            
            new MaskInput('[name=vin]', {
                mask: 'AAAAAAAAAAAAAAAAA',
                tokens: {
                    A: {
                        pattern: /[A-HJ-NPR-Z0-9]/,
                        transform: (val) => val.toUpperCase()
                    }
                }
            });
            document.querySelectorAll('[name=vin]').forEach((el) => {
                el.pattern = '[A-HJ-NPR-Z0-9]{17}'
                el.title = 'VIN должен содержать 17 символов'
            });

            // Госномер
            new MaskInput('[name=registration_number]', {
                mask: 'A###AA###',
                tokens: {
                    A: {
                        pattern: /[а-яА-Я]/,
                        transform: (val) => val.toUpperCase()
                    }
                }
            });

            // AirDatepicker
            // document.querySelectorAll('.js-air-datepicker').forEach((el) => {
            //     const altField = el.closest('label').querySelector('[type="date"]');
            //     if (!altField) return;

            //     el.addEventListener('keydown', (event) => {
            //         event.preventDefault(); // запрещаем ввод
            //     });

            //     altField.addEventListener('keydown', (event) => {
            //         event.preventDefault(); // запрещаем ввод
            //     });

            //     altField.addEventListener('invalid', (event) => {
            //         event.preventDefault();   // отменяем нативный focus + тултип
            //         el.focus();            
            //     });

            //     const dp = new AirDatepicker(el, {
            //         altField,
            //         altFieldDateFormat: 'yyyy-MM-dd',
            //         autoClose: true,
            //         buttons: ['clear'] ,

            //         onSelect({ date }) {
            //             altField.dispatchEvent(new Event('change', { bubbles: true }));
            //         }
            //     });

            //     if (altField.value) {
            //         dp.selectDate(altField.value);
            //     }
            // });
        }
        
        // ограничение на дату
        document.querySelectorAll('input[type="datetime-local"]').forEach(el => {
            const now = new Date();
            now.setHours(0, 0, 0, 0); // обнуляем время
            
            const year2050 = new Date('2050-12-31T23:59');
            
            const pad = n => String(n).padStart(2, '0');
            const fmtLocal = d =>
                `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
            
            el.min = fmtLocal(now);
            el.max = fmtLocal(year2050);
        });
    },
    
    ga(target, params = { event_category: "form" }) {
        window.gtag && gtag("event", target, params);
    },
    
    metrika(target) {
        if (window.Ya && (window.Ya.Metrika || window.Ya.Metrika2)) {
            const ym = Ya.Metrika || Ya.Metrika2;
            const counters = ym.counters() || [];
            for (let i of counters) {
                const counter = window[`yaCounter${i.id}`];
                counter && counter.reachGoal(target);
            }
        }
    },
    
    // recaptcha: {
    //     token: false,
    
    //     run(e) {
    //         if (!this.token) {
    //             e.preventDefault();
    //             this.execute()
    //                 .then((token) => {
        //                     this.token = token;
    //                     console.log(token);
    //                     htmx.trigger(e.detail.target, 'submit');
    //                 })
    //         } else {
        //             e.detail.requestConfig.parameters.token = this.token;
    //             this.token = false;
    //         }
    //     },
    
    //     isOn() {
    //         return false;
    //     },
    
    //     execute() {
    //         return new Promise((resolve, reject) => {
        //             setTimeout(() => {
            //                 resolve(12345);
    //             }, 500);
    //         });
    //     },
    // },
};

Forms.init();

// var s = document.createElement('script');
// s.setAttribute('src', 'https://www.google.com/recaptcha/api.js?render=6Lf3VZUUAAAAAG34Gk5v1ZimWIULdfOBetgoqDaX');
// document.body.appendChild(s);
