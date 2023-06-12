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

  const updateQueryString = (key, value) => {
    let url = new URL(window.location.href);
    url.searchParams.set(key, value);
    window.history.pushState({}, "", url);
  };

  const removeQueryString = (key) => {
    let url = new URL(window.location.href);

    if (!url.searchParams.get(key)) return;

    url.searchParams.delete(key);
    window.history.pushState({}, "", url);
  };

  return {
    menu: "Dashboard",
    loading: true,
    isDark: false,
    async init() {
      this.$store.checkUpdate.checkUpdate();
      await this.$store.plugins.init();

      this.$nextTick(() => {
        autoAnimate(document.getElementById("plugin-menu-list"));
      });

      this.renderQueryTab();
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
    toggleMainMenu(page, menu = "Dashboard") {
      this.menu = menu;
      this.$store.plugins.page = page;

      switch (menu) {
        case "Plugin":
          updateQueryString("tab", page);
          break;
        default:
          removeQueryString("tab");
          break;
      }
    },
    async renderQueryTab() {
      const queryTab = new URLSearchParams(window.location.search).get("tab");

      if (queryTab) {
        const activePlugins = Object.values(this.$store.plugins.activePlugins);

        if (
          activePlugins.length >= 1 &&
          activePlugins.find((plugin) => plugin.page == queryTab)
        ) {
          this.menu = "Plugin";
          await loadAjax(queryTab);
          this.$store.plugins.page = queryTab;
        }
      }
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
        // if (this.$refs.tablepluginlist) {
        //   autoAnimate(this.$refs.tablepluginlist, {
        //     duration: 300,
        //     // Easing for motion (default: 'ease-in-out')
        //     // easing: "ease-in-out",
        //   });
        // }
      });
    },
  };
}

function pluginGallery() {
  return {
    loading: true,
    error: false,
    filter: {
      type: "all",
      category: "all",
    },
    search: "",
    plugins: [],
    async init() {
      this.loading = true;
      try {
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
      } catch (error) {
      } finally {
        this.loading = false;
      }
    },
    async reload() {
      this.error = false;
      this.fetchPlugins();
    },
    async fetchPlugins() {
      this.loading = true;

      try {
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
    get list() {
      let plugins = this.plugins.filter((plugin) => {
        let search = true;
        let type = true;
        let category = true;

        if (this.search) {
          search = plugin.name
            .toLowerCase()
            .includes(this.search.toLowerCase());
        }
        if (this.filter.type != "all") {
          type = plugin.type.toLowerCase() == this.filter.type;
        }
        if (this.filter.category != "all") {
          category = plugin.category.toLowerCase() == this.filter.category;
        }

        return search && type && category;
      });

      return plugins;
    },
    reset() {
      this.search = "";
      this.filter.type = "all";
      this.filter.category = "all";
    },
    async installPlugin(plugin) {
      try {
        switch (plugin.category) {
          case "PAID":
            input = "text";
            inputAttributes = {
              required: "",
            };
            inputLabel = "License key from the plugin you purchase.";
            break;
          default:
            input = null;
            inputAttributes = {};
            inputLabel = null;

            break;
        }

        let result = await Swal.fire({
          title: `Install ${plugin.name} ?`,
          icon: "warning",
          input: input,
          inputValue: plugin.license,
          inputLabel: inputLabel,
          inputAttributes: inputAttributes,
          inputPlaceholder: "Insert License Key.",
          customClass: {
            title: "!ojt-text-lg",
            inputLabel: "!ojt-text-base !ojt-justify-start",
          },
          showCancelButton: true,
          // confirmButtonColor: "#d33",
          // cancelButtonColor: "#3085d6",
          confirmButtonText: "Yes, install!",
          showLoaderOnConfirm: true,
          preConfirm: (license) => {
            if (plugin.category != "PAID") {
              license = null;
            }

            return this.submitInstall(plugin, license);
          },
          allowOutsideClick: () => !Swal.isLoading(),
        });

        if (!result.isConfirmed) return;

        if (result.value.error) throw result.value.msg;

        this.$store.plugins.fetchInstalledPlugin();
        this.plugins = this.plugins.map((plug) =>
          plug.token === plugin.token
            ? { ...plug, installed: true, update: false }
            : plug
        );
        ajaxResponse(result.value);

        return;
      } catch (error) {
        console.log(error);
        ajaxError({
          error: 1,
          msg: error,
        });
      }
    },
    async updatePlugin(plugin) {
      try {
        let result = await Swal.fire({
          title: `Update ${plugin.name} ?`,
          icon: "warning",
          customClass: {
            title: "!ojt-text-lg",
            inputLabel: "!ojt-text-base !ojt-justify-start",
          },
          showCancelButton: true,
          // confirmButtonColor: "#d33",
          // cancelButtonColor: "#3085d6",
          confirmButtonText: "Yes, update!",
          showLoaderOnConfirm: true,
          preConfirm: () => this.submitInstall(plugin, null, true),
          allowOutsideClick: () => !Swal.isLoading(),
        });

        if (!result.isConfirmed) return;

        if (result.value.error) throw result.value.msg;

        this.$store.plugins.fetchInstalledPlugin();
        this.plugins = this.plugins.map((plug) =>
          plug.token === plugin.token
            ? { ...plug, installed: true, update: false }
            : plug
        );
        ajaxResponse(result.value);

        return;
      } catch (error) {
        console.log(error);
        ajaxError({
          error: 1,
          msg: error,
        });
      }
    },
    async submitInstall(plugin, license = null, isUpdate = false) {
      const formData = new FormData();
      formData.append("plugin", JSON.stringify(plugin));
      if (license) {
        formData.append("license", license);
      }
      if (isUpdate) {
        formData.append("update", isUpdate);
      }

      try {
        let response = await fetch(currentUrl + "installPlugin", {
          method: "POST",
          body: formData,
        });

        if (!response.ok)
          // or check for response.status
          throw new Error(response.statusText);

        return await response.json();
      } catch (error) {
        return {
          error: 1,
          msg: "There is a problem with the installed plugin, please contact us.",
        };
      }
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
