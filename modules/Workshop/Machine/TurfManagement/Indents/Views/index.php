<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indent Vouchers</title>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Indent Vouchers (Inventory Out)</h1>
        <a href="?action=showCreateForm" class="btn btn-primary">Create New Indent Voucher</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <?php if (strtolower($currentUser['role']) === 'superuser'): ?>
                        <th>Office</th>
                    <?php endif; ?>
                    <th>Date</th>
                    <th>Voucher No.</th>
                    <th>Issued To</th>
                    <th>Items</th>
                    <th>Total Qty</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($vouchers)): ?>
                    <?php foreach ($vouchers as $voucher): ?>
                        <tr>
                            <?php if (strtolower($currentUser['role']) === 'superuser'): ?>
                                <td><?php echo htmlspecialchars($voucher['office_name']); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($voucher['transaction_date']); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($voucher['voucher_no']); ?></td>
                            <td><?php echo htmlspecialchars($voucher['party'] ?? '-'); ?></td>
                            <td><span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars((string)$voucher['item_count']); ?></span></td>
                            <td><?php echo htmlspecialchars((string)$voucher['total_quantity']); ?></td>
                            <td>
                                <!-- View Details Button -->
                                <button type="button" class="btn btn-sm btn-info view-details-btn"
                                        data-voucher="<?php echo htmlspecialchars($voucher['voucher_no']); ?>">
                                    View Items
                                </button>

                                <a href="?action=delete&voucher_no=<?php echo urlencode($voucher['voucher_no']); ?>"
                                   class="btn btn-sm btn-outline-danger ms-1"
                                   onclick="return confirm('WARNING: This will permanently delete the entire voucher and ALL its items. Are you sure?');">
                                   Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?php echo (strtolower($currentUser['role']) === 'superuser') ? '7' : '6'; ?>" class="text-center py-4 text-muted">No Indent Vouchers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Viewing Details -->
<div class="modal fade" id="details-modal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-info text-dark">
        <h5 class="modal-title">Voucher Details: <span id="modal-voucher-no"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
         <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Item Description</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody id="modal-details-tbody">
                    <tr><td colspan="4" class="text-center">Loading items...</td></tr>
                </tbody>
            </table>
         </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const detailButtons = document.querySelectorAll('.view-details-btn');
    const detailsModal = new bootstrap.Modal(document.getElementById('details-modal'));
    const modalVoucherSpan = document.getElementById('modal-voucher-no');
    const modalTbody = document.getElementById('modal-details-tbody');

    detailButtons.forEach(btn => {
        btn.addEventListener('click', async function() {
            const voucherNo = this.dataset.voucher;
            modalVoucherSpan.textContent = voucherNo;
            modalTbody.innerHTML = '<tr><td colspan="4" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading...</td></tr>';

            detailsModal.show();

            try {
                const response = await fetch(`?action=viewDetails&voucher_no=${encodeURIComponent(voucherNo)}`);
                if(!response.ok) throw new Error("Failed to load details");

                const html = await response.text();
                modalTbody.innerHTML = html;

            } catch (error) {
                modalTbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Error: ${error.message}</td></tr>`;
            }
        });
    });
});
</script>
</body>
</html>
