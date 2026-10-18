<?php
/**
 * @var array $estimate
 * @var array $items
 */
?>
<div class="table-responsive">
    <table class="table table-hover table-striped align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th style="width: 50px;">#</th>
                <th style="width: 110px;">Item Code</th>
                <th>BOQ Description</th>
                <th style="width: 100px;" class="text-end">Qty</th>
                <th style="width: 80px;">Unit</th>
                <th style="width: 130px;" class="text-end">Rate (₹)</th>
                <th style="width: 150px;" class="text-end">Amount (₹)</th>
                <th style="width: 70px;" class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary"></i>
                        No items added to this estimate yet.<br>
                        <small>Search and select DSR 2023 items from the form above to build the Bill of Quantities.</small>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $idx => $it): ?>
                    <tr>
                        <td class="text-muted small"><?= $idx + 1 ?></td>
                        <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($it['item_code']) ?></span></td>
                        <td>
                            <div class="small fw-semibold text-dark"><?= htmlspecialchars($it['description']) ?></div>
                            <?php if (!empty($it['remarks'])): ?>
                                <small class="text-muted"><i class="bi bi-tag me-1"></i><?= htmlspecialchars($it['remarks']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end font-monospace fw-bold"><?= number_format((float)$it['quantity'], 2) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($it['unit']) ?></span></td>
                        <td class="text-end font-monospace text-muted"><?= number_format((float)$it['rate'], 2) ?></td>
                        <td class="text-end font-monospace fw-bold text-dark"><?= number_format((float)$it['amount'], 2) ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-danger" 
                                    hx-get="?action=deleteItem&estimate_id=<?= $estimate['id'] ?>&item_id=<?= $it['id'] ?>"
                                    hx-target="#boq-table-container"
                                    hx-confirm="Remove this item from estimate?"
                                    title="Delete item">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Cost Summary Box -->
<div class="card-footer bg-light p-3">
    <div class="row justify-content-end">
        <div class="col-md-6 col-lg-5">
            <ul class="list-group list-group-flush rounded-3 border">
                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted">Sub-Total (Items Cost):</span>
                    <span class="font-monospace fw-semibold">₹<?= number_format((float)($estimate['subtotal'] ?? $estimate['estimated_amount'] ?? 0), 2) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted">Contingencies (<?= htmlspecialchars((string)($estimate['contingency_percent'] ?? 3)) ?>%):</span>
                    <span class="font-monospace text-secondary">+ ₹<?= number_format((float)($estimate['contingency_amount'] ?? (($estimate['estimated_amount'] * ($estimate['contingency_percent'] ?? 3)) / 100)), 2) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center py-3 bg-primary bg-opacity-10 border-top border-primary">
                    <span class="fw-bold text-primary fs-6">Grand Total Estimated Cost:</span>
                    <span class="font-monospace fw-bold text-primary fs-5">₹<?= number_format((float)($estimate['total_amount'] ?? 0), 2) ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>
