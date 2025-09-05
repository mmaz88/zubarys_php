// public/assets/js/users/role-assignment.js
document.addEventListener("DOMContentLoaded", () => {
  // FIX: Check for either the create or edit page container
  const container =
    document.getElementById("user-create-page-container") ||
    document.getElementById("user-edit-page-container");
  if (!container) return;

  // --- DOM Elements ---
  const get = (id) => document.getElementById(id);
  const availableList = get("available-roles-list");
  const assignedList = get("assigned-roles-list");
  const availableSearch = get("available-roles-search");
  const assignedSearch = get("assigned-roles-search");
  const hiddenSelect = get("assigned-roles-hidden");
  const form = get("user-form");

  // --- Data Initialization ---
  let allRoles = [];
  try {
    const dataScript = get("role-assignment-data");
    const data = JSON.parse(dataScript.textContent);
    allRoles = data.available || [];
    const assignedIds = new Set(data.assigned || []);

    // Populate lists
    allRoles.forEach((role) => {
      const li = createListItem(role);
      if (assignedIds.has(role.id)) {
        assignedList.appendChild(li);
      } else {
        availableList.appendChild(li);
      }
    });
  } catch (e) {
    console.error("Failed to parse role assignment data.", e);
    return;
  }

  // --- Helper Functions ---
  function createListItem(role) {
    const li = document.createElement("li");
    li.dataset.id = role.id;
    li.dataset.name = role.name.toLowerCase();
    li.className = "dual-listbox-item";
    li.tabIndex = 0; // Make it focusable
    li.innerHTML = ` <span class="fw-medium">${App.escapeHTML(
      role.name
    )}</span> <small class="text-muted d-block">${App.escapeHTML(
      role.tenant_id ? "Tenant" : "Global"
    )}</small> `;
    return li;
  }

  function toggleSelection(e) {
    // FIX: Use .closest() to ensure the LI is always targeted,
    // even if a child span or small element is clicked.
    const listItem = e.target.closest("li.dual-listbox-item");
    if (listItem) {
      listItem.classList.toggle("selected");
    }
  }

  function moveSelected(source, destination) {
    const selected = Array.from(source.querySelectorAll(".selected"));
    selected.forEach((item) => {
      item.classList.remove("selected");
      destination.appendChild(item);
    });
  }

  function moveAll(source, destination) {
    const allItems = Array.from(source.children);
    allItems.forEach((item) => {
      item.classList.remove("selected");
      destination.appendChild(item);
    });
  }

  function filterList(input, list) {
    const query = input.value.toLowerCase();
    Array.from(list.children).forEach((item) => {
      const name = item.dataset.name || "";
      item.style.display = name.includes(query) ? "" : "none";
    });
  }

  // --- Event Listeners ---
  availableList.addEventListener("click", toggleSelection);
  assignedList.addEventListener("click", toggleSelection);

  get("add-role-btn").addEventListener("click", () =>
    moveSelected(availableList, assignedList)
  );
  get("remove-role-btn").addEventListener("click", () =>
    moveSelected(assignedList, availableList)
  );

  get("add-all-roles-btn").addEventListener("click", () =>
    moveAll(availableList, assignedList)
  );
  get("remove-all-roles-btn").addEventListener("click", () =>
    moveAll(assignedList, availableList)
  );

  availableSearch.addEventListener("input", () =>
    filterList(availableSearch, availableList)
  );
  assignedSearch.addEventListener("input", () =>
    filterList(assignedSearch, assignedList)
  );

  // Before submitting the form, update the hidden select with all assigned role IDs
  form.addEventListener("submit", () => {
    hiddenSelect.innerHTML = ""; // Clear previous options
    Array.from(assignedList.children).forEach((item) => {
      const option = document.createElement("option");
      option.value = item.dataset.id;
      option.selected = true;
      hiddenSelect.appendChild(option);
    });
  });
});
