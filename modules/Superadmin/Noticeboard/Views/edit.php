<div class="container-fluid px-0">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 text-gray-800 fw-bold mb-1">Edit Notice</h1>
                    <p class="text-muted small mb-0">Update announcement or circular details.</p>
                </div>
                <a href="?action=list" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Notices</span>
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="?action=update" method="POST">
                        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string)$notice['id']) ?>">

                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Notice Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required value="<?= htmlspecialchars($notice['title']) ?>">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="badge_type" class="form-label fw-semibold">Category / Badge Type</label>
                                <select class="form-select" id="badge_type" name="badge_type">
                                    <option value="Announcement" <?= $notice['badge_type'] === 'Announcement' ? 'selected' : '' ?>>Announcement (Blue)</option>
                                    <option value="Circular" <?= $notice['badge_type'] === 'Circular' ? 'selected' : '' ?>>Official Circular (Cyan)</option>
                                    <option value="Maintenance" <?= $notice['badge_type'] === 'Maintenance' ? 'selected' : '' ?>>Maintenance Alert (Yellow)</option>
                                    <option value="Urgent" <?= $notice['badge_type'] === 'Urgent' ? 'selected' : '' ?>>Urgent Notice (Red)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label fw-semibold">Display Priority (Higher shows first)</label>
                                <input type="number" class="form-control" id="priority" name="priority" value="<?= htmlspecialchars((string)$notice['priority']) ?>" min="1" max="100">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label fw-semibold">Notice Content / Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="5" required><?= htmlspecialchars($notice['content']) ?></textarea>
                        </div>

                        <div class="mb-4 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= $notice['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="is_active">Active (Visible to Users)</label>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="?action=list" class="btn btn-light px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
