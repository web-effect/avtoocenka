document.addEventListener("alpine:init", () => {
    Alpine.data("report", (data) => ({
        loading: false,

        report: data,
        vehicleApiData: {},

        currentStep: +data.step || 1,
        steps: {
            1: 'АМТС',
            2: 'Оценка',
            3: 'Заказчик',
            4: 'Отчёт',
            5: 'Документы'
        },

        // Шаг 1
        type: 'auto',
        vin: '',

        // Шаг 2
        discount: +data.discount || 0,

        // Шаг 5
        file: null,
        drag: false,
        selectedEmails: [],
        newEmail: '',

        init() {
            const remask = () => {
                Alpine.nextTick(() => Forms.mask());
            };
            remask();
            this.$watch('type', remask);
            this.$watch('currentStep', remask);
            
            this.normalizeData();

            if (this.report.vehicle?.vin) {
                this.vin = this.report.vehicle?.vin;
            }
            
            console.log(this.currentStep)
            console.log(this.type)

            if (this.currentStep == 1) {
                this.getVehicleApiData(); // @todo только на 1 шаге и при ручном вводе
            }
        },

        async getVehicleApiData() {
            const params = {
                brand: this.report.vehicle.brand_id || '',
                model: this.report.vehicle.model_id || '',
                generation: this.report.vehicle.generation_id || '',
                configuration: this.report.vehicle.configuration_id || '',
            }
            const queryString = new URLSearchParams(params).toString();
            const url = "/api/auto?" + queryString;

            try {
                const response = await fetch(url);
                const result = await response.json(); // Parses JSON response into a JS object
                
                if (!result.data.data) { alert('Неверный ответ от сервера'); return; }
                this.vehicleApiData = result.data.data;
                console.log(this.vehicleApiData);

            } catch (error) {
                console.error("Fetch failed:", error); // Catches network failures
            }
        },

        normalizeData() {
            // поля, которые должны быть объектами
            ["vehicle", "customer", "owner"/*, "api_prices"*/].forEach(key => {
                this.report[key] ??= {};
            });

            // поля, которые должны быть массивами
            ["sent_emails", "api_offers"].forEach(key => {
                this.report[key] ??= [];
                if (!Array.isArray(this.report[key])) {
                    this.report[key] = Object.values(this.report[key]);
                }
            });

            // if (this.report?.vehicle?.vin_decoded === false) {
            //     this.type = 'manual';
            // }

            // if (this.report?.vehicle?.vin_decoded === true) {
            //     this.type = 'auto';
            // }

            console.log(this.report)
        },

        setReportData(data) {
            if (!data) {
                alert('Неверный ответ от сервера');
                return;
            }
            this.report = data;
            this.normalizeData();
        },

        decodeVin() {
            this.loading = true;

            $request(`/api/reports/${this.report.id}/decode-vin`, { 
                vin : this.vin,
            }).then(result => {
                this.setReportData(result.data.report);
            }).finally(() => {
                this.loading = false;
            });
        },

        // заблокировано ли поле (на 1 шаге)
        isFieldEditable(field) {
            const fieldsLockedAfterStep = [
                'brand_name', 'model_name', 'year', 'region'
            ];

            return !(
                (this.report.vehicle?.vin_decoded_fields ?? []).includes(field) ||
                (this.report.step > 1 && fieldsLockedAfterStep.includes(field))
            );
        },

        // изменить поле
        change(field, value) {
            this.loading = true;

            $request(`/api/reports/${this.report.id}/change`, { 
                field,
                value
            }).then(result => {
                this.setReportData(result.data.report);

                iziToast.success({
                    message: 'Сохранено'
                });

                if (field.includes('vehicle_')) {
                    this.getVehicleApiData();
                }
            }).finally(() => {
                this.loading = false;
            });
        },

        // changeRegion(id) {
        //     if (id === '') {
        //         this.change('vehicle_region', {
        //             id: null,
        //             name: null,
        //         });

        //         return;
        //     }

        //     const item = Dict.regions.find(item => item.id == id);

        //     this.change('vehicle_region', {
        //         id: item.id,
        //         name: item.name,
        //     });
        // },

        get vehicleAge() {
            const pluralize = function(number, one, few, many) {
                const n = Math.abs(number) % 100;
                const n1 = n % 10;

                if (n >= 11 && n <= 19) {
                    return many;
                }

                if (n1 === 1) {
                    return one;
                }

                if (n1 >= 2 && n1 <= 4) {
                    return few;
                }

                return many;
            };

            const date = this.report.vehicle?.operation_start_date;

            if (!date) {
                return null;
            }

            const start = new Date(date);
            const today = new Date();

            let months =
                (today.getFullYear() - start.getFullYear()) * 12 +
                (today.getMonth() - start.getMonth());

            // Если день начала эксплуатации ещё не наступил в текущем месяце
            if (today.getDate() < start.getDate()) {
                months--;
            }

            const years = Math.floor(months / 12);
            const remainingMonths = months % 12;

            const parts = [];

            if (years) {
                parts.push(`${years} ${pluralize(years, 'год', 'года', 'лет')}`);
            }

            if (remainingMonths) {
                parts.push(
                    `${remainingMonths} ${pluralize(remainingMonths, 'месяц', 'месяца', 'месяцев')}`
                );
            }

            return parts.length ? parts.join(' ') : '0 месяцев';
        },

        formatDate(date = null) {
            if (!date) {
                const today = new Date();

                date = [
                    today.getFullYear(),
                    String(today.getMonth() + 1).padStart(2, '0'),
                    String(today.getDate()).padStart(2, '0')
                ].join('-');
            }

            const [year, month, day] = date.split('-');

            const months = [
                'января', 'февраля', 'марта', 'апреля',
                'мая', 'июня', 'июля', 'августа',
                'сентября', 'октября', 'ноября', 'декабря'
            ];

            return `${Number(day)} ${months[Number(month) - 1]} ${year} г.`;
        },

        // возможно переделать в массив, min и max не используется
        technicalConditions: {
            'Новое': { min: 0, max: 10 },
            'Очень хорошее': { min: 10, max: 20 },
            'Хорошее': { min: 20, max: 40 },
            'Удовлетворительное': { min: 40, max: 60 },
            'Условно пригодное': { min: 60, max: 75 },
            'Неудовлетворительное': { min: 75, max: 80 },
            'Предельное': { min: 80, max: 100 },
        },
        // get currentTechnicalCondition() {
        //     return this.technicalConditions[this.report.vehicle?.technical_condition];
        // },

        // setTechnicalCondition(condition) {
        //     const range = this.technicalConditions[condition];

        //     if (!range) {
        //         return;
        //     }

        //     this.change('vehicle_technical_condition', condition);
        //     //this.change('vehicle_wear', range.max);
        // },

        // validateWear(event) {
        //     const input = event.target;
        //     const range = this.currentTechnicalCondition;

        //     if (!range) {
        //         return;
        //     }

        //     input.setCustomValidity('');

        //     if (input.value === '') {
        //         return;
        //     }

        //     const value = Number(input.value);

        //     if (value < range.min || value > range.max) {
        //         input.setCustomValidity(
        //             `Введите значение от ${range.min} до ${range.max}`
        //         );
        //         input.closest('label')?.classList?.add('show-validation');
        //         input.reportValidity();
        //         return;
        //     }

        //     this.change('vehicle_wear', value);
        //     return true;
        // },

        changeStep(step) {
            this.loading = true;

            this.$nextTick(() => {
                // вызываем валидацию форм
                const forms  = this.$root.querySelectorAll('form');
                let error = false;

                if (forms && step > this.currentStep) {
                    for (let i = forms.length - 1; i >= 0; i--) {
                        forms[i].classList.add('show-validation');
                        if (!forms[i].reportValidity()) {
                            error = true;
                        }
                    };
                }

                if (error) {
                    this.loading = false;
                    iziToast.error({
                        message: 'Заполните обязательные поля'
                    });
                    return;
                }
                
                this.currentStep = +step;
                
                $request(`/api/reports/${this.report.id}/change-step`, { step }).then(result => {
                    this.setReportData(result.data.report);

                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }).catch((error) => {
                    this.currentStep = +this.report.step;
                }).finally(() => {
                    this.loading = false;
                });
            });
        },

        getTronkDictName(dict, value) {
            return Object.values(Dict[dict])[value - 1]?.name ?? '';
        },

        replaceOffer(id) {
            this.loading = true;
            
            $request(`/api/reports/${this.report.id}/replace-offer`, { 
                offer_id : id,
            }).then(result => {
                this.setReportData(result.data.report);

                iziToast.success({
                    message: 'Сохранено'
                });
            }).finally(() => {
                this.loading = false;
            });
        },

        get displayedOffers() {
            if (!this.report?.api_offers) return [];
            
            return this.report.api_offers
                // Фильтруем только те, где selected не null/undefined/false
                .filter(item => item.selected !== null && item.selected !== false && item.selected !== undefined)
                // Сортируем, приводя к числу, чтобы избежать NaN
                .sort((a, b) => Number(a.selected) - Number(b.selected));
        },

        async downloadDocument() {
            this.loading = true;

            const response = await fetch(
                `/api/reports/${this.report.id}/download-file`
            );

            if (!response.ok) {
                this.loading = false;
                throw new Error('Не удалось скачать файл');
            }

            // const disposition = response.headers.get('Content-Disposition');
            // const match = disposition?.match(/filename="?([^"]+)"?/);
            // const filename = match?.[1] ?? 'report.docx';

            const disposition = response.headers.get('Content-Disposition');

            const match = disposition?.match(/filename\*=UTF-8''([^;]+)/i);

            const filename = match
                ? decodeURIComponent(match[1])
                : 'report.docx';

            const blob = await response.blob();
            const url = URL.createObjectURL(blob);

            const link = document.createElement('a');
            link.href = url;
            link.download = filename;

            document.body.appendChild(link);
            link.click();
            link.remove();

            URL.revokeObjectURL(url);

            this.change('file_downloaded', true);

            this.loading = false;
        },

        selectFile(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.file = file;
            this.uploadFile(file);
        },

        dropFile(e) {
            this.drag = false;
            const file = e.dataTransfer.files[0];
            if (!file) return;

            this.file = file;

            this.uploadFile(file);
        },

        uploadFile(file) {
            this.loading = true;

            $request(`/api/reports/${this.report.id}/upload-file`, { file }).then(result => {
                if (!result.data.report) { alert('Неверный ответ от сервера'); return; }
                this.report = result.data.report;
                this.normalizeData();
                iziToast.success({
                    message: 'Файл загружен'
                });
            }).finally(() => {
                this.loading = false;
            });
        },
        
        sendEmail() {
            this.loading = true;

            $request(`/api/reports/${this.report.id}/send-email`, { emails: this.selectedEmails }).then(result => {
                if (!result.data.report) { alert('Неверный ответ от сервера'); return; }
                this.report = result.data.report;
                this.normalizeData();
                this.selectedEmails = [];
                iziToast.success({
                    message: 'Отправлено'
                });
            }).finally(() => {
                this.loading = false;
            });
        },

        addRecentEmail(email) {
            if (this.selectedEmails.includes(email)) return;
            this.selectedEmails.push(email);
        },

        addEmail() {
            const email = this.newEmail.trim().toLowerCase();

            if (!email) {
                //this.emailError = false;
                return;
            }

            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!re.test(email)) {
                //this.emailError = true;

                iziToast.warning({
                    title: 'Некорректный e-mail'
                });

                return;
            }

            if (!this.selectedEmails.includes(email)) {
                this.selectedEmails.push(email);
            }

            this.newEmail = '';
        },
    }));



    Alpine.data("appraiser", (profile, required) => ({
        profile,
        required,
        appraiserType: profile.appraiser_type || '',

        init() {
            const remask = () => {
                Alpine.nextTick(() => Forms.mask());
            };
            remask();
            this.$watch('appraiserType', remask);
        },

        save() {
            if (this.$root.reportValidity()) {
                iziToast.success({
                    message: 'Сохранено'
                });
            } else {
                iziToast.error({
                    message: 'Заполните обязательные поля'
                });
            }
            return;
        },

        change(field, value) {
            $request('/api/user/update', this.profile)
                .then(result => {
                    if (!result.data.profile) { alert('Неверный ответ от сервера'); return; }
                    this.profile = result.data.profile;

                    if (this.appraiserType !== this.profile.appraiser_type) {
                        this.appraiserType = this.profile.appraiser_type;
                    }

                    iziToast.success({
                        message: 'Сохранено'
                    });
                });
        },

        selectFile(e, name) {
            const file = e.target.files[0];
            if (!file) return;
            this.uploadFile(name, file);
        },

        dropFile(e, name) {
            const file = e.dataTransfer.files[0];
            if (!file) return;
            this.uploadFile(name, file);
        },

        uploadFile(name, file) {
            $request('/api/user/upload-file', { name, file }).then(result => {
                if (!result.data.profile) { alert('Неверный ответ от сервера'); return; }
                this.profile = result.data.profile;

                iziToast.success({
                    message: 'Файл загружен'
                });
            });
        },

        deleteFile(name) {
            if (!confirm('Удалить файл?')) return;

            $request('/api/user/delete-file', { name }).then(result => {
                if (!result.data.profile) { alert('Неверный ответ от сервера'); return; }
                this.profile = result.data.profile;

                iziToast.success({
                    message: 'Файл удалён'
                });
            });
        },
    }));
});