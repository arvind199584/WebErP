<?php
/** @var array|null $response */
/** @var string $query */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Assistant</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; box-sizing: border-box; }
        .btn { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .result-box { margin-top: 30px; padding: 20px; background-color: #f8f9fa; border-radius: 5px; border-left: 5px solid #007bff; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #e9ecef; }
        .teach-box { margin-top: 20px; padding: 15px; background-color: #e3f2fd; border: 1px solid #bbdefb; border-radius: 5px; }
        .teach-box h4 { margin-top: 0; color: #0d47a1; }
        .sql-input { font-family: 'Courier New', monospace; font-size: 14px; height: 100px; margin-top: 10px; }
        .magic-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .magic-input { flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-magic { background-color: #6f42c1; font-size: 14px; padding: 8px 15px; }
    </style>
    <script>
        async function magicConvert() {
            const instruction = document.getElementById('magic_instruction').value;
            const sqlBox = document.getElementById('sql_box');

            if (!instruction) return;

            sqlBox.placeholder = "Generating SQL...";
            try {
                // We use the normalize endpoint which does fuzzy mapping
                const response = await fetch('?action=normalize_sql_ajax&sql=' + encodeURIComponent(instruction));
                const data = await response.json();
                sqlBox.value = data.exact_sql;
            } catch (error) {
                alert("Magic conversion failed.");
            }
        }
    </script>
</head>
<body>
<div class="container">
    <h1>Database Assistant</h1>

    <form method="POST" action="?action=nlp">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <textarea name="query" placeholder="Type your question here..." required><?= htmlspecialchars($query) ?></textarea>
        <button type="submit" class="btn">Ask AI</button>
    </form>

    <?php if ($response): ?>
        <div class="result-box">
            <div style="font-size: 12px; color: #666; margin-bottom: 10px;">
                Source: <strong><?= htmlspecialchars($response['predicted_intent'] ?? 'AI Prediction') ?></strong>
            </div>

            <?php if ($response['type'] === 'text'): ?>
                <p><strong>Answer:</strong> <?= htmlspecialchars($response['data']) ?></p>
            <?php elseif ($response['type'] === 'table'): ?>
                <?php if (empty($response['data'])): ?>
                    <p>No results found.</p>
                <?php else: ?>
                    <table>
                        <thead><tr><?php foreach (array_keys($response['data'][0]) as $h): ?><th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $h))) ?></th><?php endforeach; ?></tr></thead>
                        <tbody><?php foreach ($response['data'] as $row): ?><tr><?php foreach ($row as $c): ?><td><?= htmlspecialchars((string)$c) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="teach-box">
            <h4>Teach the AI (Knowledge Base)</h4>
            <p>If the AI was wrong, you can provide the SQL manually OR use the Magic Converter.</p>

            <div class="magic-row">
                <input type="text" id="magic_instruction" class="magic-input" placeholder="e.g. Select name from staff where office is dgcd">
                <button type="button" class="btn btn-magic" onclick="magicConvert()">✨ Magic Convert</button>
            </div>

            <form method="POST" action="?action=train_nlp">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                <input type="hidden" name="text" value="<?= htmlspecialchars($query) ?>">
                <textarea name="sql" id="sql_box" class="sql-input" placeholder="SELECT * FROM ..."></textarea>
                <button type="submit" class="btn" style="background-color: #28a745;">Save to Knowledge Base</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
