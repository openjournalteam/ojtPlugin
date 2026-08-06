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
            placeholder="Search ...." x-model.debounce="$store.plugins.search">
        </div>
        <select
          class="ojt-w-full ojt-max-w-[10rem] ojt-bg-gray-50 ojt-border ojt-border-gray-300 ojt-text-gray-900 ojt-text-sm ojt-rounded-lg focus:ojt-ring-primary-500 focus:ojt-border-primary-500 ojt-block ojt-py-2 ojt-px-4"
          x-model="$store.plugins.type">
          <option value="all" selected>All</option>
          <option value="plugins">Plugins</option>
          <option value="themes">Themes</option>
        </select>
        <button @click="$store.plugins.resetFilter()"
          class="ojt-inline-block ojt-px-4 ojt-py-2 ojt-text-sm ojt-font-medium ojt-text-center ojt-text-white ojt-transition ojt-bg-danger-700 ojt-rounded-lg ojt-shadow ojt-ripple hover:ojt-shadow-lg hover:ojt-bg-danger-800 focus:ojt-outline-none ojt-waves-effect">
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
        <!-- Modal toggle -->

      </div>

      <div class="ojt-overflow-x-auto ojt-relative sm:ojt-rounded-lg">
        <table class="ojt-w-full ojt-text-left ojt-text-gray-500 ojt-border sm:ojt-rounded-lg">
          <thead class="ojt-text-xs ojt-text-gray-700 ojt-bg-gray-100">
            <tr class='sm:ojt-rounded-t-lg'>
              <th scope="col" class="ojt-py-3 ojt-px-6">
                #
              </th>
              <th scope="col" class="ojt-py-3 ojt-px-6">
                Plugin
              </th>
              <th scope="col" class="ojt-py-3 ojt-px-6 lg:ojt-w-[35%]">
                Description
              </th>
              <th scope="col" class="ojt-py-3 ojt-px-6">
                Version
              </th>
              <th scope="col" class="ojt-py-3 ojt-px-6">
                Enabled
              </th>
              {if $canDeletePlugins}
                <th scope="col" class="ojt-py-3 ojt-px-6">
                  Action
                </th>
              {/if}
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
                  <div class="ojt-flex ojt-items-center">
                    {* <svg @click="plugin.open = !plugin.open"
                      class="ojt-w-6 ojt-h-6 ojt-shrink-0 ojt-mr-2 ojt-cursor-pointer" :class="{ 'ojt-rotate-180' :
                      plugin.open }" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                      <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"></path>
                    </svg> *}
                    <div>
                      <template x-if="!plugin.enabled || (plugin.enabled && !plugin?.page)">
                        <span x-text="plugin.name" :class="{ 'ojt-font-bold' : plugin.enabled }"></span>
                      </template>
                      <template x-if="plugin.enabled && plugin?.page">
                        <a href="#" @click="alpineComponent('utama').toggleMainMenu(plugin.page, 'Plugin')"
                          x-text="plugin.name" :page="plugin.page"
                          class="menu_item ojt-font-bold ojt-text-primary-600 hover:ojt-text-primary-800"></a>
                      </template>
                    </div>
                  </div>
                  <div class="ojt-mt-2 ojt-flex ojt-items-center ojt-gap-4">
                    <a :href="plugin.documentation" x-show="plugin.documentation" target="_blank" class="ojt-text-blue-600 ojt-text-sm hover:ojt-underline ojt-flex ojt-flex-row ojt-gap-1">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" height="15" width="15" viewBox="0 0 384 512"><path d="M64 48l112 0 0 88c0 39.8 32.2 72 72 72l88 0 0 240c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16L48 64c0-8.8 7.2-16 16-16zM224 67.9l92.1 92.1-68.1 0c-13.3 0-24-10.7-24-24l0-68.1zM64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-261.5c0-17-6.7-33.3-18.7-45.3L242.7 18.7C230.7 6.7 214.5 0 197.5 0L64 0zm56 256c-13.3 0-24 10.7-24 24s10.7 24 24 24l144 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-144 0zm0 96c-13.3 0-24 10.7-24 24s10.7 24 24 24l144 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-144 0z"/></svg>
                      Documentation
                    </a>
                    {* <button type="button" @click="$store.plugins.uninstall(plugin)"
                      class="ojt-py-2 ojt-px-3 ojt-text-xs ojt-font-medium ojt-text-center ojt-text-white ojt-bg-danger-700 ojt-rounded-lg hover:ojt-bg-danger-800 focus:ojt-ring-4 focus:ojt-outline-none focus:ojt-ring-danger-300">Delete</button> *}
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
                    <div class="">
                      <input type="checkbox" x-model="plugin.enabled" x-on:click.prevent class="cbx ojt-hidden"
                        style="display: none;" />
                      <label for="cbx" class="toggle" @click="$store.plugins.togglePlugin(plugin);"
                        :id="'toggleplugin' + plugin.product" :class="{ldelim} 'ojt-cursor-not-allowed ojt-opacity-30' : !plugin.isAuthorized {rdelim}">
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
                {if $canDeletePlugins}
                  <td class="ojt-py-4 ojt-px-6">
                    <template x-if="plugin.canDelete">
                      <a title="Uninstall Plugin" href="#" @click.prevent="$store.plugins.uninstall(plugin)"
                        class="ojt-text-danger-500 hover:ojt-text-danger-800 ojt-text-sm hover:ojt-underline"
                        :id="'toggleplugin' + plugin.product">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="ojt-icon" viewBox="0 0 640 640"><path d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z"/></svg>
                        {* Delete *}
                      </a>
                    </template>
                  </td>
                {/if}
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
