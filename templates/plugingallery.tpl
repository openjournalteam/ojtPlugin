<div x-data="pluginGallery()" id="pluginGallery" x-init="fetchPlugins()">
  <template x-if="plugins.length < 1 && !isLoading">
    <div class="empty ojt-flex ojt-flex-col">
      <div class="empty-icon ojt-my-2 ojt-text-gray-600 ojt-mx-auto"><i class="fas fa-box-open fa-5x"></i></div>
      <p class="empty-title ojt-my-2 ojt-text-gray-600 ojt-mx-auto">There is no plugin Available for now</p>
    </div>
  </template>
  <template x-if="isLoading">
    <div class="loading-menu ojt-flex ojt-items-center ojt-h-80">
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
  <template x-if="plugins && !isLoading">
    <section class="ojt-flex ojt-flex-row ojt-flex-wrap ojt-mx-auto">
      <!-- Card Component -->
      <template x-for="(plugin, index) in plugins" :key="index">
        <div class="ojt-transition-all ojt-duration-150 ojt-flex ojt-w-full ojt-p-4 lg:ojt-w-1/2 xl:ojt-w-1/3">
          <div
            class="ojt-flex ojt-flex-col ojt-items-stretch ojt-transition-all ojt-duration-150 ojt-bg-white ojt-rounded-lg ojt-shadow-lg hover:ojt-shadow-2xl ojt-w-full dark:ojt-bg-dark dark:ojt-text-light">
            <div class="md:ojt-flex-shrink-0 md:ojt-min-h-48">
              <img :src="plugin?.placeholder ?? placeholderImg" :class="{ 'ojt-object-contain' : plugin.placeholder }"
                :alt="plugin.name" class="ojt-w-full ojt-rounded-lg ojt-rounded-b-none md:ojt-h-56" loading="lazy" />
            </div>
            <div class="ojt-flex ojt-flex-wrap ojt-p-4 ojt-x-auto">
              <a href="#" @click="$dispatch('modal-plugin', plugin)"
                class="ojt-text-2xl ojt-font-bold ojt-tracking-normal ojt-text-gray-800 hover:ojt-text-blue-800 dark:ojt-text-light"
                x-text="plugin.name"></a>
            </div>
            <hr class="ojt-border-gray-300" />
            <p class="ojt-flex ojt-flex-row ojt-flex-wrap ojt-flex-grow ojt-w-full ojt-px-4 ojt-py-2 ojt-overflow-hidden ojt-text-sm ojt-text-gray-700 dark:ojt-text-light"
              x-text="plugin.description.trimEllip(300)"></p>
            <hr class="ojt-border-gray-300" />
            <section class="ojt-p-4">
              <div class="ojt-flex ojt-items-center ojt-justify-between">
                <template x-if="plugin.shop">
                  <a :href="plugin.shop" target="_blank"
                    class="ojt-inline-block ojt-p-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-uppercase ojt-transition ojt-bg-blue-700 ojt-rounded ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-blue-800 focus:ojt-outline-none ojt-waves-effect">
                    <i class="fas fa-shopping-cart"></i>
                    Buy Now
                  </a>
                </template>
                <button @click="$dispatch('modal-plugin', plugin)"
                  class="ojt-ml-auto ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-uppercase ojt-transition ojt-bg-blue-700 ojt-rounded ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-blue-800 focus:ojt-outline-none ojt-waves-effect">
                  <i class="fas fa-info-circle"></i> Details
                </button>
              </div>
            </section>
          </div>
        </div>
      </template>

    </section>
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
      <div class="ojt-relative ojt-bg-white ojt-shadow-lg ojt-rounded-md ojt-text-gray-900 ojt-z-20 dark:ojt-bg-dark dark:ojt-text-light"
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
                <div class="ojt-py-4">
                  Doesn't have license key ?
                  <a :href="plugin.shop" target="_blank" class="ojt-text-blue-600 hover:ojt-text-blue-900"> Click here
                  </a>
                  to buy.
                </div>
              </div>
              <div class="">
                <table class="ojt-border-collapse ojt-w-full">
                  <tbody>
                    <tr x-show="!plugin.installed">
                      <td class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-blockojt-relative">
                        <input x-model="key"
                          class="ojt-tracking-wide ojt-py-2 ojt-px-4 ojt-leading-relaxed ojt-appearance-none ojt-block ojt-w-full ojt-bg-gray-200 ojt-border ojt-border-gray-200 ojt-rounded focus:ojt-outline-none focus:ojt-bg-white focus:ojt-border-gray-500"
                          id="key" placeholder="License Key" type="text">
                      </td>
                    </tr>
                    <tr x-show="!plugin.installed">
                      <td class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-blockojt-relative">
                        <button x-text="loading ? 'Installing ...' : 'Install'" :disabled="loading"
                          @click="installPlugin(plugin)"
                          class="disabled:ojt-opacity-50 ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-w-full ojt-uppercase ojt-transition ojt-bg-blue-700 ojt-rounded ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-blue-800 focus:ojt-outline-none ojt-waves-effect">
                        </button>
                      </td>
                    </tr>
                    <tr x-show="plugin.installed && plugin?.update">
                      <td class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-blockojt-relative">
                        <button x-text="loading ? 'Updating ...' : 'Update'" :disabled="loading"
                          @click="installPlugin(plugin, true)"
                          class="disabled:ojt-opacity-50 ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-w-full ojt-uppercase ojt-transition ojt-bg-blue-700 ojt-rounded ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-blue-800 focus:ojt-outline-none ojt-waves-effect">
                        </button>
                      </td>
                    </tr>
                    <tr x-show="plugin.installed && !plugin?.update">
                      <td class="ojt-w-full ojt-p-3 ojt-border ojt-border-b ojt-blockojt-relative ojt-text-green-700">
                        <small class="ojt-font-bold">Plugin is up-to-date.</small>
                      </td>
                    </tr>
                    <tr x-show="plugin.installed">
                      <td class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-blockojt-relative">
                        <label class="ojt-inline-flex ojt-items-center">
                          <input type="checkbox" x-model="resetSetting"
                            class="ojt-form-checkbox ojt-h-4 ojt-w-4 ojt-text-red-600" checked><span
                            class="ojt-ml-2 ojt-text-gray-700 ojt-text-sm">Reset Setting</span>
                        </label>
                      </td>
                    </tr>
                    <tr x-show="plugin.installed">
                      <td class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-blockojt-relative">
                        <button x-text="loading ? 'Uninstalling ...' : 'Uninstall'" :disabled="loading"
                          @click="uninstall(plugin)"
                          class="disabled:ojt-opacity-50 ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-w-full ojt-uppercase ojt-transition ojt-bg-red-700 ojt-rounded ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-red-800 focus:ojt-outline-none ojt-waves-effect">

                        </button>
                      </td>
                    </tr>
                    <tr>
                      <td class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-blockojt-relative dark:ojt-text-light">
                        <span x-text="'Version : ' + plugin.version" class="ojt-text-sm"></span>
                      </td>
                    </tr>
                    <tr>
                      <td class="ojt-w-full ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-blockojt-relative">
                        <a :href="plugin.changelog" target="_blank" class="ojt-text-blue-600 hover:ojt-text-blue-900">
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

