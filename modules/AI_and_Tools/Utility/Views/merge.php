<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merge PDFs</title>
    <!-- Load pdf-lib from CDN -->
    <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
    <script src="https://unpkg.com/downloadjs@1.4.7/download.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }

        .drop-zone { border: 2px dashed #ccc; padding: 40px; text-align: center; border-radius: 8px; cursor: pointer; transition: border-color 0.3s; margin-bottom: 20px; }
        .drop-zone:hover { border-color: #007bff; background-color: #f8f9fa; }
        .drop-zone p { margin: 0; color: #666; font-size: 1.1em; }

        #file-list { list-style: none; padding: 0; margin: 0; }
        .file-item { background: #f8f9fa; border: 1px solid #ddd; padding: 10px 15px; margin-bottom: 10px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; cursor: move; }
        .file-name { font-weight: 500; }
        .file-actions button { background: none; border: none; cursor: pointer; font-size: 1.2em; color: #666; margin-left: 5px; }
        .file-actions button:hover { color: #dc3545; }

        .actions { text-align: center; margin-top: 30px; }
        .btn { padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; color: white; font-size: 16px; transition: background 0.2s; }
        .btn-primary { background-color: #007bff; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { background-color: #6c757d; margin-right: 10px; text-decoration: none; display: inline-block; }

        .loading { display: none; text-align: center; margin-top: 20px; color: #007bff; }
    </style>
</head>
<body>

<div class="container">
    <h1>Merge PDFs</h1>
    <p style="text-align: center; color: #666; margin-bottom: 30px;">Combine multiple PDF files into a single document. Processed locally in your browser.</p>

    <div class="drop-zone" id="drop-zone" onclick="document.getElementById('file-input').click()">
        <p>Click to select files or drag & drop here</p>
        <input type="file" id="file-input" multiple accept="application/pdf" style="display: none;">
    </div>

    <ul id="file-list"></ul>

    <div class="loading" id="loading">Processing... Please wait.</div>

    <div class="actions">
        <a href="?action=index" class="btn btn-secondary">Back</a>
        <button class="btn btn-primary" onclick="mergePDFs()">Merge & Download</button>
    </div>
</div>

<script>
    let files = [];
    const fileInput = document.getElementById('file-input');
    const fileList = document.getElementById('file-list');

    fileInput.addEventListener('change', handleFiles);

    function handleFiles(e) {
        const newFiles = Array.from(e.target.files);
        files = files.concat(newFiles);
        renderList();
    }

    function renderList() {
        fileList.innerHTML = '';
        files.forEach((file, index) => {
            const li = document.createElement('li');
            li.className = 'file-item';
            li.innerHTML = `
                <span class="file-name">${index + 1}. ${file.name}</span>
                <div class="file-actions">
                    <button onclick="moveUp(${index})" title="Move Up">↑</button>
                    <button onclick="moveDown(${index})" title="Move Down">↓</button>
                    <button onclick="removeFile(${index})" title="Remove">×</button>
                </div>
            `;
            fileList.appendChild(li);
        });
    }

    function removeFile(index) {
        files.splice(index, 1);
        renderList();
    }

    function moveUp(index) {
        if (index > 0) {
            [files[index], files[index - 1]] = [files[index - 1], files[index]];
            renderList();
        }
    }

    function moveDown(index) {
        if (index < files.length - 1) {
            [files[index], files[index + 1]] = [files[index + 1], files[index]];
            renderList();
        }
    }

    async function mergePDFs() {
        if (files.length === 0) {
            alert("Please select at least one PDF file.");
            return;
        }

        document.getElementById('loading').style.display = 'block';

        try {
            const PDFDocument = PDFLib.PDFDocument;
            const mergedPdf = await PDFDocument.create();

            for (const file of files) {
                const arrayBuffer = await file.arrayBuffer();
                const pdf = await PDFDocument.load(arrayBuffer);
                const copiedPages = await mergedPdf.copyPages(pdf, pdf.getPageIndices());
                copiedPages.forEach((page) => mergedPdf.addPage(page));
            }

            const pdfBytes = await mergedPdf.save();
            download(pdfBytes, "merged_document.pdf", "application/pdf");
        } catch (error) {
            console.error(error);
            alert("An error occurred while merging PDFs. Please check the console.");
        } finally {
            document.getElementById('loading').style.display = 'none';
        }
    }
</script>

</body>
</html>
