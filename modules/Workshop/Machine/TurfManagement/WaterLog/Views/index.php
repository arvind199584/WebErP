<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Water Log</h1>
        <a href="?action=create" class="btn btn-primary">Add New Log</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Date</th>
                    <th>Morning Opening</th>
                    <th>Morning Closing</th>
                    <th>Morning Consumption</th>
                    <th>Evening Opening</th>
                    <th>Evening Closing</th>
                    <th>Evening Consumption</th>
                    <th>Total Consumption</th>
                    <th>Cumulative Consumption</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['date']); ?></td>
                            <td><?php echo htmlspecialchars($log['morning_opening']); ?></td>
                            <td><?php echo htmlspecialchars($log['morning_closing']); ?></td>
                            <td><?php echo htmlspecialchars($log['morning_consumption']); ?></td>
                            <td><?php echo htmlspecialchars($log['evening_opening']); ?></td>
                            <td><?php echo htmlspecialchars($log['evening_closing']); ?></td>
                            <td><?php echo htmlspecialchars($log['evening_consumption']); ?></td>
                            <td><?php echo htmlspecialchars($log['total_consumption']); ?></td>
                            <td><?php echo htmlspecialchars($log['cumulative_consumption']); ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $log['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?action=delete&id=<?php echo $log['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this log?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center">No water logs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
