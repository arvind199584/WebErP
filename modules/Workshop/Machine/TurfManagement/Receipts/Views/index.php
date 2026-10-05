<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Vouchers</title>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Receipt Vouchers (Inventory In)</h1>
        <a href="?action=showCreateForm" class="btn btn-primary">Create New Receipt Voucher</a>
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
                    <th>Item Description</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Received From</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($vouchers)): ?>
                    <?php foreach ($vouchers as $voucher): ?>
                        <tr>
                            <?php if (strtolower($currentUser['role']) === 'superuser'): ?>
                                <td><?php echo htmlspecialchars((string)($voucher['office_name'] ?? '')); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars((string)($voucher['transaction_date'] ?? '')); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars((string)($voucher['voucher_no'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string)($voucher['item_name'] ?? '')); ?></td>
                            <td class="fw-bold text-primary"><?php echo htmlspecialchars((string)($voucher['quantity'] ?? '0')); ?></td>
                            <td><?php echo htmlspecialchars((string)($voucher['unit'] ?? 'Pcs')); ?></td>
                            <td><?php echo htmlspecialchars((string)($voucher['party'] ?? '-')); ?></td>
                            <td>
                                <a href="?action=delete&id=<?php echo urlencode($voucher['id']); ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this receipt entry?');">
                                   Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?php echo (strtolower($currentUser['role']) === 'superuser') ? '8' : '7'; ?>" class="text-center py-4 text-muted">No Receipt entries found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Logic for view-details-btn removed as list is now flat.
</script>
</body>
</html>
