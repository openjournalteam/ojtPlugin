// akses alpine x-data melalui id
function alpineComponent(id) {
  return document.getElementById(id).__x.$data;
}
const utama = () => {
  const getTheme = () => {
    if (window.localStorage.getItem("dark")) {
      return JSON.parse(window.localStorage.getItem("dark"));
    }
    return (
      !!window.matchMedia &&
      window.matchMedia("(prefers-color-scheme: dark)").matches
    );
  };

  const setTheme = (value) => {
    window.localStorage.setItem("dark", value);
  };

  return {
    menu: "Dashboard",
    loading: true,
    isDark: false,
    init() {
      this.$store.checkUpdate.checkUpdate();
      this.$store.plugins.init();
      this.$nextTick(() => {
        autoAnimate(document.getElementById("plugin-menu-list"));
      });
    },
    toggleTheme() {
      this.isDark = !this.isDark;
      setTheme(this.isDark);
    },
    setLightTheme() {
      this.isDark = false;
      setTheme(this.isDark);
    },
    setDarkTheme() {
      this.isDark = true;
      setTheme(this.isDark);
    },
    isSettingsPanelOpen: false,
    openSettingsPanel() {
      this.isSettingsPanelOpen = true;
      this.$nextTick(() => {
        this.$refs.settingsPanel.focus();
      });
    },
    isNotificationsPanelOpen: false,
    openNotificationsPanel() {
      this.isNotificationsPanelOpen = true;
      this.$nextTick(() => {
        this.$refs.notificationsPanel.focus();
      });
    },
    isSearchPanelOpen: false,
    openSearchPanel() {
      this.isSearchPanelOpen = true;
      this.$nextTick(() => {
        this.$refs.searchInput.focus();
      });
    },
    isMobileSubMenuOpen: false,
    openMobileSubMenu() {
      this.isMobileSubMenuOpen = true;
      this.$nextTick(() => {
        this.$refs.mobileSubMenu.focus();
      });
    },
    isMobileMainMenuOpen: false,
    openMobileMainMenu() {
      this.isMobileMainMenuOpen = true;
      this.$nextTick(() => {
        this.$refs.mobileMainMenu.focus();
      });
    },
  };
};

function pluginMenu() {
  return {
    page: "dashboard",
    plugins: [],
    init() {
      autoAnimate(document.getElementById("plugin-menu-list"));
    },
  };
}

function dashboard() {
  return {
    tab: "plugin-installed",
    activeTabClass:
      "ojt-inline-block ojt-p-4 ojt-w-full  ojt-bg-gray-50 hover:ojt-bg-gray-100 focus:ojt-outline-none ojt-text-primary-600 hover:ojt-text-primary-600 ojt-border-primary-600 dark:ojt-border-primary-500",
    inactiveTabClass:
      "ojt-inline-block ojt-p-4 ojt-w-full ojt-bg-gray-50 hover:ojt-bg-gray-100 focus:ojt-outline-none ojt-text-gray-500 hover:ojt-text-gray-600 ojt-border-gray-100 hover:ojt-border-gray-300",
  };
}

