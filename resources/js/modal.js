const Default = {
  placement: "center",
  backdropClasses:
    "ojt-bg-gray-900 ojt-bg-opacity-50 dark:ojt-bg-opacity-80 ojt-fixed ojt-inset-0 ojt-z-40",
  onHide: () => {},
  onShow: () => {},
  onToggle: () => {},
};
class Modal {
  constructor(targetEl = null, options = {}) {
    this._targetEl = targetEl;
    this._options = { ...Default, ...options };
    this._isHidden = true;
    this._init();
  }

  _init() {
    this._getPlacementClasses().map((c) => {
      this._targetEl.classList.add(c);
    });
  }

  _createBackdrop() {
    if (this._isHidden) {
      const backdropEl = document.createElement("div");
      backdropEl.setAttribute("modal-backdrop", "");
      backdropEl.classList.add(...this._options.backdropClasses.split(" "));
      document.querySelector("body").append(backdropEl);
    }
  }

  _destroyBackdropEl() {
    if (!this._isHidden) {
      document.querySelector("[modal-backdrop]").remove();
    }
  }

  _getPlacementClasses() {
    switch (this._options.placement) {
      // top
      case "top-left":
        return ["ojt-justify-start", "ojt-items-start"];
      case "top-center":
        return ["ojt-justify-center", "ojt-items-start"];
      case "top-right":
        return ["ojt-justify-end", "ojt-items-start"];

      // center
      case "center-left":
        return ["ojt-justify-start", "ojt-items-center"];
      case "center":
        return ["ojt-justify-center", "ojt-items-center"];
      case "center-right":
        return ["ojt-justify-end", "ojt-items-center"];

      // bottom
      case "bottom-left":
        return ["ojt-justify-start", "ojt-items-end"];
      case "bottom-center":
        return ["ojt-justify-center", "ojt-items-end"];
      case "bottom-right":
        return ["ojt-justify-end", "ojt-items-end"];

      default:
        return ["ojt-justify-center", "ojt-items-center"];
    }
  }

  toggle() {
    if (this._isHidden) {
      this.show();
    } else {
      this.hide();
    }

    // callback function
    this._options.onToggle(this);
  }

  show() {
    this._targetEl.classList.add("ojt-flex");
    this._targetEl.classList.remove("ojt-hidden");
    this._targetEl.setAttribute("aria-modal", "true");
    this._targetEl.setAttribute("role", "dialog");
    this._targetEl.removeAttribute("aria-hidden");
    this._createBackdrop();
    this._isHidden = false;

    // callback function
    this._options.onShow(this);
  }

  hide() {
    this._targetEl.classList.add("ojt-hidden");
    this._targetEl.classList.remove("ojt-flex");
    this._targetEl.setAttribute("aria-hidden", "true");
    this._targetEl.removeAttribute("aria-modal");
    this._targetEl.removeAttribute("role");
    this._destroyBackdropEl();
    this._isHidden = true;

    // callback function
    this._options.onHide(this);
  }
}

window.Modal = Modal;

const getModalInstance = (id, instances) => {
  if (instances.some((modalInstance) => modalInstance.id === id)) {
    return instances.find((modalInstance) => modalInstance.id === id);
  }
  return false;
};

function initModal() {
  let modalInstances = [];
  document.querySelectorAll("[ojt-modal-toggle]").forEach((el) => {
    const modalId = el.getAttribute("ojt-modal-toggle");
    const modalEl = document.getElementById(modalId);
    const placement = modalEl.getAttribute("ojt-modal-placement");

    if (modalEl) {
      if (
        !modalEl.hasAttribute("aria-hidden") &&
        !modalEl.hasAttribute("aria-modal")
      ) {
        modalEl.setAttribute("aria-hidden", "true");
      }
    }

    let modal = null;
    if (getModalInstance(modalId, modalInstances)) {
      modal = getModalInstance(modalId, modalInstances);
      modal = modal.object;
    } else {
      modal = new Modal(modalEl, {
        placement: placement ? placement : Default.placement,
      });
      modalInstances.push({
        id: modalId,
        object: modal,
      });
    }

    if (
      modalEl.hasAttribute("ojt-modal-show") &&
      modalEl.getAttribute("ojt-modal-show") === "true"
    ) {
      modal.show();
    }

    el.addEventListener("click", () => {
      modal.toggle();
    });
  });
}

if (document.readyState !== "loading") {
  // DOMContentLoaded event were already fired. Perform explicit initialization now
  initModal();
} else {
  // DOMContentLoaded event not yet fired, attach initialization process to it
  document.addEventListener("DOMContentLoaded", initModal);
}

export default Modal;
