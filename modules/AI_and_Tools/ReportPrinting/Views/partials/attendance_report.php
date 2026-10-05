<?php
    $month = $filters['month'] ?? date('Y-m');
    $daysInMonth = date('t', strtotime($month . '-01'));
    $header = $reportData['header'];
    $rows = $reportData['data'];
?>

<!-- Attendance Report Header -->
<div class="report-header">
    <div class="report-title">
        <h1>ABSENTEE STATEMENT</h1>
        <p>For the Month of: <?= date('F Y', strtotime($month)) ?></p>
    </div>
    <div class="report-meta">
        <div class="meta-left">
            <p><strong>Name of Work:</strong> <?= htmlspecialchars($header['name_of_work']) ?></p>
            <p><strong>Sub-Head:</strong> <?= htmlspecialchars($header['sub_head']) ?></p>
        </div>
        <div class="meta-right">
            <p><strong>Agreement No:</strong> <?= htmlspecialchars($header['agreement_no']) ?></p>
            <p><strong>Agency:</strong> <?= htmlspecialchars($header['agency_name']) ?></p>
        </div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>S.No</th>
            <th style="text-align:left;">Name</th>
            <th style="text-align:left;">Designation</th>
            <?php for($d=1; $d<=$daysInMonth; $d++): ?>
                <th><?= $d ?></th>
            <?php endfor; ?>
            <!-- Summary Columns -->
            <th style="background-color: #ddd;">P</th>
            <th style="background-color: #ddd;">A</th>
            <th style="background-color: #ddd;">R</th>
            <th style="background-color: #ddd;">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php $sno=1; foreach ($rows as $row):
            $countP = 0; $countA = 0; $countR = 0;
        ?>
        <tr>
            <td><?= $sno++ ?></td>
            <td style="text-align:left; white-space: nowrap;"><?= htmlspecialchars($row['name']) ?></td>
            <td style="text-align:left; white-space: nowrap;"><?= htmlspecialchars($row['designation']) ?></td>
            <?php for($d=1; $d<=$daysInMonth; $d++):
                $status = $row['days'][$d] ?? '';
                $display = ($status === 'L') ? '' : $status;
                $class = 'status-' . ($status ?: 'L');
                if ($status === 'L') $class = 'status-L';

                // Count Stats
                if ($status === 'P') $countP++;
                if ($status === 'A') $countA++;
                if ($status === 'R') $countR++;
            ?>
                <td class="<?= $class ?>"><?= htmlspecialchars($display) ?></td>
            <?php endfor; ?>
            <!-- Summary Data -->
            <td><strong><?= $countP ?></strong></td>
            <td><strong><?= $countA ?></strong></td>
            <td><strong><?= $countR ?></strong></td>
            <td><strong><?= $countP + $countA + $countR ?></strong></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Attendance Report Footer -->
<div class="report-footer">
    <div>Created by</div>
    <div>Checked by</div>
    <div>Certified by</div>
</div>
