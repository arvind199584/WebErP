<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 fw-bold mb-1">Noticeboard & Public Announcements</h1>
            <p class="text-muted small mb-0">Manage announcements, circulars, and notices displayed on the login portal and public noticeboard.</p>
        </div>
        <a href="?action=showCreateForm" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-plus-circle"></i>
            <span>Post New Notice</span>
        </a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">All Published Notices</h6>
            <span class="badge bg-light text-dark border"><?= count($notices ?? []) ?> Total</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 140px;">Badge / Type</th>
                            <th>Title & Content Summary</th>
                            <th style="width: 90px;" class="text-center">Priority</th>
                            <th style="width: 100px;" class="text-center">Status</th>
                            <th style="width: 160px;">Date Posted</th>
                            <th style="width: 120px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notices)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-megaphone fs-3 d-block mb-2 text-secondary"></i>
                                    No notices posted yet. Click "Post New Notice" to create one.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($notices as $notice): ?>
                                <tr>
                                    <td class="fw-semibold text-muted">#<?= $notice['id'] ?></td>
                                    <td>
                                        <?php 
                                            $badgeClass = 'bg-primary';
                                            if ($notice['badge_type'] === 'Circular') $badgeClass = 'bg-info text-dark';
                                            if ($notice['badge_type'] === 'Maintenance') $badgeClass = 'bg-warning text-dark';
                                            if ($notice['badge_type'] === 'Urgent') $badgeClass = 'bg-danger';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($notice['badge_type']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($notice['title']) ?></div>
                                        <div class="text-muted small text-truncate" style="max-width: 480px;"><?= htmlspecialchars($notice['content']) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= $notice['priority'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($notice['is_active']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= date('M d, Y h:i A', strtotime($notice['created_at'])) ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="?action=showEditForm&id=<?= $notice['id'] ?>" class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $notice['id'] ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Delete this notice?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
