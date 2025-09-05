// public/assets/js/tenants/tenants-form.js
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("tenant-form");
  if (!form) {
    return; // Exit if not on a page with this form
  }
  const saveBtn = document.getElementById("save-tenant-btn");

  const displayFormErrors = (errors) => {
    const errorDiv = document.getElementById("form-errors");
    const errorSummary = Object.values(errors).join("<br>");
    errorDiv.innerHTML = errorSummary;
    errorDiv.classList.remove("d-none");
  };

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    App.setButtonLoading(saveBtn, true, "Saving...");

    try {
      const result = await App.api(
        form.getAttribute("action").replace(/^\/api\//, ""),
        {
          method: "POST",
          body: new FormData(form),
        }
      );
      App.notify.success(result.message);
      setTimeout(() => (window.location.href = "/tenants"), 1000);
    } catch (error) {
      if (error.status === 422 && error.response.errors) {
        displayFormErrors(error.response.errors);
      } else {
        App.notify.error(error.message || "An unexpected error occurred.");
      }
    } finally {
      App.setButtonLoading(saveBtn, false);
    }
  });
});
