<form hx-post="?action=create" hx-target="#item-table-container" hx-select="#item-table-container" hx-swap="outerHTML">
    <div class="modal-header">
        <h5 class="modal-title">Add New Inventory Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <div class="mb-3">
            <label for="description" class="form-label">Item Description</label>
            <input type="text" class="form-control" id="description" name="description" required>
        </div>

        <div class="mb-3">
            <label for="ac_unit" class="form-label">Unit (e.g., Litre, Kg, Pcs)</label>
            <input type="text" class="form-control" id="ac_unit" name="ac_unit" required>
        </div>

        <div class="mb-3">
            <label for="category_id" class="form-label">Item Category</label>
            <select class="form-select" id="category_id" name="category_id">
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save Item</button>
    </div>
</form>
