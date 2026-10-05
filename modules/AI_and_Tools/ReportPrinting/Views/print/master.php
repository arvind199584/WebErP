<?php
/** @var array $data */
/** @var array $calcs */
/** @var string $netWords */

$baseDir = __DIR__ . DIRECTORY_SEPARATOR;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Bill <?= htmlspecialchars($data['office_bill_no']) ?></title>
    <style>
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
        }
        body { background-color: #f0f0f0; color: black; line-height: 1.2; padding: 20px; }
        .page {
            max-width: 1000px; margin: 0 auto 20px auto;
            min-height: 297mm; background-color: white;
            padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .page-break { page-break-after: always; }

        .watermark {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0;
            pointer-events: none; opacity: 0.05; background-image: url('/assets/images/dda_logo.png');
            background-repeat: repeat; background-size: 150px 150px; background-position: center;
            transform: rotate(-30deg) scale(1.5);
        }
        .content-wrapper { position: relative; z-index: 1; }

        .print-btn-container { position: fixed; top: 20px; right: 20px; z-index: 1000; }
        .print-btn { background-color: #007bff; color: white; border: none; padding: 10px 20px; font-size: 14px; border-radius: 5px; cursor: pointer; font-weight: bold; margin-left: 10px; }

        /* Shared Table Styles */
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid black !important; padding: 4px 5px; text-align: center; vertical-align: middle; }
        .no-border td, .no-border th { border: none !important; }
        .left-align { text-align: left; }
        .right-align { text-align: right; }
        .bold { font-weight: bold; }
        .underline { text-decoration: underline; }

        @media print {
            body { background-color: white; padding: 0; margin: 0; }
            .page {
                box-shadow: none; margin: 0; width: 100%; max-width: none;
                padding: 10mm; min-height: 280mm; page-break-after: always;
            }
            .page:last-child { page-break-after: auto; }
            .print-btn-container { display: none; }

            /* Scaling */
            .scale-110 { transform: scale(1.1); transform-origin: top left; width: 90.9%; }
            .scale-130 { transform: scale(1.3); transform-origin: top left; width: 76.92%; }

            /* Font Sizes for Print */
            .page-2-4, .page-2-4 td, .page-2-4 p { font-size: 14px !important; }
        }
    </style>
</head>
<body>
    <div class="print-btn-container no-print">
        <button class="print-btn" onclick="window.print()">Print Bill</button>
        <button class="print-btn" style="background:#6c757d;" onclick="window.close()">Close Window</button>
    </div>

    <!-- Page 1: Abstract -->
    <div class="page scale-110" id="p1">
        <div class="watermark"></div>
        <div class="content-wrapper">
            <?php include $baseDir . 'abstract.php'; ?>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- Page 2: Memo -->
    <div class="page scale-130 page-2-4" id="p2">
        <div class="watermark"></div>
        <div class="content-wrapper">
            <?php include $baseDir . 'memo.php'; ?>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- Page 3 & 4: Forwarding -->
    <div class="page scale-130 page-2-4" id="p3">
        <div class="watermark"></div>
        <div class="content-wrapper">
            <?php include $baseDir . 'bill_forwarding.php'; ?>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
