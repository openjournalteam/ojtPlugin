<div id="pluginInstalled" x-data="pluginInstalled()" x-init="init()" class="ojt-p-4">
  <div x-show="$store.plugins.isLoading">
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
  </div>
  <div x-show="!$store.plugins.isLoading">
    <div x-show="$store.plugins.data.length" id="selected-articles">
      <div class="ojt-flex ojt-items-center ojt-m-2 ojt-gap-2">
        <select
          class="ojt-w-full ojt-max-w-[10rem] ojt-bg-gray-50 ojt-border ojt-border-gray-300 ojt-text-gray-900 ojt-text-sm ojt-rounded-lg focus:ojt-ring-primary-500 focus:ojt-border-primary-500 ojt-block ojt-w-full ojt-py-2 ojt-px-4"
          x-model="$store.plugins.type">
          <option value="all" selected>All</option>
          <option value="plugins">Plugins</option>
          <option value="themes">Themes</option>
        </select>

        <input type="text" id="default-input"
          class="ojt-bg-gray-50 ojt-border ojt-border-gray-300 ojt-text-gray-900 ojt-text-sm ojt-rounded-lg focus:ojt-ring-primary-500 focus:ojt-border-primary-500 ojt-block ojt-w-full ojt-max-w-sm ojt-py-2 ojt-px-4"
          placeholder="Search ..." x-model.debounce="$store.plugins.search">
        {* <div class="ojt-relative ojt-group ojt-grow">
          <span
            class="ojt-absolute ojt-inset-y-0 ojt-left-0 ojt-flex ojt-items-center ojt-justify-center ojt-w-9 ojt-h-9 ojt-text-gray-400 ojt-pointer-events-none group-focus-within:ojt-text-primary-500">
            <svg class="ojt-w-5 ojt-h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
              stroke-width="2" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z">
              </path>
            </svg> </span>

          <input id="tableSearchInput" placeholder="Search" type="search" autocomplete="off"
            class="ojt-block ojt-w-full ojt-max-w-sm ojt-h-9 ojt-pl-9 ojt-placeholder-gray-400 ojt-transition ojt-duration-75 ojt-border-gray-300 ojt-rounded-lg ojt-shadow-sm focus:ojt-border-primary-500 focus:ojt-ring-1 focus:ojt-ring-inset focus:ojt-ring-primary-500"
            x-model.debounce="$store.plugins.search">
        </div> *}
      </div>
      <table class="ojt-border-collapse ojt-w-full">
        <thead>
          <tr>
            <th
              class="ojt-p-3 ojt-font-bold ojt-uppercase ojt-bg-gray-200 ojt-text-gray-600 ojt-border ojt-border-gray-300 ojt-hidden lg:ojt-table-cell dark:ojt-bg-dark dark:ojt-text-light">
              #</th>
            <th
              class="ojt-p-3 ojt-font-bold ojt-uppercase ojt-bg-gray-200 ojt-text-gray-600 ojt-border ojt-border-gray-300 ojt-hidden lg:ojt-table-cell ojt-text-left dark:ojt-bg-dark dark:ojt-text-light">
              Plugin</th>
            {* <th
                class="ojt-p-3 ojt-font-bold ojt-uppercase ojt-bg-gray-200 ojt-text-gray-600 ojt-border ojt-border-gray-300 ojt-hidden lg:ojt-table-cell ojt-text-left dark:ojt-bg-dark dark:ojt-text-light">
                Description</th> *}
            <th
              class="ojt-p-3 ojt-font-bold ojt-uppercase ojt-bg-gray-200 ojt-text-gray-600 ojt-border ojt-border-gray-300 ojt-hidden lg:ojt-table-cell ojt-text-left dark:ojt-bg-dark dark:ojt-text-light">
              Version</th>
            <th
              class="ojt-p-3 ojt-font-bold ojt-uppercase ojt-bg-gray-200 ojt-text-gray-600 ojt-border ojt-border-gray-300 ojt-hidden lg:ojt-table-cell dark:ojt-bg-dark dark:ojt-text-light">
              Actions</th>
          </tr>
        </thead>
        <tbody x-ref="tablepluginlist">
          <template x-for="(plugin, index) in $store.plugins.listPlugins" :key="plugin.className">
            <tr
              class="ojt-bg-white lg:hover:ojt-bg-gray-100 ojt-flex lg:ojt-table-row ojt-flex-row lg:ojt-flex-row ojt-flex-wrap lg:ojt-flex-no-wrap">
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
                  class="lg:ojt-hidden  ojt-mb-2 ojt-bg-primary-200 ojt-px-2 ojt-py-1 ojt-text-xs ojt-font-bold ojt-uppercase">
                  Plugin</p>
                <template x-if="!plugin.enabled || (plugin.enabled && !plugin?.page)">
                  <span x-text="plugin.name" class="ojt-font-bold"></span>
                </template>
                <template x-if="plugin.enabled && plugin?.page">
                  <a href="#"
                    @click="alpineComponent('utama').menu = 'Plugin'; alpineComponent('pluginMenu').page = plugin.name"
                    x-text="plugin.name" :page="plugin.page"
                    class="menu_item ojt-font-bold ojt-text-primary-600 hover:ojt-text-primary-800"></a>
                </template>
                <p x-text="plugin.description"></p>
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
                {* <div class="ojt-flex ojt-place-items-center">
                  <div class="lg:ojt-mx-auto">
                    <input type="checkbox" x-model="plugin.enabled" x-on:click.prevent class="cbx ojt-hidden"
                      style="display: none;" />
                    <label for="cbx" class="toggle" @click="$store.plugins.togglePlugin(plugin);"
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
                </div> *}

              </td>
            </tr>
          </template>
        </tbody>
      </table>

    </div>
    <div x-show="!$store.plugins.data.length" class="empty ojt-flex ojt-flex-col">
      <div class="empty-icon ojt-my-2 ojt-text-gray-600 ojt-mx-auto"><i class="fas fa-box-open fa-5x"></i></div>
      <p class="empty-title ojt-my-2 ojt-text-gray-600 ojt-mx-auto">You have no installed plugin</p>
    </div>
  </div>
</div>