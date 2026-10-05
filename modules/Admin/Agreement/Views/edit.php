<?php
/**
 * @var \App\Modules\Agreement\DTO\AgreementDTO $agreement
 * @var array $aa_es_list
 * @var array $agencies
 * @var array $activeAgreements
 */
?>

<div class="container mt-4">
    <h2>Edit Agreement</h2>
    <hr>

    <form action="?action=update" method="POST">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <input type="hidden" name="id" value="<?php echo $agreement->id; ?>">

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="aa_es_id" class="form-label"><strong>AA&ES Reference</strong></label>
                    <select id="aa_es_id" name="aa_es_id" class="form-select" required>
                        <?php foreach ($aa_es_list as $aa_es): ?>
                            <option value="<?php echo $aa_es->id; ?>" <?php echo ($agreement->aa_es_id == $aa_es->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($aa_es->file_number . ' - ' . $aa_es->subject); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="agency_id" class="form-label"><strong>Agency</strong></label>
                    <select id="agency_id" name="agency_id" class="form-select" required>
                        <?php foreach ($agencies as $agency): ?>
                            <option value="<?php echo $agency->id; ?>" <?php echo ($agreement->agency_id == $agency->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($agency->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="agreement_number" class="form-label"><strong>Agreement Number</strong></label>
                    <input type="text" class="form-control" id="agreement_number" name="agreement_number" value="<?php echo htmlspecialchars($agreement->agreement_number); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="agreement_date" class="form-label"><strong>Agreement Date</strong></label>
                    <input type="date" class="form-control" id="agreement_date" name="agreement_date" value="<?php echo htmlspecialchars($agreement->agreement_date); ?>" required>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="period_from" class="form-label"><strong>Period From</strong></label>
                    <input type="date" class="form-control" id="period_from" name="period_from" value="<?php echo htmlspecialchars($agreement->period_from); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="period_to" class="form-label"><strong>Period To</strong></label>
                    <input type="date" class="form-control" id="period_to" name="period_to" value="<?php echo htmlspecialchars($agreement->period_to); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="predecessor_id" class="form-label"><strong>Predecessor Agreement (if any)</strong></label>
                    <select id="predecessor_id" name="predecessor_id" class="form-select">
                        <option value="">None</option>
                        <?php foreach ($activeAgreements as $active): ?>
                            <option value="<?php echo $active->id; ?>" <?php echo ($agreement->predecessor_id == $active->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($active->agreement_number . ' (' . $active->agency_name . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
