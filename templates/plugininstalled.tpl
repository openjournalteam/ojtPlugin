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
      <div class="ojt-flex ojt-items-center ojt-my-2 ojt-gap-2">
        <select
          class="ojt-w-full ojt-max-w-[10rem] ojt-bg-gray-50 ojt-border ojt-border-gray-300 ojt-text-gray-900 ojt-text-sm ojt-rounded-lg focus:ojt-ring-primary-500 focus:ojt-border-primary-500 ojt-block ojt-w-full ojt-py-2 ojt-px-4"
          x-model="$store.plugins.type">
          <option value="all" selected>All</option>
          <option value="plugins">Plugins</option>
          <option value="themes">Themes</option>
        </select>

        <input type="search" id="default-input"
          class="ojt-bg-gray-50 ojt-border ojt-border-gray-300 ojt-text-gray-900 ojt-text-sm ojt-rounded-lg focus:ojt-ring-primary-500 focus:ojt-border-primary-500 ojt-block ojt-w-full ojt-max-w-sm ojt-py-2 ojt-px-4"
          placeholder="Search ..." x-model.debounce="$store.plugins.search">
        <button @click="$store.plugins.resetFilter()"
          class="ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-sm ojt-font-medium ojt-text-center ojt-text-white ojt-transition ojt-bg-red-700 ojt-rounded-lg ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-red-800 focus:ojt-outline-none ojt-waves-effect">
          Reset
        </button>
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

      <div class="ojt-overflow-x-auto ojt-relative sm:ojt-rounded-lg">
        <table class="ojt-w-full ojt-text-left ojt-text-gray-500 ojt-border sm:ojt-rounded-lg">
          <thead class="ojt-text-xs ojt-text-gray-700 ojt-uppercase ojt-bg-gray-100">
            <tr class='sm:ojt-rounded-t-lg'>
              <th scope="col" class="ojt-py-3 ojt-px-6">
                #
              </th>
              <th scope="col" class="ojt-py-3 ojt-px-6">
                Plugin
              </th>
              <th scope="col" class="ojt-py-3 ojt-px-6">
                description
              </th>
              <th scope="col" class="ojt-py-3 ojt-px-6">
                Version
              </th>
              <th scope="col" class="ojt-py-3 ojt-px-6">
                Enabled
              </th>
            </tr>
          </thead>
          <tbody x-ref="tablepluginlist">
            <template x-for="(plugin, index) in $store.plugins.listPlugins" :key="plugin.className">
              <tr class="ojt-bg-white ojt-border-b ojt-align-top">
                <th scope="row"
                  class="ojt-py-4 ojt-px-6 ojt-font-medium ojt-text-gray-900 ojt-whitespace-nowrap ojt-w-[25px]">
                  <span x-text="index+1"></span>
                </th>
                <td class="ojt-py-4 ojt-px-6 ojt-max-w-[50%]">
                  <div class="ojt-flex">
                    <svg @click="plugin.open = !plugin.open"
                      class="ojt-w-6 ojt-h-6 ojt-shrink-0 ojt-mr-2 ojt-cursor-pointer" :class="{ 'ojt-rotate-180' :
                      plugin.open }" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                      <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"></path>
                    </svg>
                    <div>
                      <template x-if="!plugin.enabled || (plugin.enabled && !plugin?.page)">
                        <span x-text="plugin.name" :class="{ 'ojt-font-bold' : plugin.enabled }"></span>
                      </template>
                      <template x-if="plugin.enabled && plugin?.page">
                        <a href="#"
                          @click="alpineComponent('utama').menu = 'Plugin'; alpineComponent('pluginMenu').page = plugin.name"
                          x-text="plugin.name" :page="plugin.page"
                          class="menu_item ojt-font-bold ojt-text-primary-600 hover:ojt-text-primary-800"></a>
                      </template>
                    </div>
                  </div>
                  <div x-show.transition="plugin.open" class="ojt-mt-4 ojt-flex ojt-items-center ojt-gap-2">
                    <a href="#" @click.prevent="$store.plugins.uninstall(plugin)"
                      class="ojt-text-red-600 ojt-text-sm ojt-font-bold hover:ojt-underline">Delete</a>
                    {* <button type="button" @click="$store.plugins.uninstall(plugin)"
                      class="ojt-py-2 ojt-px-3 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-bg-red-700 ojt-rounded-lg hover:ojt-bg-red-800 focus:ojt-ring-4 focus:ojt-outline-none focus:ojt-ring-red-300">Delete</button> *}
                  </div>
                </td>
                <td class="ojt-py-4 ojt-px-6">
                  <p x-text="plugin.description"></p>
                </td>
                <td class="ojt-py-4 ojt-px-6">
                  <span x-text="plugin.version"></span>
                </td>
                <td class="ojt-py-4 ojt-px-6">
                  <div class="ojt-flex ojt-place-items-center">
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
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>


    </div>
    <div x-show="!$store.plugins.data.length" class="empty ojt-flex ojt-flex-col">
      <div class="empty-icon ojt-my-2 ojt-text-gray-600 ojt-mx-auto"><i class="fas fa-box-open fa-5x"></i></div>
      <p class="empty-title ojt-my-2 ojt-text-gray-600 ojt-mx-auto">You have no installed plugin</p>
    </div>
  </div>
</div>