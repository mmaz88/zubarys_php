// public/assets/js/users/users-form.js

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("user-form");
  if (!form) {
    return; // Exit if the form isn't on this page.
  }

  const saveBtn = document.getElementById("save-user-btn");

  /**
   * Clears all existing validation error styles and messages from the form.
   */
  const clearFormErrors = () => {
    form.querySelectorAll(".is-invalid").forEach((el) => {
      el.classList.remove("is-invalid");
    });
    form.querySelectorAll(".invalid-feedback").forEach((el) => {
      el.remove();
    });
  };

  /**
   * Displays validation errors by highlighting fields and adding specific
   * messages below them.
   * @param {object} errors - The errors object from the API response (e.g., {email: "Email is required."})
   */
  const displayFormErrors = (errors) => {
    clearFormErrors();

    // Use the global toast system for a summary notification, as requested.
    const firstError = Object.values(errors)[0];
    App.notify.error(firstError || "Please correct the errors below.");

    Object.entries(errors).forEach(([field, message]) => {
      // Find the input element by its 'name' attribute.
      const inputEl = form.querySelector(`[name="${field}"]`);
      if (inputEl) {
        // Add the red highlight class.
        inputEl.classList.add("is-invalid");

        // Create and insert the specific error message below the input.
        const errorDiv = document.createElement("div");
        errorDiv.className = "invalid-feedback";
        errorDiv.textContent = message;
        inputEl.parentNode.insertBefore(errorDiv, inputEl.nextSibling);
      }
    });

    // Focus on the first field that has an error.
    const firstInvalidField = form.querySelector(".is-invalid");
    if (firstInvalidField) {
      firstInvalidField.focus();
      firstInvalidField.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  };

  /**
   * Handles the form submission via AJAX, with proper success and error handling.
   */
  const handleFormSubmit = async (e) => {
    e.preventDefault(); // Prevent the browser from navigating away.
    clearFormErrors(); // Clear old errors on a new attempt.

    App.setButtonLoading(saveBtn, true, "Saving...");

    try {
      const result = await App.api(
        form.getAttribute("action").replace(/^\/api\//, ""),
        {
          method: "POST",
          body: new FormData(form),
        }
      );

      App.notify.success(result.message || "User saved successfully.");
      setTimeout(() => {
        window.location.href = "/users"; // Redirect to user list on success.
      }, 1000);
    } catch (error) {
      console.error("Error saving user:", error);
      // If the API returns a 422 Validation Failed status...
      if (error.status === 422 && error.response?.errors) {
        // ...use our new, user-friendly display function.
        displayFormErrors(error.response.errors);
      } else {
        // For all other errors, use the global toast notifier.
        App.notify.error(
          error.message || "An unexpected server error occurred."
        );
      }
    } finally {
      App.setButtonLoading(saveBtn, false);
    }
  };

  form.addEventListener("submit", handleFormSubmit);
});
