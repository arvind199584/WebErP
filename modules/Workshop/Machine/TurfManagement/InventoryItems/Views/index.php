<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">📦 Store Inventory Management</h1>
            <p class="text-muted mb-0">Browse and manage items divided into 5 core sub-store divisions.</p>
        </div>
        <div>
            <?php if (strtolower($currentUser['role']) === 'superuser'): ?>
                <button
                    class="btn btn-outline-primary fw-bold me-2"
                    hx-get="?action=showCreateForm"
                    hx-target="#modal-body"
                    hx-trigger="click"
                    data-bs-toggle="modal"
                    data-bs-target="#main-modal">
                    ➕ Add New Item Catalog
                </button>
            <?php endif; ?>
            <a href="/modules/Workshop/Machine/TurfManagement/Receipts/Controller/ReceiptController.php?action=showCreateForm" class="btn btn-success fw-bold">
                📥 Create RV Entry (Insert Stock Receipt)
            </a>
        </div>
    </div>

    <!-- Alert for HTMX messages -->
    <div id="alert-container"></div>

    <!-- 5 Core Sub-Store Division Buttons -->
    <div class="mb-3">
        <label class="form-label fw-bold text-muted small text-uppercase">Select a Sub-Store Division to View Items:</label>
        <ul class="nav nav-pills bg-light p-2 rounded border" id="storeDivisionTabs" role="tablist">
            <li class="nav-item me-2" role="presentation">
                <button class="nav-link btn btn-outline-danger fw-bold border" id="tab-fuel" data-bs-toggle="pill" data-bs-target="#content-fuel" type="button" onclick="filterStoreCategory('fuel')">
                    ⛽ Fuel / Oil
                </button>
            </li>
            <li class="nav-item me-2" role="presentation">
                <button class="nav-link btn btn-outline-warning fw-bold text-dark border" id="tab-workshop" data-bs-toggle="pill" data-bs-target="#content-workshop" type="button" onclick="filterStoreCategory('workshop')">
                    🛠️ Workshop Store (<?php echo count($spareParts); ?> Spares)
                </button>
            </li>
            <li class="nav-item me-2" role="presentation">
                <button class="nav-link btn btn-outline-success fw-bold border" id="tab-agronomy" data-bs-toggle="pill" data-bs-target="#content-agronomy" type="button" onclick="filterStoreCategory('agronomy')">
                    🌱 Agronomy Store
                </button>
            </li>
            <li class="nav-item me-2" role="presentation">
                <button class="nav-link btn btn-outline-info fw-bold text-dark border" id="tab-housekeeping" data-bs-toggle="pill" data-bs-target="#content-housekeeping" type="button" onclick="filterStoreCategory('housekeeping')">
                    🧹 Housekeeping Items
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link btn btn-outline-secondary fw-bold border" id="tab-stationery" data-bs-toggle="pill" data-bs-target="#content-stationery" type="button" onclick="filterStoreCategory('stationery')">
                    ✏️ Stationery
                </button>
            </li>
        </ul>
    </div>

    <!-- Initial Prompt Container when no button is selected -->
    <div id="initial-prompt-card" class="card border-0 shadow-sm text-center py-5 my-3 bg-light">
        <div class="card-body">
            <div class="display-4 text-muted mb-3">🏢</div>
            <h4 class="fw-bold text-dark">Select a Sub-Store Division</h4>
            <p class="text-muted max-w-md mx-auto">Click any of the 5 buttons above (Fuel, Workshop, Agronomy, Housekeeping, or Stationery) to browse inventory items and stock levels.</p>
        </div>
    </div>

    <div class="table-responsive shadow-sm rounded border" id="item-table-container" style="display: none;">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr id="table-header-row">
                    <th id="th-col-1" style="width: 180px;">Item / Part ID</th>
                    <th id="th-col-2">Description / Nomenclature</th>
                    <th id="th-col-3" class="text-center" style="width: 240px;">Available Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items) || !empty($spareParts)): ?>
                    
                    <!-- General Inventory Items -->
                    <?php foreach ($items as $item): 
                        $cat = strtolower($item['category_name'] ?? '');
                        
                        // Map category to 5 main Divisions
                        if (str_contains($cat, 'fuel') || str_contains($cat, 'lubricant') || str_contains($cat, 'oil')) {
                            $divCode = 'fuel';
                        } elseif (str_contains($cat, 'spare')) {
                            $divCode = 'workshop';
                        } elseif (str_contains($cat, 'sanitary') || str_contains($cat, 'housekeeping')) {
                            $divCode = 'housekeeping';
                        } elseif (str_contains($cat, 'station')) {
                            $divCode = 'stationery';
                        } else {
                            $divCode = 'agronomy';
                        }

                        $itemStk = (float)($item['current_stock'] ?? 0);
                        $itemStkClass = ($itemStk > 0) ? 'bg-success' : 'bg-danger';
                    ?>
                        <tr class="store-item-row" data-division="<?php echo $divCode; ?>" style="display: none;">
                            <td class="col-item-id">
                                <span class="badge bg-secondary font-monospace fs-6">ITEM-<?php echo sprintf('%03d', $item['id']); ?></span>
                            </td>
                            <td class="col-chem-type" style="display: none;">
                                <?php if (!empty($item['category_name'])): ?>
                                    <span class="badge bg-success fs-6 px-3 py-2 fw-bold">🌱 <?php echo htmlspecialchars($item['category_name']); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary fs-6 px-3 py-2">General Agronomy</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold fs-6 text-dark">
                                <?php echo htmlspecialchars($item['description']); ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?php echo $itemStkClass; ?> fs-5 px-3 py-2 fw-bold shadow-sm">
                                    <?php echo number_format($itemStk, 2); ?> <span class="fs-6 opacity-75"><?php echo htmlspecialchars($item['ac_unit']); ?></span>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <!-- Workshop Spare Parts Grouped by Machine Category -->
                    <?php if (!empty($spareParts)): 
                        // Group spare parts by group_name (e.g. Toro Green Mowers, John Deere Tractors, Cushman Vehicles)
                        $groupedSpares = [];
                        foreach ($spareParts as $sp) {
                            $gName = $sp['group_name'] ?? $sp['machine_name'] ?? 'General Workshop';
                            $groupedSpares[$gName][] = $sp;
                        }
                    ?>
                        <?php foreach ($groupedSpares as $groupName => $sparesList): ?>
                            <!-- Machine Group Header Row -->
                            <tr class="store-item-row bg-warning-subtle table-warning border-top border-bottom border-warning" data-division="workshop" style="display: none;">
                                <td colspan="3" class="fw-bold py-2 text-dark fs-6">
                                    🚜 <strong><?php echo htmlspecialchars($groupName); ?></strong> 
                                    <span class="badge bg-dark text-warning ms-2"><?php echo count($sparesList); ?> Spare Parts</span>
                                </td>
                            </tr>

                            <?php foreach ($sparesList as $sp): 
                                $stk = (float)($sp['current_stock'] ?? 0);
                                $stkClass = ($stk > 0) ? 'bg-success' : 'bg-danger';
                                $partNoDisplay = !empty($sp['part_no']) ? ' (' . htmlspecialchars($sp['part_no']) . ')' : '';
                            ?>
                                <tr class="store-item-row" data-division="workshop" style="display: none;">
                                    <td>
                                        <span class="badge bg-secondary font-monospace fs-6">PART-<?php echo sprintf('%03d', $sp['id']); ?></span>
                                    </td>
                                    <td class="fw-bold text-dark fs-6 ps-3">
                                        🔧 <?php echo htmlspecialchars($sp['nomenclature']) . '<span class="text-primary font-monospace fs-6">' . $partNoDisplay . '</span>'; ?>
                                    </td>
                                    <td class="text-center">
                                        <!-- PROMINENT QUANTITY AVAILABLE BADGE -->
                                        <span class="badge <?php echo $stkClass; ?> fs-5 px-3 py-2 fw-bold shadow-sm">
                                            <?php echo number_format($stk, 0); ?> <span class="fs-6 opacity-75"><?php echo htmlspecialchars($sp['unit'] ?? 'Pcs'); ?></span>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">No inventory items found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterStoreCategory(divCode) {
    const promptCard = document.getElementById('initial-prompt-card');
    const tableContainer = document.getElementById('item-table-container');
    const thCol1 = document.getElementById('th-col-1');
    const thCol2 = document.getElementById('th-col-2');
    const thCol3 = document.getElementById('th-col-3');
    const rows = document.querySelectorAll('.store-item-row');

    if (promptCard) promptCard.style.display = 'none';
    if (tableContainer) tableContainer.style.display = 'block';

    if (divCode === 'agronomy') {
        thCol1.textContent = 'Chemical / Item Type';
        thCol1.style.width = '240px';
        thCol2.textContent = 'Item Description';
        thCol3.textContent = 'Available Quantity';
    } else if (divCode === 'workshop') {
        thCol1.textContent = 'Part ID';
        thCol1.style.width = '180px';
        thCol2.textContent = 'Part Name & Number';
        thCol3.textContent = 'Available Stock';
    } else {
        thCol1.textContent = 'Item ID';
        thCol1.style.width = '180px';
        thCol2.textContent = 'Description';
        thCol3.textContent = 'Available Quantity';
    }

    rows.forEach(row => {
        if (row.getAttribute('data-division') === divCode) {
            row.style.display = '';
            const colItemId = row.querySelector('.col-item-id');
            const colChemType = row.querySelector('.col-chem-type');
            
            if (divCode === 'agronomy') {
                if (colItemId) colItemId.style.display = 'none';
                if (colChemType) colChemType.style.display = '';
            } else {
                if (colItemId) colItemId.style.display = '';
                if (colChemType) colChemType.style.display = 'none';
            }
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<!-- Bootstrap Modal -->
<div class="modal fade" id="main-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div id="modal-body">
                <!-- Content will be loaded here by HTMX -->
            </div>
        </div>
    </div>
</div>

<script>
    // Listen for custom events from the backend to close modal and show alerts
    document.body.addEventListener('htmx:afterRequest', function(event) {
        if(event.detail.successful && event.detail.target.id === 'item-table-container') {
             // Close the modal if a form was successfully submitted
             var myModalEl = document.getElementById('main-modal');
             var modal = bootstrap.Modal.getInstance(myModalEl);
             if (modal) {
                 modal.hide();
             }
        }
    });
</script>
