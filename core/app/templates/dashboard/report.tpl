{extends 'dashboard.tpl'}
{block 'header'}
{/block}
{block 'page_content'}
    {* {'AuthCheck'|snippet nocache} *}

    {function nav} 
        <div class="report__buttons">
            <button class="report__button-prev button _outlined _sm _muted font-normal"
                @click="changeStep(currentStep - 1)"
                :disabled="currentStep <= 1"
            >
                {'/app/img/icons/arrow-right-thin.svg'|svg}
                Назад
            </button>
            {* <div class="report__description">Заполните все обязательные поля</div> *}
            <button class="button _sm font-normal" @click="changeStep(currentStep + 1)" x-show="currentStep != 4"
                :disabled="currentStep == 1 && type != 'manual' && !report.vehicle?.vin_decoded"
            >
                Продолжить {'/app/img/icons/arrow-right-thin.svg'|svg}
            </button>
            <button class="button _sm _success font-normal" @click="changeStep(currentStep + 1)" x-show="currentStep == 4">
                {'/app/img/icons/folder-download.svg'|svg}
                Сформировать отчёт
            </button>
        </div>
    {/function}

    <div class="report" :class="{ 'is-loading': loading }" x-data='report({$report|json_encode|escape})'>
        <div class="report__steps">
            <template x-for="item, key in steps" :key="key">
                <div
                    class="report__step"
                    :class="{ '_active': currentStep == key }"
                    @click="changeStep(+key)"
                >
                    <span x-text="'Шаг ' + key + ' —'"></span>
                    <span x-text="item"></span>
                </div>
            </template>
        </div>
        
        <div class="report__steps-mobile">
            <div class="report__steps-mobile-items">
                <template x-for="item, key in steps" :key="key">
                    <div
                        class="report__steps-mobile-item"
                        :class="{ '_active': currentStep == key }"
                        @click="currentStep = +key"
                    ></div>
                </template>
            </div>
            <div class="report__steps-mobile-wrapper">
                <div
                    class="report__steps-mobile-progress"
                    x-text="'Шаг ' + currentStep + ' из 5'"
                ></div>
                <div
                    class="report__steps-mobile-title"
                    x-text="steps[currentStep]"
                ></div>
            </div>
        </div>

        <div class="report__blocks">
            <template x-if="currentStep == 1">
                {include 'dashboard/report/step-1'}
            </template>
            <template x-if="currentStep == 2">
                {include 'dashboard/report/step-2'}
            </template>
            <template x-if="currentStep == 3">
                <div>
                    {include 'dashboard/report/step-3'}
                </div>
            </template>
            <template x-if="currentStep == 4">
                <div>
                    {include 'dashboard/report/step-4'}
                </div>
            </template>
            <template x-if="currentStep == 5">
                <div>
                    {include 'dashboard/report/step-5'}
                </div>
            </template>
        </div>
    </div>
{/block}
{block 'blocks'}{/block}