{assign var="logoImage" value=$currentContext->getLocalizedData('pageHeaderLogoImage')}
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OJT Control Panel</title>
  <link rel="shortcut icon" type="image/jpg" href="{$ojtPlugin->favIcon}" />
  <link rel="preconnect" href="https://fonts.gstatic.com">
  <link href="https://fonts.googleapis.com/css2?family=Allura&display=swap" rel="stylesheet">
  <link href="{$ojtPlugin->tailwindCss}" rel="stylesheet">
  <link href="{$ojtPlugin->fontAwesomeCss}" rel="stylesheet">
  <link href="{$ojtPlugin->sweetAlertCss}" rel="stylesheet">
  <style>
    [x-cloak] {
      display: none;
    }
  </style>
</head>
<div id="moduleCss"></div>

<body id="ojt-plugin">
  <div x-data="utama()" id="utama" x-init="init()" x-cloak>
    <div class="ojt-flex ojt-h-screen ojt-antialiased ojt-text-gray-900 ojt-bg-gray-100">
      <aside class="ojt-flex-shrink-0 ojt-hidden ojt-w-72 ojt-bg-white ojt-border-r md:ojt-block">
        <div class="ojt-flex ojt-flex-col ojt-h-full">
          <div class="ojt-py-6">
            <a href="https://openjournaltheme.com/" target="_blank"
              class="custom-logo-link ojt-flex ojt-flex-col ojt-items-center ojt-gap-2" rel="home" aria-current="page">
              <img width="32" height="37" src="{$ojtPlugin->logo}" class="ojt-mx-auto">
              <span class="site-name ojt-ml-3 ojt-inline-block ojt-text-2xl ojt-tracking-wide">
                OJT Plugin
              </span>
            </a>
          </div>
          <nav aria-label="Main"
            class="ojt-flex-1 ojt-space-y-2 ojt-px-2 ojt-overflow-y-hidden hover:ojt-overflow-y-auto">
            <button @click="$store.plugins.page = 'dashboard'; menu = 'Dashboard'"
              :class="{ 'ojt-bg-gradient-to-l ojt-text-white ojt-shadow-lg ojt-shadow-primary-500/50' : $store.plugins.page == 'dashboard' }"
              role="button"
              class="ojt-w-full ojt-flex ojt-transition ojt-items-center ojt-px-4 ojt-py-2 ojt-rounded-xl ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br ojt-text-gray-500 hover:ojt-text-white ojt-gap-4">
              <span aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                  stroke="currentColor" class="ojt-w-5 ojt-h-5">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
              </span>
              <span> Dashboard </span>
            </button>
            {* <span class="ojt-uppercase ojt-tracking-widest ojt-text-gray-400 ojt-px-4 ojt-text-xs">PLUGINS</span> *}
            <div x-show="open" class="ojt-gap-2 ojt-flex ojt-flex-col" role="menu" id="plugin-menu-list">
              <template x-for="(plugin, index) in $store.plugins.activePlugins" :key="plugin.className">
                <button x-show="plugin.enabled && plugin?.page" role="button"
                  @click="menu = 'Plugin'; $store.plugins.page  = plugin.name" role="menuitem" :class="{ 
                              'ojt-bg-gradient-to-l ojt-text-white ojt-shadow-lg ojt-shadow-primary-500/50': $store.plugins.page == plugin.name,
                              }" :page="plugin.page"
                  class="ojt-w-full ojt-flex ojt-text-gray-500 ojt-items-center ojt-px-4 ojt-py-2 ojt-rounded-xl ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br hover:ojt-text-white ojt-gap-4 menu_item ojt-text-left">
                  <span aria-hidden="true" class="ojt-min-w-[1.25rem]" x-html="plugin.icon">
                  </span>
                  <span x-text="plugin.name"></span>
                </button>
              </template>
            </div>
          </nav>

          <div class="ojt-p-4 ojt-mx-auto ojt-w-full ojt-gap-2 ojt-flex ojt-flex-col">
            <a href="https://openjournaltheme.com/docs/ojs/ojs-tutorial" target="_blank"
              class="ojt-w-full ojt-flex ojt-text-gray-500 ojt-items-center ojt-px-4 ojt-py-2 ojt-rounded-xl ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br hover:ojt-text-white ojt-gap-4 ojt-text-left">
              <svg class="ojt-icon" width="48" height="48" viewBox="0 0 48 48" fill="currentColor"
                xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd"
                  d="M6 6H37V11H35V8H8V31H29.3871V33H6V6ZM36 19C37.6569 19 39 17.6569 39 16C39 14.3431 37.6569 13 36 13C34.3431 13 33 14.3431 33 16C33 17.6569 34.3431 19 36 19ZM38.0314 21.0107C39.3299 21.0107 40.3582 21.5937 41.0303 22.4959C41.6595 23.3405 41.9258 24.3864 41.9861 25.3507C42.0472 26.3298 41.9056 27.356 41.5894 28.2701C41.2881 29.1407 40.7815 30.0406 40 30.6579V40.5C40 41.2805 39.4015 41.9305 38.6237 41.9949C37.8459 42.0593 37.1487 41.5164 37.0204 40.7466L35.7293 33H35.4324L33.9743 40.7764C33.8316 41.5378 33.1325 42.0653 32.3612 41.9936C31.5899 41.9219 31 41.2746 31 40.5V26.2328C30.8874 26.4044 30.7831 26.5652 30.6911 26.7082C30.5726 26.8922 30.4752 27.0453 30.4078 27.152L30.3303 27.275L30.3105 27.3066L30.3047 27.316C30.0308 27.7555 29.5493 28.023 29.0314 28.023H24.0314C23.203 28.023 22.5314 27.3515 22.5314 26.523C22.5314 25.6946 23.203 25.023 24.0314 25.023H28.208C28.4506 24.6472 28.7705 24.1594 29.1067 23.6689C29.457 23.1577 29.8432 22.6169 30.1862 22.1935C30.3539 21.9864 30.5413 21.7699 30.7292 21.5924C30.8212 21.5056 30.9497 21.3932 31.105 21.2935C31.2158 21.2224 31.5575 21.0107 32.0314 21.0107H38.0314Z">
                </path>
              </svg>
              <span>OJS Tutorial</span>
            </a>
            <a href="https://openjournaltheme.com/about-open-journal-theme" target="_blank"
              class="focus:ojt-outline-none ojt-text-white ojt-text-sm ojt-justify-center ojt-py-2.5 ojt-px-5 ojt-rounded-xl  ojt-flex ojt-items-center ojt-bg-gradient-to-l ojt-text-white ojt-shadow-lg ojt-shadow-primary-500/50 ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br ojt-gap-4">
              <img src="{$ojtPlugin->logo}" class="ojt-h-5">
              <span>ABOUT US</span>
            </a>
          </div>
        </div>
      </aside>

      <div class="ojt-flex ojt-flex-col ojt-flex-1 ojt-min-h-screen ojt-overflow-x-hidden ojt-overflow-y-auto">
        <header class="ojt-sticky ojt-top-0 ojt-bg-white ojt-z-10">
          <div class="ojt-flex ojt-items-center ojt-justify-between ojt-p-2 ojt-border-b ">
            <button @click="isMobileMainMenuOpen = !isMobileMainMenuOpen"
              class="ojt-p-1 ojt-text-primary-400 ojt-transition-colors ojt-duration-200 ojt-rounded-md ojt-bg-primary-50 hover:ojt-text-primary-600 hover:ojt-bg-primary-100 md:ojt-hidden focus:ojt-outline-none focus:ojt-ring">
              <span class="ojt-sr-only">Open main manu</span>
              <span aria-hidden="true">
                <svg class="ojt-w-8 ojt-h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </span>
            </button>
            <div class="ojt-flex journal-header">
              {if $displayPageHeaderLogo}
                <a href="{$ojtPlugin->baseUrl}" target="_blank" rel="home" aria-current="home">
                  <img src="{$ojtPlugin->journalPublicFolder}{$logoImage['uploadName']}" class="ojt-max-h-16">
                </a>
              {else}
                <a href="{$ojtPlugin->baseUrl}" target="_blank" class="is_text">{$displayPageHeaderTitle|escape}</a>
              {/if}
            </div>
            <nav aria-label="Secondary" class="ojt-hidden ojt-space-x-2 md:ojt-flex md:ojt-items-center">
            </nav>
          </div>
          {* <div class="ojt-border-b md:ojt-hidden" x-show="isMobileMainMenuOpen"
            @click.away="isMobileMainMenuOpen = false">
            <nav x-data="pluginMenu()" id="pluginMenuMobile" aria-label="Main"
              class="ojt-flex-1 ojt-px-2 ojt-py-4 ojt-space-y-2 ojt-overflow-y-hidden hover:ojt-overflow-y-auto">
              <div>
                <a @click="page = 'dashboard'; alpineComponent('utama').menu = 'Dashboard'"
                  :class="{ 'ojt-bg-primary-100' : page == 'dashboard' }" href="#"
                  class="ojt-flex ojt-items-center ojt-p-2 ojt-text-gray-500 ojt-transition-colors ojt-rounded-md hover:ojt-bg-primary-100"
                  role="button">
                  <span aria-hidden="true">
                    <svg class="ojt-w-5 ojt-h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                  </span>
                  <span class="ojt-ml-2 ojt-text-sm"> Dashboard </span>
                </a>
              </div>

              <div>
                <template x-if="plugins.length > 0">
                  <div>
                    <a href="#" @click="$event.preventDefault();"
                      class="ojt-flex ojt-items-center ojt-p-2 ojt-text-gray-500 ojt-transition-colors ojt-rounded-md hover:ojt-bg-primary-100 "
                      role="button" aria-haspopup="true" :aria-expanded="(page == 'dashboard') ? 'true' : 'false'">
                      <span aria-hidden="true">
                        <svg class="ojt-w-5 ojt-h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                          stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                      </span>
                      <span class="ojt-ml-2 ojt-text-sm"> Plug-ins </span>
                    </a>
                    <div x-show="open" class="ojt-mt-2 ojt-space-y-2 ojt-px-7" role="menu" arial-label="Components">
                      <template x-for="plugin in plugins">
                        <a href="javascript:void(0);"
                          @click="alpineComponent('utama').menu = 'Plugin'; page = plugin.name" role="menuitem"
                          :class="{ 'ojt-bg-primary-100': page == plugin.name }"
                          class="ojt-block ojt-p-2 ojt-text-sm ojt-text-gray-400 ojt-transition-colors ojt-duration-200 ojt-rounded-md  hover:ojt-text-gray-700 menu_item"
                          :page="plugin.page" x-text="plugin.name">

                        </a>
                      </template>
                    </div>
                  </div>
                </template>
              </div>
            </nav>
          </div> *}
        </header>
        <div class="ojt-h-full ojt-flex ojt-flex-col ojt-justify-between">
          <main class="ojt-space-y-4">
            <div x-show="$store.checkUpdate.updateAvailable"
              class="update-available ojt-flex ojt-flex-col ojt-p-8 ojt-bg-white ojt-shadow-md ojt-border-l-8 ojt-border-yellow-500">
              <div class="ojt-flex ojt-items-center ojt-justify-between">
                <div class="ojt-flex ojt-items-center">
                  <div class="ojt-flex ojt-flex-col ojt-ml-3">
                    <div class="ojt-font-medium ojt-leading-none">There is a new version of OJTPlugin available!</div>
                    <p class="ojt-text-sm ojt-text-gray-600 ojt-leading-none ojt-mt-1">
                      You are currently using <b>Version {$ojtPlugin->version}</b>, the most recent version is <b
                        x-text="$store.checkUpdate.data?.latest_version"></b>.
                      <a href="#" @click="$store.checkUpdate.doUpdate()"
                        class="ojt-font-bold ojt-text-primary-700 hover:ojt-text-primary-800">Click here</a> to update
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div x-show="menu == 'Dashboard'">
              <div x-data="dashboard()" id="dashboard"
                class="ojt-w-full ojt-max-w-screen-xl ojt-m-2 lg:ojt-mx-auto ojt-bg-white ojt-rounded-lg ojt-border ojt-shadow-md">
                <div class="sm:ojt-hidden">
                  <label for="tabs" class="ojt-sr-only">Select tab</label>
                  <select id="tabs"
                    class="ojt-bg-gray-50 ojt-border-0 ojt-border-b ojt-border-gray-200 ojt-text-gray-900 sm:ojt-text-sm ojt-rounded-t-lg focus:ojt-ring-primary-500 focus:ojt-border-primary-500 ojt-block ojt-w-full ojt-p-2.5">
                    <option>Statistics</option>
                    <option>Services</option>
                    <option>FAQ</option>
                  </select>
                </div>
                <ul
                  class="ojt-text-sm ojt-font-medium ojt-text-center ojt-text-gray-500 ojt-rounded-lg ojt-divide-x ojt-divide-gray-200 sm:ojt-flex"
                  role="tablist">
                  <li class="ojt-w-full">
                    <button class="ojt-rounded-tl-lg" type="button" role="tab" @click="tab = 'plugin-installed'"
                      :class="tab === 'plugin-installed' ? activeTabClass : inactiveTabClass">
                      Plugin Installed</button>
                  </li>
                  <li class="ojt-w-full">
                    <button class="ojt-rounded-tr-lg" type="button" role="tab" @click="tab = 'plugin-gallery';"
                      :class="tab === 'plugin-gallery' ? activeTabClass : inactiveTabClass"
                      x-on:mouseenter.once="alpineComponent('pluginGallery').init()">
                      Plugin Gallery
                    </button>
                  </li>
                </ul>
                <div class="ojt-border-t ojt-border-gray-200">
                  <div x-show="tab == 'plugin-installed'" class="ojt-bg-white ojt-rounded-lg" role="tabpanel">
                    {$pluginInstalledHtml}
                  </div>
                  <div x-show="tab == 'plugin-gallery'" class="ojt-p-4 ojt-bg-white ojt-rounded-lg plugin-gallery"
                    page="ojt/pluginGallery" role="tabpanel">
                    {$pluginGalleryHtml}
                  </div>
                </div>
              </div>
            </div>
            <div x-show="menu == 'Plugin'" id="main-menu">
            </div>
          </main>
          <div
            class="ojt-bg-white ojt-flex ojt-flex-col lg:ojt-flex-row lg:ojt-items-center lg:ojt-justify-between ojt-p-2 ojt-border-t ojt-py-4">
            <div>
              Copyright © 2022 <a href="https://openjournaltheme.com" target="_blank"
                class="ojt-font-bold hover:ojt-text-purple-600 hover:ojt-text-underline">Open Journal Theme</a>. All
              rights reserved.
            </div>
            <p class="version ojt-text-right">
              Current Version : <b> {$ojtPlugin->version} </b>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

<div id="moduleJs">
</div>

<footer>
  <script>
    var api = '{$ojtPlugin->api}';
    var baseUrl = '{$ojtPlugin->baseUrl}';
    var thisPage = '{$ojtPlugin->pageName}';
    var placeholderImg = '{$ojtPlugin->placeholderImg}';
    var ojtPluginVersion = '{$ojtPlugin->version}';
  </script>

  {foreach from=$ojtPlugin->javascript item=js}
    <script src="{$js}"></script>
  {/foreach}

  <footer>

</html>