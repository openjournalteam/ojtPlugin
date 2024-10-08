{if $currentContext}
  {assign var="logoImage" value=$currentContext->getLocalizedData('pageHeaderLogoImage')}
{/if}
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
  <meta name="htmx-config" content='{ "includeIndicatorStyles": false }'>
  <style>
    [x-cloak] {
      display: none;
    }
    .htmx-indicator{
      display:none;
    }
    .htmx-request .htmx-indicator{
        display:block;
    }
    .htmx-request.htmx-indicator{
        display:block;
    }
  </style>
</head>
<div id="moduleCss"></div>

<body id="ojt-plugin" x-data="{}" class="ojt-transition-colors" :data-theme="$store.themes.active">
  <div x-data="utama()" id="utama" x-init="init()" x-cloak>
    <div class="ojt-flex ojt-h-screen ojt-antialiased ojt-text-gray-900 ojt-bg-gray-100">
      <aside class="ojt-flex-shrink-0 ojt-hidden ojt-w-72 ojt-bg-white ojt-border-r md:ojt-block">
        <div class="ojt-flex ojt-flex-col ojt-h-full">
          <div class="ojt-py-6 ojt-flex">
            <a href="https://openjournaltheme.com/" target="_blank"
              class="custom-logo-link ojt-mx-auto ojt-flex ojt-flex-col ojt-items-center ojt-gap-2" rel="home"
              aria-current="page">
              <img width="32" height="37" src="{$ojtPlugin->logo}" class="ojt-mx-auto">
              <span class="site-name ojt-ml-3 ojt-inline-block ojt-text-2xl ojt-tracking-wide">
                OJT Control Panel
              </span>
            </a>
          </div>
          <nav aria-label="Main"
            class="ojt-flex-1 ojt-space-y-2 ojt-px-2 ojt-overflow-y-hidden hover:ojt-overflow-y-auto">
            <button @click="toggleMainMenu('dashboard')" :class="{ 'ojt-bg-gradient-to-l ojt-text-white ojt-shadow-lg ojt-shadow-primary-500/50' : $store.plugins.page == 'dashboard',
              'ojt-text-gray-500' : $store.plugins.page != 'dashboard' }" role="button"
              class="ojt-w-full ojt-flex ojt-text-sm ojt-transition ojt-items-center ojt-px-4 ojt-py-2 ojt-rounded-xl ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br ojt-text-gray-500 hover:ojt-text-white ojt-gap-4">
              <span aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                  stroke="currentColor" class="ojt-w-5 ojt-h-5">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
              </span>
              <span class="ojt-font-medium"> Dashboard </span>
            </button>
            {* <span class="ojt-uppercase ojt-tracking-widest ojt-text-gray-400 ojt-px-4 ojt-text-xs">PLUGINS</span> *}
            <div x-show="open" class="ojt-gap-2 ojt-flex ojt-flex-col" role="menu" id="plugin-menu-list">
              <template x-for="(plugin, index) in $store.plugins.activePlugins" :key="plugin.className">
                <button x-show="plugin.enabled && plugin?.page" role="button"
                  @click="toggleMainMenu(plugin.page, 'Plugin')" role="menuitem" :class="{ 
                              'ojt-bg-gradient-to-l ojt-text-white ojt-shadow-lg ojt-shadow-primary-500/50': $store.plugins.page == plugin.page,
                              'ojt-text-gray-500' : $store.plugins.page != plugin.page
                              }" :page="plugin.page" :id="`plugin-menu-item-${ plugin.page }`"
                  class="ojt-w-full ojt-text-sm ojt-flex ojt-items-center ojt-px-4 ojt-py-2 ojt-rounded-xl ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br hover:ojt-text-white ojt-gap-4 menu_item ojt-text-left">
                  <span aria-hidden="true" class="ojt-min-w-[1.25rem]" x-html="plugin.icon">
                  </span>
                  <span class="ojt-font-medium" x-text="plugin.name"></span>
                </button>
              </template>
            </div>
          </nav>

          <div class="ojt-p-4 ojt-mx-auto ojt-w-full ojt-gap-2 ojt-flex ojt-flex-col ojt-border-t">

            <button @click="menu = 'Plugin'; $store.plugins.page = 'settings'" role="menuitem" :class="{ 
                      'ojt-bg-gradient-to-l ojt-text-white ojt-shadow-lg ojt-shadow-primary-500/50': $store.plugins.page == 'settings',
                      'ojt-text-gray-500' : $store.plugins.page != 'settings'
                      }" page="ojt/settings"
              class="ojt-w-full ojt-flex ojt-text-sm ojt-text-gray-500 ojt-items-center ojt-px-4 ojt-py-2 ojt-rounded-xl ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br hover:ojt-text-white ojt-gap-4 menu_item ojt-text-left">
              <svg class="ojt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                aria-hidden="true">
                <path
                  d="M17.004 10.407c.138.435-.216.842-.672.842h-3.465a.75.75 0 01-.65-.375l-1.732-3c-.229-.396-.053-.907.393-1.004a5.252 5.252 0 016.126 3.537zM8.12 8.464c.307-.338.838-.235 1.066.16l1.732 3a.75.75 0 010 .75l-1.732 3.001c-.229.396-.76.498-1.067.16A5.231 5.231 0 016.75 12c0-1.362.519-2.603 1.37-3.536zM10.878 17.13c-.447-.097-.623-.608-.394-1.003l1.733-3.003a.75.75 0 01.65-.375h3.465c.457 0 .81.408.672.843a5.252 5.252 0 01-6.126 3.538z">
                </path>
                <path fill-rule="evenodd"
                  d="M21 12.75a.75.75 0 000-1.5h-.783a8.22 8.22 0 00-.237-1.357l.734-.267a.75.75 0 10-.513-1.41l-.735.268a8.24 8.24 0 00-.689-1.191l.6-.504a.75.75 0 10-.964-1.149l-.6.504a8.3 8.3 0 00-1.054-.885l.391-.678a.75.75 0 10-1.299-.75l-.39.677a8.188 8.188 0 00-1.295-.471l.136-.77a.75.75 0 00-1.477-.26l-.136.77a8.364 8.364 0 00-1.377 0l-.136-.77a.75.75 0 10-1.477.26l.136.77c-.448.121-.88.28-1.294.47l-.39-.676a.75.75 0 00-1.3.75l.392.678a8.29 8.29 0 00-1.054.885l-.6-.504a.75.75 0 00-.965 1.149l.6.503a8.243 8.243 0 00-.689 1.192L3.8 8.217a.75.75 0 10-.513 1.41l.735.267a8.222 8.222 0 00-.238 1.355h-.783a.75.75 0 000 1.5h.783c.042.464.122.917.238 1.356l-.735.268a.75.75 0 10.513 1.41l.735-.268c.197.417.428.816.69 1.192l-.6.504a.75.75 0 10.963 1.149l.601-.505c.326.323.679.62 1.054.885l-.392.68a.75.75 0 101.3.75l.39-.679c.414.192.847.35 1.294.471l-.136.771a.75.75 0 101.477.26l.137-.772a8.376 8.376 0 001.376 0l.136.773a.75.75 0 101.477-.26l-.136-.772a8.19 8.19 0 001.294-.47l.391.677a.75.75 0 101.3-.75l-.393-.679a8.282 8.282 0 001.054-.885l.601.504a.75.75 0 10.964-1.15l-.6-.503a8.24 8.24 0 00.69-1.191l.735.268a.75.75 0 10.512-1.41l-.734-.268c.115-.438.195-.892.237-1.356h.784zm-2.657-3.06a6.744 6.744 0 00-1.19-2.053 6.784 6.784 0 00-1.82-1.51A6.704 6.704 0 0012 5.25a6.801 6.801 0 00-1.225.111 6.7 6.7 0 00-2.15.792 6.784 6.784 0 00-2.952 3.489.758.758 0 01-.036.099A6.74 6.74 0 005.251 12a6.739 6.739 0 003.355 5.835l.01.006.01.005a6.706 6.706 0 002.203.802c.007 0 .014.002.021.004a6.792 6.792 0 002.301 0l.022-.004a6.707 6.707 0 002.228-.816 6.781 6.781 0 001.762-1.483l.009-.01.009-.012a6.744 6.744 0 001.18-2.064c.253-.708.39-1.47.39-2.264a6.74 6.74 0 00-.408-2.308z"
                  clip-rule="evenodd"></path>
              </svg>
              <span class="ojt-font-medium">Settings</span>
            </button>
            <button @click="menu = 'Plugin'; $store.plugins.page  = 'report_bug'" role="menuitem" :class="{ 
                      'ojt-bg-gradient-to-l ojt-text-white ojt-shadow-lg ojt-shadow-primary-500/50': $store.plugins.page == 'report_bug',
                      'ojt-text-gray-500' : $store.plugins.page != 'report_bug'
                      }" page="ojt/reportBug"
              class="ojt-w-full ojt-flex ojt-text-sm ojt-text-gray-500 ojt-items-center ojt-px-4 ojt-py-2 ojt-rounded-xl ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br hover:ojt-text-white ojt-gap-4 menu_item ojt-text-left">
              <svg class="ojt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 24" fill="currentColor">
                <path
                  d="m7.304 2.305c-1.585 1.702-1.146 4.422-1.146 4.422 1.174 1.187 2.802 1.923 4.603 1.923.069 0 .138-.001.207-.003h-.01c.058.002.127.003.195.003 1.801 0 3.43-.735 4.604-1.922l.001-.001s.431-2.683-1.12-4.387c.971-.541 1.542-1.322 1.306-1.853-.268-.599-1.45-.652-2.646-.119-.43.19-.799.43-1.124.72l.004-.004c-.367-.072-.789-.113-1.22-.114h-.001c-.407.001-.805.037-1.192.104l.042-.006c-.314-.279-.675-.514-1.069-.689l-.027-.011c-1.196-.531-2.38-.48-2.646.119-.236.519.305 1.279 1.238 1.818zm12.028 12.39c-.196-.043-.439-.08-.687-.103l-.025-.002c0-.064.011-.124.011-.19-.005-1.025-.141-2.017-.391-2.962l.018.082c.05.002.11.004.169.004.578 0 1.125-.132 1.613-.367l-.022.01c1.2-.534 1.949-1.45 1.68-2.045s-1.45-.652-2.646-.119c-.517.225-.956.538-1.317.924l-.002.002c-.249-.6-.517-1.11-.824-1.592l.025.041c-1.366 1.146-3.085 1.915-4.972 2.126l-.043.004v9.649c0 .53-.43.96-.96.96s-.96-.43-.96-.96v-9.645c-1.928-.216-3.645-.985-5.025-2.143l.015.012c-.271.42-.531.906-.752 1.413l-.026.067c-.353-.353-.77-.642-1.232-.847l-.026-.01c-1.2-.531-2.38-.48-2.646.119s.487 1.513 1.68 2.045c.475.229 1.033.363 1.622.363h.043-.002c-.231.861-.366 1.85-.37 2.871v.003c0 .066.011.127.013.195-.202.022-.41.053-.623.098-1.558.32-2.752 1.128-2.664 1.793s1.424.945 2.986.62c.203-.042.4-.094.593-.152.274 1.261.731 2.377 1.349 3.384l-.027-.047c-.416.217-.773.484-1.08.799l-.001.001c-.96.956-1.344 2.117-.866 2.596s1.639.09 2.595-.864c.255-.255.48-.54.668-.85l.011-.02c1.201 1.236 2.869 2.012 4.719 2.043h.006c1.884-.032 3.575-.83 4.781-2.095l.003-.003c.204.35.439.652.708.92.954.956 2.117 1.344 2.596.866s.09-1.638-.866-2.593c-.328-.333-.708-.613-1.128-.826l-.025-.011c.586-.958 1.04-2.072 1.297-3.259l.013-.071c.215.067.441.131.677.182 1.561.32 2.897.047 2.987-.62s-1.111-1.471-2.67-1.793z">
                </path>
              </svg>
              <span class="ojt-font-medium">Feedback or Report Bug</span>
            </button>
            <a href="https://openjournaltheme.com/docs/ojs/ojs-tutorial" target="_blank"
              class="ojt-w-full ojt-text-sm ojt-flex ojt-text-gray-500 ojt-items-center ojt-px-4 ojt-py-2 ojt-rounded-xl ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br hover:ojt-text-white ojt-gap-4 ojt-text-left">
              <svg class="ojt-icon" width="48" height="48" viewBox="0 0 48 48" fill="currentColor"
                xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd"
                  d="M6 6H37V11H35V8H8V31H29.3871V33H6V6ZM36 19C37.6569 19 39 17.6569 39 16C39 14.3431 37.6569 13 36 13C34.3431 13 33 14.3431 33 16C33 17.6569 34.3431 19 36 19ZM38.0314 21.0107C39.3299 21.0107 40.3582 21.5937 41.0303 22.4959C41.6595 23.3405 41.9258 24.3864 41.9861 25.3507C42.0472 26.3298 41.9056 27.356 41.5894 28.2701C41.2881 29.1407 40.7815 30.0406 40 30.6579V40.5C40 41.2805 39.4015 41.9305 38.6237 41.9949C37.8459 42.0593 37.1487 41.5164 37.0204 40.7466L35.7293 33H35.4324L33.9743 40.7764C33.8316 41.5378 33.1325 42.0653 32.3612 41.9936C31.5899 41.9219 31 41.2746 31 40.5V26.2328C30.8874 26.4044 30.7831 26.5652 30.6911 26.7082C30.5726 26.8922 30.4752 27.0453 30.4078 27.152L30.3303 27.275L30.3105 27.3066L30.3047 27.316C30.0308 27.7555 29.5493 28.023 29.0314 28.023H24.0314C23.203 28.023 22.5314 27.3515 22.5314 26.523C22.5314 25.6946 23.203 25.023 24.0314 25.023H28.208C28.4506 24.6472 28.7705 24.1594 29.1067 23.6689C29.457 23.1577 29.8432 22.6169 30.1862 22.1935C30.3539 21.9864 30.5413 21.7699 30.7292 21.5924C30.8212 21.5056 30.9497 21.3932 31.105 21.2935C31.2158 21.2224 31.5575 21.0107 32.0314 21.0107H38.0314Z">
                </path>
              </svg>
              <span class="ojt-font-medium">OJS Tutorial</span>
            </a>
            <a href="{url router=\PKP\core\PKPApplication::ROUTE_PAGE page="ojt" op="support"}" target="_blank"
                class="ojt-w-full ojt-text-sm ojt-flex ojt-text-gray-500 ojt-items-center ojt-px-4 ojt-py-2 ojt-rounded-xl ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br hover:ojt-text-white ojt-gap-4 ojt-text-left">
                <svg class="ojt-icon" xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" viewBox="0 0 24 24" fill="currentColor"><g><rect fill="none" height="24" width="24"></rect></g><g><g><circle cx="9" cy="13" r="1"></circle><circle cx="15" cy="13" r="1"></circle><path d="M18,11.03C17.52,8.18,15.04,6,12.05,6c-3.03,0-6.29,2.51-6.03,6.45c2.47-1.01,4.33-3.21,4.86-5.89 C12.19,9.19,14.88,11,18,11.03z"></path><path d="M20.99,12C20.88,6.63,16.68,3,12,3c-4.61,0-8.85,3.53-8.99,9H2v6h3v-5.81c0-3.83,2.95-7.18,6.78-7.29 c3.96-0.12,7.22,3.06,7.22,7V19h-8v2h10v-3h1v-6H20.99z"></path></g></g></svg>
                <span>Get Support</span>
            </a>
            <a href="https://openjournaltheme.com/about-open-journal-theme" target="_blank"
              class="focus:ojt-outline-none ojt-text-white ojt-text-sm ojt-justify-center ojt-py-2.5 ojt-px-5 ojt-rounded-xl  ojt-flex ojt-items-center ojt-bg-gradient-to-l ojt-text-white ojt-shadow-lg ojt-shadow-primary-500/50 ojt-from-primary-500 ojt-via-primary-600 ojt-to-primary-700 hover:ojt-bg-gradient-to-br ojt-gap-4">
              <img src="{$ojtPlugin->logo}" class="ojt-h-5">
              <span class="ojt-font-medium">ABOUT US</span>
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
              <a class="ojt-flex ojt-items-center ojt-gap-2 ojt-text-primary-700 hover:ojt-text-white ojt-text-black hover:ojt-bg-primary-700 focus:ojt-ring-4 focus:ojt-outline-none focus:ojt-ring-primary-300 ojt-font-medium ojt-rounded-lg ojt-text-xs ojt-px-4 ojt-py-2 ojt-text-center"
                href="{url journal=$journal->getData('urlPath') page="submissions"}">
                <svg class="ojt-h-4 ojt-w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 24" fill="currentColor">
                  <path
                    d="m.511 13.019 16.695 10.836c.656.427 1.461-.136 1.461-1.02v-6.685l11.872 7.705c.656.427 1.461-.136 1.461-1.02v-21.669c0-.884-.805-1.447-1.461-1.02l-11.872 7.704v-6.684c0-.884-.805-1.447-1.461-1.02l-16.695 10.835c-.312.234-.511.604-.511 1.02s.2.785.508 1.017l.003.002z">
                  </path>
                </svg>
                Back to OJS Dashboard
              </a>
            </nav>
          </div>
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
                <ul
                  class="ojt-text-sm ojt-font-medium ojt-text-center ojt-text-gray-500 ojt-rounded-lg ojt-divide-x ojt-divide-gray-200 sm:ojt-flex"
                  role="tablist">
                  <li class="ojt-w-full">
                    <button class="ojt-rounded-tl-lg" type="button" role="tab" @click="tab = 'plugin-installed'"
                      :class="tab === 'plugin-installed' ? activeTabClass : inactiveTabClass">
                      Plugin Installed</button>
                  </li>
                  <li class="ojt-w-full">
                    <button class="ojt-rounded-tr-lg" type="button" role="tab"
                      @click="tab = 'plugin-gallery';alpineComponent('pluginGallery').init();"
                      :class="tab === 'plugin-gallery' ? activeTabClass : inactiveTabClass">
                      Product Gallery
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
            class="ojt-bg-white ojt-mt-4 ojt-flex ojt-flex-col lg:ojt-flex-row lg:ojt-items-center lg:ojt-justify-between ojt-p-2 ojt-border-t ojt-py-4">
            <div>
              Copyright © {date('Y')} <a href="https://openjournaltheme.com" target="_blank"
                class="ojt-font-bold hover:ojt-text-purple-600 hover:ojt-text-underline">Open Journal Theme</a>. All
              rights reserved.
            </div>
            <p class="version ojt-text-right">
              Current Version : <b> {$ojtPlugin->version} </b>
            </p>
          </div>
        </div>
        <template id="error-template">
          <div
            class="ojt-flex ojt-max-w-2xl ojt-mx-auto ojt-p-4 ojt-mb-4 ojt-text-sm ojt-text-red-700 ojt-bg-red-100 ojt-rounded-lg dark:ojt-bg-red-200 dark:ojt-text-red-800"
            role="alert">
            <svg aria-hidden="true" class="ojt-flex-shrink-0 ojt-inline ojt-w-5 ojt-h-5 ojt-mr-3" fill="currentColor"
              viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                clip-rule="evenodd"></path>
            </svg>
            <span class="sr-only">Info</span>
            <div>
              <span class="ojt-font-medium">Sorry, there's an error happened.</span>
              <div>Please try again later. If the problem still persist, please <a target="_blank"
                  href="https://openjournaltheme.com/contact-us" class="ojt-underline"> contact
                  us</a> or submit the problem <a href="#" @click="menu = 'Plugin'; $store.plugins.page  = 'report_bug'"
                  class="menu_item ojt-underline" page="ojt/reportBug">here</a>.
              </div>
              <div class="ojt-mt-4">
                <p class="ojt-font-bold">Error message:</p>
                <p id="error-message"> dsadas</p>
              </div>
            </div>
          </div>
        </template>
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