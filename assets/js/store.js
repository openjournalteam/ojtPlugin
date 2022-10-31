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
    // if (this.loading == true) {
    //   Toast.fire({
    //     icon: "info",
    //     title: "Please Wait, still Processing ...",
    //   });
    //   return;
    // }

    // this.loading = true;

    // const formData = new FormData();
    // formData.append("plugin", JSON.stringify(plugin));
    // formData.append("resetSetting", this.resetSetting);

    // let response = await fetch(currentUrl + "uninstallPlugin", {
    //   method: "POST",
    //   body: formData,
    // }).catch(function (error) {
    //   this.loading = false;
    //   ajaxError(error);
    // });

    // let data = await response.json();

    // ajaxResponse(data);

    // alpineComponent("pluginGallery").plugins = alpineComponent(
    //   "pluginGallery"
    // ).plugins.map((plug) =>
    //   plug.token === plugin.token ? { ...plug, installed: false } : plug
    // );

    // this.close();

    // this.$store.plugins.fetchInstalledPlugin();
  },
});
