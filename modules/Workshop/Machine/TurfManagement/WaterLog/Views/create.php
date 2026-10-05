<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Add New Water Log</h2>
        <a href="?action=exportTemplate" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel"></i> Export Template
        </a>
    </div>
    <hr>

    <!-- Manual Entry Form -->
    <form action="?action=create" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="date" class="form-label"><strong>Date</strong></label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="morning_opening" class="form-label"><strong>Morning Opening</strong></label>
                    <input type="number" step="0.01" class="form-control" id="morning_opening" name="morning_opening">
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="morning_closing" class="form-label"><strong>Morning Closing</strong></label>
                    <input type="number" step="0.01" class="form-control" id="morning_closing" name="morning_closing">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="evening_opening" class="form-label"><strong>Evening Opening</strong></label>
                    <input type="number" step="0.01" class="form-control" id="evening_opening" name="evening_opening">
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="evening_closing" class="form-label"><strong>Evening Closing</strong></label>
                    <input type="number" step="0.01" class="form-control" id="evening_closing" name="evening_closing">
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <a href="?action=list" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Log</button>
        </div>
    </form>

    <hr class="my-4">

    <!-- Excel Import Form -->
    <h4>Import from Excel</h4>
    <form action="?action=import" method="POST" enctype="multipart/form-data">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <div class="row align-items-end">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="excel_file" class="form-label">Upload .xlsx File</label>
                    <input class="form-control" type="file" id="excel_file" name="excel_file" accept=".xlsx" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-upload"></i> Upload and Import
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
