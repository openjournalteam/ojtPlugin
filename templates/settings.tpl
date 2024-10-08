<div class="ojt-w-auto ojt-max-w-screen-lg 2xl:ojt-mx-auto ojt-bg-white ojt-rounded-lg ojt-border ojt-shadow-md">
  <div id="ojt-settings" class="ojt-p-4 ojt-bg-white ojt-rounded-lg md:ojt-p-8">
    <div class="ojt-flex ojt-items-center ojt-justify-between ojt-mb-6">
      <h2
        class="ojt-text-3xl ojt-font-extrabold ojt-tracking-tight ojt-text-gray-900 ojt-flex ojt-items-center ojt-gap-2">
        <svg class="ojt-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 24" fill="currentColor">
          <path
            d="m7.304 2.305c-1.585 1.702-1.146 4.422-1.146 4.422 1.174 1.187 2.802 1.923 4.603 1.923.069 0 .138-.001.207-.003h-.01c.058.002.127.003.195.003 1.801 0 3.43-.735 4.604-1.922l.001-.001s.431-2.683-1.12-4.387c.971-.541 1.542-1.322 1.306-1.853-.268-.599-1.45-.652-2.646-.119-.43.19-.799.43-1.124.72l.004-.004c-.367-.072-.789-.113-1.22-.114h-.001c-.407.001-.805.037-1.192.104l.042-.006c-.314-.279-.675-.514-1.069-.689l-.027-.011c-1.196-.531-2.38-.48-2.646.119-.236.519.305 1.279 1.238 1.818zm12.028 12.39c-.196-.043-.439-.08-.687-.103l-.025-.002c0-.064.011-.124.011-.19-.005-1.025-.141-2.017-.391-2.962l.018.082c.05.002.11.004.169.004.578 0 1.125-.132 1.613-.367l-.022.01c1.2-.534 1.949-1.45 1.68-2.045s-1.45-.652-2.646-.119c-.517.225-.956.538-1.317.924l-.002.002c-.249-.6-.517-1.11-.824-1.592l.025.041c-1.366 1.146-3.085 1.915-4.972 2.126l-.043.004v9.649c0 .53-.43.96-.96.96s-.96-.43-.96-.96v-9.645c-1.928-.216-3.645-.985-5.025-2.143l.015.012c-.271.42-.531.906-.752 1.413l-.026.067c-.353-.353-.77-.642-1.232-.847l-.026-.01c-1.2-.531-2.38-.48-2.646.119s.487 1.513 1.68 2.045c.475.229 1.033.363 1.622.363h.043-.002c-.231.861-.366 1.85-.37 2.871v.003c0 .066.011.127.013.195-.202.022-.41.053-.623.098-1.558.32-2.752 1.128-2.664 1.793s1.424.945 2.986.62c.203-.042.4-.094.593-.152.274 1.261.731 2.377 1.349 3.384l-.027-.047c-.416.217-.773.484-1.08.799l-.001.001c-.96.956-1.344 2.117-.866 2.596s1.639.09 2.595-.864c.255-.255.48-.54.668-.85l.011-.02c1.201 1.236 2.869 2.012 4.719 2.043h.006c1.884-.032 3.575-.83 4.781-2.095l.003-.003c.204.35.439.652.708.92.954.956 2.117 1.344 2.596.866s.09-1.638-.866-2.593c-.328-.333-.708-.613-1.128-.826l-.025-.011c.586-.958 1.04-2.072 1.297-3.259l.013-.071c.215.067.441.131.677.182 1.561.32 2.897.047 2.987-.62s-1.111-1.471-2.67-1.793z">
          </path>
        </svg>
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
                  <!-- event handler set state to 'tab1' and add conditional :class for active state -->
                  <a @click.prevent="tab = 'tab1'" href="#"
                    class="ojt-inline-block ojt-py-2 ojt-font-medium ojt-bg-white ojt-text-primary-500 ojt-border-b-2 ojt-border-primary-500 ojt-gap-4"
                    :class="{ ' ojt-bg-white ojt-text-primary-500 ojt-border-b-2 ojt-border-primary-500' : tab === 'tab1' }">
                    <span>General</span>
                  </a>
                </li>
                <li class="ojt--mb-px">
                  <!-- event handler set state to 'tab1' and add conditional :class for active state -->
                  <a @click.prevent="tab = 'tab2'" href="#"
                    class="ojt-inline-block ojt-py-2 ojt-font-medium ojt-bg-white ojt-text-primary-500 ojt-border-b-2 ojt-border-primary-500 ojt-gap-4"
                    :class="{ ' ojt-bg-white ojt-text-primary-500 ojt-border-b-2 ojt-border-primary-500' : tab === 'tab2' }">
                    <span>Theme</span>
                  </a>
                </li>
              </ul>
              <div class="ojt-py-4 ojt-pt-4 ojt-text-left ojt-bg-white ojt-content" id="settings-tab-content">
                {* <!-- show tab1 only -->
                <div x-show="tab==='tab1'" class="ojt-text-gray-500">
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
                    <form x-data='settingForm({$settings|@json_encode})' method="POST">
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
                <div x-show="tab==='tab2'" class="ojt-text-gray-500">
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
</script>