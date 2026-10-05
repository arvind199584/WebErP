<?php if (empty($items)): ?>
    <tr><td colspan="4" class="text-center text-muted py-3">No items found in this voucher.</td></tr>
<?php else: ?>
    <?php foreach ($items as $index => $item): ?>
        <tr>
            <td><?php echo $index + 1; ?></td>
            <td class="fw-bold"><?php echo htmlspecialchars($item['item_description']); ?></td>
            <td><?php echo htmlspecialchars((string)$item['quantity']); ?></td>
            <td><?php echo htmlspecialchars($item['ac_unit']); ?></td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
