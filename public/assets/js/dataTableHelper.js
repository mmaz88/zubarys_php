/**
 * Enhanced DataTables Helper v4.3 - Built for DataTables 2.3.3+
 * FIX: Resolved initialization race condition by using the 'settings' object passed
 * into callbacks instead of relying on the 'this' context, which may not be fully
 * constructed during early events like preDraw. This makes a more robust initialization.
 * FIX: Removed SPA-related event listener.
 */
const EnhancedDataTablesHelper = (() => {
  "use strict";

  // =============================================================================
  // UTILITY FUNCTIONS
  // =============================================================================
  function escapeHtml(text) {
    if (text === null || typeof text === "undefined") {
      return "";
    }
    const div = document.createElement("div");
    div.textContent = String(text);
    return div.innerHTML;
  }

  // =============================================================================
  // ENHANCED CELL RENDERERS
  // =============================================================================
  const Renderers = {
    userNameEmailRenderer: (data, type, row) => {
      if (!row) return "";
      const name = row.name || "";
      const email = row.email || "";
      return `<div class="d-flex align-items-center">
                        <div>
                            <div class="fw-medium">${escapeHtml(name)}</div>
                            <div class="text-muted small">${escapeHtml(
                              email
                            )}</div>
                        </div>
                    </div>`;
    },
    userRenderer: (data, type, row, meta) => {
      if (!row) return "";
      const settings = meta.settings.aoColumns[meta.col];
      const name = row[settings.nameField] || "";
      const email = row[settings.emailField] || "";
      const avatar = row.avatar || null;
      const avatarHtml = avatar
        ? `<img src="${escapeHtml(avatar)}" alt="${escapeHtml(
            name
          )}" class="user-avatar me-2" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">`
        : `<div class="user-avatar-placeholder me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 50%; background: var(--bs-primary); color: white; font-size: 14px; font-weight: 500;">${escapeHtml(
            name.charAt(0).toUpperCase()
          )}</div>`;
      return `<div class="d-flex align-items-center">${avatarHtml}<div><div class="fw-medium">${escapeHtml(
        name
      )}</div><div class="text-muted small">${escapeHtml(
        email
      )}</div></div></div>`;
    },
    rowNumberRenderer: (data, type, row, meta) => {
      const info = meta.settings._iDisplayStart || 0;
      return info + meta.row + 1;
    },
    dateRenderer: (data, type) => {
      if (!data) return "";
      if (type === "sort" || type === "type") return data;
      try {
        const date = new Date(String(data).replace(" ", "T"));
        if (isNaN(date)) return escapeHtml(data);
        return date.toLocaleDateString(navigator.language, {
          year: "numeric",
          month: "short",
          day: "numeric",
        });
      } catch {
        return escapeHtml(data);
      }
    },
    datetimeRenderer: (data, type) => {
      if (!data) return "";
      if (type === "sort" || type === "type") return data;
      try {
        const date = new Date(String(data).replace(" ", "T"));
        if (isNaN(date)) return escapeHtml(data);
        return date.toLocaleString(navigator.language, {
          year: "numeric",
          month: "short",
          day: "numeric",
          hour: "2-digit",
          minute: "2-digit",
        });
      } catch {
        return escapeHtml(data);
      }
    },
    booleanRenderer: (data, type) => {
      if (type === "sort" || type === "type") return data ? 1 : 0;
      const isTrue = Boolean(
        Number(data) ||
          (typeof data === "string" &&
            ["true", "yes", "1"].includes(data.toLowerCase()))
      );
      const icon = isTrue ? "checkmark-circle" : "close-circle";
      const color = isTrue ? "text-success" : "text-muted";
      const label = isTrue ? "Yes" : "No";
      return `<div class="d-flex align-items-center justify-content-center"><ion-icon name="${icon}" class="${color}" style="font-size: 1.2rem;" aria-hidden="true"></ion-icon><span class="visually-hidden">${label}</span></div>`;
    },
    statusRenderer: (data, type, row, meta) => {
      if (type === "sort" || type === "type") return escapeHtml(data);
      const map = meta.settings.aoColumns[meta.col].statusMap || {};
      const config = map[data] || {
        label: String(data),
        class: "badge bg-secondary",
      };
      const icon = config.icon
        ? `<ion-icon name="${config.icon}" class="me-1"></ion-icon>`
        : "";
      return `<span class="${
        escapeHtml(config.class) || "badge bg-secondary"
      }" role="status" aria-label="${escapeHtml(
        config.label
      )}">${icon}${escapeHtml(config.label)}</span>`;
    },
    actionsRenderer: (data, type, row, meta) => {
      if (!row?.id) return "";
      const id = row.id;
      const actionsConfig = meta.settings.aoColumns[meta.col].actions || {};
      const permissions = row.can || {}; // Get the 'can' object from the API data

      const buttons = Object.entries(actionsConfig)
        .map(([key, config]) => {
          // THE FIX: Check the permission for this action key before creating the button.
          if (!permissions[key]) {
            return ""; // If permission is false, return an empty string.
          }

          const tag = config.isButton ? "button" : "a";
          const path = config.isButton
            ? "#"
            : (config.path || "#").replace(/%ID%/g, id);
          const href = tag === "a" ? `href="${escapeHtml(path)}"` : "";
          const typeAttr = tag === "button" ? 'type="button"' : "";
          const btnClass = config.class || "btn-outline-primary";
          const title = escapeHtml(config.title || key);
          const actionAttr = `data-action="${escapeHtml(key)}"`;
          const idAttr = `data-id="${escapeHtml(id)}"`;

          return `<${tag} ${href} ${typeAttr} class="btn btn-sm ${btnClass} btn-icon" title="${title}" ${actionAttr} ${idAttr}>
                            <ion-icon name="${
                              config.icon || "ellipse"
                            }" aria-hidden="true"></ion-icon>
                        </${tag}>`;
        })
        .join("");

      // If no buttons were rendered, return a placeholder.
      if (buttons.trim() === "") {
        return '<div class="text-center text-muted small">--</div>';
      }

      return `<div class="d-flex justify-content-center gap-1" role="group">${buttons}</div>`;
    },
    numberRenderer: (data, type) => {
      if (data === null || typeof data === "undefined" || data === "")
        return "";
      const num = parseFloat(data);
      if (isNaN(num)) return "";
      if (type === "sort" || type === "type") return num;
      return new Intl.NumberFormat(navigator.language).format(num);
    },
    currencyRenderer: (data, type, row, meta) => {
      if (data === null || typeof data === "undefined" || data === "")
        return "";
      const num = parseFloat(data);
      if (isNaN(num)) return "";
      if (type === "sort" || type === "type") return num;
      const currency = meta.settings.aoColumns[meta.col].currency || "USD";
      return new Intl.NumberFormat(navigator.language, {
        style: "currency",
        currency: currency,
      }).format(num);
    },
    progressRenderer: (data, type) => {
      if (type === "sort" || type === "type") return parseFloat(data) || 0;
      const value = Math.max(0, Math.min(100, parseFloat(data) || 0));
      const colorClass =
        value >= 80 ? "bg-success" : value >= 50 ? "bg-warning" : "bg-danger";
      return `<div class="progress" style="height: 20px;" role="progressbar" aria-valuenow="${value}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar ${colorClass}" style="width: ${value}%"><small class="text-white fw-medium">${value.toFixed(
        0
      )}%</small></div></div>`;
    },
    imageRenderer: (data, type) => {
      if (!data)
        return '<div class="text-muted text-center small">No image</div>';
      if (type === "sort" || type === "type") return data;
      return `<img src="${escapeHtml(
        data
      )}" alt="Image" class="img-thumbnail" style="max-width: 60px; max-height: 60px; object-fit: cover;" loading="lazy" onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2260%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%23f8f9fa%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%236c757d%22 font-size=%2210%22%3EError%3C/text%3E%3C/svg%3E';">`;
    },
    linkRenderer: (data, type, row, meta) => {
      if (!data) return "";
      if (type === "sort" || type === "type") return data;
      const urlPattern = meta.settings.aoColumns[meta.col].urlPattern;
      const url = urlPattern ? urlPattern.replace(/%ID%/g, row.id || "") : "#";
      return `<a href="${escapeHtml(
        url
      )}" class="text-decoration-none">${escapeHtml(data)}</a>`;
    },
  };

  // =============================================================================
  // TABLE MANAGER
  // =============================================================================
  const TableManager = {
    instances: new Map(),
    initializeTable(tableElement, config) {
      const tableId = tableElement.id;
      try {
        this.processRenderers(config);
        this.addEnhancedCallbacks(config);
        const dataTable = new DataTable(tableElement, config);
        const instanceData = {
          table: dataTable,
          config: config,
        };
        this.instances.set(tableId, instanceData);
        this.addCustomEventListeners(dataTable);
        return dataTable;
      } catch (error) {
        console.error(
          `DataTables initialization failed for #${tableId}:`,
          error
        );
        return null;
      }
    },
    processRenderers(config) {
      if (!config.columns) return;
      config.columns.forEach((column) => {
        if (typeof column.render === "string" && Renderers[column.render]) {
          column.render = Renderers[column.render];
        }
      });
    },
    addEnhancedCallbacks(config) {
      const originalDrawCallback = config.drawCallback;
      config.drawCallback = function (settings) {
        originalDrawCallback?.call(this, settings);
        // `this` is the API instance here, but we pass `settings` for safety.
        TableManager.updateSelectAllCheckbox(settings);
        TableManager.toggleLoadingState(settings, false);
      };

      const originalInitComplete = config.initComplete;
      config.initComplete = function (settings, json) {
        originalInitComplete?.call(this, settings, json);
        TableManager.enhanceAccessibility(settings);
        $(settings.nTable).trigger("dt:initialized", [this, json]);
      };

      const originalPreDrawCallback = config.preDrawCallback;
      config.preDrawCallback = function (settings) {
        // Pass the reliable `settings` object to the helper.
        TableManager.toggleLoadingState(settings, true);
        return originalPreDrawCallback
          ? originalPreDrawCallback.call(this, settings)
          : true;
      };
    },
    addCustomEventListeners(dataTable) {
      const tableNode = dataTable.table().node(); // Here it's safe to use .table()
      $(tableNode).on("click", "a[data-confirm]", function (e) {
        e.preventDefault();
        const message =
          this.dataset.confirmMessage ||
          "Are you sure you want to perform this action?";
        const href = this.href;
        if (window.confirm(message)) {
          window.location.href = href;
        }
      });
      $(tableNode).on("change", ".select-all-checkbox", function () {
        $(tableNode)
          .find(".select-row-checkbox")
          .prop("checked", this.checked)
          .trigger("change");
      });
      $(tableNode).on("change", ".select-row-checkbox", function () {
        // Pass the DataTable instance, which is fully formed by this point.
        TableManager.updateSelectAllCheckbox(dataTable.settings()[0]);
        const selectedData = [];
        $(tableNode)
          .find(".select-row-checkbox:checked")
          .each(function () {
            const row = dataTable.row($(this).closest("tr"));
            if (row.any()) selectedData.push(row.data());
          });
        $(tableNode).trigger("dt:selectionChanged", [selectedData]);
      });
    },
    updateSelectAllCheckbox(settings) {
      const tableNode = settings.nTable;
      const $selectAll = $(tableNode).find(".select-all-checkbox");
      if ($selectAll.length === 0) return;
      const totalCheckboxes = $(tableNode).find(".select-row-checkbox").length;
      const checkedCheckboxes = $(tableNode).find(
        ".select-row-checkbox:checked"
      ).length;
      $selectAll.prop(
        "checked",
        totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes
      );
      $selectAll.prop(
        "indeterminate",
        checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes
      );
    },
    toggleLoadingState(settings, isLoading) {
      // Use the settings object to reliably get the table's main container.
      const $wrapper = $(settings.nTable).closest(".dataTables_wrapper");
      if (isLoading) {
        $wrapper.addClass("dt-loading");
      } else {
        $wrapper.removeClass("dt-loading");
      }
    },
    enhanceAccessibility(settings) {
      const $wrapper = $(settings.nTable).closest(".dataTables_wrapper");
      $wrapper.find(".paginate_button").each(function () {
        const $this = $(this);
        if ($this.attr("aria-label")) return;
        const text = $this.text().trim();
        const labels = {
          Previous: "Go to previous page",
          Next: "Go to next page",
        };
        $this.attr("aria-label", labels[text] || `Go to page ${text}`);
      });
    },
  };

  // =============================================================================
  // GLOBAL FUNCTIONS AND INITIALIZATION
  // =============================================================================
  window.serverSideDataProcessor = (data, callback, settings) => {
    // This function is fine as is.
    return data;
  };

  window.printCustomize = (win) => {
    $(win.document.body)
      .css("font-size", "10pt")
      .prepend(
        "<h1>Data Export</h1><p>Generated on " +
          new Date().toLocaleString() +
          "</p>"
      );
  };

  const init = () => {
    document.querySelectorAll("script[data-table-id]").forEach((script) => {
      const tableId = script.dataset.tableId;
      const tableElement = document.getElementById(tableId);
      if (!tableElement) {
        console.warn(
          `EnhancedDataTablesHelper: Table element #${tableId} not found.`
        );
        return;
      }
      if ($.fn.DataTable.isDataTable(tableElement)) {
        return;
      }
      try {
        const config = JSON.parse(script.textContent);
        TableManager.initializeTable(tableElement, config);
      } catch (error) {
        console.error(
          `EnhancedDataTablesHelper: Failed to parse config for table #${tableId}.`,
          error
        );
      }
    });
  };

  // =============================================================================
  // PUBLIC API
  // =============================================================================
  return {
    init,
    Renderers,
    getInstance: (tableId) => TableManager.instances.get(tableId)?.table,
    getSelectedRows: (tableId) => {
      const table = TableManager.instances.get(tableId)?.table;
      if (!table) return [];
      const selectedData = [];
      $(table.table().node())
        .find(".select-row-checkbox:checked")
        .each(function () {
          const row = table.row($(this).closest("tr"));
          if (row.any()) selectedData.push(row.data());
        });
      return selectedData;
    },
    refreshTable: (tableId, resetPaging = false) => {
      TableManager.instances.get(tableId)?.table.ajax.reload(null, resetPaging);
    },
  };
})();

// Initialize on DOM ready for standard page loads.
$(document).ready(EnhancedDataTablesHelper.init);

// Create a global alias for easier access if needed.
window.DataTablesHelper = EnhancedDataTablesHelper;
