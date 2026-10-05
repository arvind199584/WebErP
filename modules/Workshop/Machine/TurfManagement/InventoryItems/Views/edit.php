<form hx-post="?action=update" hx-target="#item-table-container" hx-select="#item-table-container" hx-swap="outerHTML">
    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
    <div class="modal-header">
        <h5 class="modal-title">Edit Inventory Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <div class="mb-3">
            <label for="description" class="form-label">Item Description</label>
            <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($item['description']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="ac_unit" class="form-label">Unit (e.g., Litre, Kg, Pcs)</label>
            <input type="text" class="form-control" id="ac_unit" name="ac_unit" value="<?php echo htmlspecialchars($item['ac_unit']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="category_id" class="form-label">Item Category</label>
            <select class="form-select" id="category_id" name="category_id">
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($item['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</form>
