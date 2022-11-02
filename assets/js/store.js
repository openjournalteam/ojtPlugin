Spruce.store("plugins", {
  page: "dashboard",
  data: [],
  isLoading: true,
  search: "",
  type: "all",
  async init() {
    this.isLoading = true;

    await this.fetchInstalledPlugin();

    this.isLoading = false;
  },
  get listPlugins() {
    let plugins = this.data;
    if (this.search) {
      plugins = plugins.filter((plugin) =>
        plugin.name.toLowerCase().includes(this.search.toLowerCase())
      );
    }
    switch (this.type) {
      case "themes":
        plugins = plugins.filter(
          (plugin) => plugin.productType == "plugins.themes"
        );
        break;
      case "plugins":
        plugins = plugins.filter(
          (plugin) => plugin.productType != "plugins.themes"
        );
        break;
    }

    return plugins;
  },
  async fetchInstalledPlugin() {
    let res = await fetch(currentUrl + "getInstalledPlugin");

    if (res.status != 200) {
      return ajaxError();
    }

    let plugins = await res.json();

    this.data = plugins;
  },
  getPluginByProduct(product) {
    return this.data.find((plugin) => plugin.product == product);
  },
  get activePlugins() {
    return this.data.filter((plugin) => plugin.enabled && plugin.page);
  },
  resetFilter() {
    this.search = "";
    this.type = "all";
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

    this.data = this.data.map((plugin) =>
      plugin.product === currentPlugin.product
        ? {
            ...plugin,
            enabled: !plugin.enabled,
          }
        : plugin
    );
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
  async uninstall(plugin) {
    Swal.fire({
      title: "Are you sure you wish to delete this plugin from the system?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Yes, delete it!",
      showLoaderOnConfirm: true,
      preConfirm: async () => {
        const formData = new FormData();
        formData.append("plugin", JSON.stringify(plugin));

        return await fetch(currentUrl + "uninstallPlugin", {
          method: "POST",
          body: formData,
        })
          .then((response) => {
            return response.json();
          })
          .catch(function (error) {
            ajaxError(error);
          });
      },
      allowOutsideClick: () => !Swal.isLoading(),
    }).then((result) => {
      console.log(result);
      if (result.isConfirmed) {
        if (result.value.error) {
          ajaxError(result.value);
          return;
        }

        this.fetchInstalledPlugin();
        ajaxResponse(result.value);
        return;
        // show success message then reload page
      }
    });
  },
});

Spruce.store(
  "checkUpdate",
  {
    updateAvailable: false,
    lastChecked: null,
    data: {},
    checkUpdate: async function () {
      try {
        if (!this.isTimeToCheckUpdate()) return;
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

        this.lastChecked = Date.now();
      } catch (error) {}
    },
    isTimeToCheckUpdate() {
      if (!this.lastChecked) return true;

      var dateNow = Date.now();

      var difference = dateNow - this.lastChecked;
      var hoursDifference = Math.floor(difference / 1000 / 60);

      if (hoursDifference > 60) {
        return true;
      }

      return false;
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
  },
  true
);
