<?php
/**
 * @var array $aa_es_list An array of AA_ES data.
 * @var string|null $message Success message.
 * @var string|null $error Error message.
 */
$isSuperuser = \App\Modules\Superadmin\Users\Services\UserService::isSuperuser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage AA & ES</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f9f9f9; }
        .container { max-width: 1200px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .btn { padding: 6px 12px; text-decoration: none; border-radius: 4px; color: white; display: inline-block; font-size: 13px; border: none; cursor: pointer; margin-right: 5px; }
        .btn-create { background-color: #28a745; padding: 10px 15px; font-size: 14px; }
        .btn-view { background-color: #17a2b8; }
        .btn-edit { background-color: #ffc107; color: #212529; }
        .btn-approve { background-color: #28a745; }
        .btn-revert { background-color: #fd7e14; }
        .btn-delete { background-color: #dc3545; }

        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .no-data { text-align: center; padding: 20px; }

        table.table2 { width: 100%; border-collapse: collapse; }
        .table2 th, .table2 td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table2 thead { background-color: #f2f2f2; }
        .table2 tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .table2 tbody tr:hover { background-color: #f1f1f1; }
        .actions form { display: inline; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 400px; border-radius: 8px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: black; }
    </style>
    <script>
        function openApproveModal(id) {
            document.getElementById('approve_id').value = id;
            document.getElementById('approveModal').style.display = "block";
        }
        function closeApproveModal() {
            document.getElementById('approveModal').style.display = "none";
        }
    </script>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>AA & ES Management</h1>
        <a href="?action=showCreateForm" class="btn btn-create">Create New AA & ES</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <table class="table2">
        <thead>
            <tr>
                <th style="width: 50px;">S.No.</th>
                <th>Alias</th>
                <th>Sub Head</th>
                <th>Type</th>
                <th>Budget Code</th>
                <th>Amount (Rs.)</th>
                <th>Status</th>
                <th style="width: 250px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($aa_es_list)): ?>
                <tr>
                    <td colspan="8" class="no-data">No records found.</td>
                </tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($aa_es_list as $row): ?>
                    <tr>
                        <td><?= $sno++ ?></td>
                        <td><strong><?= htmlspecialchars($row['alias']) ?></strong></td>
                        <td><?= htmlspecialchars(substr($row['sub_head'], 0, 40)) . '...' ?></td>
                        <td><?= htmlspecialchars($row['type']) ?></td>
                        <td><?= htmlspecialchars($row['budget_code']) ?></td>
                        <td><?= number_format((float)$row['aa_es_amount'], 2) ?></td>
                        <td>
                            <span style="color: <?= $row['status'] === 'Approved' ? 'green' : 'orange' ?>; font-weight: bold;">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="?action=view&id=<?= $row['id'] ?>" class="btn btn-view">View</a>

                            <?php if ($row['status'] === 'Draft'): ?>
                                <a href="?action=showEditForm&id=<?= $row['id'] ?>" class="btn btn-edit">Edit</a>
                                <button onclick="openApproveModal(<?= $row['id'] ?>)" class="btn btn-approve">Approve</button>
                                <?php if ($isSuperuser): ?>
                                    <form action="?action=delete&id=<?= $row['id'] ?>" method="POST" onsubmit="return confirm('Are you sure?');">
                                        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                                        <button type="submit" class="btn btn-delete">Delete</button>
                                    </form>
                                <?php endif; ?>
                            <?php elseif ($row['status'] === 'Approved' && $isSuperuser): ?>
                                <a href="?action=revert&id=<?= $row['id'] ?>" class="btn btn-revert" onclick="return confirm('Revert to Draft? This will remove the approval status.');">Revert</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Approval Modal -->
<div id="approveModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeApproveModal()">&times;</span>
        <h2>Approve AA & ES</h2>
        <form action="?action=approve" method="POST" enctype="multipart/form-data">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
            <input type="hidden" id="approve_id" name="id">
            <div style="margin-bottom: 15px;">
                <label for="approval_pdf" style="display: block; margin-bottom: 5px; font-weight: bold;">Upload Approval Order (PDF):</label>
                <input type="file" id="approval_pdf" name="approval_pdf" accept="application/pdf" required style="width: 100%;">
            </div>
            <div style="text-align: right;">
                <button type="button" class="btn btn-secondary" onclick="closeApproveModal()" style="background-color: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn btn-approve" style="padding: 8px 16px;">Confirm Approval</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