<script>
  function pluginGallery() {
    return {
      isLoading: true,
      plugins: [],
      async fetchPlugins() {
        this.isLoading = true;
        let res = await fetch(api + 'list/31', {
          mode: 'cors',
        }).catch(function(error) {
          this.isLoading = false;
          ajaxError(error);
        });

        if (res.status != 200) {
          ajaxError();
          return;
        }

        var plugins = await res.json();
        plugins = Promise.all(plugins.map(async (plugin) => {
          let formData = new FormData();

          formData.append('pluginFolder', plugin.folder);
          formData.append('pluginVersion', plugin.version);

          let response = await fetch(currentUrl + 'checkCurrentPlugin', {
            method: 'POST',
            body: formData
          });

          let data = await response.json();
          return { ...plugin, installed: data.installed, update: data.update }
        }))

        this.plugins = await plugins;
        this.isLoading = false;
      },
    }
  }

  function modalPlugin() {
    return {
      show: false,
      plugin: null,
      resetSetting: false,
      loading: false,
      key: '',
      close() {
        this.show = false;
        this.key = '';
        this.loading = false;
        this.resetSetting = false;
      },
      showPlugin(plugin) {
        this.plugin = plugin;
        this.show = true;
      },
      async installPlugin(plugin, update = false) {
        if (this.key == '' && !update) {
          Toast.fire({
            icon: 'info',
            title: 'Please insert license Key ..'
          })
          return;
        }

        if (this.loading == true) {
          Toast.fire({
            icon: 'info',
            title: 'Please Wait, still Processing ...'
          })
          return;
        }

        this.loading = true;

        const formData = new FormData();
        formData.append('plugin', JSON.stringify(plugin));
        formData.append('license', this.key);
        if (update) {
          formData.append('update', true);
        }

        let response = await fetch(currentUrl + 'installPlugin', {
          method: 'POST',
          body: formData
        }).catch(function(error) {
          this.loading = false;
          ajaxError(error);
          return;
        });

        let data = await response.json();
        if (data.error == 1) {
          this.loading = false;
          console.log(data);
          ajaxError(data);
          return;
        }

        ajaxResponse(data);

        alpineComponent('pluginGallery').plugins = alpineComponent('pluginGallery').plugins.map((plug) => plug
          .token === plugin.token ? { ...plug, installed: true } : plug)

        this.close();

        alpineComponent('pluginInstalled').fetchInstalledPlugin();
        alpineComponent('ojt-setting').tab = 1;
      },
      async uninstall(plugin) {
        if (this.loading == true) {
          Toast.fire({
            icon: 'info',
            title: 'Please Wait, still Processing ...'
          })
          return;
        }

        this.loading = true;

        const formData = new FormData();
        formData.append('plugin', JSON.stringify(plugin));
        formData.append('resetSetting', this.resetSetting);

        let response = await fetch(currentUrl + 'uninstallPlugin', {
          method: 'POST',
          body: formData
        }).catch(function(error) {
          this.loading = false;
          ajaxError(error);
        });

        let data = await response.json();

        ajaxResponse(data);

        alpineComponent('pluginGallery').plugins = alpineComponent('pluginGallery').plugins.map((plug) => plug
          .token === plugin.token ? { ...plug, installed: false } : plug)

        this.close();

        alpineComponent('pluginInstalled').fetchInstalledPlugin();
        alpineComponent('ojt-setting').tab = 1;
      }
    }
  }
</script>