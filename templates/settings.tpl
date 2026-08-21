<div class="ojt-w-auto ojt-max-w-screen-lg 2xl:ojt-mx-auto ojt-bg-white ojt-rounded-lg ojt-border ojt-shadow-md">
  <div id="ojt-settings" class="ojt-p-4 ojt-bg-white ojt-rounded-lg md:ojt-p-8">
    <div class="ojt-flex ojt-items-center ojt-justify-between ojt-mb-6">
      <h2
        class="ojt-text-3xl ojt-font-extrabold ojt-tracking-tight ojt-text-gray-900 ojt-flex ojt-items-center ojt-gap-2">
        <span class="ojt-flex ojt-h-10 ojt-w-10 ojt-items-center ojt-justify-center ojt-rounded-xl ojt-bg-primary-50 ojt-text-primary-600">
          <svg class="ojt-h-5 ojt-w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z" stroke="currentColor" stroke-width="1.6" />
            <path d="m19 13.5 1.25 1-.96 1.66-1.5-.55a7.4 7.4 0 0 1-1.65 1.02l-.2 1.6h-1.92l-.2-1.6a7.4 7.4 0 0 1-1.65-1.02l-1.5.55-.96-1.66 1.25-1a7.3 7.3 0 0 1 0-2.02l-1.25-1 .96-1.66 1.5.55a7.4 7.4 0 0 1 1.65-1.02l.2-1.6h1.92l.2 1.6a7.4 7.4 0 0 1 1.65 1.02l1.5-.55.96 1.66-1.25 1a7.3 7.3 0 0 1 0 2.02Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
          </svg>
        </span>
        Settings
      </h2>
    </div>
    <section>
      <div class="ojt-items-center">
        <div class="ojt-justify-center ojt-w-full">
          <div class="ojt-justify-start ojt-w-full ojt-text-left">
            <div x-data="{ tab: 'tab1' }">
              <ul class="ojt-flex ojt-gap-3 ojt-mt-6 ojt-space-x-4 ojt-text-gray-600 ojt-border-b" id="settings-tab">
                <li class="ojt--mb-px">
                  <a @click.prevent="tab = 'tab1'" href="#"
                    class="ojt-inline-block ojt-py-2 ojt-font-medium ojt-gap-4 ojt-transition-colors ojt-duration-150"
                    :class="tab === 'tab1' ? 'ojt-text-primary-500 ojt-border-b-2 ojt-border-primary-500' : 'ojt-text-gray-700 hover:ojt-text-primary-500'">
                    <span>General</span>
                  </a>
                </li>
                <li class="ojt--mb-px">
                  <a @click.prevent="tab = 'tab2'" href="#"
                    class="ojt-inline-block ojt-py-2 ojt-font-medium ojt-gap-4 ojt-transition-colors ojt-duration-150"
                    :class="tab === 'tab2' ? 'ojt-text-primary-500 ojt-border-b-2 ojt-border-primary-500' : 'ojt-text-gray-700 hover:ojt-text-primary-500'">
                    <span>Theme</span>
                  </a>
                </li>
                {if $settings.can_manage_background_jobs}
                  <li class="ojt--mb-px">
                    <a @click.prevent="tab = 'tab3'" href="#"
                      class="ojt-inline-block ojt-py-2 ojt-font-medium ojt-gap-4 ojt-transition-colors ojt-duration-150"
                      :class="tab === 'tab3' ? 'ojt-text-primary-500 ojt-border-b-2 ojt-border-primary-500' : 'ojt-text-gray-700 hover:ojt-text-primary-500'">
                      <span>Background Jobs</span>
                    </a>
                  </li>
                {/if}
              </ul>
              <div class="ojt-py-4 ojt-pt-4 ojt-text-left ojt-bg-white ojt-content" id="settings-tab-content">
                {* <!-- show tab1 only -->
                <div x-show="tab==='tab1'" x-transition:enter="ojt-transition ojt-duration-200 ojt-ease-out" x-transition:enter-start="ojt-opacity-0 ojt-translate-y-1" x-transition:enter-end="ojt-opacity-100 ojt-translate-y-0" x-transition:leave="ojt-transition ojt-duration-150 ojt-ease-in" x-transition:leave-start="ojt-opacity-100 ojt-translate-y-0" x-transition:leave-end="ojt-opacity-0 ojt-translate-y-1" class="ojt-text-gray-500">
                  <main>
                    <!-- === Remove and replace with your own content... === -->
                    Content
                    <!-- === End ===  -->
                  </main>
                </div> *}
                <!-- show tab1 only -->
                <div x-show="tab==='tab1'" class="ojt-text-gray-500">
                  <main>
                    <!-- === Remove and replace with your own content... === -->
                    <form x-data='settingForm({$settings|@json_encode|escape:"html"})' method="POST">
                      <div class="ojt-mb-6">
                        <div class="ojt-flex ojt-items-center">
                          <input id="default-checkbox" type="checkbox" x-model="data.enable_diagnostic"
                            class="ojt-w-4 ojt-h-4 ojt-text-primary-600 ojt-bg-gray-100 ojt-border-gray-300 ojt-rounded focus:ojt-ring-primary-500 dark:focus:ojt-ring-primary-600 dark:ojt-ring-offset-gray-800 focus:ojt-ring-2 dark:ojt-bg-gray-700 dark:ojt-border-gray-600">
                          <div class="ojt-ml-2">
                            <label class="ojt-text-sm ojt-font-medium ojt-text-gray-900 dark:ojt-text-gray-300">Enable
                              Diagnostics</label>
                            <p class="ojt-text-xs">
                              Help us fix things and improve OJT Products and services. Send diagnostics data (error
                              log) to Open
                              Journal Theme.
                            </p>
                          </div>
                        </div>
                      </div>
                      <div class="ojt-mb-6">
                        <div class="ojt-flex ojt-items-center">
                          <input id="default-checkbox" type="checkbox" x-model="data.show_support_link_ojs"
                            class="ojt-w-4 ojt-h-4 ojt-text-primary-600 ojt-bg-gray-100 ojt-border-gray-300 ojt-rounded focus:ojt-ring-primary-500 dark:focus:ojt-ring-primary-600 dark:ojt-ring-offset-gray-800 focus:ojt-ring-2 dark:ojt-bg-gray-700 dark:ojt-border-gray-600">
                          <div class="ojt-ml-2">
                            <label class="ojt-text-sm ojt-font-medium ojt-text-gray-900 dark:ojt-text-gray-300">Show Get Support Link on OJS Dashboard</label>
                            <p class="ojt-text-xs">
                              Allow plugin to show Get Support Link on OJS Dashboard.
                            </p>
                          </div>
                        </div>
                      </div>

                      <button type="submit" @click.prevent="submit" :disabled="loading"
                        class="ojt-text-white ojt-bg-primary-700 hover:ojt-bg-primary-800 focus:ojt-ring-4 focus:ojt-outline-none focus:ojt-ring-primary-300 ojt-font-medium ojt-rounded-lg ojt-text-sm ojt-w-full sm:ojt-w-auto ojt-px-4 ojt-py-2 ojt-text-center disabled:ojt-opacity-75">
                        Submit
                      </button>
                    </form>
                    <!-- === End ===  -->
                  </main>
                </div>
                <div x-show="tab==='tab2'" x-transition:enter="ojt-transition ojt-duration-200 ojt-ease-out" x-transition:enter-start="ojt-opacity-0 ojt-translate-y-1" x-transition:enter-end="ojt-opacity-100 ojt-translate-y-0" x-transition:leave="ojt-transition ojt-duration-150 ojt-ease-in" x-transition:leave-start="ojt-opacity-100 ojt-translate-y-0" x-transition:leave-end="ojt-opacity-0 ojt-translate-y-1" class="ojt-text-gray-500">
                  <main>
                    <ul class="ojt-grid ojt-w-full ojt-gap-6 md:ojt-grid-cols-4">
                      <template x-for="(color, index) in Object.keys($store.themes.list)" :key="index">
                        <li :data-theme="color">
                          <input type="radio" :id="'color-' + color" name="color" :value="color"
                            x-model="$store.themes.active" class="ojt-hidden ojt-peer" required>
                          <label :for="'color-' + color"
                            class="ojt-inline-flex ojt-items-center ojt-justify-between ojt-w-full ojt-p-5 ojt-text-primary-500 ojt-bg-white ojt-border ojt-border-gray-200 ojt-rounded-lg ojt-cursor-pointer peer-checked:ojt-bg-primary-600 peer-checked:ojt-text-white hover:ojt-text-primary-600 hover:ojt-bg-primary-100 hover:ojt-border-primary-400 ojt-transition-all">
                            <div class="ojt-block">
                              <div class="ojt-w-full ojt-text-lg ojt-font-semibold ojt-capitalize" x-text="color"></div>
                            </div>
                          </label>
                        </li>
                      </template>

                    </ul>

                  </main>
                </div>
                {if $settings.can_manage_background_jobs}
                  <div x-show="tab==='tab3'" x-transition:enter="ojt-transition ojt-duration-200 ojt-ease-out" x-transition:enter-start="ojt-opacity-0 ojt-translate-y-1" x-transition:enter-end="ojt-opacity-100 ojt-translate-y-0" x-transition:leave="ojt-transition ojt-duration-150 ojt-ease-in" x-transition:leave-start="ojt-opacity-100 ojt-translate-y-0" x-transition:leave-end="ojt-opacity-0 ojt-translate-y-1">
                  <div x-data='backgroundJobs({$settings|@json_encode})' x-init="init()" class="ojt-text-gray-500">
                    <main>
                    <div class="ojt-flex ojt-flex-wrap ojt-items-start ojt-justify-between ojt-gap-4 ojt-mb-4">
                      <div>
                        <div class="ojt-flex ojt-flex-wrap ojt-items-center ojt-gap-2">
                          <h3 class="ojt-m-0 ojt-text-xl ojt-font-bold ojt-text-gray-800">Background Jobs</h3>
                          <span class="ojt-inline-flex ojt-items-center ojt-gap-1 ojt-rounded-full ojt-px-2 ojt-py-1 ojt-text-xs ojt-font-semibold" :class="jobsEnabled ? 'ojt-bg-success-50 ojt-text-success-700' : 'ojt-bg-gray-100 ojt-text-gray-500'">
                            <span class="ojt-h-2 ojt-w-2 ojt-rounded-full" :class="jobsEnabled ? 'ojt-bg-success-500' : 'ojt-bg-gray-400'"></span>
                            <span x-text="activeCount() + ' ACTIVE'"></span>
                          </span>
                        </div>
                        <p class="ojt-mt-1 ojt-text-sm ojt-text-gray-500">Monitor, pause, and control queued and running tasks.</p>
                      </div>
                      <div x-show="view === 'queue'" x-cloak class="ojt-flex ojt-items-start">
                        <button type="button" @click="confirmStopAllJobs" :disabled="activeCount() === 0"
                          class="ojt-inline-flex ojt-items-center ojt-gap-2 ojt-rounded-lg ojt-border ojt-border-danger-300 ojt-bg-white ojt-px-3 ojt-py-2 ojt-text-sm ojt-font-medium ojt-text-danger-600 hover:ojt-bg-danger-50 disabled:ojt-cursor-not-allowed disabled:ojt-opacity-50">
                          <svg class="ojt-h-3.5 ojt-w-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <rect x="4.25" y="4.25" width="7.5" height="7.5" rx="1" stroke="currentColor" stroke-width="1.5" />
                          </svg>
                          Stop All Jobs
                        </button>
                      </div>
                    </div>

                    <div class="ojt-mt-5 ojt-flex ojt-gap-6 ojt-border-b ojt-border-gray-200">
                      <button type="button" @click="view = 'queue'"
                        class="ojt-relative ojt-inline-flex ojt-items-center ojt-border-b-2 ojt-pb-2 ojt-text-sm ojt-font-medium ojt-text-gray-600 ojt-transition-colors ojt-duration-150"
                        :class="view === 'queue' ? 'ojt-border-primary-500' : 'ojt-border-transparent'">
                        <span class="ojt-inline-flex ojt-items-center ojt-gap-1">
                          <span :class="view === 'queue' ? 'ojt-font-semibold ojt-text-primary-600' : 'ojt-font-normal ojt-text-gray-500'">Queue</span>
                          <span class="ojt-inline-flex ojt-items-center ojt-rounded-full ojt-px-1.5 ojt-py-0.5 ojt-text-[10px] ojt-font-semibold ojt-leading-none" :class="view === 'queue' ? 'ojt-bg-primary-50 ojt-text-primary-600' : 'ojt-bg-gray-100 ojt-text-gray-500'" x-text="countFor('all')"></span>
                        </span>
                      </button>
                      <button type="button" @click="view = 'scheduled'"
                        class="ojt-relative ojt-inline-flex ojt-items-center ojt-border-b-2 ojt-pb-2 ojt-text-sm ojt-font-medium ojt-text-gray-600 ojt-transition-colors ojt-duration-150"
                        :class="view === 'scheduled' ? 'ojt-border-primary-500' : 'ojt-border-transparent'">
                        <span class="ojt-inline-flex ojt-items-center ojt-gap-1">
                          <span :class="view === 'scheduled' ? 'ojt-font-semibold ojt-text-primary-600' : 'ojt-font-normal ojt-text-gray-500'">Scheduled</span>
                          <span class="ojt-inline-flex ojt-items-center ojt-rounded-full ojt-px-1.5 ojt-py-0.5 ojt-text-[10px] ojt-font-semibold ojt-leading-none" :class="view === 'scheduled' ? 'ojt-bg-primary-50 ojt-text-primary-600' : 'ojt-bg-gray-100 ojt-text-gray-500'" x-text="schedules.length"></span>
                        </span>
                      </button>
                      <button type="button" @click="view = 'settings'"
                        class="ojt-relative ojt-inline-flex ojt-items-center ojt-border-b-2 ojt-pb-2 ojt-text-sm ojt-font-medium ojt-text-gray-600 ojt-transition-colors ojt-duration-150"
                        :class="view === 'settings' ? 'ojt-border-primary-500' : 'ojt-border-transparent'">
                        <span :class="view === 'settings' ? 'ojt-font-semibold ojt-text-primary-600' : 'ojt-font-normal ojt-text-gray-500'">Settings</span>
                      </button>
                    </div>

                    <div x-show="view === 'queue'" x-transition:enter="ojt-transition ojt-duration-200 ojt-ease-out" x-transition:enter-start="ojt-opacity-0 ojt-translate-y-1" x-transition:enter-end="ojt-opacity-100 ojt-translate-y-0" x-transition:leave="ojt-transition ojt-duration-150 ojt-ease-in" x-transition:leave-start="ojt-opacity-100 ojt-translate-y-0" x-transition:leave-end="ojt-opacity-0 ojt-translate-y-1" class="ojt-pt-4">
                      <div class="ojt-mb-3 ojt-flex ojt-flex-wrap ojt-items-center ojt-justify-between ojt-gap-3">
                        <div class="ojt-flex ojt-flex-wrap ojt-items-center ojt-gap-1 ojt-rounded-lg ojt-bg-gray-100 ojt-p-1">
                          <template x-for="filterOption in filterOptions" :key="filterOption.key">
                            <button type="button" @click="setFilter(filterOption.key)"
                              class="ojt-rounded-md ojt-px-3 ojt-py-1.5 ojt-text-xs ojt-font-semibold ojt-transition-all ojt-duration-150"
                              :class="filter === filterOption.key ? 'ojt-bg-white ojt-text-primary-600 ojt-shadow-sm' : 'ojt-text-gray-500 hover:ojt-text-gray-700'">
                              <span x-text="filterOption.label"></span>
                              <span class="ojt-ml-1 ojt-rounded-full ojt-bg-gray-200 ojt-px-1.5 ojt-py-0.5 ojt-text-[10px]" :class="filter === filterOption.key ? 'ojt-bg-primary-50 ojt-text-primary-600' : 'ojt-text-gray-500'" x-text="countFor(filterOption.key)"></span>
                            </button>
                          </template>
                        </div>
                        <div class="ojt-flex ojt-w-full ojt-flex-wrap ojt-gap-2 sm:ojt-w-auto">
                          <label class="ojt-relative ojt-flex ojt-items-center ojt-flex-1 sm:ojt-flex-none">
                            <svg class="ojt-pointer-events-none ojt-absolute ojt-left-3 ojt-h-4 ojt-w-4 ojt-text-gray-400" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                              <circle cx="8.75" cy="8.75" r="5.25" stroke="currentColor" stroke-width="1.5" />
                              <path d="m12.75 12.75 3.5 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                            <input type="search" x-model="query" @input.debounce.350ms="page = 1; loadJobs()" placeholder="Search jobs..." aria-label="Search jobs"
                              class="ojt-h-9 ojt-w-full ojt-rounded-lg ojt-border ojt-border-gray-300 ojt-bg-white ojt-pl-9 ojt-pr-3 ojt-text-sm ojt-text-gray-700 placeholder:ojt-text-gray-400 focus:ojt-border-primary-500 focus:ojt-ring-primary-200 sm:ojt-w-52" />
                          </label>
                          <div class="ojt-relative">
                            <button type="button" @click="dateOpen = !dateOpen"
                              class="ojt-inline-flex ojt-h-9 ojt-items-center ojt-gap-2 ojt-rounded-lg ojt-border ojt-bg-white ojt-px-3 ojt-text-sm ojt-font-medium ojt-transition-colors"
                              :class="dateOpen || dateRange !== 'all' ? 'ojt-border-primary-500 ojt-text-primary-600' : 'ojt-border-gray-300 ojt-text-gray-600'"
                              aria-haspopup="listbox" :aria-expanded="dateOpen">
                              <svg class="ojt-h-4 ojt-w-4 ojt-text-gray-400" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <rect x="3.25" y="4.5" width="13.5" height="12" rx="1.5" stroke="currentColor" stroke-width="1.4" />
                                <path d="M6.5 3v3M13.5 3v3M3.5 8h13" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
                              </svg>
                              <span x-text="selectedDateLabel()"></span>
                              <svg class="ojt-h-3.5 ojt-w-3.5 ojt-text-gray-400" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            </button>
                            <div x-show="dateOpen" x-cloak @click.outside="dateOpen = false"
                              x-transition:enter="ojt-transition ojt-duration-150 ojt-ease-out" x-transition:enter-start="ojt-opacity-0 ojt-translate-y-1" x-transition:enter-end="ojt-opacity-100 ojt-translate-y-0"
                              x-transition:leave="ojt-transition ojt-duration-100 ojt-ease-in" x-transition:leave-start="ojt-opacity-100" x-transition:leave-end="ojt-opacity-0"
                              class="ojt-absolute ojt-right-0 ojt-z-10 ojt-mt-1 ojt-rounded-lg ojt-border ojt-border-gray-200 ojt-bg-white ojt-p-1 ojt-shadow-md"
                              style="top: 100%; min-width: 11rem" role="listbox" aria-label="Filter jobs by date">
                              <template x-for="dateOption in dateOptions" :key="dateOption.key">
                                <button type="button" @click="setDateRange(dateOption.key)" role="option" :aria-selected="dateRange === dateOption.key"
                                  class="ojt-flex ojt-w-full ojt-items-center ojt-justify-between ojt-rounded-md ojt-px-3 ojt-py-2 ojt-text-left ojt-text-sm ojt-transition-colors"
                                  :class="dateRange === dateOption.key ? 'ojt-bg-primary-50 ojt-font-semibold ojt-text-primary-600' : 'ojt-text-gray-600 hover:ojt-bg-gray-50'">
                                  <span x-text="dateOption.label"></span>
                                  <svg x-show="dateRange === dateOption.key" class="ojt-h-4 ojt-w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m3.5 8 2.25 2.25L12.5 4.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                </button>
                              </template>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div x-show="error" x-text="error" class="ojt-mb-3 ojt-rounded-lg ojt-border ojt-border-danger-200 ojt-bg-danger-50 ojt-p-3 ojt-text-sm ojt-text-danger-700"></div>

                      <div class="ojt-overflow-hidden ojt-rounded-xl ojt-border ojt-border-gray-200 ojt-bg-white">
                        <template x-for="job in pagedJobs()" :key="job.id">
                          <div x-transition:enter="ojt-transition ojt-duration-200 ojt-ease-out" x-transition:enter-start="ojt-opacity-0 ojt-translate-y-1" x-transition:enter-end="ojt-opacity-100 ojt-translate-y-0" x-transition:leave="ojt-transition ojt-duration-150 ojt-ease-in" x-transition:leave-start="ojt-opacity-100" x-transition:leave-end="ojt-opacity-0" class="ojt-border-b ojt-border-gray-200 ojt-p-4 last:ojt-border-b-0 sm:ojt-p-5">
                            <div class="ojt-flex ojt-items-start ojt-gap-3">
                              <div class="ojt-flex ojt-h-9 ojt-w-9 ojt-flex-none ojt-items-center ojt-justify-center ojt-rounded-lg"
                                :class="iconClasses(job.status)">
                                <svg x-show="job.status === 'processing'" class="ojt-h-5 ojt-w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12a8 8 0 0 1 13.66-5.66L20 8.68M20 4v4.68h-4.68M20 12a8 8 0 0 1-13.66 5.66L4 15.32M4 20v-4.68h4.68" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                <svg x-show="job.status === 'paused'" class="ojt-h-4 ojt-w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 6v12M16 6v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                                <svg x-show="job.status === 'queued'" class="ojt-h-5 ojt-w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6" /><path d="M12 8v4l2.5 1.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg>
                                <svg x-show="job.status === 'done'" class="ojt-h-5 ojt-w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6" /><path d="m8.5 12 2.25 2.25L15.5 9.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                <svg x-show="job.status === 'failed'" class="ojt-h-5 ojt-w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 4 8 15H4L12 4Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" /><path d="M12 9v4M12 16h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                              </div>
                              <div class="ojt-min-w-0 ojt-flex-1">
                                <div class="ojt-flex ojt-flex-wrap ojt-items-center ojt-justify-between ojt-gap-2">
                                  <div class="ojt-flex ojt-min-w-0 ojt-flex-wrap ojt-items-center ojt-gap-2">
                                    <span class="ojt-truncate ojt-text-sm ojt-font-semibold ojt-text-gray-800" x-text="job.name"></span>
                                    <span class="ojt-inline-flex ojt-items-center ojt-gap-1 ojt-rounded-full ojt-px-2 ojt-py-0.5 ojt-text-[10px] ojt-font-bold ojt-uppercase" :class="statusClasses(job.status)">
                                      <span class="ojt-h-1.5 ojt-w-1.5 ojt-rounded-full" :class="statusDotClasses(job.status)"></span>
                                      <span x-text="statusLabel(job.status)"></span>
                                    </span>
                                  </div>
                                  <div class="ojt-flex ojt-items-center ojt-gap-2 ojt-text-xs ojt-font-semibold ojt-text-gray-500">
                                    <span :class="job.status === 'queued' ? 'ojt-animate-pulse' : ''" x-text="job.meta"></span>
                                    <button x-show="job.status === 'processing' && !job.paused" type="button" @click="confirmJobAction('pause', job.id)" class="ojt-inline-flex ojt-items-center ojt-gap-1 ojt-rounded-md ojt-border ojt-border-gray-200 ojt-bg-gray-50 ojt-px-2.5 ojt-py-1 ojt-text-xs ojt-font-medium ojt-text-gray-600 hover:ojt-bg-gray-100">
                                      <svg class="ojt-h-3 ojt-w-3" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7 5.5v9M13 5.5v9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg>
                                      Pause
                                    </button>
                                    <button x-show="job.status === 'paused'" type="button" @click="confirmJobAction('resume', job.id)" class="ojt-inline-flex ojt-items-center ojt-gap-1 ojt-rounded-md ojt-border ojt-border-gray-200 ojt-bg-gray-50 ojt-px-2.5 ojt-py-1 ojt-text-xs ojt-font-medium ojt-text-gray-600 hover:ojt-bg-gray-100">
                                      <svg class="ojt-h-3 ojt-w-3" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5.75 6 4.25-6 4.25V5.75Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" /></svg>
                                      Resume
                                    </button>
                                    <button x-show="job.status === 'processing' || job.status === 'paused' || job.status === 'queued'" type="button" @click="confirmJobAction('stop', job.id)" class="ojt-inline-flex ojt-items-center ojt-gap-1 ojt-rounded-md ojt-border ojt-border-danger-200 ojt-bg-danger-50 ojt-px-2.5 ojt-py-1 ojt-text-xs ojt-font-medium ojt-text-danger-600 hover:ojt-bg-danger-100">
                                      <svg class="ojt-h-3 ojt-w-3" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="6" y="6" width="8" height="8" rx="1" stroke="currentColor" stroke-width="1.5" /></svg>
                                      Stop
                                    </button>
                                    <button x-show="job.status === 'failed'" type="button" @click="confirmJobAction('retry', job.id)" class="ojt-inline-flex ojt-items-center ojt-gap-1 ojt-rounded-md ojt-border ojt-border-gray-200 ojt-bg-gray-50 ojt-px-2.5 ojt-py-1 ojt-text-xs ojt-font-medium ojt-text-gray-600 hover:ojt-bg-gray-100">
                                      <svg class="ojt-h-3 ojt-w-3" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M15.5 9a5.5 5.5 0 1 0-1.3 4.2M15.5 5.5V9h-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                      Retry
                                    </button>
                                    <button type="button" @click="openJobDetails(job)" class="ojt-inline-flex ojt-items-center ojt-gap-1 ojt-rounded-md ojt-border ojt-border-gray-200 ojt-bg-white ojt-px-2.5 ojt-py-1 ojt-text-xs ojt-font-medium ojt-text-gray-600 hover:ojt-border-primary-300 hover:ojt-bg-primary-50 hover:ojt-text-primary-600">
                                      <svg class="ojt-h-3 ojt-w-3" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5" /><path d="M10 9v4M10 6.5h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg>
                                      Details
                                    </button>
                                  </div>
                                </div>
                                <div x-show="job.status !== 'done'" class="ojt-mt-3 ojt-h-1.5 ojt-overflow-hidden ojt-rounded-full ojt-bg-gray-200">
                                  <div class="ojt-h-full ojt-rounded-full ojt-transition-all" :class="progressClasses(job.status)" :style="'width: ' + job.progress + '%' "></div>
                                </div>
                                <div class="ojt-mt-2 ojt-flex ojt-flex-wrap ojt-items-center ojt-gap-x-3 ojt-gap-y-1 ojt-text-xs ojt-text-gray-400">
                                  <span x-show="job.status === 'queued' && job.queuedAt" class="ojt-inline-flex ojt-items-center ojt-gap-1 ojt-leading-none">
                                    <svg class="ojt-h-3 ojt-w-3 ojt-flex-none ojt-text-gray-400" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                      <circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.7" />
                                      <path d="M10 6.5v3.75l2.25 1.25" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span>Queued <strong class="ojt-font-semibold ojt-text-gray-600" x-text="job.queuedAt"></strong></span>
                                  </span>
                                  <span x-show="job.started && job.started !== '—'" class="ojt-inline-flex ojt-items-center ojt-gap-1 ojt-leading-none">
                                    <svg class="ojt-h-3 ojt-w-3 ojt-flex-none ojt-text-gray-400" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                      <circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.7" />
                                      <path d="M10 6.5v3.75l2.25 1.25" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span>Started <strong class="ojt-font-semibold ojt-text-gray-600" x-text="job.started"></strong></span>
                                  </span>
                                  <span x-show="job.finished && job.status === 'done'">Finished <strong class="ojt-font-semibold ojt-text-gray-600" x-text="job.finished"></strong></span>
                                  <span x-show="job.stopped && job.status === 'failed'">Stopped <strong class="ojt-font-semibold ojt-text-gray-600" x-text="job.stopped"></strong></span>
                                  <span x-show="job.pausedAt && job.status === 'paused'">Paused <strong class="ojt-font-semibold ojt-text-gray-600" x-text="job.pausedAt"></strong></span>
                                  <span x-show="job.duration">Duration <strong class="ojt-font-semibold ojt-text-gray-600" x-text="job.duration"></strong></span>
                                  <span x-show="job.attempts > 0">Attempts <strong class="ojt-font-semibold ojt-text-gray-600" x-text="job.attempts"></strong></span>
                                </div>
                                <div x-show="job.error" class="ojt-mt-2 ojt-text-xs ojt-text-danger-600">
                                  <span class="ojt-font-semibold">Error:</span> <span x-text="job.error"></span>
                                </div>
                              </div>
                            </div>
                          </div>
                        </template>
                        <div x-show="pagedJobs().length === 0" x-transition:enter="ojt-transition ojt-duration-150" x-transition:enter-start="ojt-opacity-0" x-transition:enter-end="ojt-opacity-100" class="ojt-p-10 ojt-text-center ojt-text-sm ojt-text-gray-400">No jobs match your search.</div>
                      </div>

                      <div class="ojt-mt-4 ojt-flex ojt-flex-wrap ojt-items-center ojt-justify-between ojt-gap-3">
                        <span class="ojt-text-xs ojt-text-gray-500" x-text="pageInfo()"></span>
                        <div class="ojt-flex ojt-items-center ojt-gap-1.5">
                          <button type="button" @click="previousPage" :disabled="page === 1" class="ojt-flex ojt-h-8 ojt-w-8 ojt-items-center ojt-justify-center ojt-rounded-lg ojt-border ojt-border-gray-200 ojt-text-gray-400 ojt-transition-all ojt-duration-150 hover:ojt-border-primary-300 hover:ojt-text-primary-600 disabled:ojt-cursor-not-allowed disabled:ojt-opacity-50" aria-label="Previous page" title="Previous page">‹</button>
                          <template x-for="pageNumber in visiblePages()" :key="pageNumber">
                            <button type="button" @click="page = pageNumber; loadJobs()" :disabled="pageNumber === 'ellipsis-start' || pageNumber === 'ellipsis-end'" class="ojt-flex ojt-h-8 ojt-items-center ojt-justify-center ojt-rounded-lg ojt-border ojt-text-sm ojt-transition-all ojt-duration-150" :class="pageNumber === 'ellipsis-start' || pageNumber === 'ellipsis-end' ? 'ojt-w-5 ojt-border-transparent ojt-bg-transparent ojt-text-gray-400' : (page === pageNumber ? 'ojt-w-8 ojt-border-primary-500 ojt-bg-primary-600 ojt-text-white ojt-scale-105' : 'ojt-w-8 ojt-border-gray-200 ojt-bg-white ojt-text-gray-600 hover:ojt-border-primary-300 hover:ojt-bg-primary-50')" x-text="pageNumber === 'ellipsis-start' || pageNumber === 'ellipsis-end' ? '…' : pageNumber" :aria-label="pageNumber === 'ellipsis-start' || pageNumber === 'ellipsis-end' ? 'More pages' : 'Page ' + pageNumber"></button>
                          </template>
                          <button type="button" @click="nextPage" :disabled="page === totalPages()" class="ojt-flex ojt-h-8 ojt-w-8 ojt-items-center ojt-justify-center ojt-rounded-lg ojt-border ojt-border-gray-200 ojt-text-gray-600 ojt-transition-all ojt-duration-150 hover:ojt-border-primary-300 hover:ojt-text-primary-600 disabled:ojt-cursor-not-allowed disabled:ojt-opacity-50" aria-label="Next page" title="Next page">›</button>
                        </div>
                      </div>
                    </div>

                    <div x-show="view === 'scheduled'" x-transition:enter="ojt-transition ojt-duration-200 ojt-ease-out" x-transition:enter-start="ojt-opacity-0 ojt-translate-y-1" x-transition:enter-end="ojt-opacity-100 ojt-translate-y-0" x-transition:leave="ojt-transition ojt-duration-150 ojt-ease-in" x-transition:leave-start="ojt-opacity-100 ojt-translate-y-0" x-transition:leave-end="ojt-opacity-0 ojt-translate-y-1" class="ojt-overflow-hidden ojt-rounded-xl ojt-border ojt-border-gray-200 ojt-bg-white ojt-mt-4">
                      <div x-show="scheduleLoading" class="ojt-p-10 ojt-text-center ojt-text-sm ojt-text-gray-400">Loading schedules…</div>
                      <template x-for="schedule in schedules" :key="schedule.id">
                        <div class="ojt-border-b ojt-border-gray-200 last:ojt-border-b-0">
                          <div class="ojt-flex ojt-flex-wrap ojt-items-center ojt-gap-3 ojt-p-4 sm:ojt-p-5">
                          <div class="ojt-flex ojt-h-10 ojt-w-10 ojt-flex-none ojt-items-center ojt-justify-center ojt-rounded-lg ojt-bg-primary-50 ojt-text-primary-600">
                            <svg class="ojt-h-5 ojt-w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6" /><path d="M12 8v4l2.5 1.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg>
                          </div>
                          <div class="ojt-min-w-0 ojt-flex-1">
                            <div class="ojt-flex ojt-flex-wrap ojt-items-center ojt-gap-2">
                              <span class="ojt-text-sm ojt-font-semibold" :class="schedule.enabled ? 'ojt-text-gray-800' : 'ojt-text-gray-400'" x-text="schedule.name"></span>
                              <span x-show="schedule.lastStatus" class="ojt-inline-flex ojt-items-center ojt-rounded-full ojt-px-2 ojt-py-0.5 ojt-text-[10px] ojt-font-semibold" :class="scheduleLastStatusClasses(schedule.lastStatus)" x-text="schedule.lastStatusLabel"></span>
                            </div>
                            <div class="ojt-mt-1 ojt-flex ojt-flex-wrap ojt-items-center ojt-gap-x-2 ojt-gap-y-1 ojt-text-xs ojt-text-gray-400">
                              <span class="ojt-font-medium ojt-text-gray-600" x-text="schedule.human"></span><span class="ojt-text-gray-400" x-text="'(' + schedule.timezone + ')'" ></span><span>·</span><span>Next run: <strong class="ojt-font-semibold ojt-text-gray-600" x-text="schedule.next"></strong></span><span>·</span><span>Last run: <strong class="ojt-font-semibold ojt-text-gray-600" x-text="schedule.last"></strong></span>
                            </div>
                          </div>
                          <div class="ojt-flex ojt-items-center ojt-gap-3">
                            <div class="ojt-flex ojt-items-center ojt-gap-2">
                            <button type="button" @click="confirmScheduleToggle(schedule)" class="ojt-relative ojt-h-6 ojt-w-11 ojt-rounded-full ojt-transition-colors" :class="schedule.enabled ? 'ojt-bg-primary-600' : 'ojt-bg-gray-300'" :aria-label="schedule.enabled ? 'Pause schedule' : 'Activate schedule'">
                              <span class="ojt-absolute ojt-left-0 ojt-top-0.5 ojt-h-5 ojt-w-5 ojt-rounded-full ojt-bg-white ojt-shadow-sm ojt-transition-transform" :class="schedule.enabled ? 'ojt-translate-x-5' : 'ojt-translate-x-0.5'"></span>
                            </button>
                            <span class="ojt-hidden ojt-text-xs ojt-font-semibold sm:ojt-inline" :class="schedule.enabled ? 'ojt-text-primary-600' : 'ojt-text-gray-400'" x-text="schedule.enabled ? 'Active' : 'Paused'"></span>
                            </div>
                            <div class="ojt-flex ojt-items-center ojt-gap-1 ojt-border-l ojt-border-gray-200 ojt-pl-3">
                            <button type="button" @click="openScheduleHistory(schedule)" class="ojt-relative ojt-inline-flex ojt-h-9 ojt-w-9 ojt-items-center ojt-justify-center ojt-rounded-lg ojt-border ojt-border-gray-200 ojt-bg-white ojt-text-gray-500 ojt-transition-colors hover:ojt-border-primary-300 hover:ojt-bg-primary-50 hover:ojt-text-primary-600" aria-label="View schedule history" :title="'View schedule history' + (schedule.failedCount ? ' — ' + schedule.failedCount + ' failed run' + (schedule.failedCount === 1 ? '' : 's') : '')">
                              <svg class="ojt-h-5 ojt-w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4.25 8.75a5.75 5.75 0 1 1 1.6 4.05" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /><path d="M4.25 8.75V5.4m0 3.35h3.35" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /><path d="M10 7.25v3.05l2.15 1.3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                              <span x-show="schedule.failedCount > 0" class="ojt-absolute ojt-right-0.5 ojt-top-0.5 ojt-h-2 ojt-w-2 ojt-rounded-full ojt-bg-danger-500" aria-hidden="true"></span>
                            </button>
                            <button type="button" @click="confirmRunSchedule(schedule)" class="ojt-inline-flex ojt-h-9 ojt-w-9 ojt-items-center ojt-justify-center ojt-rounded-lg ojt-border ojt-border-gray-200 ojt-bg-white ojt-text-gray-500 ojt-transition-colors hover:ojt-border-primary-300 hover:ojt-bg-primary-50 hover:ojt-text-primary-600" aria-label="Run schedule now" title="Run schedule now">
                              <svg class="ojt-h-5 ojt-w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7 5.25 6 4.75-6 4.75V5.25Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" /></svg>
                            </button>
                            <button type="button" @click="editSchedule(schedule)" class="ojt-inline-flex ojt-h-9 ojt-w-9 ojt-items-center ojt-justify-center ojt-rounded-lg ojt-border ojt-border-gray-200 ojt-bg-white ojt-text-gray-500 ojt-transition-colors hover:ojt-border-primary-300 hover:ojt-bg-primary-50 hover:ojt-text-primary-600" aria-label="Edit schedule" title="Edit schedule">
                              <svg class="ojt-h-5 ojt-w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m12.75 4.25 3 3M5 15l.65-2.95L12.2 5.5a1.4 1.4 0 0 1 2 0l.3.3a1.4 1.4 0 0 1 0 2L7.95 14.35 5 15Z" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            </button>
                            </div>
                          </div>
                        </div>
                      </template>
                      <div x-show="!scheduleLoading && schedules.length === 0" class="ojt-p-10 ojt-text-center ojt-text-sm ojt-text-gray-400">
                        No schedules have been registered by the installed plugins.
                      </div>
                    </div>

                    <div x-show="view === 'settings'" x-transition:enter="ojt-transition ojt-duration-200 ojt-ease-out" x-transition:enter-start="ojt-opacity-0 ojt-translate-y-1" x-transition:enter-end="ojt-opacity-100 ojt-translate-y-0" x-transition:leave="ojt-transition ojt-duration-150 ojt-ease-in" x-transition:leave-start="ojt-opacity-100 ojt-translate-y-0" x-transition:leave-end="ojt-opacity-0 ojt-translate-y-1" class="ojt-mt-4">
                      <div class="ojt-flex ojt-flex-wrap ojt-items-center ojt-justify-between ojt-gap-4 ojt-rounded-xl ojt-border ojt-border-gray-200 ojt-bg-gray-50 ojt-px-4 ojt-py-3">
                        <div class="ojt-min-w-0">
                            <p class="ojt-text-sm ojt-font-semibold ojt-text-gray-800">Background job processing</p>
                            <p class="ojt-mt-1 ojt-text-xs ojt-text-gray-500">Allow WorkerBee to dispatch and process queued tasks.</p>
                        </div>
                        <div class="ojt-flex ojt-flex-none ojt-items-center ojt-gap-3">
                          <span class="ojt-text-sm ojt-font-semibold"
                                :class="jobsEnabled ? 'ojt-text-primary-600' : 'ojt-text-gray-500'"
                                x-text="jobsEnabled ? 'Enabled' : 'Disabled'"></span>
                          <button type="button" role="switch" @click="confirmToggleJobs" :aria-checked="jobsEnabled"
                            :class="jobsEnabled ? 'ojt-bg-primary-600' : 'ojt-bg-gray-300'"
                            class="ojt-relative ojt-inline-flex ojt-h-8 ojt-w-11 ojt-flex-none ojt-items-center ojt-overflow-hidden ojt-rounded-full ojt-p-1 ojt-transition-colors ojt-duration-200"
                            :title="jobsEnabled ? 'Disable background jobs' : 'Enable background jobs'"
                            :aria-label="jobsEnabled ? 'Disable background jobs' : 'Enable background jobs'">
                            <span :style="jobsEnabled ? 'transform: translateX(16px)' : 'transform: translateX(0)'"
                              class="ojt-inline-block ojt-h-5 ojt-w-5 ojt-rounded-full ojt-bg-white ojt-shadow ojt-transition-transform ojt-duration-200"></span>
                          </button>
                        </div>
                      </div>
                    </div>

                    <template x-if="selectedJob">
                      <div x-show="detailsOpen" x-cloak @keydown.escape.window="closeJobDetails()" @click="closeJobDetails()"
                        x-transition:enter="ojt-transition ojt-duration-200 ojt-ease-out" x-transition:enter-start="ojt-opacity-0" x-transition:enter-end="ojt-opacity-100"
                        x-transition:leave="ojt-transition ojt-duration-150 ojt-ease-in" x-transition:leave-start="ojt-opacity-100" x-transition:leave-end="ojt-opacity-0"
                        class="ojt-fixed ojt-inset-0 ojt-z-50 ojt-flex ojt-items-center ojt-justify-center ojt-bg-black ojt-bg-opacity-50 ojt-p-4">
                        <div @click.stop x-show="detailsOpen"
                          x-transition:enter="ojt-transition ojt-duration-200 ojt-ease-out" x-transition:enter-start="ojt-opacity-0 ojt-translate-y-1" x-transition:enter-end="ojt-opacity-100 ojt-translate-y-0"
                          class="ojt-w-full ojt-max-w-2xl ojt-overflow-y-auto ojt-overflow-x-hidden ojt-rounded-xl ojt-bg-white ojt-shadow-lg" style="max-height: 90vh">
                          <div class="ojt-flex ojt-items-start ojt-justify-between ojt-gap-4 ojt-border-b ojt-border-gray-200 ojt-p-5">
                            <div class="ojt-min-w-0">
                              <div class="ojt-flex ojt-flex-wrap ojt-items-center ojt-gap-2">
                                <h3 class="ojt-m-0 ojt-truncate ojt-text-lg ojt-font-bold ojt-text-gray-800" x-text="selectedJob.name"></h3>
                                <span class="ojt-inline-flex ojt-items-center ojt-gap-1 ojt-rounded-full ojt-px-2 ojt-py-0.5 ojt-text-[10px] ojt-font-bold ojt-uppercase" :class="statusClasses(selectedJob.status)">
                                  <span class="ojt-h-1.5 ojt-w-1.5 ojt-rounded-full" :class="statusDotClasses(selectedJob.status)"></span>
                                  <span x-text="statusLabel(selectedJob.status)"></span>
                                </span>
                              </div>
                              <p class="ojt-mt-1 ojt-text-xs ojt-text-gray-500" x-text="selectedJob.type || 'Background job'"></p>
                            </div>
                            <button type="button" @click="closeJobDetails()" class="ojt-inline-flex ojt-h-8 ojt-w-8 ojt-flex-none ojt-items-center ojt-justify-center ojt-rounded-md ojt-text-gray-400 hover:ojt-bg-gray-100 hover:ojt-text-gray-600" aria-label="Close job details" title="Close job details">
                              <svg class="ojt-h-4 ojt-w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5 5 10 10M15 5 5 15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg>
                            </button>
                          </div>
                          <div class="ojt-grid ojt-grid-cols-2 ojt-gap-3 ojt-p-5">
                            <div class="ojt-rounded-lg ojt-bg-gray-50 ojt-p-3">
                              <p class="ojt-text-xs ojt-text-gray-400">Status</p>
                              <p class="ojt-mt-1 ojt-text-sm ojt-font-semibold ojt-text-gray-700" x-text="statusLabel(selectedJob.status)"></p>
                            </div>
                            <div class="ojt-rounded-lg ojt-bg-gray-50 ojt-p-3">
                              <p class="ojt-text-xs ojt-text-gray-400">Progress</p>
                              <p class="ojt-mt-1 ojt-text-sm ojt-font-semibold ojt-text-gray-700" x-text="selectedJob.progress + '%' "></p>
                            </div>
                            <div class="ojt-rounded-lg ojt-bg-gray-50 ojt-p-3">
                              <p class="ojt-text-xs ojt-text-gray-400">Queue</p>
                              <p class="ojt-mt-1 ojt-text-sm ojt-font-semibold ojt-text-gray-700" x-text="selectedJob.queue || 'default'"></p>
                            </div>
                            <div class="ojt-rounded-lg ojt-bg-gray-50 ojt-p-3">
                              <p class="ojt-text-xs ojt-text-gray-400">Job ID</p>
                              <p class="ojt-mt-1 ojt-text-sm ojt-font-semibold ojt-text-gray-700" x-text="selectedJob.id"></p>
                            </div>
                            <div class="ojt-rounded-lg ojt-bg-gray-50 ojt-p-3">
                              <p class="ojt-text-xs ojt-text-gray-400">Queued</p>
                              <p class="ojt-mt-1 ojt-text-sm ojt-font-semibold ojt-text-gray-700" x-text="selectedJob.queuedAt || '—'"></p>
                            </div>
                            <div class="ojt-rounded-lg ojt-bg-gray-50 ojt-p-3">
                              <p class="ojt-text-xs ojt-text-gray-400">Started</p>
                              <p class="ojt-mt-1 ojt-text-sm ojt-font-semibold ojt-text-gray-700" x-text="selectedJob.started || '—'"></p>
                            </div>
                            <div class="ojt-rounded-lg ojt-bg-gray-50 ojt-p-3">
                              <p class="ojt-text-xs ojt-text-gray-400">Paused</p>
                              <p class="ojt-mt-1 ojt-text-sm ojt-font-semibold ojt-text-gray-700" x-text="selectedJob.pausedAt || '—'"></p>
                            </div>
                            <div class="ojt-rounded-lg ojt-bg-gray-50 ojt-p-3">
                              <p class="ojt-text-xs ojt-text-gray-400">Finished / Stopped</p>
                              <p class="ojt-mt-1 ojt-text-sm ojt-font-semibold ojt-text-gray-700" x-text="selectedJob.finished || selectedJob.stopped || '—'"></p>
                            </div>
                            <div class="ojt-rounded-lg ojt-bg-gray-50 ojt-p-3">
                              <p class="ojt-text-xs ojt-text-gray-400">Duration</p>
                              <p class="ojt-mt-1 ojt-text-sm ojt-font-semibold ojt-text-gray-700" x-text="selectedJob.duration || '—'"></p>
                            </div>
                            <div class="ojt-rounded-lg ojt-bg-gray-50 ojt-p-3">
                              <p class="ojt-text-xs ojt-text-gray-400">Attempts</p>
                              <p class="ojt-mt-1 ojt-text-sm ojt-font-semibold ojt-text-gray-700" x-text="selectedJob.attempts || 0"></p>
                            </div>
                          </div>
                          <div x-show="selectedJob.details && Object.keys(selectedJob.details).length" class="ojt-mx-5 ojt-mb-5 ojt-rounded-lg ojt-border ojt-border-gray-200 ojt-bg-gray-50 ojt-p-3">
                            <p class="ojt-text-xs ojt-font-semibold ojt-uppercase ojt-tracking-wide ojt-text-gray-500">Payload and details</p>
                            <pre class="ojt-mt-2 ojt-overflow-y-auto ojt-rounded-md ojt-bg-white ojt-p-3 ojt-font-mono ojt-text-xs ojt-text-gray-600" style="max-height: 14rem; white-space: pre-wrap" x-text="formatDetails(selectedJob.details)"></pre>
                          </div>
                          <div x-show="selectedJob.error" class="ojt-mx-5 ojt-mb-5 ojt-rounded-lg ojt-border ojt-border-danger-200 ojt-bg-danger-50 ojt-p-3 ojt-text-sm ojt-text-danger-700">
                            <p class="ojt-font-semibold">Error</p>
                            <p class="ojt-mt-1" x-text="selectedJob.error"></p>
                          </div>
                          <div class="ojt-flex ojt-justify-end ojt-border-t ojt-border-gray-200 ojt-p-4">
                            <button type="button" @click="closeJobDetails()" class="ojt-rounded-lg ojt-bg-primary-600 ojt-px-4 ojt-py-2 ojt-text-sm ojt-font-semibold ojt-text-white hover:ojt-bg-primary-700">Close</button>
                          </div>
                        </div>
                      </div>
                    </template>
                    </main>
                  </div>
                  </div>
                {/if}
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>


  </div>
