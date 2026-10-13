<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Vouchers - RV Control System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<div class="container-fluid py-3">

    <!-- Header & Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h3 fw-bold text-dark mb-0">
                <i class="bi bi-box-arrow-in-down text-primary me-2"></i>Receipt Vouchers (RV Control)
            </h2>
            <small class="text-muted">Multi-RV Staging &bull; Contract/Agreement Linked &bull; Central Inventory Inward</small>
        </div>
        <div class="d-flex gap-2">
            <a href="?action=showCreateForm" class="btn btn-primary fw-bold shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> New Receipt Voucher (Multi-RV Staging)
            </a>
        </div>
    </div>

    <!-- Alert / Toast Messages -->
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <?php if (strtolower($currentUser['role'] ?? '') === 'superuser'): ?>
                                <th>Office</th>
                            <?php endif; ?>
                            <th>Date</th>
                            <th>Voucher No.</th>
                            <th>Source & Contract Ref</th>
                            <th>Received From / Agency</th>
                            <th>Category</th>
                            <th>Items Received</th>
                            <th>Consignment</th>
                            <th class="text-end" style="min-width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($vouchers)): ?>
                            <?php foreach ($vouchers as $voucher): ?>
                                <?php 
                                    $items = $voucher['items'] ?? [];
                                    $itemCount = is_array($items) ? count($items) : 0;
                                    $firstItemDesc = $items[0]['description'] ?? '';
                                    if ($itemCount > 1) {
                                        $itemsSummary = htmlspecialchars($firstItemDesc) . " <span class='badge bg-secondary rounded-pill'>+" . ($itemCount - 1) . " more</span>";
                                    } elseif ($itemCount === 1) {
                                        $itemsSummary = htmlspecialchars($firstItemDesc);
                                    } else {
                                        $itemsSummary = "<span class='text-muted'>No items</span>";
                                    }
                                ?>
                                <tr>
                                    <?php if (strtolower($currentUser['role'] ?? '') === 'superuser'): ?>
                                        <td class="small fw-semibold"><?php echo htmlspecialchars((string)($voucher['office_name'] ?? '')); ?></td>
                                    <?php endif; ?>

                                    <td class="small text-nowrap"><?php echo htmlspecialchars((string)($voucher['voucher_date'] ?? '')); ?></td>

                                    <td>
                                        <span class="badge bg-primary fs-6 fw-bold">
                                            <i class="bi bi-receipt me-1"></i><?php echo htmlspecialchars((string)($voucher['voucher_no'] ?? '')); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php 
                                            $src = strtoupper($voucher['source_type'] ?? 'MANUAL');
                                            $ref = $voucher['source_ref_no'] ?? '';
                                            if ($src === 'AGREEMENT') {
                                                echo "<span class='badge bg-success small me-1'>Agreement</span><span class='small fw-bold'>" . htmlspecialchars($ref) . "</span>";
                                            } elseif ($src === 'SUPPLY_ORDER') {
                                                echo "<span class='badge bg-warning text-dark small me-1'>Supply Order</span><span class='small fw-bold'>" . htmlspecialchars($ref) . "</span>";
                                            } elseif ($src === 'WORK_ORDER') {
                                                echo "<span class='badge bg-info text-dark small me-1'>Work Order</span><span class='small fw-bold'>" . htmlspecialchars($ref) . "</span>";
                                            } else {
                                                echo "<span class='badge bg-light text-dark border small'>Manual / Retail</span>";
                                            }
                                        ?>
                                    </td>

                                    <td>
                                        <div class="fw-bold small text-dark">
                                            <?php echo htmlspecialchars((string)($voucher['party'] ?? $voucher['agency_name'] ?? '-')); ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary text-uppercase small">
                                            <?php echo htmlspecialchars((string)($voucher['store_category'] ?? 'General')); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="small">
                                            <?php echo $itemsSummary; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if (($voucher['supply_status'] ?? '') === 'PARTIAL'): ?>
                                            <span class="badge bg-warning text-dark small">Partial</span>
                                        <?php else: ?>
                                            <span class="badge bg-success small">Complete</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" title="View Voucher Line Items"
                                                    onclick="viewVoucherModal(<?php echo (int)$voucher['id']; ?>)">
                                                <i class="bi bi-eye"></i> Details
                                            </button>
                                            <a href="?action=printVoucher&id=<?php echo urlencode($voucher['id']); ?>" 
                                               target="_blank" class="btn btn-outline-secondary" title="Print Official Voucher">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <?php if (strtolower($currentUser['role'] ?? '') === 'superuser'): ?>
                                                <a href="?action=delete&id=<?php echo urlencode($voucher['id']); ?>"
                                                   class="btn btn-outline-danger" title="Delete Voucher"
                                                   onclick="return confirm('Are you sure you want to delete Voucher <?php echo htmlspecialchars($voucher['voucher_no']); ?> and roll back all inventory ledger entries?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo (strtolower($currentUser['role'] ?? '') === 'superuser') ? '9' : '8'; ?>" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                    No Receipt Vouchers found. Click <strong>"New Receipt Voucher"</strong> to stage and enter inward stock!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: View Voucher Line Items -->
