<?php
require_once __DIR__ . '/../config/config.php';

$billNumber = sanitize($_GET['bill'] ?? '');
if (!$billNumber) { die('Bill not found'); }

$bill = db()->fetchOne(
    "SELECT b.*, t.table_number, o.order_number FROM bills b JOIN tables t ON b.table_id=t.id JOIN orders o ON b.order_id=o.id WHERE b.bill_number=?",
    [$billNumber]
);
if (!$bill) { die('Bill not found'); }

$items = db()->fetchAll("SELECT * FROM order_items WHERE order_id=?", [$bill['order_id']]);

// Build WhatsApp Message Text
$waMessage = "*" . APP_NAME . "*\n";
if (defined('APP_TAGLINE') && APP_TAGLINE) {
    $waMessage .= "_" . APP_TAGLINE . "_\n";
}
$waMessage .= "--------------------------------\n";
$waMessage .= "Bill No: " . $bill['bill_number'] . "\n";
$waMessage .= "Date: " . date('d M Y, h:i A', strtotime($bill['created_at'])) . "\n";
if ($bill['customer_name']) {
    $waMessage .= "Customer: " . $bill['customer_name'] . "\n";
}
$waMessage .= "--------------------------------\n";
$waMessage .= "Items:\n";
foreach ($items as $item) {
    $waMessage .= "- " . $item['item_name'] . " x" . $item['quantity'] . "  = Rs " . number_format($item['subtotal'], 2) . "\n";
}
$waMessage .= "--------------------------------\n";
$waMessage .= "Subtotal: Rs " . number_format($bill['subtotal'], 2) . "\n";
if ($bill['tax_amount'] > 0) {
    $waMessage .= "GST (" . $bill['tax_percent'] . "%): Rs " . number_format($bill['tax_amount'], 2) . "\n";
}
if ($bill['discount'] > 0) {
    $waMessage .= "Discount: -Rs " . number_format($bill['discount'], 2) . "\n";
}
$waMessage .= "*TOTAL: Rs " . number_format($bill['total_amount'], 2) . "*\n";
$waMessage .= "Payment: " . strtoupper($bill['payment_method'] ?? 'CASH') . "\n";
$waMessage .= "--------------------------------\n";
$waMessage .= "Thank you! Visit again 🙏";

