function OjtPluginUpdater(pluginPage) {
  return {
    updateAvailable: false,
    checkUpdate: async function () {
      let urlCheckUpdate = `${baseUrl}/${pluginPage}/check_update`,
        request = await fetch(urlCheckUpdate),
        response = await request.json();
      this.updateAvailable = response?.update_available;
    },
    update: async function () {
      if (!this.updateAvailable) {
        return;
      }
      Swal.fire({
        title: "Are you sure want to update plugin ?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes",
        showLoaderOnConfirm: true,
        preConfirm: async () => {
          let urlUpdate = `${baseUrl}/${pluginPage}/update`;
          return fetch(urlUpdate)
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
          if (result.value.error) {
            toastFire("error", result.value.msg);
            return;
          }
          toastFire("success", result.value.msg);
          location.reload();
        }
      });
    },
  };
}