</div>

<script>
  function settingForm(data = {}) {
    return {
      data: data,
      loading: false,
      async submit() {
        // trigger browser default validation 
        if (!this.$el.reportValidity()) {
          return;
        }

        try {
          this.loading = true;
          let formData = new FormData();

          Object.entries(this.data).forEach(([key, val]) => {
            formData.append(key, val)
          });

          let request = await fetch(
            baseUrl + ' /ojt/saveSettings', { method: "POST", body: formData });
          let response = await request.json();
          if (response.error) {
            throw response.msg;
            return;
          }
          ajaxResponse(response);
        } catch (e) {
          console.log(e);
        } finally {
          this.loading = false;
        }
      }
    }
  }

  function backgroundJobsMock() {
    return {
      view: 'queue',
      jobsEnabled: true,
      filter: 'all',
      query: '',
      dateRange: 'all',
      dateOpen: false,
      selectedJob: null,
      detailsOpen: false,
      page: 1,
      pageSize: 10,
      filterOptions: [
        { key: 'all', label: 'All' },
        { key: 'active', label: 'Active' },
        { key: 'done', label: 'Completed' },
        { key: 'failed', label: 'Failed' },
      ],
      dateOptions: [
        { key: 'all', label: 'All time' },
        { key: 'today', label: 'Today' },
        { key: '7d', label: 'Last 7 days' },
        { key: '30d', label: 'Last 30 days' },
        { key: 'month', label: 'This month' },
      ],
      jobs: [
        { id: 1, name: 'Reindex article metadata', status: 'processing', progress: 64, paused: false, meta: '64%', started: '14:32:05', finished: '', duration: '' },
        { id: 2, name: 'Generate DOI batch (Crossref)', status: 'processing', progress: 31, paused: false, meta: '31%', started: '14:33:40', finished: '', duration: '' },
        { id: 3, name: 'Send digest notifications', status: 'paused', progress: 12, paused: true, meta: 'Paused', started: '14:34:12', finished: '', duration: '' },
        { id: 4, name: 'Rebuild search index', status: 'queued', progress: 0, paused: false, meta: 'Waiting...', started: '—', finished: '', duration: '' },
        { id: 5, name: 'Export OAI records', status: 'queued', progress: 0, paused: false, meta: 'Waiting...', started: '—', finished: '', duration: '' },
        { id: 6, name: 'Optimize cover images', status: 'done', progress: 100, paused: false, meta: 'Completed', started: '14:05:11', finished: '14:07:48', duration: '2m 37s' },
        { id: 7, name: 'Nightly database backup', status: 'done', progress: 100, paused: false, meta: 'Completed', started: '02:00:00', finished: '02:18:33', duration: '18m 33s' },
        { id: 8, name: 'Sync ORCID profiles', status: 'done', progress: 100, paused: false, meta: 'Completed', started: '13:50:20', finished: '13:51:02', duration: '42s' },
        { id: 9, name: 'Import submissions (CSV)', status: 'failed', progress: 47, paused: false, meta: 'Failed at 47%', started: '14:20:00', finished: '14:22:14', duration: '2m 14s' },
        { id: 10, name: 'PDF galley conversion', status: 'failed', progress: 8, paused: false, meta: 'Failed at 8%', started: '14:28:31', finished: '14:28:49', duration: '18s' },
        { id: 11, name: 'Purge expired sessions', status: 'done', progress: 100, paused: false, meta: 'Completed', started: '03:00:00', finished: '03:00:42', duration: '42s' },
        { id: 12, name: 'Recompute citation counts', status: 'done', progress: 100, paused: false, meta: 'Completed', started: '01:15:00', finished: '01:41:20', duration: '26m 20s' },
        { id: 13, name: 'Warm PDF thumbnail cache', status: 'failed', progress: 22, paused: false, meta: 'Failed at 22%', started: '12:04:00', finished: '12:05:31', duration: '1m 31s' },
        { id: 14, name: 'Archive old issues', status: 'done', progress: 100, paused: false, meta: 'Completed', started: '22:00:00', finished: '22:47:10', duration: '47m 10s' },
      ],
      schedules: [
        { id: 1, name: 'Nightly database backup', cron: '0 2 * * *', human: 'Daily at 02:00', enabled: true, next: 'Tomorrow 02:00', last: 'Today 02:00' },
        { id: 2, name: 'Rebuild search index', cron: '0 */6 * * *', human: 'Every 6 hours', enabled: true, next: 'Today 18:00', last: 'Today 12:00' },
        { id: 3, name: 'Send digest notifications', cron: '0 6 * * 1', human: 'Mondays at 06:00', enabled: true, next: 'Mon 06:00', last: 'Last Mon 06:00' },
        { id: 4, name: 'Purge expired sessions', cron: '*/30 * * * *', human: 'Every 30 minutes', enabled: false, next: 'Paused', last: 'Today 03:00' },
        { id: 5, name: 'Recompute citation counts', cron: '0 1 * * 0', human: 'Sundays at 01:00', enabled: true, next: 'Sun 01:00', last: 'Last Sun 01:00' },
      ],
      activeCount() {
        return this.jobs.filter((job) => ['processing', 'paused', 'queued'].includes(job.status)).length;
      },
      totalPages() {
        return Math.max(1, Math.ceil(this.filteredJobs().length / this.pageSize));
      },
      visiblePages() {
        const total = this.totalPages();
        const current = Math.min(this.page, total);
        if (total <= 7) return Array.from({ length: total }, (_, index) => index + 1);
        if (current <= 4) return [1, 2, 3, 4, 'ellipsis-end', total];
        if (current >= total - 3) return [1, 'ellipsis-start', total - 3, total - 2, total - 1, total];
        return [1, 'ellipsis-start', current - 1, current, current + 1, 'ellipsis-end', total];
      },
      pageInfo() {
        const total = this.filteredJobs().length;
        if (!total) return 'Showing 0 of 0';
        const start = ((this.page - 1) * this.pageSize) + 1;
        const end = Math.min(this.page * this.pageSize, total);
        return 'Showing ' + start + '–' + end + ' of ' + total;
      },
      filteredJobs() {
        const query = this.query.trim().toLowerCase();
        return this.jobs.filter((job) => {
          const matchesQuery = !query || job.name.toLowerCase().includes(query);
          let matchesFilter = true;
          if (this.filter === 'active') matchesFilter = ['processing', 'paused', 'queued'].includes(job.status);
          if (this.filter === 'done') matchesFilter = job.status === 'done';
          if (this.filter === 'failed') matchesFilter = job.status === 'failed';
          return matchesQuery && matchesFilter;
        });
      },
      pagedJobs() {
        const start = (this.page - 1) * this.pageSize;
        return this.filteredJobs().slice(start, start + this.pageSize);
      },
      countFor(filter) {
        if (filter === 'all') return this.jobs.length;
        if (filter === 'active') return this.jobs.filter((job) => ['processing', 'paused', 'queued'].includes(job.status)).length;
        return this.jobs.filter((job) => job.status === filter).length;
      },
      setFilter(filter) {
        this.filter = filter;
        this.page = 1;
      },
      selectedDateLabel() {
        const option = this.dateOptions.find((item) => item.key === this.dateRange);
        return option ? option.label : 'All time';
      },
      setDateRange(range) {
        this.dateRange = range;
        this.dateOpen = false;
        this.page = 1;
      },
      openJobDetails(job) {
        this.selectedJob = job;
        this.detailsOpen = true;
      },
      closeJobDetails() {
        this.detailsOpen = false;
      },
      formatDetails(details) {
        try {
          return JSON.stringify(details || {}, null, 2);
        } catch (error) {
          return 'Unable to display job details.';
        }
      },
      previousPage() {
        this.page = Math.max(1, this.page - 1);
      },
      nextPage() {
        this.page = Math.min(this.totalPages(), this.page + 1);
      },
      setJobsEnabled(enabled) {
        this.jobsEnabled = enabled;
      },
      confirmToggleJobs() {
        this.setJobsEnabled(!this.jobsEnabled);
      },
      confirmJobAction(action, id) {
        const job = this.jobs.find((item) => item.id === id);
        if (!job) return;

        const copy = {
          pause: { title: 'Pause this job?', text: 'The job will remain in the queue until resumed.', confirm: 'Pause job', color: '#7c3aed', icon: 'question' },
          resume: { title: 'Resume this job?', text: 'The job will continue processing.', confirm: 'Resume job', color: '#7c3aed', icon: 'question' },
          stop: { title: 'Stop this job?', text: 'This job will be stopped and marked as failed.', confirm: 'Stop job', color: '#ef4444', icon: 'warning' },
          retry: { title: 'Retry this job?', text: 'A new attempt will be added to the queue.', confirm: 'Retry job', color: '#7c3aed', icon: 'question' },
        }[action];
        if (!copy) return;
        if (typeof Swal === 'undefined') {
          this[action + 'Job'](id);
          return;
        }

        Swal.fire({
          title: copy.title,
          text: job.name + '. ' + copy.text,
          icon: copy.icon,
          showCancelButton: true,
          confirmButtonText: copy.confirm,
          cancelButtonText: 'Cancel',
          confirmButtonColor: copy.color,
          reverseButtons: true,
        }).then((result) => {
          if (result.isConfirmed) this[action + 'Job'](id);
        });
      },
      confirmStopAllJobs() {
        if (typeof Swal === 'undefined') {
          this.stopAllJobs();
          return;
        }

        Swal.fire({
          title: 'Stop all active jobs?',
          text: 'All processing, paused, and queued jobs will be stopped.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Stop all jobs',
          cancelButtonText: 'Cancel',
          confirmButtonColor: '#ef4444',
          reverseButtons: true,
        }).then((result) => {
          if (result.isConfirmed) this.stopAllJobs();
        });
      },
      updateJob(id, changes) {
        const job = this.jobs.find((item) => item.id === id);
        if (job) Object.assign(job, changes);
      },
      pauseJob(id) {
        this.updateJob(id, { status: 'paused', paused: true, meta: 'Paused' });
      },
      resumeJob(id) {
        this.updateJob(id, { status: 'processing', paused: false, meta: this.jobs.find((job) => job.id === id).progress + '%' });
      },
      stopJob(id) {
        this.updateJob(id, { status: 'failed', paused: false, meta: 'Stopped' });
      },
      retryJob(id) {
        this.updateJob(id, { status: 'processing', paused: false, progress: 0, meta: '0%' });
      },
      stopAllJobs() {
        this.jobs.filter((job) => ['processing', 'paused', 'queued'].includes(job.status)).forEach((job) => {
          Object.assign(job, { status: 'failed', paused: false, meta: 'Stopped' });
        });
      },
      statusLabel(status) {
        return { processing: 'Processing', paused: 'Paused', queued: 'Queued', done: 'Done', failed: 'Failed' }[status];
      },
      iconClasses(status) {
        return { processing: 'ojt-bg-primary-50 ojt-text-primary-600', paused: 'ojt-bg-primary-50 ojt-text-primary-600', queued: 'ojt-bg-gray-100 ojt-text-gray-500', done: 'ojt-bg-primary-50 ojt-text-primary-600', failed: 'ojt-bg-primary-50 ojt-text-primary-600' }[status];
      },
      statusClasses(status) {
        return { processing: 'ojt-bg-primary-50 ojt-text-primary-700', paused: 'ojt-bg-warning-50 ojt-text-warning-700', queued: 'ojt-bg-gray-100 ojt-text-gray-600', done: 'ojt-bg-success-50 ojt-text-success-700', failed: 'ojt-bg-danger-50 ojt-text-danger-700' }[status];
      },
      statusDotClasses(status) {
        return { processing: 'ojt-bg-primary-500', paused: 'ojt-bg-warning-500', queued: 'ojt-bg-gray-500', done: 'ojt-bg-success-500', failed: 'ojt-bg-danger-500' }[status];
      },
      progressClasses(status) {
        return { processing: 'ojt-bg-primary-600 ojt-job-progress-processing', paused: 'ojt-bg-primary-600', queued: 'ojt-bg-gray-300', done: 'ojt-bg-primary-600', failed: 'ojt-bg-primary-600' }[status];
      },
    };
  }

  function backgroundJobs(config = {}) {
    return typeof createBackgroundJobs === 'function'
      ? createBackgroundJobs(config)
      : backgroundJobsMock(config);
  }
</script>
