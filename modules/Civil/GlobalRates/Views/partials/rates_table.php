<?php
/**
 * @var array $items
 * @var int $total
 * @var int $page
 * @var int $totalPages
 * @var string|null $query
 * @var string|null $selectedCategory
 */
?>
<div class="table-responsive">
    <table class="table table-hover table-striped align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th style="width: 100px;">Code</th>
                <th style="width: 140px;">Category</th>
                <th>Description</th>
                <th style="width: 90px;">Unit</th>
                <th style="width: 140px;" class="text-end">DSR Base (₹)</th>
                <th style="width: 220px;" class="text-end">Market Rate (₹)</th>
                <th style="width: 120px;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No resources found matching the criteria.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $item): 
                    $isModified = ((float)$item['current_market_rate'] !== (float)$item['dsr_base_rate']);
                    $catBadge = match (true) {
                        str_contains(strtolower($item['category']), 'labour') => 'bg-info text-dark',
                        str_contains(strtolower($item['category']), 'plant') || str_contains(strtolower($item['category']), 'machinery') => 'bg-warning text-dark',
                        default => 'bg-primary'
                    };
                ?>
                    <tr id="row-<?= htmlspecialchars($item['resource_code']) ?>">
                        <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($item['resource_code']) ?></span></td>
                        <td><span class="badge <?= $catBadge ?>"><?= htmlspecialchars($item['category']) ?></span></td>
                        <td>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($item['description']) ?></div>
                            <?php if (!empty($item['notes'])): ?>
                                <small class="text-muted"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($item['notes']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($item['unit']) ?></span></td>
                        <td class="text-end font-monospace text-muted">
                            <?= number_format((float)$item['dsr_base_rate'], 2) ?>
                        </td>
                        <td class="text-end">
                            <form hx-post="?action=updateRate" 
                                  hx-target="#status-<?= htmlspecialchars($item['resource_code']) ?>" 
                                  hx-swap="innerHTML"
                                  class="d-flex align-items-center justify-content-end gap-2 m-0">
                                <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                                <input type="hidden" name="resource_code" value="<?= htmlspecialchars($item['resource_code']) ?>">
                                <div class="input-group input-group-sm" style="max-width: 150px;">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" min="0" 
                                           name="market_rate" 
                                           class="form-control text-end font-monospace <?= $isModified ? 'border-primary fw-bold text-primary' : '' ?>" 
                                           value="<?= htmlspecialchars((string)$item['current_market_rate']) ?>" 
                                           required>
                                </div>
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Save rate">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                        </td>
                        <td class="text-center" id="status-<?= htmlspecialchars($item['resource_code']) ?>">
                            <?php if ($isModified): ?>
                                <span class="badge bg-warning text-dark" title="Overridden from DSR base">
                                    <i class="bi bi-pencil-fill me-1"></i>Custom
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">Standard</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
        <small class="text-muted">
            Showing page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong> (Total: <?= number_format($total) ?> resources)
        </small>
        <nav aria-label="Rate Pagination">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?action=list&page=<?= $page - 1 ?>&q=<?= urlencode((string)$query) ?>&category=<?= urlencode((string)$selectedCategory) ?>">&laquo; Prev</a>
                </li>
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                for ($p = $startPage; $p <= $endPage; $p++):
                ?>
                    <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?action=list&page=<?= $p ?>&q=<?= urlencode((string)$query) ?>&category=<?= urlencode((string)$selectedCategory) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?action=list&page=<?= $page + 1 ?>&q=<?= urlencode((string)$query) ?>&category=<?= urlencode((string)$selectedCategory) ?>">Next &raquo;</a>
                </li>
            </ul>
        </nav>
    </div>
<?php endif; ?>
