<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF to Text (OCR)</title>
    <!-- Load Local Libraries -->
    <script src="/assets/js/pdf.min.js"></script>
    <script src="/assets/js/tesseract.min.js"></script>
    <script src="/assets/js/download.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = '/assets/js/pdf.worker.min.js';
    </script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }

        .drop-zone { border: 2px dashed #ccc; padding: 40px; text-align: center; border-radius: 8px; cursor: pointer; transition: border-color 0.3s; margin-bottom: 20px; }
        .drop-zone:hover { border-color: #007bff; background-color: #f8f9fa; }

        .progress-bar { width: 100%; background-color: #e9ecef; border-radius: 4px; height: 20px; margin-top: 20px; display: none; }
        .progress-fill { height: 100%; background-color: #17a2b8; width: 0%; border-radius: 4px; transition: width 0.3s; text-align: center; color: white; font-size: 12px; line-height: 20px; }

        .actions { text-align: center; margin-top: 30px; }
        .btn { padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; color: white; font-size: 16px; transition: background 0.2s; }
        .btn-primary { background-color: #17a2b8; }
        .btn-secondary { background-color: #6c757d; margin-right: 10px; text-decoration: none; display: inline-block; }

        #status { text-align: center; margin-top: 10px; color: #666; font-size: 0.9em; }
        #output { white-space: pre-wrap; background: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 4px; margin-top: 20px; max-height: 300px; overflow-y: auto; display: none; }
    </style>
</head>
<body>

<div class="container">
    <h1>PDF to Text (OCR)</h1>
    <p style="text-align: center; color: #666;">Extracts text from scanned PDFs using OCR. Processed in your browser.</p>

    <div class="drop-zone" onclick="document.getElementById('file-input').click()">
        <p>Click to select a PDF file</p>
        <input type="file" id="file-input" accept="application/pdf" style="display: none;">
    </div>

    <div class="progress-bar" id="progress-bar">
        <div class="progress-fill" id="progress-fill">0%</div>
    </div>
    <div id="status"></div>

    <div class="actions">
        <a href="?action=index" class="btn btn-secondary">Back</a>
        <button class="btn btn-primary" onclick="runOCR()">Start OCR</button>
    </div>

    <textarea id="output" rows="15"></textarea>
</div>

<script>
    let selectedFile = null;
    const fileInput = document.getElementById('file-input');
    const statusEl = document.getElementById('status');
    const outputEl = document.getElementById('output');
    const progressFill = document.getElementById('progress-fill');
    const progressBar = document.getElementById('progress-bar');

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            selectedFile = e.target.files[0];
            statusEl.innerText = "Selected: " + selectedFile.name;
            outputEl.style.display = 'none';
            outputEl.value = '';
        }
    });

    async function runOCR() {
        if (!selectedFile) {
            alert("Please select a PDF file first.");
            return;
        }

        progressBar.style.display = 'block';
        outputEl.style.display = 'block';
        outputEl.value = ''; // Clear previous results

        try {
            const arrayBuffer = await selectedFile.arrayBuffer();
            const pdf = await pdfjsLib.getDocument(arrayBuffer).promise;
            const numPages = pdf.numPages;
            let fullText = '';

            const worker = Tesseract.createWorker({
                logger: m => {
                    if (m.status === 'recognizing text') {
                        const progress = Math.round(m.progress * 100);
                        progressFill.style.width = progress + '%';
                        progressFill.innerText = `Recognizing... ${progress}%`;
                    }
                }
            });

            await worker.load();
            await worker.loadLanguage('eng');
            await worker.initialize('eng');

            for (let i = 1; i <= numPages; i++) {
                statusEl.innerText = `Processing page ${i} of ${numPages}...`;

                const page = await pdf.getPage(i);
                const viewport = page.getViewport({ scale: 2.0 }); // Higher scale for better accuracy
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                await page.render({ canvasContext: context, viewport: viewport }).promise;

                const { data: { text } } = await worker.recognize(canvas);
                fullText += text + "\n\n--- Page " + i + " ---\n\n";
                outputEl.value = fullText;
                outputEl.scrollTop = outputEl.scrollHeight; // Auto-scroll
            }

            await worker.terminate();
            statusEl.innerText = "OCR Complete! You can copy the text or download it.";

            // Add download button
            const downloadBtn = document.createElement('button');
            downloadBtn.className = 'btn btn-primary';
            downloadBtn.innerText = 'Download as .txt';
            downloadBtn.onclick = () => {
                download(fullText, selectedFile.name.replace('.pdf', '.txt'), 'text/plain');
            };
            document.querySelector('.actions').appendChild(downloadBtn);

        } catch (error) {
            console.error(error);
            alert("An error occurred: " + error.message);
            statusEl.innerText = "Error occurred.";
        }
    }
</script>

</body>
</html>
