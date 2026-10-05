<!DOCTYPE html>
<html>
<head>
    <title>AI Training Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1100px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; vertical-align: top; }
        th { background-color: #e9ecef; }
        textarea { width: 100%; height: 60px; font-family: monospace; padding: 5px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { padding: 8px 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-magic { background-color: #6f42c1; margin-bottom: 5px; font-size: 11px; }

        /* Tabs */
        .tab-container { margin-bottom: 10px; }
        .tab-btn { padding: 5px 10px; cursor: pointer; background: #eee; border: 1px solid #ccc; border-bottom: none; border-radius: 4px 4px 0 0; }
        .tab-btn.active { background: #fff; border-bottom: 2px solid #fff; font-weight: bold; }
        .tab-content { display: none; padding: 10px; border: 1px solid #ccc; background: #f9f9f9; }
        .tab-content.active { display: block; }

        /* Builder Styles */
        .builder-row { margin-bottom: 10px; }
        .builder-row label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 3px; }
        .builder-row select, .builder-row input { width: 100%; padding: 5px; }
        .col-checkboxes { max-height: 100px; overflow-y: auto; border: 1px solid #ccc; background: #fff; padding: 5px; }
        .col-checkboxes label { display: block; font-weight: normal; font-size: 12px; }
    </style>
    <script>
        async function magicCorrect(id) {
            const textarea = document.getElementById('sql_' + id);
            const fuzzySql = textarea.value;
            if (!fuzzySql) return;
            textarea.placeholder = "Correcting...";
            try {
                const response = await fetch('?action=normalize_sql_ajax&sql=' + encodeURIComponent(fuzzySql));
                const data = await response.json();
                textarea.value = data.exact_sql;
            } catch (error) { alert("Magic correction failed."); }
        }

        function switchTab(id, tabName) {
            document.getElementById('tab_manual_' + id).classList.remove('active');
            document.getElementById('tab_builder_' + id).classList.remove('active');
            document.getElementById('content_manual_' + id).classList.remove('active');
            document.getElementById('content_builder_' + id).classList.remove('active');

            document.getElementById('tab_' + tabName + '_' + id).classList.add('active');
            document.getElementById('content_' + tabName + '_' + id).classList.add('active');
        }

        async function loadTables(id) {
            const select = document.getElementById('table_select_' + id);
            if (select.options.length > 1) return; // Already loaded

            const response = await fetch('?action=get_tables_ajax');
            const tables = await response.json();
            tables.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t;
                opt.textContent = t;
                select.appendChild(opt);
            });
        }

        async function loadColumns(id, table) {
            const container = document.getElementById('cols_container_' + id);
            container.innerHTML = 'Loading...';

            const response = await fetch('?action=get_columns_ajax&table=' + table);
            const cols = await response.json();

            container.innerHTML = '';
            cols.forEach(c => {
                const label = document.createElement('label');
                label.innerHTML = `<input type="checkbox" name="builder_cols[]" value="${c}"> ${c}`;
                container.appendChild(label);
            });
        }
    </script>
</head>
<body>
<div class="container">
    <h1>AI Training Hub (Superuser)</h1>
    <p>Review questions and provide SQL. Use <b>Magic Correct</b> or the <b>No-Code Builder</b>.</p>

    <?php if (empty($pending)): ?>
        <p>No pending training requests.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User Question</th>
                    <th style="width: 50%;">Provide SQL Query</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending as $item): ?>
                <?php 
                $decoded = json_decode($item['user_query'], true);
                $isJson = ($decoded && is_array($decoded));
                $questionText = $isJson ? ($decoded['question'] ?? '') : $item['user_query'];
                ?>
                <tr>
                    <td><?= date('d.m.y H:i', strtotime($item['created_at'])) ?></td>
                    <td>
                        <?php if ($isJson): ?>
                            <strong><?= htmlspecialchars($questionText) ?></strong><br>
                            <div style="font-size: 11px; margin-top: 5px; color: #555; background: #f8f9fa; padding: 5px; border-left: 3px solid #007bff; border-radius: 3px; line-height: 1.4;">
                                <strong>Level 1 (NLP):</strong> <?= htmlspecialchars($decoded['level_1'] ?? 'No match') ?><br>
                                <strong>Level 2 (LLM):</strong> <?= htmlspecialchars($decoded['level_2'] ?? 'Skipped/Failed') ?>
                            </div>
                        <?php else: ?>
                            <strong><?= htmlspecialchars($item['user_query']) ?></strong>
                        <?php endif; ?>
                    </td>

                    <td>
                        <div class="tab-container">
                            <span id="tab_manual_<?= $item['id'] ?>" class="tab-btn active" onclick="switchTab(<?= $item['id'] ?>, 'manual')">Manual SQL</span>
                            <span id="tab_builder_<?= $item['id'] ?>" class="tab-btn" onclick="switchTab(<?= $item['id'] ?>, 'builder'); loadTables(<?= $item['id'] ?>)">No-Code Builder</span>
                        </div>

                        <!-- MANUAL TAB -->
                        <form method="POST" action="?action=resolve_training" id="form_manual_<?= $item['id'] ?>">
                            <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="text" value="<?= htmlspecialchars($questionText) ?>">

                            <div id="content_manual_<?= $item['id'] ?>" class="tab-content active">
                                <button type="button" class="btn btn-magic" onclick="magicCorrect(<?= $item['id'] ?>)">✨ Magic Correct</button>
                                <textarea name="sql" id="sql_<?= $item['id'] ?>" placeholder="e.g. SELECT name FROM staff"></textarea>
                                <div style="margin-top: 5px; text-align: right;">
                                    <button type="submit" class="btn">Train AI</button>
                                </div>
                            </div>
                        </form>

                        <!-- BUILDER TAB -->
                        <form method="POST" action="?action=resolve_training" id="form_builder_<?= $item['id'] ?>">
                            <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="text" value="<?= htmlspecialchars($questionText) ?>">

                            <div id="content_builder_<?= $item['id'] ?>" class="tab-content">
                                <div class="builder-row">
                                    <label>1. Select Table:</label>
                                    <select name="builder_table" id="table_select_<?= $item['id'] ?>" onchange="loadColumns(<?= $item['id'] ?>, this.value)" required>
                                        <option value="">-- Select --</option>
                                    </select>
                                </div>
                                <div class="builder-row">
                                    <label>2. Select Columns:</label>
                                    <div id="cols_container_<?= $item['id'] ?>" class="col-checkboxes">Select a table first</div>
                                </div>
                                <div class="builder-row">
                                    <label>3. Condition (Optional):</label>
                                    <input type="text" name="builder_condition" placeholder="e.g. status = 'Active'">
                                </div>
                                <div style="margin-top: 5px; text-align: right;">
                                    <button type="submit" class="btn">Train AI</button>
                                </div>
                            </div>
                        </form>
                    </td>
                    <td></td> <!-- Action button moved inside forms -->
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
