{assign var="logoImage" value=$currentContext->getLocalizedData('pageHeaderLogoImage')}

<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OJT Control Panel</title>
  <link rel="shortcut icon" type="image/jpg" href="{$ojtPlugin->favIcon}" />
  <link href="{$ojtPlugin->themeCss}" rel="stylesheet">
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

<body>
  <div x-data="setup()" id="utama" x-init="$refs.loading.classList.add('ojt-hidden');" :class="{ 'dark': isDark }">
    <div
      class="ojt-flex ojt-h-screen ojt-antialiased ojt-text-gray-900 ojt-bg-gray-100 dark:ojt-bg-dark dark:ojt-text-light">
      <div x-ref="loading"
        class="ojt-fixed ojt-inset-0 ojt-z-50 ojt-flex ojt-items-center ojt-justify-center ojt-text-2xl ojt-font-semibold ojt-text-white ojt-bg-opacity-90 ojt-bg-blue-800">
        Loading.....
      </div>
      <aside
        class="ojt-flex-shrink-0 ojt-hidden w-64 ojt-bg-white ojt-border-r dark:ojt-border-blue-800 dark:ojt-bg-darker md:ojt-block">
        <div class="ojt-flex ojt-flex-col ojt-h-full">
          <a href="https://openjournaltheme.com/" class="custom-logo-link ojt-flex ojt-place-items-center ojt-p-2"
            rel="home" aria-current="page">
            <img width="32" height="37" src="{$ojtPlugin->logo}">
            <span class="site-name ojt-ml-3 ojt-inline-block ojt-text-2xl ojt-tracking-wider dark:ojt-text-light"> Open
              Journal Theme</span>
          </a>
          <nav x-data="pluginMenu()" id="pluginMenu" aria-label="Main"
            class="ojt-flex-1 ojt-px-2 ojt-py-4 ojt-space-y-2 ojt-overflow-y-hidden hover:ojt-overflow-y-auto">
            <div>
              <a @click="page = 'dashboard'; alpineComponent('utama').menu = 'Dashboard'"
                :class="{ 'ojt-bg-blue-100 dark:ojt-bg-blue-600' : page == 'dashboard' }" href="#"
                class="ojt-flex ojt-items-center ojt-p-2 ojt-text-gray-500 ojt-transition-colors ojt-rounded-md dark:ojt-text-light hover:ojt-bg-blue-100 dark:hover:ojt-bg-blue-600"
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
                    class="ojt-flex ojt-items-center ojt-p-2 ojt-text-gray-500 ojt-transition-colors ojt-rounded-md dark:ojt-text-light hover:ojt-bg-blue-100 dark:ojt-hover:bg-blue-600"
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
                      <div>
                        <template x-if="plugin.enabled && plugin?.page">
                          <a href="javascript:void(0);"
                            @click="alpineComponent('utama').menu = 'Plugin'; page = plugin.name" role="menuitem"
                            :class="{ 
                              'ojt-bg-blue-100 dark:ojt-bg-blue-600 ojt-text-gray-700': page == plugin.name,
                              'ojt-text-gray-400 dark:ojt-text-gray-400 dark:hover:ojt-text-light hover:ojt-text-gray-700' : page != plugin.name
                              }" :page="plugin.page" x-text="plugin.name"
                            class="ojt-block ojt-p-2 ojt-text-sm ojt-transition-colors ojt-duration-200 ojt-rounded-md  menu_item">

                          </a>
                        </template>
                      </div>
                    </template>
                  </div>
                </div>
              </template>
            </div>
          </nav>
          <div class="ojt-mt-auto ojt-p-4 ojt-mx-auto">
            <a href="https://openjournaltheme.com/about-open-journal-theme" target="_blank"
              class="focus:ojt-outline-none ojt-text-white ojt-text-sm ojt-py-2.5 ojt-px-5 ojt-rounded-md ojt-bg-purple-500 hover:ojt-bg-purple-600 hover:ojt-shadow-lg ojt-flex ojt-items-center">
              <svg class="ojt-w-4 ojt-h-4 ojt-mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              ABOUT US
            </a>
          </div>
        </div>
      </aside>

      <div class="ojt-flex ojt-flex-col ojt-flex-1 ojt-min-h-screen ojt-overflow-x-hidden ojt-overflow-y-auto">
        <header class="ojt-sticky ojt-top-0 ojt-relative ojt-bg-white dark:ojt-bg-darker">
          <div class="ojt-flex ojt-items-center ojt-justify-between ojt-p-2 ojt-border-b dark:ojt-border-blue-800">
            <button @click="isMobileMainMenuOpen = !isMobileMainMenuOpen"
              class="ojt-p-1 ojt-text-blue-400 ojt-transition-colors ojt-duration-200 ojt-rounded-md ojt-bg-blue-50 hover:ojt-text-blue-600 hover:ojt-bg-blue-100 dark:hover:ojt-text-light dark:hover:ojt-bg-blue-700 dark:ojt-bg-dark md:ojt-hidden focus:ojt-outline-none focus:ojt-ring">
              <span class="ojt-sr-only">Open main manu</span>
              <span aria-hidden="true">
                <svg class="ojt-w-8 ojt-h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </span>
            </button>
            <div class="ojt-flex journal-header">
              <a href="{$ojtPlugin->baseUrl}" rel="home" aria-current="home">
                <img src="{$ojtPlugin->journalPublicFolder}{$logoImage['uploadName']}" class="ojt-max-h-16">
              </a>
            </div>
            <nav aria-label="Secondary" class="ojt-hidden ojt-space-x-2 md:ojt-flex md:ojt-items-center">
            </nav>
          </div>
          <div class="ojt-border-b md:ojt-hidden dark:ojt-border-blue-800" x-show="isMobileMainMenuOpen"
            @click.away="isMobileMainMenuOpen = false">
            <nav x-data="pluginMenu()" id="pluginMenu" aria-label="Main"
              class="ojt-flex-1 ojt-px-2 ojt-py-4 ojt-space-y-2 ojt-overflow-y-hidden hover:ojt-overflow-y-auto">
              <div>
                <a @click="page = 'dashboard'; alpineComponent('utama').menu = 'Dashboard'"
                  :class="{ 'ojt-bg-blue-100 dark:ojt-bg-blue-600' : page == 'dashboard' }" href="#"
                  class="ojt-flex ojt-items-center ojt-p-2 ojt-text-gray-500 ojt-transition-colors ojt-rounded-md dark:ojt-text-light hover:ojt-bg-blue-100 dark:hover:ojt-bg-blue-600"
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
                      class="ojt-flex ojt-items-center ojt-p-2 ojt-text-gray-500 ojt-transition-colors ojt-rounded-md dark:ojt-text-light hover:ojt-bg-blue-100 dark:ojt-hover:bg-blue-600"
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
                          :class="{ 'ojt-bg-blue-100 dark:ojt-bg-blue-600': page == plugin.name }"
                          class="ojt-block ojt-p-2 ojt-text-sm ojt-text-gray-400 ojt-transition-colors ojt-duration-200 ojt-rounded-md dark:ojt-text-gray-400 dark:hover:ojt-text-light hover:ojt-text-gray-700 menu_item"
                          :page="plugin.page" x-text="plugin.name">

                        </a>
                      </template>
                    </div>
                  </div>
                </template>
              </div>
            </nav>
          </div>
        </header>
        <div class="ojt-h-full ojt-flex ojt-flex-col ojt-justify-between">
          <main class="ojt-space-y-4">
            <div x-data="checkUpdate()" x-init="() => { checkUpdate() }" x-show="updateAvailable"
              class="update-available ojt-flex ojt-flex-col ojt-p-8 ojt-bg-white ojt-shadow-md ojt-border-l-8 ojt-border-yellow-500">
              <div class="ojt-flex ojt-items-center ojt-justify-between">
                <div class="ojt-flex ojt-items-center">
                  <div class="ojt-flex ojt-flex-col ojt-ml-3">
                    <div class="ojt-font-medium ojt-leading-none">There is a new version of OJTPlugin available!</div>
                    <p class="ojt-text-sm ojt-text-gray-600 ojt-leading-none ojt-mt-1">
                      You are currently using <b>Version {$ojtPlugin->version}</b>, the most recent version is <b
                        x-text="data?.latest_version"></b>.
                      <a href="#" @click="doUpdate()"
                        class="ojt-font-bold ojt-text-blue-700 hover:ojt-text-blue-800">Click here</a> to update
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div x-show="menu == 'Dashboard'" x-transition:enter="ojt-transition ojt-ease-out ojt-duration-300"
              x-transition:enter-start="ojt-opacity-0 ojt-transform ojt-scale-90"
              x-transition:enter-end="ojt-opacity-100 ojt-transform ojt-scale-100" id="dashboard" class="ajax_load"
              page="ojt/setting">

            </div>
            <div x-show="menu == 'Plugin'" x-transition:enter="ojt-transition ojt-ease-out ojt-duration-300"
              x-transition:enter-start="ojt-opacity-0 ojt-transform ojt-scale-50"
              x-transition:enter-end="ojt-opacity-100 ojt-transform ojt-scale-100" id="main-menu">

            </div>
          </main>
          <div
            class="ojt-bg-white ojt-flex ojt-items-center ojt-justify-between ojt-p-2 ojt-border-t dark:ojt-border-blue-800 ojt-py-4">
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