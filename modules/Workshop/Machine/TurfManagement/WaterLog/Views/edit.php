<?php
/**
 * @var array $log
 */
?>

<div class="container mt-4">
    <h2>Edit Water Log</h2>
    <hr>

    <form action="?action=update" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="id" value="<?php echo $log['id']; ?>">

        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="date" class="form-label"><strong>Date</strong></label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($log['date']); ?>" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="morning_opening" class="form-label"><strong>Morning Opening</strong></label>
                    <input type="number" step="0.01" class="form-control" id="morning_opening" name="morning_opening" value="<?php echo htmlspecialchars($log['morning_opening']); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="morning_closing" class="form-label"><strong>Morning Closing</strong></label>
                    <input type="number" step="0.01" class="form-control" id="morning_closing" name="morning_closing" value="<?php echo htmlspecialchars($log['morning_closing']); ?>">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="evening_opening" class="form-label"><strong>Evening Opening</strong></label>
                    <input type="number" step="0.01" class="form-control" id="evening_opening" name="evening_opening" value="<?php echo htmlspecialchars($log['evening_opening']); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="evening_closing" class="form-label"><strong>Evening Closing</strong></label>
                    <input type="number" step="0.01" class="form-control" id="evening_closing" name="evening_closing" value="<?php echo htmlspecialchars($log['evening_closing']); ?>">
                </div>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-end gap-2">
            <a href="?action=list" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>
