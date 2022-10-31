<div x-data="pluginGallery()" id="pluginGallery" x-init="init()">
  <template x-if="loading && !error">
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
  </template>
  <template x-if="plugins.length < 1 && !loading && !error">
    <div class="empty ojt-flex ojt-flex-col">
      <div class="empty-icon ojt-my-2 ojt-text-gray-600 ojt-mx-auto"><i class="fas fa-box-open fa-5x"></i></div>
      <p class="empty-title ojt-my-2 ojt-text-gray-600 ojt-mx-auto">There is no plugin Available for now</p>
    </div>
  </template>
  <template x-if="plugins && !loading && !error">
    <div>
      {* <div class="ojt-flex ojt-pb-4">
        <div class="ojt-w-full">
          <input type="search" id="search-dropdown"
            class="ojt-block ojt-bg-white ojt-py-2 ojt-px-4 ojt-w-full ojt-z-20 ojt-text-sm ojt-text-gray-900 ojt-bg-gray-50 ojt-rounded-lg ojt-border ojt-border-gray-300 focus:ojt-ring-primary-500 focus:ojt-border-primary-500"
            placeholder="Search plugins..." x-model.debounce="search">
        </div>
      </div> *}
      <div class="ojt-flex ojt-items-center ojt-my-2 ojt-gap-2">
        <select
          class="ojt-w-full ojt-max-w-[10rem] ojt-bg-gray-50 ojt-border ojt-border-gray-300 ojt-text-gray-900 ojt-text-sm ojt-rounded-lg focus:ojt-ring-primary-500 focus:ojt-border-primary-500 ojt-block ojt-w-full ojt-py-2 ojt-px-4">
          <option value="all">All</option>
          <option value="plugins">Plugins</option>
          <option value="themes">Themes</option>
        </select>

        <input type="search" id="default-input"
          class="ojt-bg-gray-50 ojt-border ojt-border-gray-300 ojt-text-gray-900 ojt-text-sm ojt-rounded-lg focus:ojt-ring-primary-500 focus:ojt-border-primary-500 ojt-block ojt-w-full ojt-max-w-sm ojt-py-2 ojt-px-4"
          placeholder="Search ..." x-model.debounce="search">
        <button @click="search = ''"
          class="ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-sm ojt-font-medium ojt-text-center ojt-text-white ojt-transition ojt-bg-red-700 ojt-rounded-lg ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-red-800 focus:ojt-outline-none ojt-waves-effect">
          Reset
        </button>
      </div>
      <section class="ojt-flex ojt-flex-row ojt-flex-wrap ojt-mx-auto ojt-gap-4" x-ref="gallerylist">
        <!-- Card Component -->
        <template x-for="(plugin, index) in filteredPlugins" :key="plugin.token">
          <div
            class="ojt-max-w-sm ojt-h-min ojt-flex ojt-flex-col ojt-items-stretch ojt-transition-all ojt-duration-150 ojt-bg-white ojt-rounded-lg ojt-w-full dark:ojt-bg-dark dark:ojt-text-light ojt-border ojt-border-gray-300 hover:ojt-ring-2 hover:ojt-ring-primary-600">
            <div class="md:ojt-flex-shrink-0 md:ojt-min-h-[12rem] ojt-relative">
              <img :src="plugin?.placeholder ?? placeholderImg" :class="{ 'ojt-object-contain' : plugin.placeholder }"
                :alt="plugin.name" class="ojt-w-full ojt-rounded-lg ojt-rounded-b-none md:ojt-h-56" loading="lazy" />
              <div class="ojt-flex ojt-absolute ojt-left-0 ojt-bottom-0 ojt-items-center ojt-px-4 ojt-py-2"
                x-show="plugin.installed">
                <span
                  class="ojt-bg-green-100 ojt-text-green-800 ojt-text-xs ojt-font-semibold ojt-mr-2 ojt-px-2.5 ojt-py-0.5 ojt-rounded ">Installed</span>
              </div>
            </div>
            <div class="ojt-flex ojt-flex-wrap ojt-p-4 ojt-x-auto ojt-border-t">
              <a href="#" @click="$dispatch('modal-plugin', plugin)"
                class="ojt-text-xl ojt-font-bold ojt-tracking-normal ojt-text-primary-600 hover:ojt-text-primary-800 dark:ojt-text-light"
                x-text="plugin.name"></a>
            </div>
            <div class="ojt-border-t ojt-border-gray-300">
              <p class="ojt-px-4 ojt-py-2 ojt-overflow-hidden ojt-text-sm ojt-text-gray-700 dark:ojt-text-light"
                x-text="plugin.description.trimEllip(200)"></p>
            </div>
            <section class="ojt-p-4 ojt-border-gray-300 ojt-border-t" x-show="plugin.shop">
              <div class="ojt-flex ojt-items-center ojt-justify-between">
                <template x-if="plugin.shop">
                  <a :href="plugin.shop" target="_blank"
                    class="ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-uppercase ojt-transition ojt-bg-blue-700 ojt-rounded-lg ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-blue-800 focus:ojt-outline-none ojt-waves-effect">
                    <i class="fas fa-shopping-cart"></i>
                    Buy Now
                  </a>
                </template>
                {* <button @click="$dispatch('modal-plugin', plugin)"
                    class="ojt-ml-auto ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-uppercase ojt-transition ojt-bg-primary-700 ojt-rounded ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-primary-800 focus:ojt-outline-none ojt-waves-effect">
                    <i class="fas fa-info-circle"></i> Details
                  </button> *}
              </div>
            </section>
          </div>
        </template>
      </section>
    </div>
  </template>
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
    x-show="show" x-transition:enter="ojt-transition ojt-duration-300" x-transition:enter-start="ojt-opacity-0"
    x-transition:enter-end="ojt-opacity-100" x-transition:leave="ojt-transition ojt-duration-300"
    x-transition:leave-start="ojt-opacity-100" x-transition:leave-end="ojt-opacity-0">
    <div class="ojt-relative sm:ojt-w-3/4 ojt-mx-2 sm:ojt-mx-auto ojt-my-10 ojt-opacity-100 ">
      <div
        class="ojt-relative ojt-bg-white ojt-shadow-lg ojt-rounded-md ojt-text-gray-900 ojt-z-20 dark:ojt-bg-dark dark:ojt-text-light"
        @click.away="show=false;key='';" x-show="show"
        x-transition:enter="ojt-transition ojt-transform ojt-duration-300" x-transition:enter-start="ojt-scale-0"
        x-transition:enter-end="ojt-scale-100" x-transition:leave="ojt-transition ojt-transform ojt-duration-300"
        x-transition:leave-start="ojt-scale-100" x-transition:leave-end="ojt-scale-0">
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
              <div class="ojt-col-span-2">
                <p class="ojt-mt-4 ojt-text-sm ojt-text-justify" x-text="plugin.description"></p>
                <div class="ojt-py-4" x-show="isValidURL(plugin.shop)">
                  Doesn't have license key ?
                  <a :href="plugin.shop" target="_blank" class="ojt-text-primary-600 hover:ojt-text-primary-900">
                    Click here
                  </a>
                  to buy.
                </div>
              </div>
              <div class="">
                <table class="ojt-border-collapse ojt-w-full">
                  <tbody>
                    <tr x-show="!plugin.installed">
                      <td class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block">
                        <input x-model="key"
                          class="ojt-tracking-wide ojt-py-2 ojt-px-4 ojt-leading-relaxed ojt-appearance-none ojt-block ojt-w-full ojt-bg-gray-200 ojt-border ojt-border-gray-200 ojt-rounded focus:ojt-outline-none focus:ojt-bg-white focus:ojt-border-gray-500"
                          id="key" placeholder="License Key" type="text">
                      </td>
                    </tr>
                    <tr x-show="!plugin.installed">
                      <td class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block">
                        <button x-text="(loading || installing) ? 'Installing ...' : 'Install'" :disabled="loading"
                          @click="installPlugin(plugin)"
                          class="disabled:ojt-opacity-50 ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-w-full ojt-uppercase ojt-transition ojt-bg-primary-700 ojt-rounded ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-primary-800 focus:ojt-outline-none ojt-waves-effect">
                        </button>
                      </td>
                    </tr>
                    <tr x-show="plugin.installed && plugin?.update">
                      <td class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block">
                        <button x-text="loading ? 'Updating ...' : 'Update'" :disabled="loading"
                          @click="installPlugin(plugin, true)"
                          class="disabled:ojt-opacity-50 ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-w-full ojt-uppercase ojt-transition ojt-bg-primary-700 ojt-rounded ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-primary-800 focus:ojt-outline-none ojt-waves-effect">
                        </button>
                      </td>
                    </tr>
                    <tr x-show="plugin.installed && !plugin?.update">
                      <td class="ojt-w-full ojt-p-3 ojt-border ojt-border-b ojt-block ojt-text-green-700">
                        <small class="ojt-font-bold">Plugin is up-to-date.</small>
                      </td>
                    </tr>
                    <tr x-show="plugin.installed">
                      <td class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block">
                        <label class="ojt-inline-flex ojt-items-center">
                          <input type="checkbox" x-model="resetSetting"
                            class="ojt-form-checkbox ojt-h-4 ojt-w-4 ojt-text-red-600">
                          <span class="ojt-ml-2 ojt-text-gray-700 ojt-text-sm">Reset Setting</span>
                        </label>
                      </td>
                    </tr>
                    <tr x-show="plugin.installed">
                      <td class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block">
                        <button x-text="loading ? 'Uninstalling ...' : 'Uninstall'" :disabled="loading"
                          @click="uninstall(plugin)"
                          class="disabled:ojt-opacity-50 ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-w-full ojt-uppercase ojt-transition ojt-bg-red-700 ojt-rounded ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-red-800 focus:ojt-outline-none ojt-waves-effect">

                        </button>
                      </td>
                    </tr>
                    <tr>
                      <td
                        class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block dark:ojt-text-light">
                        <span x-text="'Version : ' + plugin.version" class="ojt-text-sm"></span>
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