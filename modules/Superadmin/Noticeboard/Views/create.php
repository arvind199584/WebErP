<div class="container-fluid px-0">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 text-gray-800 fw-bold mb-1">Post New Notice</h1>
                    <p class="text-muted small mb-0">Publish an announcement or circular to the noticeboard & public login portal.</p>
                </div>
                <a href="?action=list" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Notices</span>
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="?action=create" method="POST">
                        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>

                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Notice Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required placeholder="e.g. Scheduled System Maintenance / New Directive">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="badge_type" class="form-label fw-semibold">Category / Badge Type</label>
                                <select class="form-select" id="badge_type" name="badge_type">
                                    <option value="Announcement" selected>Announcement (Blue)</option>
                                    <option value="Circular">Official Circular (Cyan)</option>
                                    <option value="Maintenance">Maintenance Alert (Yellow)</option>
                                    <option value="Urgent">Urgent Notice (Red)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label fw-semibold">Display Priority (Higher shows first)</label>
                                <input type="number" class="form-control" id="priority" name="priority" value="5" min="1" max="100">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label fw-semibold">Notice Content / Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="5" required placeholder="Write full details of the notice, circular reference, instructions, or dates..."></textarea>
                        </div>

                        <div class="mb-4 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label fw-semibold" for="is_active">Publish immediately (Active)</label>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="?action=list" class="btn btn-light px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-send me-1"></i> Publish Notice
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
