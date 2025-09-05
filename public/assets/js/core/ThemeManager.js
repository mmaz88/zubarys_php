// public/assets/js/core/ThemeManager.js
/**
 * Manages the application's theme by applying the selected theme
 * from localStorage and handling user theme changes.
 */
class ThemeManager {
  constructor() {
    this.currentTheme = localStorage.getItem("theme") || "light";
    this.init();
  }

  /**
   * Initializes the theme manager by applying the current theme
   * and attaching event listeners to the theme switcher.
   */
  init() {
    this.applyTheme(this.currentTheme);
    document.addEventListener("DOMContentLoaded", () => {
      document.body.addEventListener("click", (e) => {
        const themeSwitcher = e.target.closest("[data-set-theme]");
        if (themeSwitcher) {
          e.preventDefault();
          const newTheme = themeSwitcher.dataset.setTheme;
          this.setTheme(newTheme);
        }
      });
    });
  }

  /**
   * Applies a theme to the root <html> element.
   * @param {string} themeName - The name of the theme to apply.
   */
  applyTheme(themeName) {
    document.documentElement.setAttribute("data-theme", themeName);
  }

  /**
   * Sets a new theme and saves it to localStorage.
   * @param {string} themeName - The name of the new theme.
   */
  setTheme(themeName) {
    this.currentTheme = themeName;
    this.applyTheme(themeName);
    try {
      localStorage.setItem("theme", themeName);
    } catch (e) {
      console.error("Failed to save theme to localStorage.", e);
    }
  }
}

// Initialize the theme manager as soon as the script loads.
new ThemeManager();
