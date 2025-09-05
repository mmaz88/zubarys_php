// public/assets/js/login.js
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("login-form");
  if (!form) {
    return; // Early exit if the login form is not on this page
  }

  const submitButton = form.querySelector('button[type="submit"]');

  form.addEventListener("submit", async (event) => {
    event.preventDefault(); // Prevent default form submission

    // Show loading state
    submitButton.classList.add("btn-loading");
    submitButton.disabled = true;

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    try {
      const response = await fetch("/api/auth/login", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(data),
      });

      const result = await response.json();

      if (result.success) {
        // On successful login, redirect to the dashboard
        window.location.href = "/dashboard";
      } else {
        // Show error message using the alert system
        let errorMessage = result.message || "An unknown error occurred.";
        if (result.errors) {
          errorMessage = Object.values(result.errors).join("<br>");
        }

        window.AlertSystem.show({
          variant: "danger",
          title: "Login Failed",
          message: errorMessage,
          position: "top-center",
        });
      }
    } catch (error) {
      // Handle network or other fetch errors
      console.error("Login request failed:", error);
      window.AlertSystem.show({
        variant: "danger",
        title: "Network Error",
        message: "Could not connect to the server. Please try again later.",
        position: "top-center",
      });
    } finally {
      // Restore button state
      submitButton.classList.remove("btn-loading");
      submitButton.disabled = false;
    }
  });
});
