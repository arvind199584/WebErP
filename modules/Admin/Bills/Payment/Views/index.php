<?php
/**
 * @var array $bills
 * @var string|null $searchTerm
 * @var string|null $message
 * @var string|null $error
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payments - Bills List</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1200px; margin: 40px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 8px 15px; border-radius: 4px; text-decoration: none; color: white; font-size: 14px; border: none; cursor: pointer; }
        .btn-primary { background-color: #007bff; }
        .btn-success { background-color: #28a745; }
        .btn-warning { background-color: #ffc107; color: black; }
        .btn-danger { background-color: #dc3545; }
        .btn-sm { padding: 4px 8px; font-size: 12px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
        th, td { border: 1px solid #dee2e6; padding: 10px; text-align: left; }
        th { background-color: #e9ecef; }

        .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .status-Draft { background: #eee; color: #666; }
        .status-Processed { background: #fff3cd; color: #856404; }
        .status-Paid { background: #d4edda; color: #155724; }

        .date-info { font-size: 11px; color: #666; display: block; }
        .alias-text { color: #0056b3; font-weight: 600; }

        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 15% auto; padding: 20px; width: 400px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group select, .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
    <script>
        function openStatusModal(id, currentStatus) {
            document.getElementById('bill_id').value = id;
            document.getElementById('status_select').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }
        function closeModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
    </script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Payments & Bills</h1>
        <a href="?action=showCreateForm" class="btn btn-primary">+ Create New Bill</a>
    </div>

    <?php if ($message): ?><div style="padding:10px; background:#d4edda; color:#155724; margin-bottom:20px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div style="padding:10px; background:#f8d7da; color:#721c24; margin-bottom:20px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="GET" style="margin-bottom: 20px;">
        <input type="hidden" name="action" value="list">
        <input type="text" name="search" placeholder="Search by Bill No or Alias..." value="<?= htmlspecialchars($searchTerm ?? '') ?>" style="padding:8px; width:300px; border:1px solid #ccc; border-radius:4px;">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">S.No.</th>
                <th>Bill No.</th>
                <th>Agreement Alias</th>
                <th>Gross Amount</th>
                <th>Dates (Process / Paid)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $sno = 1; foreach ($bills as $bill): ?>
            <tr>
                <td><?= $sno++ ?></td>
                <td>
                    <strong><?= htmlspecialchars($bill['office_bill_no'] ?: 'N/A') ?></strong>
                    <span class="date-info">Bill Date: <?= date('d.m.Y', strtotime($bill['bill_date'])) ?></span>
                </td>
                <td><span class="alias-text"><?= htmlspecialchars($bill['agreement_alias'] ?? 'N/A') ?></span></td>
                <td style="font-weight: bold;">₹ <?= number_format($bill['gross_amount'], 2) ?></td>
                <td>
                    <?php if (!empty($bill['status_processed_at'])): ?>
                        <span class="date-info">Proc: <?= date('d.m.Y', strtotime($bill['status_processed_at'])) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($bill['status_paid_at'])): ?>
                        <span class="date-info">Paid: <?= date('d.m.Y', strtotime($bill['status_paid_at'])) ?></span>
                    <?php endif; ?>
                    <?php if (empty($bill['status_processed_at']) && empty($bill['status_paid_at'])): ?>
                        <span class="date-info">Pending</span>
                    <?php endif; ?>
                </td>
                <td><span class="status-badge status-<?= $bill['status'] ?>"><?= $bill['status'] ?></span></td>
                <td>
                    <button class="btn btn-warning btn-sm" onclick="openStatusModal(<?= $bill['id'] ?>, '<?= $bill['status'] ?>')">Status</button>
                    <?php if ($bill['status'] === 'Draft'): ?>
                        <a href="?action=delete&id=<?= $bill['id'] ?>" class="btn btn-danger btn-sm">Del</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <h3>Update Bill Status</h3>
        <form method="POST" action="?action=updateStatus">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
            <input type="hidden" name="id" id="bill_id">
            <div class="form-group">
                <label>New Status:</label>
                <select name="status" id="status_select" required>
                    <option value="Draft">Draft</option>
                    <option value="Processed">Processed</option>
                    <option value="Paid">Paid</option>
                </select>
            </div>
            <div class="form-group">
                <label>Date of Action:</label>
                <input type="date" name="status_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn" style="background:#6c757d;" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