function checkUpdate() {
  return {
    updateAvailable: false,
    data: {},
    checkUpdate: async function () {
      try {
        let res = await fetch(
          "https://openjournaltheme.com/index.php/wp-json/openjournalvalidation/v1/ojtplugin/check_update",
          {
            mode: "cors",
          }
        );
        let ojtPlugin = await res.json();

        this.data = ojtPlugin;

        if (ojtPlugin.latest_version > ojtPluginVersion) {
          this.updateAvailable = true;
        }
      } catch (error) {}
    },
    doUpdate() {
      Swal.fire({
        title: "Are you sure want to Update Plugin?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, update it!",
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          const formData = new FormData();
          formData.append("ojtPlugin", JSON.stringify(this.data));

          return fetch(currentUrl + "updatePanel", {
            method: "POST",
            body: formData,
          })
            .then((response) => {
              return response.json();
            })
            .catch((error) => {
              Swal.showValidationMessage(`Request failed: ${error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading(),
      }).then((result) => {
        if (result.isConfirmed) {
          // show success message then reload page
          Swal.fire(result.value.msg).then(() => {
            if (result.value.error) {
              return;
            }
            location.reload();
          });
        }
      });
    },
  };
}

function pluginInstalled() {
  return {
    async init() {
      this.$nextTick(() => {
        if (this.$refs.tablepluginlist) {
          autoAnimate(this.$refs.tablepluginlist, {
            duration: 300,
            // Easing for motion (default: 'ease-in-out')
            // easing: "ease-in-out",
          });
        }
      });
    },
  };
}

function pluginGallery() {
  return {
    loading: true,
    error: false,
    search: "",
    plugins: [],
    async init() {
      await this.fetchPlugins();
      this.$nextTick(() => {
        if (this.$refs.gallerylist) {
          autoAnimate(this.$refs.gallerylist, {
            // duration: 500,
            // Easing for motion (default: 'ease-in-out')
            easing: "ease-in-out",
          });
        }
      });
    },
    async reload() {
      this.error = false;
      this.fetchPlugins();
    },
    async fetchPlugins() {
      try {
        this.loading = true;
        let res = await fetch(currentUrl + "getPluginGalleryList");

        let response = await res.json();

        if (response.error) {
          throw response;
        }

        this.plugins = response;
      } catch (error) {
        this.loading = false;
        this.error = true;
        ajaxError(error);
        return;
      } finally {
        this.loading = false;
      }
    },
    get filteredPlugins() {
      if (!this.search) {
        return this.plugins;
      }

      let plugins = this.plugins.filter((plugin) => {
        return plugin.name.toLowerCase().includes(this.search.toLowerCase());
      });

      return plugins;
    },
  };
}

function modalPlugin() {
  return {
    show: false,
    plugin: null,
    resetSetting: false,
    loading: false,
    installing: false,
    uninstalling: false,
    updating: false,
    key: "",
    close() {
      this.show = false;
      this.key = "";
      this.loading = false;
      this.resetSetting = false;
    },
    showPlugin(plugin) {
      this.plugin = plugin;
      this.show = true;
    },
    async installPlugin(plugin, update = false) {
      if (this.key == "" && !update) {
        Toast.fire({
          icon: "info",
          title: "Please insert license Key ..",
        });
        return;
      }

      if (this.loading == true) {
        Toast.fire({
          icon: "info",
          title: "Please Wait, still Processing ...",
        });
        return;
      }

      this.loading = true;

      const formData = new FormData();
      formData.append("plugin", JSON.stringify(plugin));
      formData.append("license", this.key);
      if (update) {
        formData.append("update", true);
      }

      let response = await fetch(currentUrl + "installPlugin", {
        method: "POST",
        body: formData,
      }).catch(function (error) {
        this.loading = false;
        ajaxError(error);
        return;
      });

      let data = await response.json();
      if (data.error == 1) {
        this.loading = false;
        console.log(data);
        ajaxError(data);
        return;
      }

      ajaxResponse(data);

      alpineComponent("pluginGallery").plugins = alpineComponent(
        "pluginGallery"
      ).plugins.map((plug) =>
        plug.token === plugin.token ? { ...plug, installed: true } : plug
      );

      this.close();

      this.$store.plugins.fetchInstalledPlugin();
    },
    async uninstall(plugin) {
      if (this.loading == true) {
        Toast.fire({
          icon: "info",
          title: "Please Wait, still Processing ...",
        });
        return;
      }

      this.loading = true;

      const formData = new FormData();
      formData.append("plugin", JSON.stringify(plugin));
      formData.append("resetSetting", this.resetSetting);

      let response = await fetch(currentUrl + "uninstallPlugin", {
        method: "POST",
        body: formData,
      }).catch(function (error) {
        this.loading = false;
        ajaxError(error);
      });

      let data = await response.json();

      ajaxResponse(data);

      alpineComponent("pluginGallery").plugins = alpineComponent(
        "pluginGallery"
      ).plugins.map((plug) =>
        plug.token === plugin.token ? { ...plug, installed: false } : plug
      );

      this.close();

      this.$store.plugins.fetchInstalledPlugin();
    },
    isValidURL(string) {
      var res = string.match(
        /(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/g
      );
      return res !== null;
    },
  };
}
