<?php
/**
 * @var array $results
 */
?>
<?php if (empty($results)): ?>
    <div class="list-group-item text-muted text-center py-3">
        <i class="bi bi-search me-1"></i> No matching DSR 2023 items found.
    </div>
<?php else: ?>
    <?php foreach ($results as $res): ?>
        <button type="button" 
                class="list-group-item list-group-item-action p-2 text-start d-flex justify-content-between align-items-center"
                onclick="selectDsrItem('<?= htmlspecialchars($res['item_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($res['description']), ENT_QUOTES) ?>', '<?= htmlspecialchars($res['unit'], ENT_QUOTES) ?>', '<?= htmlspecialchars((string)$res['rate'], ENT_QUOTES) ?>')">
            <div>
                <span class="badge bg-primary font-monospace me-2"><?= htmlspecialchars($res['item_code']) ?></span>
                <span class="small fw-semibold text-dark"><?= htmlspecialchars(mb_strimwidth($res['description'], 0, 90, '...')) ?></span>
                <div class="text-muted" style="font-size: 0.75rem;">
                    Sub-head <?= $res['sub_head_no'] ?>: <?= htmlspecialchars($res['sub_head_name']) ?>
                </div>
            </div>
            <div class="text-end ms-3">
                <span class="fw-bold font-monospace text-success">₹<?= number_format((float)$res['rate'], 2) ?></span>
                <span class="badge bg-light text-dark border">/ <?= htmlspecialchars($res['unit']) ?></span>
            </div>
        </button>
    <?php endforeach; ?>
<?php endif; ?>
