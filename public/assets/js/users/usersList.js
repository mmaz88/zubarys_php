// public/assets/js/users/usersList.js
(() => {
  const initializeUsersIndexPage = () => {
    const tableId = "users-table";
    const tableEl = document.getElementById(tableId);

    if (!tableEl) return () => {}; // Exit if table not found

    // Get a reference to the initialized DataTable instance.
    // A small delay ensures the main DataTablesHelper.js has run.
    let dataTable = null;
    setTimeout(() => {
      if ($.fn.DataTable.isDataTable("#" + tableId)) {
        dataTable = $(tableEl).DataTable();
      }
    }, 100);

    const handleTableClick = async (e) => {
      // Use event delegation to catch clicks on delete buttons
      const deleteBtn = e.target.closest('button[data-action="delete"]');
      if (!deleteBtn) return;

      const userId = deleteBtn.dataset.id;
      if (!userId) return;

      try {
        // Use the global App.confirm helper
        await App.confirm({
          title: "Delete User?",
          message:
            "This will permanently remove the user. This action cannot be undone.",
          confirmVariant: "destructive",
        });

        // On confirmation, call the delete API
        const result = await App.api(`users/${userId}/delete`, {
          method: "POST",
        });
        App.notify.success(result.message || "User deleted successfully.");

        // Redraw the table to reflect the changes without reloading the page
        dataTable?.draw();
      } catch (error) {
        // App.confirm rejects if the user cancels. Only show an error if one exists.
        if (error && error.message) {
          App.notify.error(error.message);
        }
      }
    };

    tableEl.addEventListener("click", handleTableClick);

    // Return a cleanup function for the SPA router
    return () => {
      console.log("Cleaning up Users page listeners.");
      tableEl.removeEventListener("click", handleTableClick);

      // Destroy the DataTable instance to prevent memory leaks
      if ($.fn.DataTable.isDataTable("#" + tableId)) {
        $("#" + tableId)
          .DataTable()
          .destroy();
      }
    };
  };

  // Register the initializer with the SPA router
  if (window.App && window.App.pageInitializers) {
    window.App.pageInitializers["users-index"] = initializeUsersIndexPage;
  } else {
    // Fallback for direct page load
    document.addEventListener("DOMContentLoaded", initializeUsersIndexPage);
  }
})();
