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
  <div x-data="utama()" id="utama" x-cloak>
    <div class="ojt-flex ojt-h-screen ojt-antialiased ojt-text-gray-900 ojt-bg-gray-100">
      <aside class="ojt-flex-shrink-0 ojt-hidden ojt-w-72 ojt-bg-white ojt-border-r md:ojt-block">
        <div class="ojt-flex ojt-flex-col ojt-h-full">
          <a href="https://openjournaltheme.com/" target="_blank"
            class="custom-logo-link ojt-flex ojt-flex-col ojt-items-center ojt-py-6 ojt-gap-2" rel="home"
            aria-current="page">
            <img width="32" height="37" src="{$ojtPlugin->logo}" class="ojt-mx-auto">
            <span class="site-name ojt-ml-3 ojt-inline-block ojt-text-2xl ojt-tracking-wide">OJT
              Plugin</span>
          </a>
          <nav x-data="pluginMenu()" id="pluginMenu" aria-label="Main"
            class="ojt-flex-1 ojt-space-y-2 ojt-px-2 ojt-overflow-y-hidden hover:ojt-overflow-y-auto">
            <button @click="page = 'dashboard'; alpineComponent('utama').menu = 'Dashboard'"
              :class="{ 'ojt-bg-gradient-to-l ojt-text-white ojt-shadow-lg ojt-shadow-primary-500/50' : page == 'dashboard'}"
              role="button"
              class="ojt-w-full ojt-flex ojt-transition ojt-items-center ojt-px-4 ojt-py-2 ojt-rounded-lg ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br ojt-text-gray-500 hover:ojt-text-white ojt-gap-4">
              <span aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                  stroke="currentColor" class="ojt-w-5 ojt-h-5">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
              </span>
              <span> Dashboard </span>
            </button>
            <template x-if="plugins.length > 0">
              <div>
                <span class="ojt-uppercase ojt-tracking-widest ojt-text-gray-400 ojt-px-4 ojt-text-xs">PLUGINS</span>
                <div x-show="open" class="ojt-mt-2 ojt-space-y-2" role="menu">
                  <template x-for="plugin in plugins">
                    <div>
                      <template x-if="plugin.enabled && plugin?.page">
                        <button role="button" @click="alpineComponent('utama').menu = 'Plugin'; page = plugin.name"
                          role="menuitem" :class="{ 
                              'ojt-bg-gradient-to-l ojt-text-white ojt-shadow-lg ojt-shadow-primary-500/50': page == plugin.name,
                              }" :page="plugin.page"
                          class="ojt-w-full ojt-flex ojt-text-gray-500 ojt-items-center ojt-px-4 ojt-py-2 ojt-rounded-xl ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br hover:ojt-text-white ojt-gap-4 menu_item ojt-text-left">
                          <span aria-hidden="true" class="ojt-min-w-[1.25rem]" x-html="plugin.icon">
                          </span>
                          <span x-text="plugin.name"></span>
                        </button>
                      </template>
                    </div>
                  </template>
                </div>
              </div>
            </template>
          </nav>
          <div class="ojt-mt-auto ojt-p-4 ojt-mx-auto ojt-w-full">
            <a href="https://openjournaltheme.com/about-open-journal-theme" target="_blank"
              class="focus:ojt-outline-none ojt-text-white ojt-text-sm ojt-justify-center ojt-py-2.5 ojt-px-5 ojt-rounded-xl  ojt-flex ojt-items-center ojt-bg-gradient-to-l ojt-text-white ojt-shadow-lg ojt-shadow-primary-500/50 ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br ojt-gap-4">
              <img src="{$ojtPlugin->logo}" class="ojt-h-5">
              <span>ABOUT US</span>
            </a>
          </div>
        </div>
      </aside>

      <div class="ojt-flex ojt-flex-col ojt-flex-1 ojt-min-h-screen ojt-overflow-x-hidden ojt-overflow-y-auto">
        <header class="ojt-sticky ojt-top-0 ojt-bg-white dark:ojt-bg-darker ojt-z-10">
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
          <div class="ojt-border-b md:ojt-hidden dark:ojt-border-blue-800" x-show="isMobileMainMenuOpen"
            @click.away="isMobileMainMenuOpen = false">
            <nav x-data="pluginMenu()" id="pluginMenuMobile" aria-label="Main"
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
            <div x-data="checkUpdate()" id="checkUpdate" x-init="() => { checkUpdate() }" x-show="updateAvailable"
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
            <div x-show="menu == 'Dashboard'" id="dashboard" class="ajax_load" page="ojt/setting">

            </div>
            <div x-show="menu == 'Plugin'" id="main-menu">

            </div>
          </main>
          <div
            class="ojt-bg-white ojt-flex ojt-flex-col lg:ojt-flex-row lg:ojt-items-center lg:ojt-justify-between ojt-p-2 ojt-border-t dark:ojt-border-blue-800 ojt-py-4">
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