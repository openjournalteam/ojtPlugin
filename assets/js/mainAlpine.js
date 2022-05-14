// akses alpine x-data melalui id
function alpineComponent(id) {
  return document.getElementById(id).__x.$data;
}

function pluginMenu() {
  return {
    page: "dashboard",
    plugins: [],
  };
}

function checkUpdate() {
  return {
    updateAvailable: false,
    data: {},
    checkUpdate: async function () {
      let res = await fetch(
        "https://demo.ini-sudah.online/index.php/wp-json/openjournalvalidation/v1/ojtpanel/check_update",
        {
          mode: "cors",
        }
      );
      // let res = await fetch("http://localhost/update.json", {
      //   mode: "cors",
      // });
      let ojtPlugin = await res.json();

      this.data = ojtPlugin;

      if (ojtPlugin.latest_version > ojtPluginVersion) {
        this.updateAvailable = true;
      }
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
