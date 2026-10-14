<div class="report__fifth">
    <div class="report__title">Получение и отправка документов</div>

    <div class="documents__blocks">

        <div class="documents__block" :class="{ '_active': report.file_downloaded }">
            <div class="documents__block-header">
                <div class="documents__block-left">
                    <div class="documents__block-icon">
                        {'app/img/icons/checkmark-circle-fill.svg'|svg}
                        <span>1</span>
                    </div>
                    <div class="documents__block-title">
                        Шаг 1 — Скачайте отчёт об оценке
                    </div>
                </div>
                <div class="documents__block-header-description">Готов к скачиванию</div>
            </div>
            <div class="documents__block-wrapper">
                <div class="documents__block-document">
                    <div class="documents__document-left">
                        {'app/img/icons/documents.svg'|svg}
                        <div class="documents__document-content">
                            <div class="documents__document-title">Отчёт об оценке № <span x-text="report.report_number"></span>.docx</div>
                            <div class="documents__document-description">
                                <span x-text="report.vehicle.brand_name"></span>
                                <span x-text="report.vehicle.model_name"></span>
                                <span x-text="report.vehicle.year"></span>
                                —
                                <span x-text="$numberFormat(report.price - report.discount_price, '₽')"></span>
                                — 
                                <span x-text="formatDate(report.report_date)"></span>
                            </div>
                        </div>
                    </div>
                    <button class="documents__document-button" @click="downloadDocument()">
                        Скачать DOCX
                    </button>
                </div>
            </div>
        </div>
        
        <div class="documents__block" :class="{ '_active': report.file, '_disabled': !report.file_downloaded }">
            <div class="documents__block-header">
                <div class="documents__block-left">
                    <div class="documents__block-icon">
                        {'app/img/icons/checkmark-circle-fill.svg'|svg}
                        <span>2</span>
                    </div>
                    <div class="documents__block-title">
                        Шаг 2 — Подпишите и загрузите подписанный отчёт
                    </div>
                </div>
                <div class="documents__block-header-description | text-muted" x-show="!report.file_downloaded">Заблокировано</div>
                <div class="documents__block-header-description | text-warning" x-show="report.file_downloaded && !report.file">Ожидает загрузки</div>
                <div class="documents__block-header-description" x-show="report.file">Загружено</div>
            </div>
            <div class="documents__block-wrapper">
                <div class="documents__steps">
                    <div class="documents__step" :class="{ '_active': report.file_downloaded }">
                        <div class="documents__step-icon">
                            {'app/img/icons/checkmark-circle-2.svg'|svg}
                            <span>1</span>
                        </div>
                        <div class="documents__step-content">
                            <div class="documents__step-title">Скачайте DOCX</div>
                            <div class="documents__step-description">Файл отчёта получен на вашем устройстве</div>
                        </div>
                    </div>
                    
                    <div class="documents__steps-divider"></div>
                    
                    <div class="documents__step" :class="{ '_active': report.file }">
                        <div class="documents__step-icon">
                            {'app/img/icons/checkmark-circle-2.svg'|svg}
                            <span>2</span>
                        </div>
                        <div class="documents__step-content">
                            <div class="documents__step-title">Откройте в Word и подпишите / проставьте печать</div>
                            <div class="documents__step-description">Сохраните в PDF или оставьте в DOCX</div>
                        </div>
                    </div>
                    
                    <div class="documents__steps-divider"></div>
                    
                    <div class="documents__step" :class="{ '_active': report.file }">
                        <div class="documents__step-icon">
                            {'app/img/icons/checkmark-circle-2.svg'|svg}
                            <span>3</span>
                        </div>
                        <div class="documents__step-content">
                            <div class="documents__step-title">Загрузите подписанный файл ниже</div>
                            <div class="documents__step-description">Форматы: PDF, DOCX. Максимуб 20 МБ.</div>
                        </div>
                    </div>
                </div>
                
                <div
                    class="documents__files"
                    @dragover.prevent="drag = true"
                    @dragleave.prevent="drag = false"
                    @drop.prevent="dropFile($event)"
                    @click="$refs.file.click()"
                >
                    <input
                        type="file"
                        x-ref="file"
                        class="hidden"
                        accept=".pdf,.doc,.docx"
                        @change="selectFile($event)"
                        hidden
                    >
                    <div class="documents__files-list">
                        <!-- Если файла нет -->
                        <template x-if="!file && !report.file">
                            <div class="documents__file">
                                <div class="documents__file-icon">
                                    <svg fill="none" height="40" viewBox="0 0 40 40" width="40" xmlns="http://www.w3.org/2000/svg"><rect fill="#5b6375" fill-opacity=".1" height="40" rx="20" width="40"/><g stroke="#5b6375" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="m30 20c0 5.5228-4.4772 10-10 10s-10-4.4772-10-10 4.4772-10 10-10 10 4.4772 10 10z"/><path d="m23.5 18.5s-2.5777-3.5-3.5-3.5-3.5 3.5-3.5 3.5m3.5-3v6.5m-3.5 3h7"/></g></svg>
                                </div>
                                <div class="documents__file-name">
                                    Файл ещё не загружен
                                </div>
                            </div>
                        </template>
                
                        <!-- Если файл выбран -->
                        <template x-if="file || report.file">
                            <div class="documents__file">
                                <div class="documents__file-icon">
                                    {'app/img/icons/files.svg'|svg}
                                </div>
                                <div
                                    class="documents__file-name"
                                    x-text="file?.name || 'Файл загружен'"
                                ></div>
                            </div>
                        </template>
                    </div>
                    <div class="documents__files-content">
                        <div
                            class="documents__files-title"
                            x-show="file || report.file"
                        >
                            Нажмите, чтобы загрузить другой файл
                        </div>
                        <div
                            class="documents__files-title"
                            x-show="!file && !report.file"
                        >
                            Перетащите файл или нажмите для выбора
                        </div>
                        <div class="documents__files-description">
                            PDF или DOCX. До 20 МБ
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="documents__block" :class="{ '_active': report.sent_emails?.length, '_disabled': !report.file }">
            <div class="documents__block-header">
                <div class="documents__block-left">
                    <div class="documents__block-icon">
                        {'app/img/icons/checkmark-circle-fill.svg'|svg}
                        <span>3</span>
                    </div>
                    <div class="documents__block-title">
                        Шаг 3 — Отправьте документы заказчику
                    </div>
                </div>
                <div class="documents__block-header-description | text-muted" x-show="!report.file">Заблокировано</div>
            </div>
            <div class="documents__block-wrapper">
                <div class="documents__emails">
                    <div class="documents__emails-label">
                        Получатели
                    </div>
                    <div class="documents__emails-wrapper">
                        <div class="documents__emails-list" x-show="selectedEmails">
                            <template x-for="email in selectedEmails" :key="email">
                                <div class="documents__email">
                                    <div
                                        class="documents__email-name"
                                        x-text="email"
                                    ></div>
                                    <button
                                        class="documents__email-remove"
                                        @click="selectedEmails = selectedEmails.filter(e => e !== email)"
                                    >
                                        {'app/img/icons/delete.svg'|svg}
                                    </button>
                                </div>
                            </template>
                        </div>
                        <input
                            class="documents__email-input"
                            x-model="newEmail"
                            @keydown.enter.prevent="addEmail()"
                            @keyup.space="addEmail()"
                            @blur="addEmail()"
                            placeholder="Добавить e-mail..."
                        >
                    </div>
                </div>
                
                {* <div class="" x-text="JSON.stringify(report)"></div> *}
                
                <div class="documents__email-inner" x-show="report.sent_emails?.length">
                    <div class="documents__email-recent">
                        <div class="documents__recent-title">Было отправлено:</div>
                        <div class="documents__recent-list">
                            <template x-for="email in report.sent_emails" :key="email">
                                <div
                                    class="documents__recent-email"
                                    @click="addRecentEmail(email)"
                                >
                                    <div
                                        class="documents__recent-email-name"
                                        x-text="email"
                                    ></div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="documents__email-description">
                        Для повторного отправления выберите e-mail
                    </div>
                </div>
                
                <button
                    class="button _sm _success font-normal"
                    :disabled="!selectedEmails.length"
                    @click="sendEmail()"
                >
                    {'app/img/icons/sent-2.svg'|svg} Отправить документы
                </button>
            </div>
        </div>
    </div>
        {* <div class="documents__right">
            <div class="report__alert _success">
                <svg fill="none" height="20" viewBox="0 0 20 20" width="20" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><clipPath id="a"><path d="m0 0h20v20h-20z"/></clipPath><g clip-path="url(#a)" stroke="#2c6e49" stroke-width="1.5"><path d="m18.3327 9.99992c0-4.60237-3.731-8.33333-8.33335-8.33333-4.60237 0-8.33333 3.73096-8.33333 8.33333 0 4.60238 3.73096 8.33338 8.33333 8.33338 4.60235 0 8.33335-3.731 8.33335-8.33338z"/><path d="m6.66602 10.6248s1.33333.7605 2 1.875c0 0 1.99998-4.37496 4.66668-5.8333" stroke-linecap="round" stroke-linejoin="round"/></g></svg>
                <b>
                    Отчёт сформирован и готов к скачиванию
                </b>
            </div>
            <div class="report__alert">
                {'/app/img/icons/info-icon.svg'|svg}
                <span>
                    Подписанный файл будет <b>сохранён в истории</b> проекта и доступен для повторного скачивания.
                </span>
            </div>
        </div> *}
</div>