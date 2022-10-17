<div x-data="pluginInstalled()" id="pluginInstalled" x-init="fetchInstalledPlugin();">
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
    <div class="ojt-mt-4">
      <div x-show.transition="plugins.length > 0" id="selected-articles">
        <table class="ojt-border-collapse ojt-w-full">
          <thead>
            <tr>
              <th
                class="ojt-p-3 ojt-font-bold ojt-uppercase ojt-bg-gray-200 ojt-text-gray-600 ojt-border ojt-border-gray-300 ojt-hidden lg:ojt-table-cell dark:ojt-bg-dark dark:ojt-text-light">
                #</th>
              <th
                class="ojt-p-3 ojt-font-bold ojt-uppercase ojt-bg-gray-200 ojt-text-gray-600 ojt-border ojt-border-gray-300 ojt-hidden lg:ojt-table-cell ojt-text-left dark:ojt-bg-dark dark:ojt-text-light">
                Plugin</th>
              <th
                class="ojt-p-3 ojt-font-bold ojt-uppercase ojt-bg-gray-200 ojt-text-gray-600 ojt-border ojt-border-gray-300 ojt-hidden lg:ojt-table-cell ojt-text-left dark:ojt-bg-dark dark:ojt-text-light">
                Description</th>
              <th
                class="ojt-p-3 ojt-font-bold ojt-uppercase ojt-bg-gray-200 ojt-text-gray-600 ojt-border ojt-border-gray-300 ojt-hidden lg:ojt-table-cell ojt-text-left dark:ojt-bg-dark dark:ojt-text-light">
                Version</th>
              <th
                class="ojt-p-3 ojt-font-bold ojt-uppercase ojt-bg-gray-200 ojt-text-gray-600 ojt-border ojt-border-gray-300 ojt-hidden lg:ojt-table-cell dark:ojt-bg-dark dark:ojt-text-light">
                Enabled</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="(plugin, index) in plugins" :key="index">
              <tr
                class="ojt-bg-white lg:hover:ojt-bg-gray-100 ojt-flex lg:ojt-table-row ojt-flex-row lg:ojt-flex-row ojt-flex-wrap lg:ojt-flex-no-wrap ojt-mb-10 lg:ojt-mb-0">
                <td
                  class="ojt-w-full lg:ojt-w-1/12 ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block lg:ojt-table-cell ojt-relative lg:ojt-static lg:ojt-text-center dark:ojt-bg-dark dark:ojt-text-light">
                  <p
                    class="lg:ojt-hidden ojt-mb-2 ojt-bg-primary-200 ojt-px-2 ojt-py-1 ojt-text-xs ojt-font-bold ojt-uppercase">
                    #</p>
                  <span x-text="index+1"></span>
                </td>
                <td
                  class="ojt-w-full lg:ojt-w-auto ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block lg:ojt-table-cell ojt-relative lg:ojt-static ojt-min-w-[12rem] dark:ojt-bg-dark dark:ojt-text-light">
                  <p
                    class="lg:ojt-hidden ojt-mb-2 ojt-bg-primary-200 ojt-px-2 ojt-py-1 ojt-text-xs ojt-font-bold ojt-uppercase">
                    Plugin</p>
                  <template x-if="!plugin.enabled || (plugin.enabled && !plugin?.page)">
                    <span x-text="plugin.name"></span>
                  </template>
                  <template x-if="plugin.enabled && plugin?.page">
                    <a href="#"
                      @click="alpineComponent('utama').menu = 'Plugin'; alpineComponent('pluginMenu').page = plugin.name"
                      x-text="plugin.name" :page="plugin.page"
                      class="menu_item ojt-text-primary-600 hover:ojt-text-primary-800"></a>
                  </template>
                </td>
                <td
                  class="ojt-w-full lg:ojt-w-auto ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block lg:ojt-table-cell ojt-relative lg:ojt-static dark:ojt-bg-dark dark:ojt-text-light">
                  <p
                    class="lg:ojt-hidden ojt-mb-2 ojt-bg-primary-200 ojt-px-2 ojt-py-1 ojt-text-xs ojt-font-bold ojt-uppercase">
                    Description</p>
                  <span x-text="plugin.description"></span>
                </td>
                <td
                  class="ojt-w-full lg:ojt-w-auto ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block lg:ojt-table-cell ojt-relative lg:ojt-static dark:ojt-bg-dark dark:ojt-text-light">
                  <p
                    class="lg:ojt-hidden ojt-mb-2 ojt-bg-primary-200 ojt-px-2 ojt-py-1 ojt-text-xs ojt-font-bold ojt-uppercase">
                    Version</p>
                  <span x-text="plugin.version"></span>
                </td>
                <td
                  class="ojt-w-full lg:ojt-w-1/5 ojt-p-3 ojt-text-gray-800 ojt-border ojt-border-b ojt-block lg:ojt-table-cell ojt-relative lg:ojt-static lg:ojt-text-center dark:ojt-bg-dark dark:ojt-text-light">
                  <p
                    class="lg:ojt-hidden ojt-mb-2 ojt-bg-primary-200 ojt-px-2 ojt-py-1 ojt-text-xs ojt-font-bold ojt-uppercase">
                    Enabled</p>
                  <div class="ojt-flex ojt-place-items-center">
                    <div class="lg:ojt-mx-auto">
                      <input type="checkbox" x-model="plugin.enabled" x-on:click.prevent class="cbx ojt-hidden"
                        style="display: none;" />
                      <label for="cbx" class="toggle" @click="togglePlugin(plugin);"
                        :id="'toggleplugin' + plugin.product">
                        <span>
                          <svg width="10px" height="10px" viewBox="0 0 10 10">
                            <path
                              d="M5,1 L5,1 C2.790861,1 1,2.790861 1,5 L1,5 C1,7.209139 2.790861,9 5,9 L5,9 C7.209139,9 9,7.209139 9,5 L9,5 C9,2.790861 7.209139,1 5,1 L5,9 L5,1 Z">
                            </path>
                          </svg>
                        </span>
                      </label>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <div x-show="plugins.length < 1" class="empty ojt-flex ojt-flex-col">
        <div class="empty-icon ojt-my-2 ojt-text-gray-600 ojt-mx-auto"><i class="fas fa-box-open fa-5x"></i></div>
        <p class="empty-title ojt-my-2 ojt-text-gray-600 ojt-mx-auto">You have no installed plugin</p>
      </div>
    </div>
  </template>
</div>