// public/assets/js/core/app.js

window.App = (function (config) {
  "use strict";

  const safeConfig = {
    baseUrl: config.baseUrl || "",
    apiUrl: config.apiUrl || "/api",
  };

  const loadingOverlay = document.getElementById("spa-loading-overlay");

  /**
   * Shows the main page loading overlay.
   */
  const showPageLoader = () => {
    if (loadingOverlay) loadingOverlay.classList.remove("hidden");
  };

  /**
   * Hides the main page loading overlay.
   */
  const hidePageLoader = () => {
    if (loadingOverlay) loadingOverlay.classList.add("hidden");
  };

  /**
   * A robust API fetch wrapper.
   * @param {string} endpoint - The API endpoint (e.g., 'users').
   * @param {object} options - Fetch options (method, body, etc.).
   * @returns {Promise<object>} - The JSON response from the API.
   */
  const api = async (endpoint, options = {}) => {
    const url = `${safeConfig.apiUrl}/${endpoint.replace(/^\//, "")}`;
    const defaultHeaders = {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
    };

    const fetchConfig = { ...options };
    fetchConfig.headers = { ...defaultHeaders, ...options.headers };

    if (fetchConfig.body) {
      if (fetchConfig.body instanceof FormData) {
        delete fetchConfig.headers["Content-Type"];
      } else if (
        typeof fetchConfig.body === "object" &&
        fetchConfig.body !== null
      ) {
        fetchConfig.body = JSON.stringify(fetchConfig.body);
        fetchConfig.headers["Content-Type"] = "application/json";
      }
    }

    try {
      const response = await fetch(url, fetchConfig);
      const result = await response.json();
      if (!response.ok) {
        const error = new Error(
          result.message || `API Error: ${response.status}`
        );
        error.response = result;
        error.status = response.status;
        throw error;
      }
      return result;
    } catch (error) {
      console.error(`API call to ${endpoint} failed:`, error);
      if (error instanceof SyntaxError) {
        const newError = new Error(
          "The server returned an invalid response. Check server logs."
        );
        newError.status = 500;
        throw newError;
      }
      throw error;
    }
  };

  /**
   * Toggles the loading state of a button.
   * @param {HTMLElement} button - The button element.
   * @param {boolean} isLoading - Whether to show the loading state.
   * @param {string} [loadingText=''] - Text to show while loading (optional).
   */
  const setButtonLoading = (button, isLoading, loadingText = "") => {
    if (!button) return;
    if (isLoading) {
      button.disabled = true;
      button.classList.add("loading");
      if (loadingText) {
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = `<span>${loadingText}</span>`;
      }
    } else {
      button.disabled = false;
      button.classList.remove("loading");
      if (button.dataset.originalText) {
        button.innerHTML = button.dataset.originalText;
        delete button.dataset.originalText;
      }
    }
  };

  /**
   * Helper for debouncing function calls.
   */
  const debounce = (func, delay = 350) => {
    let timeoutId;
    return (...args) => {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
  };

  /**
   * Safely escapes HTML to prevent XSS.
   */
  const escapeHTML = (str) => {
    const val = String(str || "");
    return val.replace(
      /[&<>"']/g,
      (m) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#039;",
        }[m])
    );
  };

  /**
   * A notification system using the existing alert.js.
   */
  const notify = {
    success: (message) =>
      window.AlertSystem?.show({
        variant: "success",
        title: "Success",
        message,
      }),
    error: (message) =>
      window.AlertSystem?.show({
        variant: "danger",
        title: "Error",
        message,
      }),
    info: (message) =>
      window.AlertSystem?.show({
        variant: "info",
        title: "Information",
        message,
      }),
  };

  /**
   * Shows a confirmation modal and returns a promise.
   * @param {object} options - Configuration for the modal.
   * @returns {Promise<void>} - Resolves on confirm, rejects on cancel.
   */
  const confirm = (options = {}) => {
    const modalEl = document.getElementById("confirmation-modal");
    if (!modalEl) {
      console.error("Confirmation modal element not found in main.php.");
      return Promise.reject(new Error("Modal element not found"));
    }
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const titleEl = modalEl.querySelector("#confirmation-modal-title");
    const messageEl = modalEl.querySelector("#confirmation-modal-message");
    const confirmBtn = modalEl.querySelector("#confirmation-modal-confirm");

    const config = {
      title: "Confirm Action",
      message: "Are you sure you want to proceed?",
      confirmText: "Confirm",
      confirmVariant: "danger", // Maps to btn-danger
      ...options,
    };

    titleEl.textContent = config.title;
    messageEl.innerHTML = config.message;
    confirmBtn.textContent = config.confirmText;
    confirmBtn.className = `btn btn-${config.confirmVariant}`;

    modal.show();

    return new Promise((resolve, reject) => {
      const onConfirm = () => {
        cleanup();
        resolve();
      };
      const onCancel = () => {
        cleanup();
        reject();
      };
      const cleanup = () => {
        confirmBtn.removeEventListener("click", onConfirm);
        modalEl.removeEventListener("hidden.bs.modal", onCancel);
        modal.hide();
      };

      confirmBtn.addEventListener("click", onConfirm, { once: true });
      modalEl.addEventListener("hidden.bs.modal", onCancel, { once: true });
    });
  };

  const pageInitializers = {};
  let currentPageCleanup = null;

  return {
    api,
    debounce,
    escapeHTML,
    notify,
    confirm,
    setButtonLoading,
    pageInitializers,
    showPageLoader, // Expose the new function
    hidePageLoader, // Expose the new function
    get currentPageCleanup() {
      return currentPageCleanup;
    },
    set currentPageCleanup(fn) {
      currentPageCleanup = fn;
    },
  };
})(window.AppConfig || {});

document.addEventListener("DOMContentLoaded", () => {
  // This listener is for non-AJAX navigation to give visual feedback.
  // It should IGNORE forms that will be handled by AJAX.
  document.body.addEventListener("click", (e) => {
    const link = e.target.closest("a");
    if (
      link &&
      link.href &&
      link.href.startsWith(window.location.origin) &&
      !link.href.includes("#") &&
      link.target !== "_blank" &&
      !e.ctrlKey &&
      !e.metaKey &&
      !link.hasAttribute("data-no-loader")
    ) {
      App.showPageLoader();
    }
  });

  // Hide overlay if the user navigates back (e.g., using browser back button)
  window.addEventListener("pageshow", (event) => {
    if (event.persisted) {
      App.hidePageLoader();
    }
  });
});
