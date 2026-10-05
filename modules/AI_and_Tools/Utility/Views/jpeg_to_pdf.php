<!DOCTYPE html>
<html>
<head>
    <title>JPEG to PDF</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .drop-zone { border: 2px dashed #007bff; padding: 40px; text-align: center; border-radius: 8px; cursor: pointer; margin-bottom: 20px; background: #f0f7ff; }
        #sortable-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 20px; min-height: 100px; border: 1px solid #eee; padding: 15px; border-radius: 8px; }
        .img-item { border: 2px solid #ddd; padding: 5px; border-radius: 4px; background: #fff; cursor: move; position: relative; }
        .img-item img { width: 100%; height: 150px; object-fit: cover; border-radius: 2px; }
        .img-item .remove { position: absolute; top: -10px; right: -10px; background: red; color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; line-height: 20px; cursor: pointer; font-size: 12px; font-weight: bold; }
        .btn { padding: 12px 25px; background: #28a745; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 16px; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>JPEG to PDF (Client-Side)</h1>
    <p>Upload multiple JPEGs, drag to reorder, then download as a single PDF.</p>
    <p><a href="?action=index">&larr; Back to Utilities</a></p>

    <div class="drop-zone" onclick="document.getElementById('fileInput').click()">
        Click to Select Multiple JPEGs
        <input type="file" id="fileInput" accept="image/jpeg" multiple style="display:none" onchange="handleFiles(this.files)">
    </div>

    <div id="sortable-list"></div>

    <div style="text-align:center;">
        <button id="generateBtn" class="btn" style="display:none" onclick="generatePDF()">Generate & Download PDF</button>
    </div>
</div>

<script>
    const list = document.getElementById('sortable-list');
    new Sortable(list, { animation: 150 });

    function handleFiles(files) {
        for (let file of files) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'img-item';
                div.innerHTML = `
                    <span class="remove" onclick="this.parentElement.remove(); checkBtn();">×</span>
                    <img src="${e.target.result}" data-name="${file.name}">
                `;
                list.appendChild(div);
                checkBtn();
            };
            reader.readAsDataURL(file);
        }
    }

    function checkBtn() {
        document.getElementById('generateBtn').style.display = list.children.length > 0 ? 'inline-block' : 'none';
    }

    async function generatePDF() {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF();
        const items = list.querySelectorAll('img');

        const btn = document.getElementById('generateBtn');
        btn.disabled = true;
        btn.innerText = "Generating...";

        for (let i = 0; i < items.length; i++) {
            if (i > 0) pdf.addPage();

            const img = items[i];
            const props = pdf.getImageProperties(img.src);
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (props.height * pdfWidth) / props.width;

            pdf.addImage(img.src, 'JPEG', 0, 0, pdfWidth, pdfHeight);
        }

        pdf.save('merged_images.pdf');
        btn.disabled = false;
        btn.innerText = "Generate & Download PDF";
    }
</script>
</body>
</html>
