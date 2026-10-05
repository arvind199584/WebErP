<!DOCTYPE html>
<html>
<head>
    <title>Compress JPEG</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .drop-zone { border: 2px dashed #007bff; padding: 40px; text-align: center; border-radius: 8px; cursor: pointer; margin-bottom: 20px; }
        .preview-container { display: flex; gap: 20px; margin-top: 20px; }
        .preview-box { flex: 1; text-align: center; }
        img { max-width: 100%; border-radius: 4px; border: 1px solid #ddd; }
        .controls { margin-top: 20px; text-align: center; }
        input[type="range"] { width: 100%; }
        .btn { padding: 10px 20px; background: #28a745; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>Compress JPEG (Client-Side)</h1>
    <p><a href="?action=index">&larr; Back to Utilities</a></p>

    <div class="drop-zone" onclick="document.getElementById('fileInput').click()">
        Drag & Drop JPEG here or Click to Upload
        <input type="file" id="fileInput" accept="image/jpeg" style="display:none" onchange="handleFile(this.files[0])">
    </div>

    <div id="editor" style="display:none">
        <div class="controls">
            <label>Compression Quality: <span id="qualityVal">0.7</span></label>
            <input type="range" id="quality" min="0.1" max="1.0" step="0.05" value="0.7" oninput="updateQuality(this.value)">
        </div>

        <div class="preview-container">
            <div class="preview-box">
                <h4>Original</h4>
                <div id="origSize"></div>
                <img id="origImg">
            </div>
            <div class="preview-box">
                <h4>Compressed</h4>
                <div id="compSize"></div>
                <img id="compImg">
            </div>
        </div>

        <div style="text-align:center; margin-top:30px;">
            <button class="btn" onclick="downloadImage()">Download Compressed Image</button>
        </div>
    </div>
</div>

<script>
    let originalFile = null;

    function handleFile(file) {
        if (!file) return;
        originalFile = file;
        document.getElementById('origSize').innerText = (file.size / 1024).toFixed(2) + " KB";

        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('origImg').src = e.target.result;
            document.getElementById('editor').style.display = 'block';
            compress();
        };
        reader.readAsDataURL(file);
    }

    function updateQuality(val) {
        document.getElementById('qualityVal').innerText = val;
        compress();
    }

    function compress() {
        const img = document.getElementById('origImg');
        const quality = parseFloat(document.getElementById('quality').value);

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        ctx.drawImage(img, 0, 0);

        const dataUrl = canvas.toDataURL('image/jpeg', quality);
        document.getElementById('compImg').src = dataUrl;

        // Calculate size
        const head = 'data:image/jpeg;base64,';
        const size = Math.round((dataUrl.length - head.length) * 3 / 4);
        document.getElementById('compSize').innerText = (size / 1024).toFixed(2) + " KB";
    }

    function downloadImage() {
        const link = document.createElement('a');
        link.download = 'compressed_' + originalFile.name;
        link.href = document.getElementById('compImg').src;
        link.click();
    }
</script>
</body>
</html>