<div class="modal fade" id="voucherDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modal-voucher-title">
                    <i class="bi bi-receipt me-2"></i>Receipt Voucher Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3 border-bottom pb-3">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Voucher Number:</small>
                        <strong class="fs-5 text-primary" id="modal-vno">-</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Voucher Date:</small>
                        <strong id="modal-vdate">-</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Store Category:</small>
                        <span class="badge bg-secondary fs-6 text-uppercase" id="modal-vcat">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Received From / Vendor:</small>
                        <strong id="modal-vparty">-</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Source Reference:</small>
                        <span id="modal-vsource" class="fw-bold">-</span>
                    </div>
                </div>

                <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-1"></i>Line Items Inward:</h6>
                <div class="table-responsive border rounded mb-3">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Item Description</th>
                                <th class="text-end">Quantity</th>
                                <th>Unit</th>
                                <th class="text-end">Unit Rate</th>
                                <th class="text-end">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody id="modal-items-tbody">
                            <!-- Populated via JS -->
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2">Total</td>
                                <td class="text-end" id="modal-total-qty">0</td>
                                <td></td>
                                <td></td>
                                <td class="text-end text-success" id="modal-total-amount">₹0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div id="modal-remarks-row" style="display: none;">
                    <small class="text-muted d-block">Remarks:</small>
                    <p class="small text-dark mb-0 bg-light p-2 rounded" id="modal-remarks-text"></p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="modal-print-btn" target="_blank" class="btn btn-primary">
                    <i class="bi bi-printer me-1"></i> Print Voucher
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let detailsModalInstance = null;

    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('voucherDetailsModal');
        if (modalEl) {
            detailsModalInstance = new bootstrap.Modal(modalEl);
        }
    });

    function viewVoucherModal(id) {
        fetch(`?action=getVoucherDetails&id=${encodeURIComponent(id)}`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    const v = res.data;
                    document.getElementById('modal-vno').textContent = v.voucher_no;
                    document.getElementById('modal-vdate').textContent = v.voucher_date;
                    document.getElementById('modal-vcat').textContent = v.store_category || 'General';
                    document.getElementById('modal-vparty').textContent = v.party || v.agency_name || '-';

                    let srcText = v.source_type || 'MANUAL';
                    if (v.source_ref_no) srcText += ': ' + v.source_ref_no;
                    document.getElementById('modal-vsource').textContent = srcText;

                    document.getElementById('modal-print-btn').href = `?action=printVoucher&id=${v.id}`;

                    // Items table
                    const tbody = document.getElementById('modal-items-tbody');
                    tbody.innerHTML = '';
                    const items = Array.isArray(v.items) ? v.items : [];
                    let totQty = 0;
                    let totAmt = 0;

                    items.forEach((it, idx) => {
                        const qty = parseFloat(it.quantity || 0);
                        const rate = parseFloat(it.rate || 0);
                        const amt = (it.amount !== undefined) ? parseFloat(it.amount) : (qty * rate);

                        totQty += qty;
                        totAmt += amt;

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${idx + 1}</td>
                            <td class="fw-bold">${escapeHtml(it.description || 'Item')}</td>
                            <td class="text-end fw-bold text-primary">${qty}</td>
                            <td>${escapeHtml(it.unit || 'Units')}</td>
                            <td class="text-end">${rate > 0 ? '₹' + rate.toFixed(2) : '-'}</td>
                            <td class="text-end fw-bold">${amt > 0 ? '₹' + amt.toFixed(2) : '-'}</td>
                        `;
                        tbody.appendChild(tr);
                    });

                    document.getElementById('modal-total-qty').textContent = totQty;
                    document.getElementById('modal-total-amount').textContent = '₹' + totAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                    if (v.remarks) {
                        document.getElementById('modal-remarks-row').style.display = 'block';
                        document.getElementById('modal-remarks-text').textContent = v.remarks;
                    } else {
                        document.getElementById('modal-remarks-row').style.display = 'none';
                    }

                    if (detailsModalInstance) {
                        detailsModalInstance.show();
                    }
                } else {
                    alert('Could not load voucher details: ' + (res.message || ''));
                }
            })
            .catch(err => {
                console.error('Error fetching voucher:', err);
                alert('Connection error loading voucher details.');
            });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
</script>
</body>
</html>
