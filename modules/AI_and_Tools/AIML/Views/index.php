<?php
/**
 * @var array|null $response The query execution result passed from the controller.
 * @var string $query The original query string.
 */
$currentUser = \App\Modules\Superadmin\Users\Services\UserService::getCurrentUser();
$isSuperUser = ($currentUser && $currentUser['role'] === 'superuser');

// Auto-charting engine detection logic
$chartData = null;
$isChartable = false;
$data = [];

if ($response && $response['type'] !== 'text' && !empty($response['data'])) {
    $data = $response['data'];
    $keys = array_keys($data[0]);
    $numericKeys = [];
    $labelKey = null;
    
    // Blacklisted internal keys
    $blacklist = ['id', 'officeid', 'agreement_id', 'employee_id', 'budgetid', 'agency_id', 'aa_es_id'];
    
    foreach ($keys as $key) {
        $lowKey = strtolower($key);
        if (in_array($lowKey, $blacklist)) continue;
        
        if (is_numeric($data[0][$key])) {
            $numericKeys[] = $key;
        } else if ($labelKey === null) {
            $labelKey = $key;
        }
    }
    
    // Check if chartable (needs at least one label, one numeric value, and > 1 records)
    if ($labelKey && !empty($numericKeys) && count($data) > 1) {
        $isChartable = true;
        $labels = [];
        $datasets = [];
        
        // Colors palette (premium gradients)
        $colors = [
            ['bg' => 'rgba(79, 70, 229, 0.65)', 'border' => 'rgba(79, 70, 229, 1)'],   # Indigo
            ['bg' => 'rgba(16, 185, 129, 0.65)', 'border' => 'rgba(16, 185, 129, 1)'], # Emerald
            ['bg' => 'rgba(245, 158, 11, 0.65)', 'border' => 'rgba(245, 158, 11, 1)'], # Amber
            ['bg' => 'rgba(239, 68, 68, 0.65)', 'border' => 'rgba(239, 68, 68, 1)'],   # Rose
            ['bg' => 'rgba(6, 182, 212, 0.65)', 'border' => 'rgba(6, 182, 212, 1)']    # Cyan
        ];
        
        $colorIdx = 0;
        foreach ($numericKeys as $nKey) {
            $c = $colors[$colorIdx % count($colors)];
            $datasets[$nKey] = [
                'label' => ucwords(str_replace('_', ' ', $nKey)),
                'data' => [],
                'backgroundColor' => $c['bg'],
                'borderColor' => $c['border'],
                'borderWidth' => 2,
                'fill' => false,
                'tension' => 0.1
            ];
            $colorIdx++;
        }
        
        foreach ($data as $row) {
            $labels[] = (string)$row[$labelKey];
            foreach ($numericKeys as $nKey) {
                $datasets[$nKey]['data'][] = (float)$row[$nKey];
            }
        }
        
        $chartData = [
            'labels' => $labels,
            'datasets' => array_values($datasets)
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIML Intelligence Assistant</title>
    <!-- Include premium Google fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Include Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Include SheetJS for Excel exports -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); 
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .container { 
            max-width: 1000px; 
            margin: 30px auto; 
            background: rgba(255, 255, 255, 0.95); 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); 
            backdrop-filter: blur(10px);
        }
        h1 { 
            color: #1e293b; 
            text-align: center; 
            margin-top: 0;
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        .chat-box { margin-bottom: 25px; position: relative; }
        textarea { 
            width: 100%; 
            height: 110px; 
            padding: 18px; 
            border: 2px solid #e2e8f0; 
            border-radius: 10px; 
            font-size: 16px; 
            font-family: inherit;
            box-sizing: border-box; 
            transition: all 0.3s ease; 
            resize: none;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }
        textarea:focus { 
            border-color: #4f46e5; 
            outline: none; 
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        .controls { 
            display: flex; 
            gap: 12px; 
            align-items: center; 
            margin-top: 12px; 
        }
        .btn { 
            padding: 12px 28px; 
            background-color: #4f46e5; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: 600; 
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }
        .btn:hover { 
            background-color: #4338ca; 
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -1px rgba(79, 70, 229, 0.3);
        }
        .btn-mic { 
            background-color: #ef4444; 
            padding: 12px 18px; 
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2);
        }
        .btn-mic:hover {
            background-color: #dc2626;
            box-shadow: 0 6px 12px -1px rgba(239, 68, 68, 0.3);
        }
        .btn-mic.recording { 
            animation: pulse 1.5s infinite; 
        }
        @keyframes pulse { 
            0% { transform: scale(1); opacity: 1; } 
            50% { transform: scale(0.95); opacity: 0.7; } 
            100% { transform: scale(1); opacity: 1; } 
        }
        .example-queries {
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .example-btn {
            background: white;
            border: 1px solid #cbd5e1;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .example-btn:hover {
            border-color: #4f46e5;
            color: #4f46e5;
            background: rgba(79, 70, 229, 0.02);
            transform: scale(1.02);
        }
        .result-container { 
            margin-top: 30px; 
            padding: 30px; 
            background-color: #ffffff; 
            border-radius: 12px; 
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
            font-size: 14px; 
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        th, td { 
            padding: 14px 16px; 
            text-align: left; 
            border-bottom: 1px solid #e2e8f0;
        }
        th { 
            background-color: #f8fafc; 
            font-weight: 600;
            color: #1e293b;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:hover {
            background-color: #f8fafc;
        }
        .hub-link { 
            display: inline-block; 
            float: right;
            background: #10b981; 
            color: white; 
            text-decoration: none; 
            font-weight: 600; 
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
            transition: all 0.2s;
        }
        .hub-link:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        .group-header { 
            background: linear-gradient(90deg, #4f46e5 0%, #6366f1 100%); 
            color: white; 
            padding: 12px 20px; 
            border-radius: 8px; 
            margin-top: 25px; 
            font-weight: 600; 
            font-size: 1.1rem;
        }
    </style>
    <script>
        function setQuery(text) {
            const input = document.getElementById('query-input');
            input.value = text;
            document.getElementById('query-form').submit();
        }

        async function reportWrongOutput() {
            const btn = document.getElementById('report-wrong-btn');
            if (btn.disabled) return;
            btn.textContent = "Logging...";
            btn.disabled = true;
            
            const query = <?= json_encode($query) ?>;
            const sql = <?= json_encode($response['sql'] ?? '') ?>;
            
            const formData = new FormData();
            formData.append('query', query);
            formData.append('sql', sql);
            formData.append('csrf_token', '<?= \App\Core\CSRFManager::generateToken() ?>');
            
            try {
                const response = await fetch('?action=report_wrong_ajax', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    btn.textContent = "✓ Logged for Training";
                    btn.style.backgroundColor = "#10b981";
                    alert("Logged successfully. An administrator will review and train the AI on this query in the Training Hub.");
                } else {
                    btn.textContent = "⚠️ Error Logging";
                    btn.disabled = false;
                    btn.style.backgroundColor = "#dc2626";
                    alert("Error: " + (result.error || "Failed to log."));
                }
            } catch (error) {
                btn.textContent = "⚠️ Error Logging";
                btn.disabled = false;
                btn.style.backgroundColor = "#dc2626";
                alert("Failed to connect to the server.");
            }
        }
    </script>
</head>
<body>
<div class="container">
    <?php if ($isSuperUser): ?>
        <a href="?action=training_hub" class="hub-link">⚙️ Training Hub</a>
    <?php endif; ?>

    <h1>AIML Assistant</h1>
    <p style="text-align: center; color: #64748b; font-size: 1.1rem; margin-bottom: 25px; margin-top: -10px;">Ask reporting questions about your office data in English or Hindi.</p>

    <!-- Query Form -->
    <form method="POST" action="?action=ask" id="query-form">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <div class="chat-box">
            <textarea name="query" id="query-input" placeholder="Type your question here..." required><?= htmlspecialchars($query ?? '') ?></textarea>
            <div class="controls">
                <button type="submit" class="btn" id="ask-btn">Ask Assistant</button>
                <button type="button" id="mic-btn" class="btn btn-mic" title="Speak your question">🎤</button>
            </div>
        </div>
    </form>

    <!-- Prompt Library Suggestions -->
    <div class="example-queries">
        <h3 style="font-size: 0.95rem; color: #475569; margin-top: 0; margin-bottom: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">💡 Suggested Prompts</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <button type="button" class="example-btn" onclick="setQuery('Show all agreements')">All Agreements</button>
            <button type="button" class="example-btn" onclick="setQuery('List all employees')">List of Employees</button>
            <button type="button" class="example-btn" onclick="setQuery('How much is the total budget allocated')">Total Budget Allocation</button>
            <button type="button" class="example-btn" onclick="setQuery('Show fuel consumption last month')">Fuel Consumption</button>
            <button type="button" class="example-btn" onclick="setQuery('List of off road machines')">Off Road Machinery</button>
        </div>
    </div>

    <!-- Speech Recognition Helper Script -->
    <script>
        const micBtn = document.getElementById('mic-btn');
        const queryInput = document.getElementById('query-input');
        const queryForm = document.getElementById('query-form');

        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognition = new SpeechRecognition();

            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'en-IN'; // Support Indian Accent

            micBtn.addEventListener('click', () => {
                if (micBtn.classList.contains('recording')) {
                    recognition.stop();
                } else {
                    recognition.start();
                }
            });

            recognition.onstart = () => {
                micBtn.classList.add('recording');
                micBtn.textContent = '🛑';
                queryInput.placeholder = 'Listening... Please speak your prompt...';
            };

            recognition.onend = () => {
                micBtn.classList.remove('recording');
                micBtn.textContent = '🎤';
                queryInput.placeholder = 'Type your question here...';
            };

            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                queryInput.value = transcript;
            };

            recognition.onerror = (event) => {
                console.error('Speech recognition error', event.error);
                micBtn.classList.remove('recording');
                micBtn.textContent = '🎤';
            };
        } else {
            micBtn.style.display = 'none';
        }
    </script>

    <!-- Response Area -->
    <?php if ($response): ?>
        <div class="result-container" id="results-wrapper">
            <!-- 1. Text Responses -->
            <?php if ($response['type'] === 'text'): ?>
                <div style="font-size: 1.1rem; color: #334155; line-height: 1.6;">
                    <?= htmlspecialchars(is_array($response['data']) ? (array_values($response['data'][0] ?? [])[0] ?? 'No data') : $response['data']) ?>
                </div>
            
            <!-- 2. Structured Table Responses -->
            <?php else: ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                    <h3 style="margin: 0; color: #1e293b; font-weight: 600;">Report Results</h3>
                    <div style="display: flex; gap: 8px;">
                        <button class="btn" style="background-color: #10b981; font-size: 14px; padding: 8px 16px; box-shadow: none;" onclick="exportTableToExcel('ai-data-table', 'AI_Assistant_Report')">🟢 Excel Export</button>
                        <button class="btn" style="background-color: #64748b; font-size: 14px; padding: 8px 16px; box-shadow: none;" onclick="window.print()">🖨️ Print PDF</button>
                    </div>
                </div>

                <!-- Generated SQL collapsible detailing (for superusers) -->
                <?php if ($isSuperUser && !empty($response['sql'])): ?>
                    <details style="background: #f8fafc; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e2e8f0; cursor: pointer;">
                        <summary style="font-weight: 600; color: #64748b; font-size: 14px; outline: none;">Show Generated SQL</summary>
                        <pre style="margin-top: 12px; font-family: monospace; white-space: pre-wrap; background: #0f172a; color: #e2e8f0; padding: 16px; border-radius: 6px; overflow-x: auto; font-size: 13px; cursor: text;" onclick="event.stopPropagation()"><?= htmlspecialchars($response['sql']) ?></pre>
                    </details>
                <?php endif; ?>

                <!-- Auto-Charting visualization panel -->
                <?php if ($isChartable): ?>
                    <div style="background: #ffffff; padding: 25px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h4 style="margin: 0; font-weight: 600; color: #334155; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em;">Interactive Visualization</h4>
                            <div>
                                <label style="font-size: 13px; font-weight: 600; color: #64748b; margin-right: 8px;">Chart type:</label>
                                <select id="chart-type-selector" onchange="changeChartType(this.value)" style="padding: 6px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: white;">
                                    <option value="bar">Bar Chart</option>
                                    <option value="line">Line Chart</option>
                                    <option value="pie">Pie Chart</option>
                                </select>
                            </div>
                        </div>
                        <div style="position: relative; height: 350px;">
                            <canvas id="ai-report-chart"></canvas>
                        </div>
                    </div>

                    <script>
                        let reportChart = null;
                        const chartData = <?= json_encode($chartData) ?>;

                        function initChart(type = 'bar') {
                            const ctx = document.getElementById('ai-report-chart').getContext('2d');
                            if (reportChart) {
                                reportChart.destroy();
                            }

                            let dataConfig = JSON.parse(JSON.stringify(chartData));
                            
                            // Generate unique slice colors if pie chart
                            if (type === 'pie') {
                                dataConfig.datasets.forEach(ds => {
                                    const bgColors = [];
                                    const borderColors = [];
                                    for (let i = 0; i < ds.data.length; i++) {
                                        const hue = (i * 360 / ds.data.length) % 360;
                                        bgColors.push(`hsla(${hue}, 75%, 65%, 0.75)`);
                                        borderColors.push(`hsla(${hue}, 75%, 55%, 1)`);
                                    }
                                    ds.backgroundColor = bgColors;
                                    ds.borderColor = borderColors;
                                });
                            }

                            reportChart = new Chart(ctx, {
                                type: type,
                                data: dataConfig,
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: true, position: type === 'pie' ? 'right' : 'top' }
                                    },
                                    scales: type === 'pie' ? {} : {
                                        y: { beginAtZero: true }
                                    }
                                }
                            });
                        }

                        function changeChartType(type) {
                            initChart(type);
                        }

                        document.addEventListener('DOMContentLoaded', () => {
                            if (chartData) {
                                initChart('bar');
                            }
                        });
                    </script>
                <?php endif; ?>

                <!-- Table data display -->
                <?php
                    // Office grouping check
                    $grouped = [];
                    $hasOffice = false;
                    if (!empty($data)) {
                        $firstRowKeys = array_change_key_case($data[0], CASE_LOWER);
                        if (array_key_exists('officename', $firstRowKeys)) {
                            $hasOffice = true;
                            foreach ($data as $row) {
                                $officeKey = '';
                                foreach (array_keys($row) as $k) {
                                    if (strtolower($k) === 'officename') { $officeKey = $k; break; }
                                }
                                $office = $row[$officeKey];
                                unset($row[$officeKey]);
                                $grouped[$office][] = $row;
                            }
                        }
                    }
                ?>

                <div class="table-responsive">
                    <?php if (empty($data)): ?>
                        <div class="alert alert-info text-center py-4" style="background: #f1f5f9; color: #475569; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 1.1rem; font-weight: 500;">
                            No records found matching your query.
                        </div>
                    <?php elseif ($hasOffice): ?>
                        <div id="ai-data-table">
                            <?php foreach ($grouped as $office => $rows): ?>
                                <div class="group-header"><?= htmlspecialchars($office) ?></div>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <?php foreach (array_keys($rows[0]) as $h): ?>
                                                <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $h))) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rows as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $c): ?>
                                                    <td><?= htmlspecialchars((string)$c) ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Standard Flat Table -->
                        <table class="table" id="ai-data-table">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($data[0]) as $h): ?>
                                        <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $h))) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $c): ?>
                                            <td><?= htmlspecialchars((string)$c) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <?php if (!empty($response['sql'])): ?>
                    <div style="margin-top: 25px; border-top: 1px solid #cbd5e1; padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 13px; color: #64748b;">Notice something wrong with this output? Help train the AI by reporting it.</span>
                        <button class="btn" id="report-wrong-btn" style="background-color: #dc2626; font-size: 13px; padding: 6px 12px; box-shadow: none;" onclick="reportWrongOutput()">⚠️ Report Incorrect Output</button>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // SheetJS Excel export handler
    function exportTableToExcel(tableID, filename = ''){
        var tableSelect = document.getElementById(tableID);
        if (!tableSelect) return;
        
        var wb = XLSX.utils.table_to_book(tableSelect, {sheet:"AI Report"});
        XLSX.writeFile(wb, filename + ".xlsx");
    }
</script>
</body>
</html>
