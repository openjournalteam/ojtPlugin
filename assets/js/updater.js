function OjtPluginUpdater() {
  return {
    updateAvailable: false,
    checkUpdate: async (pluginPage) => {
      let urlCheckUpdate = `${baseUrl}/${pluginPage}/check_update`,
        request = await fetch(urlCheckUpdate),
        response = request.json();
      this.updateAvailable = response?.update_available;
    },
    update: async (pluginPage) => {
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
            .error((error) => {
              Swal.showValidationMessage(`Request failed: ${error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading(),
      }).then((result) => {
        if (result.isConfirmed) {
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
