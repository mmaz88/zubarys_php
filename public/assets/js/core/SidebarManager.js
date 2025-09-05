// public/assets/js/core/SidebarManager.js
/**
 * Manages the state of the main sidebar (collapsed or expanded)
 * and persists the state in localStorage.
 */
class SidebarManager {
  constructor() {
    this.isCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
    this.rootElement = document.documentElement;
    this.init();
  }

  /**
   * Initializes the sidebar manager by applying the initial state
   * and attaching the toggle event listener.
   */
  init() {
    this.applyState();
    document.addEventListener("DOMContentLoaded", () => {
      const toggleButton = document.getElementById("sidebar-toggle");
      if (toggleButton) {
        toggleButton.addEventListener("click", () => this.toggle());
      }
    });
  }

  /**
   * Toggles the collapsed/expanded state of the sidebar.
   */
  toggle() {
    this.isCollapsed = !this.isCollapsed;
    this.applyState();
    try {
      localStorage.setItem("sidebarCollapsed", this.isCollapsed);
    } catch (e) {
      console.error("Failed to save sidebar state to localStorage.", e);
    }
  }

  /**
   * Applies the 'sidebar-collapsed' class to the root element
   * based on the current state.
   */
  applyState() {
    this.rootElement.classList.toggle("sidebar-collapsed", this.isCollapsed);
  }
}

// Initialize the sidebar manager as soon as the script loads.
new SidebarManager();
