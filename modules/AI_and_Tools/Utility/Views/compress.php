<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Compress PDF</title>
    <!-- Load Local Libraries -->
    <script src="/assets/js/pdf-lib.min.js"></script>
    <script src="/assets/js/download.min.js"></script>
    <script src="/assets/js/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = '/assets/js/pdf.worker.min.js';
    </script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }

        .drop-zone { border: 2px dashed #ccc; padding: 40px; text-align: center; border-radius: 8px; cursor: pointer; transition: border-color 0.3s; margin-bottom: 20px; }
        .drop-zone:hover { border-color: #007bff; background-color: #f8f9fa; }

        .controls { margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px; }
        .form-group { margin-bottom: 10px; }
        .form-group label { font-weight: 600; margin-right: 10px; }

        .progress-bar { width: 100%; background-color: #e9ecef; border-radius: 4px; height: 20px; margin-top: 20px; display: none; }
        .progress-fill { height: 100%; background-color: #28a745; width: 0%; border-radius: 4px; transition: width 0.3s; text-align: center; color: white; font-size: 12px; line-height: 20px; }

        .actions { text-align: center; margin-top: 30px; }
        .btn { padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; color: white; font-size: 16px; transition: background 0.2s; }
        .btn-primary { background-color: #007bff; }
        .btn-secondary { background-color: #6c757d; margin-right: 10px; text-decoration: none; display: inline-block; }

        #status { text-align: center; margin-top: 10px; color: #666; font-size: 0.9em; }
        .log-box { max-height: 150px; overflow-y: auto; background: #333; color: #0f0; font-family: monospace; padding: 10px; border-radius: 4px; margin-top: 10px; font-size: 12px; display: none; text-align: left; }
    </style>
</head>
<body>

<div class="container">
    <h1>Smart PDF Compressor</h1>
    <p style="text-align: center; color: #666;">
        Intelligently distinguishes between <b>Text Pages</b> (preserved) and <b>Scanned Pages</b> (compressed).
    </p>

    <div class="drop-zone" onclick="document.getElementById('file-input').click()">
        <p>Click to select a PDF file</p>
        <input type="file" id="file-input" accept="application/pdf" style="display: none;">
    </div>

    <div class="controls">
        <div class="form-group">
            <label for="quality">Image Quality (0.1 - 1.0):</label>
            <input type="range" id="quality" min="0.1" max="1.0" step="0.1" value="0.6" oninput="document.getElementById('q-val').innerText = this.value">
            <span id="q-val">0.6</span>
        </div>
        <div class="form-group">
            <label for="scale">Resolution Scale:</label>
            <select id="scale">
                <option value="1">1x (Standard)</option>
                <option value="1.5">1.5x (Better Text)</option>
                <option value="2">2x (High Res)</option>
            </select>
        </div>
        <div class="form-group">
            <label><input type="checkbox" id="force-compress"> Force Compress All Pages (Ignore Text Detection)</label>
        </div>
    </div>

    <div class="progress-bar" id="progress-bar">
        <div class="progress-fill" id="progress-fill">0%</div>
    </div>
    <div id="status"></div>
    <div class="log-box" id="log-box"></div>

    <div class="actions">
        <a href="?action=index" class="btn btn-secondary">Back</a>
        <button class="btn btn-primary" onclick="compressPDF()">Start Compression</button>
    </div>
</div>

<script>
    let selectedFile = null;
    const fileInput = document.getElementById('file-input');
    const logBox = document.getElementById('log-box');

    function log(msg) {
        logBox.style.display = 'block';
        logBox.innerHTML += `> ${msg}<br>`;
        logBox.scrollTop = logBox.scrollHeight;
    }

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            selectedFile = e.target.files[0];
            document.getElementById('status').innerText = "Selected: " + selectedFile.name + " (" + (selectedFile.size / 1024 / 1024).toFixed(2) + " MB)";
            logBox.innerHTML = ''; // Clear log
        }
    });

    async function compressPDF() {
        if (!selectedFile) {
            alert("Please select a PDF file first.");
            return;
        }

        const quality = parseFloat(document.getElementById('quality').value);
        const scale = parseFloat(document.getElementById('scale').value);
        const forceCompress = document.getElementById('force-compress').checked;
        const progressBar = document.getElementById('progress-bar');
        const progressFill = document.getElementById('progress-fill');
        const status = document.getElementById('status');

        progressBar.style.display = 'block';
        status.innerText = "Analyzing PDF...";
        log("Starting process...");

        try {
            const arrayBuffer = await selectedFile.arrayBuffer();

            // 1. Load Original PDF for Copying (High Fidelity)
            const pdfDocOriginal = await PDFLib.PDFDocument.load(arrayBuffer);

            // 2. Load PDF for Rendering/Analysis (pdf.js)
            const pdf = await pdfjsLib.getDocument(arrayBuffer).promise;
            const numPages = pdf.numPages;

            // 3. Create New PDF
            const newPdf = await PDFLib.PDFDocument.create();

            for (let i = 1; i <= numPages; i++) {
                const percent = Math.round((i / numPages) * 100);
                progressFill.style.width = percent + "%";
                progressFill.innerText = percent + "%";

                // Analyze Page Content
                const page = await pdf.getPage(i);
                const textContent = await page.getTextContent();
                const textLength = textContent.items.map(item => item.str).join('').length;

                // Heuristic: If text length > 50 chars, it's likely a text page.
                const isTextPage = textLength > 50;

                if (isTextPage && !forceCompress) {
                    log(`Page ${i}: Detected Text (${textLength} chars). Preserving original.`);
                    // Copy page from original PDF
                    const [copiedPage] = await newPdf.copyPages(pdfDocOriginal, [i - 1]);
                    newPdf.addPage(copiedPage);
                } else {
                    log(`Page ${i}: Detected Image/Scan. Compressing...`);
                    // Rasterize and Compress
                    const viewport = page.getViewport({ scale: scale });
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    await page.render({ canvasContext: context, viewport: viewport }).promise;

                    const imgDataUrl = canvas.toDataURL('image/jpeg', quality);
                    const jpgImage = await newPdf.embedJpg(imgDataUrl);
                    const newPage = newPdf.addPage([viewport.width, viewport.height]);
                    newPage.drawImage(jpgImage, {
                        x: 0,
                        y: 0,
                        width: viewport.width,
                        height: viewport.height,
                    });
                }
            }

            status.innerText = "Finalizing file...";
            const pdfBytes = await newPdf.save();

            const originalSize = selectedFile.size;
            const newSize = pdfBytes.byteLength;
            const reduction = ((originalSize - newSize) / originalSize * 100).toFixed(1);

            // Color code result
            const resultColor = newSize < originalSize ? "green" : "orange";
            const resultText = newSize < originalSize ? `Reduced by ${reduction}%` : `Size increased (Original was already optimized)`;

            status.innerHTML = `<b style="color:${resultColor}">Done! ${resultText}</b>`;
            log(`Original: ${(originalSize/1024).toFixed(0)}KB -> New: ${(newSize/1024).toFixed(0)}KB`);

            download(pdfBytes, "smart_compressed_" + selectedFile.name, "application/pdf");

        } catch (error) {
            console.error(error);
            alert("An error occurred: " + error.message);
            status.innerText = "Error occurred.";
        }
    }
</script>

</body>
</html>
