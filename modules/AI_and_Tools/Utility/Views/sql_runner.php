<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Runner</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; color: #333; }
        .container { max-width: 1200px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #0056b3; }
        textarea { width: 100%; height: 150px; font-family: monospace; font-size: 14px; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; color: white; background-color: #007bff; }
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin-top: 15px; border-radius: 4px; }
        .info { color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; margin-top: 15px; border-radius: 4px; }
        .loader { text-align: center; display: none; margin: 20px; }
        .table-responsive { overflow-x: auto; margin-top: 20px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #e9ecef; }
    </style>
</head>
<body>

<div class="container">
    <h1>SQL Runner (Read-Only)</h1>
    <p>Execute any <strong>SELECT</strong> query against the database to inspect data. Destructive commands (UPDATE, DELETE, DROP) are blocked.</p>

    <textarea id="sql-query" placeholder="SELECT * FROM turf_machines;"></textarea>
    <div style="text-align:right; margin-top:10px;">
        <button id="execute-btn" class="btn">Execute Query</button>
    </div>

    <div id="error-container" class="error" style="display: none;"></div>
    <div id="loader" class="loader">Executing...</div>
    <div id="results-container" class="table-responsive"></div>
</div>

<script>
function renderTable(container, data) {
    container.innerHTML = '';
    if (!data || data.length === 0) {
        container.innerHTML = '<p class="info">Query executed successfully, but returned no results.</p>';
        return;
    }
    const table = document.createElement('table');
    table.className = 'table';
    const thead = document.createElement('thead');
    const headerRow = document.createElement('tr');
    const headers = Object.keys(data[0]);
    headers.forEach(key => {
        const th = document.createElement('th');
        th.textContent = key;
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);
    const tbody = document.createElement('tbody');
    data.forEach(rowData => {
        const row = document.createElement('tr');
        headers.forEach(key => {
            const td = document.createElement('td');
            td.textContent = rowData[key];
            row.appendChild(td);
        });
        tbody.appendChild(row);
    });
    table.appendChild(tbody);
    container.appendChild(table);
}

document.getElementById('execute-btn').addEventListener('click', async () => {
    const query = document.getElementById('sql-query').value.trim();
    if (!query) {
        alert('Please enter a SQL query.');
        return;
    }

    const loader = document.getElementById('loader');
    const resultsContainer = document.getElementById('results-container');
    const errorContainer = document.getElementById('error-container');

    loader.style.display = 'block';
    resultsContainer.innerHTML = '';
    errorContainer.style.display = 'none';

    try {
        // --- MODIFIED: Route request to the central SQLRunnerController ---
        const response = await fetch('/modules/AI_and_Tools/Utility/Controller/SQLRunnerController.php?action=execute', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                    csrf_token: "<?php echo \App\Core\CSRFManager::generateToken(); ?>", sql: query })
        });

        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.error || 'An unknown error occurred.');
        }

        // If the result has a 'message' (from UPDATE), display it as info
        if (result.message) {
            resultsContainer.innerHTML = `<p class="info">${result.message}</p>`;
        } else {
            renderTable(resultsContainer, result.data);
        }

    } catch (e) {
        errorContainer.textContent = e.message;
        errorContainer.style.display = 'block';
    } finally {
        loader.style.display = 'none';
    }
});
</script>

</body>
</html>
