// public/assets/js/alert.js
(function () {
  "use strict";

  class AlertSystem {
    constructor() {
      this.container = null;
      this.iconMap = {
        success: "checkmark-circle-outline",
        danger: "alert-circle-outline",
        warning: "warning-outline",
        info: "information-circle-outline",
      };
      this.classMap = {
        success: "flash-message-success",
        danger: "flash-message-error",
        warning: "flash-message-warning",
        info: "flash-message-info",
      };

      // This ensures the container is created only once when needed.
      this._setupContainer();
    }

    /**
     * Finds or creates the main container for all alert messages.
     * @private
     */
    _setupContainer() {
      const containerId = "flash-messages-container";
      this.container = document.getElementById(containerId);
      if (this.container) return;

      this.container = document.createElement("div");
      this.container.id = containerId;
      this.container.className = "flash-messages";
      document.body.appendChild(this.container);
    }

    /**
     * Creates the DOM element for a single alert.
     * @private
     * @param {object} config - The alert configuration.
     * @returns {HTMLElement} The alert element.
     */
    _createAlertElement(config) {
      const alertEl = document.createElement("div");
      const variantClass = this.classMap[config.variant] || this.classMap.info;
      alertEl.className = `flash-message ${variantClass}`;

      const iconName = this.iconMap[config.variant] || this.iconMap.info;

      alertEl.innerHTML = `
                <div class="flash-message-icon">
                    <ion-icon name="${iconName}"></ion-icon>
                </div>
                <div class="flash-message-content">
                    <strong class="flash-message-title">${config.title}</strong>
                    <p class="flash-message-body">${config.message}</p>
                </div>
                <button class="flash-message-close" aria-label="Close">&times;</button>
            `;
      return alertEl;
    }

    /**
     * Displays a new alert message.
     * @param {object} options - Configuration for the alert.
     * @param {string} options.message - The main message text.
     * @param {string} [options.variant='info'] - 'success', 'danger', 'warning', or 'info'.
     * @param {string} [options.title='Notification'] - The title of the alert.
     * @param {number} [options.duration=5000] - Duration in ms before auto-closing.
     * @param {boolean} [options.autoClose=true] - Whether to close automatically.
     */
    show(options = {}) {
      const config = {
        message: "This is a default message.",
        variant: "info",
        title: "Notification",
        duration: 5000,
        autoClose: true,
        ...options,
      };

      const alertEl = this._createAlertElement(config);
      this.container.appendChild(alertEl);

      // Animate in
      requestAnimationFrame(() => {
        alertEl.style.opacity = "1";
        alertEl.style.transform = "translateX(0)";
      });

      const dismiss = () => {
        alertEl.style.opacity = "0";
        alertEl.style.transform = "translateX(20px)";
        // Wait for animation to finish before removing from DOM
        alertEl.addEventListener("transitionend", () => alertEl.remove(), {
          once: true,
        });
      };

      if (config.autoClose) {
        setTimeout(dismiss, config.duration);
      }

      alertEl
        .querySelector(".flash-message-close")
        .addEventListener("click", dismiss);
    }
  }

  // --- Singleton Initialization ---
  // This ensures there is only one instance of the AlertSystem,
  // and it's attached to the window object as soon as the script loads.
  if (!window.AlertSystem) {
    window.AlertSystem = new AlertSystem();
  }
})();
