<?php
// This is a placeholder for the delete logic.
$office_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// In a real application, you would:
// 1. Verify user permissions.
// 2. Connect to the database.
// 3. Execute a DELETE statement for the given $office_id.
// 4. Add error handling.

echo "Simulating deletion of Office ID: " . htmlspecialchars($office_id);

// Redirect back to the main list after a short delay.
header("Refresh:2; url=index.php");
echo "<br><br>You will be redirected back to the office list shortly.";
exit;
?>
