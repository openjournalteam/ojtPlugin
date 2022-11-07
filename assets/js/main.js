$(document).ready(function () {
  initiateAjaxContent();
});
var currentUrl = baseUrl + "/" + thisPage + "/";
var ajaxOptions = {
  error: ajaxError,
  success: ajaxResponse,
  dataType: "json",
};

String.prototype.trimEllip = function (length) {
  return this.length > length ? this.substring(0, length) + "..." : this;
};

const loading_menu = `<div class="loading-menu ojt-flex ojt-items-center ojt-h-80">
                        <div class="lds-roller ojt-self-center ojt-mx-auto">
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                      </div>`;
const loadingSmall = `<svg xmlns="http://www.w3.org/2000/svg"
xmlns:xlink="http://www.w3.org/1999/xlink" style="margin: auto; display: block; shape-rendering: auto;" width="24px" height="24px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
<rect x="17.5" y="30" width="15" height="40" fill="#1d3f72">
  <animate attributeName="y" repeatCount="indefinite" dur="1s" calcMode="spline" keyTimes="0;0.5;1" values="18;30;30" keySplines="0 0.5 0.5 1;0 0.5 0.5 1" begin="-0.2s"></animate>
  <animate attributeName="height" repeatCount="indefinite" dur="1s" calcMode="spline" keyTimes="0;0.5;1" values="64;40;40" keySplines="0 0.5 0.5 1;0 0.5 0.5 1" begin="-0.2s"></animate>
</rect>
<rect x="42.5" y="30" width="15" height="40" fill="#5699d2">
  <animate attributeName="y" repeatCount="indefinite" dur="1s" calcMode="spline" keyTimes="0;0.5;1" values="20.999999999999996;30;30" keySplines="0 0.5 0.5 1;0 0.5 0.5 1" begin="-0.1s"></animate>
  <animate attributeName="height" repeatCount="indefinite" dur="1s" calcMode="spline" keyTimes="0;0.5;1" values="58.00000000000001;40;40" keySplines="0 0.5 0.5 1;0 0.5 0.5 1" begin="-0.1s"></animate>
</rect>
<rect x="67.5" y="30" width="15" height="40" fill="#d8ebf9">
  <animate attributeName="y" repeatCount="indefinite" dur="1s" calcMode="spline" keyTimes="0;0.5;1" values="20.999999999999996;30;30" keySplines="0 0.5 0.5 1;0 0.5 0.5 1"></animate>
  <animate attributeName="height" repeatCount="indefinite" dur="1s" calcMode="spline" keyTimes="0;0.5;1" values="58.00000000000001;40;40" keySplines="0 0.5 0.5 1;0 0.5 0.5 1"></animate>
</rect>
</svg>`;

const Toast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: false,
  didOpen: (toast) => {
    toast.addEventListener("mouseenter", Swal.stopTimer);
    toast.addEventListener("mouseleave", Swal.resumeTimer);
  },
});

function toastFire(icon, message) {
  Toast.fire({
    icon: icon,
    title: message,
  });
}

function ajaxError(responseText, statusText, xhr, form) {
  toastFire(
    "error",
    responseText?.msg ??
      responseText?.message ??
      "Something went wrong, please contact us"
  );
}

function ajaxResponse(responseText, statusText, xhr, form) {
  if (responseText.error == 1) ajaxError(responseText);

  if (responseText.error == 0) {
    let msg = responseText.msg ? responseText.msg : "Success ...";

    Toast.fire({
      icon: "success",
      title: msg,
    });

    let clearform = $(form).attr("clearform");
    if (typeof clearform !== "undefined" && clearform !== false) {
      $(form).clearForm();
    }
    runCallBack();
  }

  //run callback
  function runCallBack() {
    let callback = $(form).attr("callback");
    if (typeof callback !== typeof undefined && callback !== false) {
      window[callback]();
    }
  }
}

async function loadAjax(name, dom = false) {
  dom = dom ? dom : $("#main-menu");

  dom.html(loading_menu);

  url = baseUrl + "/" + name;

  xhr = $.get(url);
  xhr.done(function (resp) {
    if (resp.css) {
      $.each(resp.css, function (key, value) {
        addCss(value);
      });
    }
    if (resp.js) {
      $.each(resp.js, function (key, value) {
        addJs(value);
      });
    }
    dom.html(resp.html);
  });

  xhr.fail(ajaxError);
}

// init ajax form
$(document).on("submit", ".ajax_form", function (e) {
  e.preventDefault();

  submitButton = $(this).find(`[type='submit']`);

  if (submitButton.attr("disabled")) {
    return;
  }

  $(this).ajaxSubmit(ajaxOptions);
});

$(document).on("click", ".menu_item", function (e) {
  let page = $(this).attr("page");
  let dom = $(this).attr("dom");
  if (!page || page == "undefined") {
    console.error("page not found");
    return;
  }

  if (dom) {
    loadAjax(page, $(dom));
    return;
  }

  loadAjax(page);
});

function addCss(href, empty = true) {
  let moduleCss = $("#moduleCss");
  if (empty) moduleCss.empty();

  // prevent adding the same css twice
  isExist = $("link[href='" + href + "']").length ? true : false;
  if (isExist) return;

  var link = $("<link />", {
    rel: "stylesheet",
    type: "text/css",
    href: href,
  });
  moduleCss.prepend(link);
}

function addJs(src, empty = true) {
  // prevent adding the same js twice
  let moduleJs = $("#moduleJs");
  if (empty) moduleJs.empty();

  isExist = $("script[src='" + src + "']").length ? true : false;
  if (isExist) return;

  var link = $("<script></script>", {
    src: src,
  });
  moduleJs.append(link);
}

function initiateAjaxContent(dom) {
  if (dom) {
    let page = dom.attr("page");
    loadAjax(page, dom);
    return;
  }

  $(".ajax_load").each(function () {
    let dom = $(this);
    let page = dom.attr("page");
    loadAjax(page, dom);
  });
}
