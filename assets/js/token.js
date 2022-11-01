function token_handler(formData = {}) {
  return {
    formData: formData,
    quota_added: 20,
    current_quota: 55,
    activationButton: {
      loading: false,
      textLoading: "<i class='fas fa-circle-notch fa-spin mr-2'></i>Loading...",
      text: "<i class='fas fa-paper-plane mr-2'></i>Submit",
    },
    async submit() {
      if (!this.$refs.form.reportValidity()) return;

      try {
        this.activationButton.loading = true;
        let formData = new FormData();

        Object.entries(this.formData).forEach(([key, val]) => {
          if (key == "template") val = get_template_id(val);
          formData.append(key, val);
        });

        let request = await fetch(
          baseUrl + " /letterofacceptance/submit_token",
          {
            method: "POST",
            body: formData,
          }
        );
        let response = await request.json();

        if (response.error) throw response;

        this.page = "finish_setup";

        ajaxResponse(response);
      } catch (e) {
        ajaxError(e);
      } finally {
        this.activationButton.loading = false;
      }
    },
  };
}
