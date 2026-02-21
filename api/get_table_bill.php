<?php
require_once __DIR__ . '/../config/config.php';

$tableId = (int)($_GET['table_id'] ?? 0);
if (!$tableId) { jsonError('Invalid table'); }

$table = db()->fetchOne("SELECT * FROM tables WHERE id=?", [$tableId]);
if (!$table) { jsonError('Table not found'); }

// Get active bill
$bill = db()->fetchOne("
    SELECT b.*, o.order_number, o.status as order_status
    FROM bills b
    JOIN orders o ON b.order_id=o.id
    WHERE b.table_id=? AND b.payment_status IN('initiated','pending')
    ORDER BY b.created_at DESC LIMIT 1", [$tableId]);

$html = '';

if (!$bill) {
    $html = '<div class="empty-state" style="padding:40px">
        <div class="empty-icon">🪑</div>
        <h3>No Active Bill</h3>
        <p>No pending bill for this table.</p>
        <div style="margin-top: 20px;">
            <a href="'.BASE_URL.'/admin/pos.php?table_id='.$tableId.'" class="topbar-btn btn-primary"><i class="fa fa-plus"></i> Add Order</a>
        </div>
    </div>';
} else {
    $items = db()->fetchAll("SELECT oi.* FROM order_items oi WHERE oi.order_id=?", [$bill['order_id']]);

    $statusOptions = '';
    foreach(['initiated','pending','paid','cancelled'] as $s) {
        $sel = $bill['payment_status']===$s?'selected':'';
        $statusOptions .= "<option value='$s' $sel>".ucfirst($s)."</option>";
    }

    $methodOptions = '';
    foreach(['cash','card','upi','online'] as $m) {
        $sel = ($bill['payment_method']??'cash')===$m?'selected':'';
        $methodOptions .= "<option value='$m' $sel>".ucfirst($m)."</option>";
    }

   $itemsHtml = '';
foreach ($items as $item) {
    $statusSel = '';
    foreach (['pending','preparing','served','cancelled'] as $s) {
        $sel = $item['status'] === $s ? 'selected' : '';
        $statusSel .= "<option value='$s' $sel>" . ucfirst($s) . "</option>";
    }

    $itemId = $item['id']; // safer & cleaner

    $itemsHtml .= "
    <tr>
        <td>" . htmlspecialchars($item['item_name']) . "</td>
        <td style='text-align:center'>{$item['quantity']}</td>
        <td>" . formatCurrency($item['item_price']) . "</td>
        <td><strong>" . formatCurrency($item['subtotal']) . "</strong></td>
        <td>
            <select 
                style='background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:6px;color:#fff;padding:3px 6px;font-size:11px'
                onchange='updateItemStatus($itemId, this.value)'>
                $statusSel
            </select>
            <button class='topbar-btn btn-danger' style='padding: 4px 8px; border-radius: 4px; display:inline-flex' onclick='deleteOrderItem($itemId, \"{$bill['bill_number']}\")' title='Delete Item'>
                <i class='fa fa-trash'></i>
            </button>
        </td>
    </tr>";
}

    $html = "
    <div style='margin-bottom:16px'>
        <div style='display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px'>
            <div style='flex:1;min-width:120px'>
                <label style='font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px'>Bill No</label>
                <div style='font-weight:700;margin-top:4px'>{$bill['bill_number']}</div>
            </div>
            <div style='flex:1'>
                <label style='font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px'>Customer</label>
                <input type='text' id='bCustName' class='form-control' value='".htmlspecialchars($bill['customer_name']??'')."' placeholder='Customer name' style='margin-top:4px'>
            </div>
            <div style='flex:1'>
                <label style='font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px'>Mobile</label>
                <input type='text' id='bCustMobile' class='form-control' value='".htmlspecialchars($bill['customer_mobile']??'')."' placeholder='Mobile no.' style='margin-top:4px'>
            </div>
        </div>
    </div>
    <table class='data-table'>
        <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>$itemsHtml</tbody>
    </table>
    <div class='bill-total-section' style='margin-top:16px;padding:16px;background:rgba(255,255,255,0.03);border-radius:10px'>
        <div class='total-row'><span>Subtotal</span><span>".formatCurrency($bill['subtotal'])."</span></div>
        <div class='total-row'><span>Tax ({$bill['tax_percent']}%)</span><span>".formatCurrency($bill['tax_amount'])."</span></div>
        <div class='total-row grand'><span>TOTAL</span><span>".formatCurrency($bill['total_amount'])."</span></div>
    </div>
    <div style='display:flex;gap:10px;margin-top:16px;flex-wrap:wrap'>
        <div style='flex:1'>
            <label style='font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px'>Payment Status</label>
            <select id='bPayStatus' class='form-control' style='margin-top:4px'>$statusOptions</select>
        </div>
        <div style='flex:1'>
            <label style='font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px'>Payment Method</label>
            <select id='bPayMethod' class='form-control' style='margin-top:4px'>$methodOptions</select>
        </div>
    </div>
    <div style='display:flex;gap:8px;margin-top:16px;flex-wrap:wrap'>
        <button class='topbar-btn btn-primary' onclick='saveBillFromModal(\"{$bill['bill_number']}\")' style='flex:1'><i class='fa fa-floppy-disk'></i> Save Bill</button>
        <a href='".BASE_URL."/admin/print_bill.php?bill={$bill['bill_number']}' target='_blank' class='topbar-btn btn-secondary'><i class='fa fa-print'></i> Print</a>
        <a href='".BASE_URL."/admin/pos.php?table_id={$tableId}' class='topbar-btn btn-warning'><i class='fa fa-plus'></i> Add Items</a>
    </div>";
}

echo json_encode(['success' => true, 'html' => $html, 'bill' => $bill]);
