<?php
/** @var array $data */
/** @var array $calcs */
/** @var string $netWords */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Bill <?= htmlspecialchars($data['office_bill_no']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
        }

        body {
            background-color: #f0f0f0;
            color: black;
            line-height: 1.2;
            padding: 20px;
        }

        .page {
            max-width: 1000px;
            margin: 0 auto 20px auto;
            min-height: 297mm;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .page-break {
            page-break-after: always;
            height: 0;
        }

        .watermark {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0;
            pointer-events: none; opacity: 0.1; background-image: url('/assets/images/dda_logo.png');
            background-repeat: repeat; background-size: 150px 150px; background-position: center;
            transform: rotate(-30deg) scale(1.5);
        }

        .content-wrapper { position: relative; z-index: 1; flex: 1; display: flex; flex-direction: column; }
        .print-btn-container { position: fixed; top: 20px; right: 20px; z-index: 1000; }
        .print-btn { background-color: #007bff; color: white; border: none; padding: 10px 20px; font-size: 14px; border-radius: 5px; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .print-btn:hover { background-color: #0056b3; }

        .header { text-align: center; margin-bottom: 10px; border-bottom: 1px solid #333; padding-bottom: 5px; }
        .header h1 { font-size: 16px; font-weight: bold; margin-bottom: 5px; }
        .work-details { text-align: left; margin-bottom: 10px; line-height: 1.3; }
        .work-details p { margin: 3px 0; }

        .bill-table, .memo-table, .deduction-table, .payment-table {
            width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 11px;
        }
        .bill-table th, .bill-table td, .memo-table td, .deduction-table th, .deduction-table td {
            border: 1px solid black; padding: 4px 5px; text-align: center; vertical-align: middle;
        }
        .bill-table th { background-color: #f0f0f0; font-weight: bold; }
        .left-align { text-align: left; }
        .right-align { text-align: right; }
        .bold { font-weight: bold; }

        .two-column { display: flex; gap: 15px; margin: 10px 0; }
        .column { flex: 1; }

        .calculation-section { margin: 10px 0; padding: 8px; border: 1px solid #ccc; font-size: 11px; }
        .calculation-row { display: flex; justify-content: space-between; margin: 3px 0; padding: 1px 0; }

        .signature-area { display: flex; justify-content: space-between; padding-bottom: 10px; }
        .signature-box { text-align: center; width: 45%; padding-top: 5px; }
        .signature-box p { font-weight: bold; text-decoration: underline; }

        /* Push signatures to bottom on Page 1 */
        #page-one .signature-area {
            margin-top: auto;
            padding-bottom: 50px; /* Approx 0.5 inches from bottom */
        }

        .document-container { padding: 15px; flex: 1; display: flex; flex-direction: column; }
        .content-section { flex: 1; }
        .content-section p { margin-bottom: 14px; text-align: left; }
        .highlight-box { background-color: #f9f9f9; border-left: 3px solid #3498db; padding: 15px; margin: 20px 0; }
        .payment-table td { padding: 8px 12px; }
        .payment-table .fixed-label { font-weight: bold; width: 55%; vertical-align: top; text-align: left; }
        .payment-table .dynamic-content { text-align: right; width: 45%; }

        /* Page 2 Signature Block */
        .signature-section {
            margin-top: 50pt; /* Precise 50 points gap */
            display: flex;
            justify-content: space-between;
        }

        .signature-left { text-align: left; }
        .signature-right { text-align: right; }
        .signature-line { font-weight: bold; text-decoration: underline; margin-bottom: 35px; }

        .amount-highlight { font-weight: bold; color: #2c3e50; }
        .section-divider { margin: 20px 0; border-top: 1px dashed #ccc; }

        .memo-table .serial { width: 5%; text-align: center; font-weight: bold; }
        .memo-table .label { width: 25%; font-weight: bold; text-align: left; }
        .memo-table .value { width: 70%; text-align: left; }
        .bank-table td { border: none !important; padding: 2px 0 !important; text-align: left; }
        .certification-title { font-weight: bold; text-decoration: underline; margin: 20px 0 10px 0; }

        @media print {
            body { padding: 0; margin: 0; }
            .page {
                box-shadow: none; margin: 0; width: 100%; max-width: none;
                padding: 10mm; height: 296mm; page-break-after: always;
                display: flex; flex-direction: column;
            }
            .page:last-child { page-break-after: auto; }
            .print-btn-container { display: none; }
            .watermark { display: block; }

            .content-wrapper { flex: 1; display: flex; flex-direction: column; }
            #page-one .signature-area { margin-top: auto !important; } /* Keep it at bottom */

            .compact-text, .content-section, .paragraph-content p { font-size: 14px !important; }
            .payment-table td, .memo-table td, .bank-table td { font-size: 14px !important; }
            .certification-title { font-size: 15px !important; }

            .print-scale-110 { transform: scale(1.1); transform-origin: top left; width: 90.9%; }
            .print-scale-130 { transform: scale(1.3); transform-origin: top left; width: 76.92%; }
        }
    </style>
</head>
<body>
    <div class="print-btn-container">
        <button class="print-btn" onclick="window.print()">Print Bill</button>
    </div>

    <!-- Page 1: Abstract -->
    <div class="page" id="page-one">
        <div class="watermark"></div>
        <div class="content-wrapper">
            <?php include __DIR__ . '/abstract.php'; ?>
        </div>
    </div>

    <!-- Page 2: Memo -->
    <div class="page">
        <div class="watermark"></div>
        <div class="content-wrapper">
            <?php include __DIR__ . '/memo.php'; ?>
        </div>
    </div>

    <!-- Page 3 & 4: Bill Forwarding Memo -->
    <?php include __DIR__ . '/bill_forwarding.php'; ?>

    <script>
        window.onbeforeprint = function() {
            const pages = document.querySelectorAll('.page');
            if (pages.length > 0) {
                pages[0].classList.add('print-scale-110');
            }
            for (let i = 1; i < pages.length; i++) {
                pages[i].classList.add('print-scale-130');
            }
        };

        window.onafterprint = function() {
            const pages = document.querySelectorAll('.page');
            pages.forEach(page => {
                page.classList.remove('print-scale-110');
                page.classList.remove('print-scale-130');
            });
        };
    </script>
</body>
</html>