// Encode for URL
$waMessageEncoded = urlencode($waMessage);
$waCustomerMobile = $bill['customer_mobile'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice — <?= $billNumber ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/invoice.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
<div class="no-print" style="text-align:center;padding:20px;background:#0d0d1a;border-bottom:1px solid rgba(255,255,255,0.1)">
    <button onclick="window.print()" style="background:linear-gradient(135deg,#6c5ce7,#fd79a8);border:none;color:#fff;padding:10px 24px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:Inter,sans-serif;margin-right:10px">🖨 Print Invoice</button>
    <button id="wa_text_btn" onclick="sendWhatsAppText()" style="background:#25D366;border:none;color:#fff;padding:10px 24px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:Inter,sans-serif;margin-right:10px">💬 Text</button>
    <button id="wa_pdf_btn" onclick="sendWhatsAppPDF()" style="background:#075e54;border:none;color:#fff;padding:10px 24px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:Inter,sans-serif">📄 PDF</button>
    <a href="javascript:history.back()" style="color:rgba(255,255,255,0.5);margin-left:16px;font-size:13px;text-decoration:none">← Back</a>
</div>

<div class="invoice-wrapper">
    <div class="invoice">
        <!-- Header -->
        <div class="invoice-header">
            <div class="biz-logo">🍽️</div>
            <div class="biz-info">
                <h1><?= APP_NAME ?></h1>
                <p><?= APP_TAGLINE ?></p>
                <p>📍 विठ्ठल टॉवर, बीड रोड, जामखेड</p>
                <p>📞 +91 70288 93232</p>
            </div>
        </div>

        <!-- Bill Details -->
        <div class="bill-meta">
            <div class="meta-left">
                <div class="meta-row"><strong>Bill No:</strong> <span><?= $bill['bill_number'] ?></span></div>
                <div class="meta-row"><strong>Order #:</strong> <span><?= $bill['order_number'] ?></span></div>
                <div class="meta-row"><strong>Table:</strong> <span><?= $bill['table_number'] ?></span></div>
            </div>
            <div class="meta-right">
                <div class="meta-row"><strong>Date:</strong> <span><?= date('d M Y', strtotime($bill['created_at'])) ?></span></div>
                <div class="meta-row"><strong>Time:</strong> <span><?= date('h:i A', strtotime($bill['created_at'])) ?></span></div>
                <div class="meta-row"><strong>Payment:</strong> <span class="status-badge status-<?= $bill['payment_status'] ?>"><?= strtoupper($bill['payment_status']) ?></span></div>
            </div>
        </div>

        <?php if ($bill['customer_name']): ?>
        <div class="customer-block">
            <strong>Customer:</strong> <?= htmlspecialchars($bill['customer_name']) ?>
            <?php if ($bill['customer_mobile']): ?> &nbsp;|&nbsp; <?= $bill['customer_mobile'] ?><?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $i => $item): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($item['item_name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= formatCurrency($item['item_price']) ?></td>
                <td><strong><?= formatCurrency($item['subtotal']) ?></strong></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-block">
            <div class="total-line"><span>Subtotal</span><span><?= formatCurrency($bill['subtotal']) ?></span></div>
            <div class="total-line"><span>GST (<?= $bill['tax_percent'] ?>%)</span><span><?= formatCurrency($bill['tax_amount']) ?></span></div>
            <?php if ($bill['discount'] > 0): ?>
            <div class="total-line"><span>Discount</span><span>-<?= formatCurrency($bill['discount']) ?></span></div>
            <?php endif; ?>
            <div class="total-line grand"><span>TOTAL AMOUNT</span><span><?= formatCurrency($bill['total_amount']) ?></span></div>
            <div class="payment-method">Payment Method: <?= strtoupper($bill['payment_method'] ?? 'CASH') ?></div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <p>Thank you for dining with us! 🙏</p>
            <p>Visit again at <?= APP_NAME ?></p>
            <div class="divider"></div>
            <p style="font-size:10px;color:#aaa">This is a computer generated invoice. No signature required.</p>
        </div>
    </div>
</div>

<script>
function sendWhatsAppText() {
    let mobile = "<?= $waCustomerMobile ?>";
    const message = "<?= $waMessageEncoded ?>";
    
    if (!mobile || mobile.length < 10) {
        mobile = prompt("Enter WhatsApp Number (with country code, e.g. 919876543210):", "91");
    } else if (mobile.length === 10) {
        mobile = "91" + mobile;
    }
    
    if (mobile && mobile.length >= 10) {
        const url = `https://wa.me/${mobile}?text=${message}`;
        window.open(url, '_blank');
    }
}

async function sendWhatsAppPDF() {
    let mobile = "<?= $waCustomerMobile ?>";
    const billNumber = "<?= $bill['bill_number'] ?>";
    const btn = document.getElementById('wa_pdf_btn');
    const originalText = btn.innerHTML;
    
    if (!mobile || mobile.length < 10) {
        mobile = prompt("Enter WhatsApp Number (with country code, e.g. 919876543210):", "91");
    } else if (mobile.length === 10) {
        mobile = "91" + mobile;
    }
    
    // Even if no mobile, we proceed with generate and download/share.
    btn.disabled = true;
    btn.innerHTML = '⌛ Generating...';

    const element = document.querySelector('.invoice-wrapper');
    const opt = {
        margin: 5,
        filename: `bill_${billNumber}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    try {
        const blob = await html2pdf().set(opt).from(element).outputPdf('blob');
        const file = new File([blob], `bill_${billNumber}.pdf`, { type: 'application/pdf' });

        // Share API (Mobile/Direct)
        if (navigator.canShare && navigator.canShare({ files: [file] })) {
            await navigator.share({
                files: [file],
                title: 'Bill - ' + billNumber,
                text: 'Invoice from ' + "<?= APP_NAME ?>"
            });
        } else {
            // Fallback for Desktop: Download + Open WhatsApp
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `bill_${billNumber}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            
            if (mobile && mobile.length >= 10) {
                const message = encodeURIComponent(`*Bill from ${"<?= APP_NAME ?>" }*\n\nYour PDF bill has been downloaded. Please attach it to this chat.`);
                window.open(`https://wa.me/${mobile}?text=${message}`, '_blank');
            } else {
                alert('PDF downloaded! Please share it with the customer.');
            }
        }
    } catch (error) {
        console.error('Error sharing PDF:', error);
        alert('Failed to share PDF. Please try again.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Auto-actions when opened with hash
window.onload = function() {
    if (window.location.hash === '#autoprint') window.print();
    if (window.location.hash === '#whatsapp-text') sendWhatsAppText();
    if (window.location.hash === '#whatsapp-pdf') sendWhatsAppPDF();
};
</script>
</body>
</html>
