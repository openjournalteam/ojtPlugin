<div x-data="pluginGallery()" id="pluginGallery">
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
      <p class="empty-title ojt-my-2 ojt-text-gray-600 ojt-mx-auto">There is no plugin Available for now</p>
    </div>
  </div>
  <div x-show="plugins && !loading && !error">
    <div>
      <div class="ojt-flex ojt-flex-wrap ojt-items-center ojt-my-2 ojt-gap-2">
        {* <input type="search" id="default-input"
          class="ojt-bg-gray-50 ojt-border ojt-border-gray-300 ojt-text-gray-900 ojt-text-sm ojt-rounded-lg focus:ojt-ring-primary-500 focus:ojt-border-primary-500 ojt-block ojt-w-full ojt-max-w-sm ojt-py-2 ojt-px-4"
          placeholder="Search ..." x-model.debounce="search"> *}
        <div class="ojt-relative ojt-grow">
          <div class="ojt-flex ojt-absolute ojt-inset-y-0 ojt-left-0 ojt-items-center ojt-pl-3 ojt-pointer-events-none">
            <svg aria-hidden="true" class="ojt-w-5 ojt-h-5 ojt-text-gray-500" fill="none" stroke="currentColor"
              viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
          </div>
          <input type="search"
            class="ojt-block ojt-p-2 ojt-pl-10 ojt-w-full ojt-text-sm ojt-text-gray-900 ojt-bg-gray-50 ojt-rounded-lg ojt-border ojt-border-gray-300 focus:ojt-ring-primary-500 focus:ojt-border-primary-500"
            placeholder="Search ...." x-model.debounce="search">
        </div>
        <select
          class="ojt-w-full ojt-max-w-[10rem] ojt-bg-gray-50 ojt-border ojt-border-gray-300 ojt-text-gray-900 ojt-text-sm ojt-rounded-lg focus:ojt-ring-primary-500 focus:ojt-border-primary-500 ojt-block ojt-w-full ojt-py-2 ojt-px-4"
          x-model="filter.type">
          <option value="all">Type</option>
          <option value="plugin">Plugins</option>
          <option value="theme">Themes</option>
        </select>
        <select
          class="ojt-w-full ojt-max-w-[10rem] ojt-bg-gray-50 ojt-border ojt-border-gray-300 ojt-text-gray-900 ojt-text-sm ojt-rounded-lg focus:ojt-ring-primary-500 focus:ojt-border-primary-500 ojt-block ojt-w-full ojt-py-2 ojt-px-4"
          x-model="filter.category">
          <option value="all">Category</option>
          <option value="free">Free</option>
          <option value="paid">Paid</option>
          <option value="subscription">Subscription</option>
        </select>
        <button @click="reset()"
          class="ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-sm ojt-font-medium ojt-text-center ojt-text-white ojt-transition ojt-bg-red-700 ojt-rounded-lg ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-red-800 focus:ojt-outline-none ojt-waves-effect">
          Reset
        </button>
      </div>
      {* <section class="ojt-flex ojt-flex-row ojt-flex-wrap ojt-mx-auto ojt-gap-4" x-ref="gallerylist"> *}
      <section class="ojt-grid ojt-grid-cols-1 xl:ojt-grid-cols-3  ojt-mx-auto ojt-gap-4" x-ref="gallerylist">
        <!-- Card Component -->
        <template x-for="(plugin, index) in list" :key="plugin.token">
          <div>
            <div
              class="ojt-h-full ojt-flex ojt-flex-col ojt-items-stretch ojt-transition-all ojt-duration-150 ojt-bg-white ojt-rounded-lg ojt-w-full dark:ojt-bg-dark dark:ojt-text-light ojt-border ojt-border-gray-300 hover:ojt-ring-2 hover:ojt-ring-primary-600">
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
              <div class="ojt-flex ojt-flex-col ojt-flex-wrap ojt-p-4 ojt-x-auto ojt-border-t">
                <a href="#" @click="$dispatch('modal-plugin', plugin)" {* :ojt-modal-toggle="'modal-' + plugin.token" *}
                  class="ojt-text-xl ojt-font-bold ojt-tracking-normal ojt-text-primary-600 hover:ojt-text-primary-800 dark:ojt-text-light"
                  x-text="plugin.name"></a>
                <div class="ojt-text-xs">Latest Version : <span class="ojt-font-bold" x-text="plugin.version"></span>
                </div>
              </div>
              {* <div class="ojt-flex ojt-flex-wrap ojt-px-4 ojt-py-1 ojt-x-auto ojt-border-t ojt-gap-2 ojt-items-center"
                x-show="plugin.type">
                <div class="ojt-font-bold ojt-text-sm">Type : </div>
                <span
                  class="ojt-bg-primary-100 ojt-text-primary-800 ojt-text-sm ojt-font-medium ojt-px-2.5 ojt-py-0.5 ojt-rounded"
                  x-text="plugin.type"></span>
              </div>
              <div class="ojt-flex ojt-flex-wrap ojt-px-4 ojt-py-1 ojt-x-auto ojt-border-t ojt-gap-2 ojt-items-center"
                x-show="plugin.tags">
                <div class="ojt-font-bold ojt-text-sm">Tags : </div>
                <template x-for="(tag, index) in plugin.tags">
                  <span
                    class="ojt-bg-blue-100 ojt-text-blue-800 ojt-text-sm ojt-font-medium ojt-px-2.5 ojt-py-0.5 ojt-rounded"
                    x-text="tag"></span>
                </template>
              </div> *}
              <div class="ojt-border-t ojt-border-gray-300 ojt-h-full">
                <p class="ojt-px-4 ojt-py-2 ojt-overflow-hidden ojt-text-sm ojt-text-gray-700 dark:ojt-text-light"
                  x-text="plugin.description.trimEllip(200)"></p>
              </div>
              <section class="ojt-p-4 ojt-border-gray-300 ojt-border-t" x-show="plugin.shop">
                <div class="ojt-flex ojt-items-center ojt-justify-between">
                  <template x-if="plugin.shop && plugin.category != 'free'">
                    <a :href="plugin.shop" target="_blank"
                      class="ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-transition ojt-bg-blue-700 ojt-rounded-lg ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-blue-800 focus:ojt-outline-none ojt-waves-effect">
                      <i class="fas fa-shopping-cart"></i>
                      Shop
                    </a>
                  </template>
                  <template x-if="plugin.update">
                    <button
                      class="ojt-flex ojt-items-center ojt-px-4 ojt-py-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white  ojt-transition ojt-bg-orange-700 ojt-rounded-lg ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-orange-800 focus:ojt-outline-none ojt-waves-effect ojt-gap-1">
                      <svg class="ojt-w-4 ojt-h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 15 15"
                        fill="currentColor">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M1.90321 7.29677C1.90321 10.341 4.11041 12.4147 6.58893 12.8439C6.87255 12.893 7.06266 13.1627 7.01355 13.4464C6.96444 13.73 6.69471 13.9201 6.41109 13.871C3.49942 13.3668 0.86084 10.9127 0.86084 7.29677C0.860839 5.76009 1.55996 4.55245 2.37639 3.63377C2.96124 2.97568 3.63034 2.44135 4.16846 2.03202L2.53205 2.03202C2.25591 2.03202 2.03205 1.80816 2.03205 1.53202C2.03205 1.25588 2.25591 1.03202 2.53205 1.03202L5.53205 1.03202C5.80819 1.03202 6.03205 1.25588 6.03205 1.53202L6.03205 4.53202C6.03205 4.80816 5.80819 5.03202 5.53205 5.03202C5.25591 5.03202 5.03205 4.80816 5.03205 4.53202L5.03205 2.68645L5.03054 2.68759L5.03045 2.68766L5.03044 2.68767L5.03043 2.68767C4.45896 3.11868 3.76059 3.64538 3.15554 4.3262C2.44102 5.13021 1.90321 6.10154 1.90321 7.29677ZM13.0109 7.70321C13.0109 4.69115 10.8505 2.6296 8.40384 2.17029C8.12093 2.11718 7.93465 1.84479 7.98776 1.56188C8.04087 1.27898 8.31326 1.0927 8.59616 1.14581C11.4704 1.68541 14.0532 4.12605 14.0532 7.70321C14.0532 9.23988 13.3541 10.4475 12.5377 11.3662C11.9528 12.0243 11.2837 12.5586 10.7456 12.968L12.3821 12.968C12.6582 12.968 12.8821 13.1918 12.8821 13.468C12.8821 13.7441 12.6582 13.968 12.3821 13.968L9.38205 13.968C9.10591 13.968 8.88205 13.7441 8.88205 13.468L8.88205 10.468C8.88205 10.1918 9.10591 9.96796 9.38205 9.96796C9.65819 9.96796 9.88205 10.1918 9.88205 10.468L9.88205 12.3135L9.88362 12.3123C10.4551 11.8813 11.1535 11.3546 11.7585 10.6738C12.4731 9.86976 13.0109 8.89844 13.0109 7.70321Z"
                          fill="currentColor"></path>
                      </svg>
                      Update Now
                    </button>
                  </template>
                  <template x-if="!plugin.installed">
                    <button @click="installPlugin(plugin)"
                      class="ojt-flex ojt-items-center ojt-px-4 ojt-py-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white  ojt-transition ojt-bg-primary-700 ojt-rounded-lg ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-primary-800 focus:ojt-outline-none ojt-waves-effect ojt-gap-1">
                      <svg class="ojt-w-4 ojt-h-4" viewBox="0 0 24 24">
                        <path fill="none" stroke="currentColor" stroke-width="2"
                          d="M19 13.5v4L12 22l-7-4.5v-4m7 8.5v-8.5m6.5-5l-6.5-4L15.5 2L22 6l-3.5 2.5h0zm-13 0l6.5-4L8.5 2L2 6l3.5 2.5h0zm13 .5L12 13l3.5 2.5l6.5-4L18.5 9h0zm-13 0l6.5 4l-3.5 2.5l-6.5-4L5.5 9h0z">
                        </path>
                      </svg>
                      Install
                    </button>
                  </template>
                </div>
              </section>
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
      <p class="empty-title ojt-my-2 ojt-text-gray-600 ojt-mx-auto">Failed to load Plugin Gallery</p>
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

