Spruce.store("plugins", {
  plugins: [],
  isLoading: false,
  async fetchInstalledPlugin() {
    this.isLoading = true;
    let res = await fetch(currentUrl + "getInstalledPlugin");

    if (res.status != 200) {
      return ajaxError();
    }

    let plugins = await res.json();

    this.plugins = plugins;

    this.isLoading = false;
  },
  getPluginByProduct(product) {
    return this.plugins.find((plugin) => plugin.product == product);
  },
  get activePlugins() {
    return this.plugins.filter((plugin) => plugin.enabled && plugin.page);
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
});
