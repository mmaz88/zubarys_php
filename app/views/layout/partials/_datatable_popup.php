<!-- DataTables Advanced Filter Popup (controlled by dataTableHelper.js) -->
<div id="dt-filter-popup" style="display: none;">
    <div class="filter-group">
        <select id="dt-filter-condition" class="form-select form-select-sm">
            <option value="contains">Contains</option>
            <option value="not_contains">Doesn't Contain</option>
            <option value="equals">Equals</option>
            <option value="not_equals">Doesn't Equal</option>
            <option value="starts_with">Starts With</option>
            <option value="ends_with">Ends With</option>
        </select>
        <input type="text" id="dt-filter-value" class="form-input form-input-sm" placeholder="Filter value...">
    </div>
    <div class="filter-popup-actions">
        <button id="dt-filter-clear" class="btn btn-sm btn-ghost">Clear</button>
        <button id="dt-filter-apply" class="btn btn-sm btn-primary">Apply</button>
    </div>
</div>