{* Modal *}
<div x-data="modalPlugin()" @modal-plugin.window="showPlugin($event.detail)" id="modalPlugin" x-cloak>
  <div
    class="ojt-fixed ojt-inset-0 ojt-w-full ojt-h-full ojt-z-20 ojt-bg-black ojt-bg-opacity-50 ojt-duration-300 ojt-overflow-y-auto"
    x-show="show" x-transition:enter="ojt-transition ojt-duration-400" x-transition:enter-start="ojt-opacity-0"
    x-transition:enter-end="ojt-opacity-100" x-transition:leave="ojt-transition ojt-duration-400"
    x-transition:leave-start="ojt-opacity-100" x-transition:leave-end="ojt-opacity-0">
    <div class="ojt-relative ojt-max-w-4xl ojt-mx-2 sm:ojt-mx-auto ojt-my-10 ojt-opacity-100 ">
      <div
        class="ojt-relative ojt-bg-white ojt-shadow-lg ojt-rounded-md ojt-text-gray-900 ojt-z-20 dark:ojt-bg-dark dark:ojt-text-light"
        @click.away="close()">
        <template x-if="plugin">
          <div>
            <header class="ojt-relative ojt-flex ojt-items-center ojt-justify-between">
              <img :src="plugin.thumbnail ?? placeholderImg" :alt="plugin.name"
                class="ojt-w-full ojt-rounded-md ojt-rounded-b-none ojt-max-h-60 ojt-object-contain ojt-mt-2">
              <div
                class="ojt-rounded-lg ojt-absolute ojt-bottom-2 ojt-left-2 ojt-py-1 ojt-px-3 ojt-bg-pink-900 ojt-bg-opacity-80 ojt-text-white dark:ojt-bg-pink-600 dark:ojt-text-light">
                <p x-text="plugin.name" class="ojt-text-lg lg:ojt-text-2xl "></p>
              </div>
            </header>
            <main class="ojt-grid ojt-p-4 lg:ojt-grid-cols-3 ojt-grid-row-col ojt-gap-4">
              <div class="lg:ojt-col-span-2">
                <p class="ojt-text-sm ojt-text-justify" x-text="plugin.description"></p>

                <div class="ojt-py-4" x-show="isValidURL(plugin.shop) && plugin.category == 'PAID'">
                  Doesn't have license key ?
                  <a :href="plugin.shop" target="_blank" class="ojt-text-primary-600 hover:ojt-text-primary-900">
                    Click here
                  </a>
                  to buy.
                </div>
                <div class="ojt-py-4" x-show="plugin.category == 'SUBSCRIPTION'">
                  Trial token :
                  <span class="ojt-text-primary-600" @click="navigator.clipboard.writeText('OJT-TRIAL-SUBSCRIPTION');">
                    OJT-TRIAL-SUBSCRIPTION
                  </span>
                </div>
              </div>
              <div class="lg:ojt-col-span-1">
                <table class="ojt-border-collapse ojt-w-full">
                  <tbody>
                    <tr x-show="plugin.installed && !plugin?.update">
                      <td class="ojt-w-full ojt-p-3 ojt-border ojt-border-b ojt-block ojt-text-green-700">
                        <small class="ojt-font-bold">Plugin is up-to-date.</small>
                      </td>
                    </tr>
                    <tr>
                      <td
                        class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block dark:ojt-text-light">
                        <span class="ojt-text-sm">Version : </span>
                        <span x-text="plugin.version" class="ojt-text-sm ojt-font-bold"></span>
                      </td>
                    </tr>

                    <tr x-show="plugin.tags">
                      <td
                        class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block dark:ojt-text-light">
                        <div class="ojt-flex ojt-flex-wrap ojt-x-auto ojt-gap-2 ojt-items-center">
                          <span class="ojt-text-sm">Tags :</span>
                          <template x-for="(tag, index) in plugin.tags">
                            <span
                              class="ojt-bg-blue-100 ojt-text-blue-800 ojt-text-sm ojt-font-medium ojt-px-2.5 ojt-py-0.5 ojt-rounded"
                              x-text="tag"></span>
                          </template>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td
                        class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block dark:ojt-text-light">
                        <div class="ojt-flex ojt-flex-wrap ojt-x-auto ojt-gap-2 ojt-items-center" x-show="plugin.type">
                          <div class="ojt-text-sm">Type : </div>
                          <span
                            class="ojt-bg-primary-100 ojt-text-primary-800 ojt-text-sm ojt-font-medium ojt-px-2.5 ojt-py-0.5 ojt-rounded"
                            x-text="plugin.type"></span>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td
                        class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block dark:ojt-text-light">
                        <div class="ojt-flex ojt-flex-wrap ojt-x-auto ojt-gap-2 ojt-items-center" x-show="plugin.type">
                          <div class="ojt-text-sm">Category : </div>
                          <span
                            class="ojt-bg-primary-100 ojt-capitalize ojt-text-primary-800 ojt-text-sm ojt-font-medium ojt-px-2.5 ojt-py-0.5 ojt-rounded"
                            x-text="plugin.category"></span>
                        </div>
                      </td>
                    </tr>

                    <tr x-show="isValidURL(plugin.changelog)">
                      <td class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block">
                        <a :href="plugin.changelog" target="_blank"
                          class="ojt-text-primary-600 hover:ojt-text-primary-900">
                          Changelog </a>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </main>
          </div>
        </template>
      </div>
    </div>
  </div>
</div>