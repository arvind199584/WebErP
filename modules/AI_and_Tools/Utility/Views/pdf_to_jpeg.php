<!DOCTYPE html>
<html>
<head>
    <title>PDF to JPEG</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .drop-zone { border: 2px dashed #007bff; padding: 40px; text-align: center; border-radius: 8px; cursor: pointer; margin-bottom: 20px; }
        .pages-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .page-box { border: 1px solid #ddd; padding: 10px; border-radius: 4px; text-align: center; background: #f9f9f9; }
        canvas { max-width: 100%; height: auto; border: 1px solid #ccc; }
        .btn { padding: 8px 15px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <h1>PDF to JPEG (Client-Side)</h1>
    <p><a href="?action=index">&larr; Back to Utilities</a></p>

    <div class="drop-zone" onclick="document.getElementById('fileInput').click()">
        Select PDF to Convert
        <input type="file" id="fileInput" accept="application/pdf" style="display:none" onchange="handleFile(this.files[0])">
    </div>

    <div id="status"></div>
    <div id="pages" class="pages-grid"></div>
</div>

<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

    async function handleFile(file) {
        if (!file) return;
        document.getElementById('pages').innerHTML = '';
        document.getElementById('status').innerText = "Processing PDF...";

        const reader = new FileReader();
        reader.onload = async function() {
            const typedarray = new Uint8Array(this.result);
            const pdf = await pdfjsLib.getDocument(typedarray).promise;

            document.getElementById('status').innerText = `Found ${pdf.numPages} pages. Rendering...`;

            for (let i = 1; i <= pdf.numPages; i++) {
                const page = await pdf.getPage(i);
                const viewport = page.getViewport({ scale: 1.5 });

                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                await page.render({ canvasContext: context, viewport: viewport }).promise;

                const pageBox = document.createElement('div');
                pageBox.className = 'page-box';
                pageBox.innerHTML = `<h4>Page ${i}</h4>`;
                pageBox.appendChild(canvas);

                const btn = document.createElement('button');
                btn.className = 'btn';
                btn.innerText = 'Download as JPEG';
                btn.onclick = () => {
                    const link = document.createElement('a');
                    link.download = `page_${i}.jpg`;
                    link.href = canvas.toDataURL('image/jpeg', 0.9);
                    link.click();
                };
                pageBox.appendChild(btn);

                document.getElementById('pages').appendChild(pageBox);
            }
            document.getElementById('status').innerText = "Conversion complete.";
        };
        reader.readAsArrayBuffer(file);
    }
</script>
</body>
</html>
