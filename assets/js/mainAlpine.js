// akses alpine x-data melalui id
function alpineComponent(id) {
  return document.getElementById(id).__x.$data;
}

function pluginMenu() {
  return {
    page: "dashboard",
    plugins: [],
    // get activePlugins() {
    //   return this.plugins.filter((plugin) => plugin.enabled && plugin.page);
    // },
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
    isLoading: true,
    plugins: null,
    async fetchInstalledPlugin() {
      this.isLoading = true;
      let res = await fetch(currentUrl + "getInstalledPlugin");

      if (res.status != 200) {
        return ajaxError();
      }

      let plugins = await res.json();

      this.plugins = plugins;

      this.passPluginsToMenu();

      this.isLoading = false;
    },
    getPluginByProduct(product) {
      return this.plugins.find((plugin) => plugin.product == product);
    },
    async togglePlugin(currentPlugin) {
      const formData = new FormData();
      formData.append("className", currentPlugin.className);
      formData.append("productType", currentPlugin.productType);
      formData.append("pluginFolder", currentPlugin.product);
      formData.append("enabled", !currentPlugin.enabled);

      let response = await fetch(currentUrl + "toggleInstalledPlugin", {
        method: "POST",
        body: formData,
      });

      let data = await response.json();

      ajaxResponse(data);

      this.plugins = this.plugins.map((plugin) =>
        plugin.product === currentPlugin.product
          ? {
              ...plugin,
              enabled: !plugin.enabled,
            }
          : plugin
      );

      this.passPluginsToMenu();
    },
    passPluginsToMenu() {
      alpineComponent("pluginMenu").plugins = this.plugins;
    },
    async resetSetting(pluginName) {
      var swal = await Swal.fire({
        title: "Are you sure you wish to reset this plugin setting?",
        showCancelButton: true,
        confirmButtonText: `Yes`,
      });

      if (!swal.isConfirmed) {
        return;
      }

      let response = await fetch(currentUrl + "resetSetting/" + pluginName);

      if (res.status != 200) {
        ajaxError();
        return;
      }

      let data = await response.json();

      ajaxResponse(data);
    },
  };
}

function pluginGallery() {
  return {
    loading: true,
    error: false,
    search: "",
    plugins: [],
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

      alpineComponent("pluginInstalled").fetchInstalledPlugin();
      alpineComponent("ojt-setting").tab = 1;
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

      alpineComponent("pluginInstalled").fetchInstalledPlugin();
      alpineComponent("ojt-setting").tab = 1;
    },
    isValidURL(string) {
      var res = string.match(
        /(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/g
      );
      return res !== null;
    },
  };
}
