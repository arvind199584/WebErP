<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Machine List</h1>
        <div class="d-flex gap-2">
            <input type="text" id="machine-search-input" class="form-control" placeholder="Type to search machines...">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#machine-modal" data-action="create">Add New</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <?php if (strtolower($currentUser['role']) === 'superuser'): ?>
                        <th>Office</th>
                    <?php endif; ?>
                    <th>Name</th>
                    <th>Make</th>
                    <th>Fuel Type</th>
                    <th>Service Interval</th>
                    <th>Tracks Run Duration</th>
                    <th>Daily Run</th>
                    <th>Rest Day</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="machine-table-body">
                <?php if (!empty($machines)): ?>
                    <?php foreach ($machines as $machine): ?>
                        <tr class="machine-row">
                            <?php if (strtolower($currentUser['role']) === 'superuser'): ?>
                                <td><?php echo htmlspecialchars($machine['office_name']); ?></td>
                            <?php endif; ?>
                            <td class="machine-name"><?php echo htmlspecialchars($machine['name']); ?></td>
                            <td><?php echo htmlspecialchars($machine['make'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($machine['fuel_type_name'] ?? 'N/A'); ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo number_format((float)($machine['service_interval_hours'] ?? 100)); ?> hrs</span></td>
                            <td><?php echo $machine['runduration'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo ($machine['runduration'] && $machine['daily_run']) ? 'Yes' : 'No'; ?></td>
                            <td>
                                <?php
                                    if ($machine['runduration'] && $machine['daily_run'] && $machine['has_rest_day']) {
                                        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                        echo $days[$machine['rest_day']] ?? 'N/A';
                                    } else {
                                        echo 'N/A';
                                    }
                                ?>
                            </td>
                            <td><span class="badge bg-success"><?php echo htmlspecialchars($machine['status']); ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#machine-modal" data-action="edit" data-id="<?php echo $machine['id']; ?>">Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9">No machines found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bootstrap Modal -->
<div class="modal fade" id="machine-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body" id="modal-body-content">
                <div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('machine-search-input');
    const tableBody = document.getElementById('machine-table-body');

    // --- Live Search Logic ---
    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = tableBody.getElementsByClassName('machine-row');

        for (let row of rows) {
            const nameCell = row.querySelector('.machine-name');
            if (nameCell) {
                const name = nameCell.textContent.toLowerCase();
                if (name.startsWith(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }
    });

    // --- Modal Form Loading Logic ---
    const machineModal = document.getElementById('machine-modal');
    const modalBody = document.getElementById('modal-body-content');

    machineModal.addEventListener('show.bs.modal', async function (event) {
        const button = event.relatedTarget;
        const action = button.getAttribute('data-action');
        const machineId = button.getAttribute('data-id');

        // Show a loading spinner
        modalBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>';

        let url = '';
        if (action === 'edit') {
            url = `?action=showEditForm&id=${machineId}`;
        } else {
            // For 'create', you would have a 'showCreateForm' action
            // For now, we focus on edit.
            url = `?action=showCreateForm`; // This needs to be implemented if you want a create modal
        }

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Network response was not ok.');

            const formHtml = await response.text();
            modalBody.innerHTML = formHtml;

            // Re-evaluate scripts contained in formHtml because innerHTML does not execute script tags
            modalBody.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.textContent = oldScript.textContent;
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
        } catch (error) {
            modalBody.innerHTML = `<div class="alert alert-danger">Failed to load form: ${error.message}</div>`;
        }
    });
});
</script>
