<div x-data="pluginExclusive()" id="pluginExclusive">
    <div x-show="loading && !error">
        <div class="loading-menu ojt-flex ojt-items-center ojt-h-50">
            <div class="lds-roller ojt-self-center ojt-mx-auto">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
    </div>

    <div x-show="plugins.length < 1 && !loading && !error">
        <div class="empty ojt-flex ojt-flex-col">
            <div class="empty-icon ojt-my-2 ojt-text-gray-600 ojt-mx-auto"><i class="fas fa-box-open fa-5x"></i></div>
            <p class="empty-title ojt-my-2 ojt-text-gray-600 ojt-mx-auto">There is no <span class="ojt-text-primary-500">exclusive plugin</span> available for now</p>
        </div>
    </div>

    <div x-show="plugins && !loading && !error">
        <div>
            <section class="ojt-grid ojt-grid-cols-1 xl:ojt-grid-cols-3  ojt-mx-auto ojt-gap-4" x-ref="gallerylist">
                <!-- Card Component -->
                <template x-for="(plugin, index) in list" :key="plugin.token">
                    <div>
                        <div class="ojt-h-full ojt-flex ojt-flex-col ojt-items-stretch ojt-transition-all ojt-duration-150 ojt-bg-white ojt-rounded-lg ojt-w-full dark:ojt-bg-dark dark:ojt-text-light ojt-border ojt-border-gray-300 hover:ojt-ring-2 hover:ojt-ring-primary-600">
                            <div class="md:ojt-flex-shrink-0 md:ojt-min-h-[12rem] ojt-relative">
                                <img :src="plugin?.placeholder ?? placeholderImg" :class="{ 'ojt-object-contain' : plugin.placeholder }"
                                :alt="plugin.name" class="ojt-w-full ojt-rounded-lg ojt-rounded-b-none md:ojt-h-56" loading="lazy" />
                                <div class="ojt-flex ojt-absolute ojt-left-0 ojt-bottom-0 ojt-items-center ojt-px-4 ojt-py-2">
                                <span x-show="plugin.installed"
                                    class="ojt-bg-green-100 ojt-text-green-800 ojt-text-xs ojt-font-semibold ojt-mr-2 ojt-px-2.5 ojt-py-0.5 ojt-rounded ">
                                    Installed
                                </span>
                                <span x-show="plugin.update"
                                    class="ojt-bg-orange-100 ojt-text-orange-800 ojt-text-xs ojt-font-semibold ojt-mr-2 ojt-px-2.5 ojt-py-0.5 ojt-rounded ">
                                    Update Available
                                </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </section>
        </div>
    </div>

    <template x-if="error">
        <div class="empty ojt-flex ojt-flex-col">
            <div class="empty-icon ojt-my-2 ojt-text-gray-600 ojt-mx-auto">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                class="ojt-w-10 ojt-h-10">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="empty-title ojt-my-2 ojt-text-gray-600 ojt-mx-auto">Failed to load Exclusive Plugin</p>
            <button @click.prevent="reload"
                class="ojt-flex ojt-items-center ojt-w-max ojt-mx-auto ojt-px-4 ojt-py-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-uppercase ojt-transition ojt-bg-primary-700 ojt-rounded ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-primary-800 focus:ojt-outline-none ojt-waves-effect ojt-gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                class="ojt-w-4 ojt-h-4">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Reload
            </button>
        </div>
    </template>
</div>