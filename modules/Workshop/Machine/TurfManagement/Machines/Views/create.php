<form method="POST" action="?action=create">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
    <div class="modal-header">
        <h5 class="modal-title">Add New Machine</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <?php if ($userRole === 'superuser'): ?>
            <div class="mb-3">
                <label for="office_id" class="form-label">Office</label>
                <select id="office_id" name="office_id" class="form-select" required>
                    <option value="">-- Select Office --</option>
                    <?php foreach ($offices as $office): ?>
                        <option value="<?php echo $office['officeid']; ?>"><?php echo htmlspecialchars($office['officename']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="name" class="form-label">Machine Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>

        <div class="mb-3">
            <label for="make" class="form-label">Make / Brand</label>
            <input type="text" class="form-control" id="make" name="make">
        </div>

        <div class="mb-3">
            <label for="fuel_item_id" class="form-label">Fuel Type</label>
            <select id="fuel_item_id" name="fuel_item_id" class="form-select">
                <option value="">None</option>
                <?php foreach ($inventoryItems as $item): ?>
                    <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['description']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="service_interval_hours" class="form-label">Service Interval (Running Hours)</label>
            <input type="number" class="form-control" id="service_interval_hours" name="service_interval_hours" value="100" min="1" step="1" required>
            <div class="form-text">Default interval between routine machine servicing (e.g. 100 hrs).</div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="runduration" name="runduration">
            <label class="form-check-label" for="runduration">
                Tracks Run Duration (Hours)
            </label>
        </div>

        <!-- Smart Auto-Backfill Options -->
        <div id="create-auto-backfill-options" style="display: none;">
            <div class="form-check mb-3 ms-3">
                <input class="form-check-input" type="checkbox" value="1" id="create_daily_run" name="daily_run">
                <label class="form-check-label" for="create_daily_run">
                    This machine runs daily (Enable Auto-Backfill)
                </label>
            </div>

            <div id="create-rest-day-options" class="ms-3" style="display: none;">
                <div class="form-check mb-3 ms-3">
                    <input class="form-check-input" type="checkbox" value="1" id="create_has_rest_day" name="has_rest_day">
                    <label class="form-check-label" for="create_has_rest_day">
                        Has a scheduled weekly rest day
                    </label>
                </div>

                <div id="create-rest-day-select-container" class="mb-3 ms-5" style="display: none;">
                    <label for="create_rest_day" class="form-label">Rest Day</label>
                    <select id="create_rest_day" name="rest_day" class="form-select form-select-sm">
                        <option value="-1" selected>No Rest Day</option>
                        <option value="0">Sunday</option>
                        <option value="1">Monday</option>
                        <option value="2">Tuesday</option>
                        <option value="3">Wednesday</option>
                        <option value="4">Thursday</option>
                        <option value="5">Friday</option>
                        <option value="6">Saturday</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="Working" selected>Working</option>
                <option value="OffRoad">Off-Road</option>
                <option value="Condemned">Condemned</option>
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save Machine</button>
    </div>
</form>

<script>
    (function() {
        const rundurationCheck = document.getElementById('runduration');
        const backfillOptions = document.getElementById('create-auto-backfill-options');
        const dailyRunCheck = document.getElementById('create_daily_run');
        const restDayOptions = document.getElementById('create-rest-day-options');
        const hasRestDayCheck = document.getElementById('create_has_rest_day');
        const restDaySelectContainer = document.getElementById('create-rest-day-select-container');

        const updateVisibility = () => {
            if(rundurationCheck) backfillOptions.style.display = rundurationCheck.checked ? 'block' : 'none';
            if(dailyRunCheck) restDayOptions.style.display = dailyRunCheck.checked ? 'block' : 'none';
            if(hasRestDayCheck) restDaySelectContainer.style.display = hasRestDayCheck.checked ? 'block' : 'none';
        };

        if(rundurationCheck) {
            rundurationCheck.addEventListener('change', () => {
                if (!rundurationCheck.checked) {
                    if(dailyRunCheck) dailyRunCheck.checked = false;
                    if(hasRestDayCheck) hasRestDayCheck.checked = false;
                }
                updateVisibility();
            });
        }
        if(dailyRunCheck) {
            dailyRunCheck.addEventListener('change', () => {
                if (!dailyRunCheck.checked) {
                    if(hasRestDayCheck) hasRestDayCheck.checked = false;
                }
                updateVisibility();
            });
        }
        if(hasRestDayCheck) {
            hasRestDayCheck.addEventListener('change', () => {
                updateVisibility();
            });
        }

        updateVisibility();
    })();
</script>