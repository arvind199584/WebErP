<!DOCTYPE html>
<html>
<head>
    <title>Utilities</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: 40px auto; text-align: center; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 30px; }
        .card { background: #fff; padding: 30px 15px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-decoration: none; color: #333; transition: all 0.3s; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); border-bottom: 4px solid #007bff; }
        .card h2 { margin: 10px 0 0 0; font-size: 18px; color: #2c3e50; }
        .icon { font-size: 35px; }
    </style>
</head>
<body>
<div class="container">
    <h1>System Utilities</h1>
    <div class="grid">
        <a href="?action=budget_tracking" class="card">
            <div class="icon">📈</div>
            <h2>Budget Tracking</h2>
        </a>
        <a href="?action=merge" class="card">
            <div class="icon">📄➕</div>
            <h2>Merge PDF</h2>
        </a>
        <a href="?action=compress" class="card">
            <div class="icon">🗜️</div>
            <h2>Compress PDF</h2>
        </a>
        <a href="?action=compress_jpeg" class="card">
            <div class="icon">🖼️📉</div>
            <h2>Compress JPEG</h2>
        </a>
        <a href="?action=pdf_to_jpeg" class="card">
            <div class="icon">📄➡️🖼️</div>
            <h2>PDF to JPEG</h2>
        </a>
        <a href="?action=jpeg_to_pdf" class="card">
            <div class="icon">🖼️➡️📄</div>
            <h2>JPEG to PDF</h2>
        </a>
        <a href="?action=convert_word" class="card">
            <div class="icon">📝</div>
            <h2>Word Tools</h2>
        </a>
        <a href="?action=convert_excel" class="card">
            <div class="icon">📊</div>
            <h2>Excel Tools</h2>
        </a>
    </div>
</div>
</body>
</html>
