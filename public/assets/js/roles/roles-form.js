// public/assets/js/roles/roles-form.js
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("role-form");
  if (!form) return;

  const saveBtn = document.getElementById("save-role-btn");
  const permissionsGrid = document.getElementById("permissions-grid");
  const searchInput = document.getElementById("permission-search-input");
  const noResultsMessage = document.getElementById("no-permissions-found");
  const editPageContainer = document.getElementById("role-edit-page-container");
  const isEditMode = !!editPageContainer;

  /**
   * Pre-populates the form with data for editing a role.
   */
  const populateFormForEdit = async () => {
    if (!isEditMode) return;
    const roleId = editPageContainer.dataset.roleId;
    try {
      App.showPageLoader();
      const result = await App.api(`roles/${roleId}`);
      const data = result.data;
      if (data.permissions && Array.isArray(data.permissions)) {
        data.permissions.forEach((permissionId) => {
          const checkbox = permissionsGrid.querySelector(
            `input.permission-checkbox[value="${permissionId}"]`
          );
          if (checkbox) checkbox.checked = true;
        });
        updateAllGroupCheckboxes();
      }
    } catch (error) {
      App.notify.error(error.message || "Failed to load role details.");
    } finally {
      App.hidePageLoader();
    }
  };

  /**
   * Handles the live filtering of permission cards based on search input.
   */
  const handleSearch = () => {
    const query = searchInput.value.toLowerCase().trim();
    let visibleCount = 0;
    permissionsGrid
      .querySelectorAll(".permission-group-card")
      .forEach((card) => {
        const searchTerm = card.dataset.searchTerm || "";
        const isVisible = searchTerm.includes(query);
        card.style.display = isVisible ? "" : "none";
        if (isVisible) visibleCount++;
      });

    noResultsMessage.style.display = visibleCount === 0 ? "block" : "none";
  };

  // Debounce the search handler for better performance
  searchInput.addEventListener("input", App.debounce(handleSearch, 200));

  /**
   * Logic for "Select All" checkboxes in permission groups.
   */
  permissionsGrid.addEventListener("change", (e) => {
    const target = e.target;
    if (target.classList.contains("select-all-group")) {
      const group = target.dataset.group;
      permissionsGrid
        .querySelectorAll(`.permission-checkbox[data-group="${group}"]`)
        .forEach((cb) => {
          cb.checked = target.checked;
        });
    }
    updateAllGroupCheckboxes();
  });

  const updateAllGroupCheckboxes = () => {
    permissionsGrid
      .querySelectorAll(".select-all-group")
      .forEach((groupCheckbox) => {
        const group = groupCheckbox.dataset.group;
        const inGroup = permissionsGrid.querySelectorAll(
          `.permission-checkbox[data-group="${group}"]`
        );
        const checkedInGroup = permissionsGrid.querySelectorAll(
          `.permission-checkbox[data-group="${group}"]:checked`
        );
        groupCheckbox.checked =
          inGroup.length > 0 && inGroup.length === checkedInGroup.length;
        groupCheckbox.indeterminate =
          checkedInGroup.length > 0 && checkedInGroup.length < inGroup.length;
      });
  };

  /**
   * Handles the AJAX form submission for both creating and updating roles.
   */
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    App.setButtonLoading(saveBtn, true, "Saving...");
    // This is a simplified version, ideally you would have a more robust form validation helper
    document.getElementById("form-errors").classList.add("d-none");

    try {
      const result = await App.api(
        form.getAttribute("action").replace(/^\/api\//, ""),
        {
          method: "POST",
          body: new FormData(form),
        }
      );
      App.notify.success(result.message);
      setTimeout(() => (window.location.href = "/roles"), 1000);
    } catch (error) {
      const errorMessages =
        error.status === 422 && error.response.errors
          ? Object.values(error.response.errors).join("<br>")
          : error.message || "An unexpected error occurred.";

      const errorDiv = document.getElementById("form-errors");
      errorDiv.innerHTML = errorMessages;
      errorDiv.classList.remove("d-none");
      App.notify.error("Please correct the errors shown above.");
    } finally {
      App.setButtonLoading(saveBtn, false);
    }
  });

  // --- Initial Load ---
  populateFormForEdit();
});
