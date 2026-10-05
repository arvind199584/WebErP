<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF to Excel</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; color: white; font-size: 16px; width: 100%; }
        .btn-primary { background-color: #17a2b8; }
        .btn-secondary { background-color: #6c757d; display: block; text-align: center; text-decoration: none; margin-top: 10px; }
        .message { padding: 15px; background-color: #e2e3e5; color: #383d41; border-radius: 4px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

<div class="container">
    <h1>PDF to Excel</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="message"><?= htmlspecialchars($_SESSION['message']) ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <form action="?action=process_convert_excel" method="POST" enctype="multipart/form-data">
        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>
        <div class="form-group">
            <label for="pdf_file">Select PDF File</label>
            <input type="file" id="pdf_file" name="pdf_file" accept="application/pdf" required>
        </div>

        <button type="submit" class="btn btn-primary">Convert to XLSX</button>
        <a href="?action=index" class="btn btn-secondary">Back</a>
    </form>
</div>

</body>
</html>
