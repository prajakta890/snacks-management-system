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
    $html = '<div class="empty-cart" style="margin-top:20px;">
        <div class="icon" style="font-size:32px;margin-bottom:10px;">🧾</div>
        <p>No active orders for this table.<br>Start ordering from the menu!</p>
    </div>';
} else {
    $items = db()->fetchAll("SELECT oi.* FROM order_items oi WHERE oi.order_id=?", [$bill['order_id']]);

    $itemsHtml = '';
    foreach ($items as $item) {
        $statusBadge = '';
        if ($item['status'] === 'pending') $statusBadge = '<span style="font-size:10px;background:var(--warning);color:#000;padding:2px 6px;border-radius:4px;font-weight:700">Pending</span>';
        elseif ($item['status'] === 'preparing') $statusBadge = '<span style="font-size:10px;background:var(--primary);color:#fff;padding:2px 6px;border-radius:4px;font-weight:700">Preparing</span>';
        elseif ($item['status'] === 'served') $statusBadge = '<span style="font-size:10px;background:var(--success);color:#fff;padding:2px 6px;border-radius:4px;font-weight:700">Served</span>';
        
        $itemsHtml .= "
        <div class='cart-item' style='padding:12px; border-bottom:1px solid rgba(255,255,255,0.05); align-items:center;'>
            <div style='flex:1'>
                <div style='font-weight:600;font-size:14px;display:flex;align-items:center;gap:6px;'>
                    {$item['item_name']} $statusBadge
                </div>
                <div style='font-size:12px;color:var(--text-muted);margin-top:2px;'>
                    {$item['quantity']} x ₹" . number_format($item['item_price'], 2) . "
                </div>
            </div>
            <div style='font-weight:700'>₹" . number_format($item['subtotal'], 2) . "</div>
        </div>";
    }

    $html = "
    <div style='margin-bottom:16px; padding:12px; background:rgba(255,255,255,0.05); border-radius:8px;'>
        <div style='display:flex;justify-content:space-between;margin-bottom:4px'>
            <span style='color:var(--text-muted);font-size:12px'>Order No:</span>
            <span style='font-weight:700;font-size:12px'>{$bill['order_number']}</span>
        </div>
        <div style='display:flex;justify-content:space-between'>
            <span style='color:var(--text-muted);font-size:12px'>Status:</span>
            <span style='font-weight:700;font-size:12px;text-transform:uppercase'>{$bill['order_status']}</span>
        </div>
    </div>

    <div style='max-height: 40vh; overflow-y: auto; margin:0 -16px; padding:0 16px;'>
        $itemsHtml
    </div>
    
    <div class='cart-total-block' style='margin-top:16px; display:block;'>
        <div class='cart-total-row'><span>Subtotal</span><span>" . formatCurrency($bill['subtotal']) . "</span></div>
        <div class='cart-total-row'><span>Tax ({$bill['tax_percent']}%)</span><span>" . formatCurrency($bill['tax_amount']) . "</span></div>
        <div class='cart-total-row grand'><span>TOTAL</span><span>" . formatCurrency($bill['total_amount']) . "</span></div>
    </div>
    
    <div style='text-align:center; margin-top:16px; font-size:12px; color:var(--text-muted);'>
        <i class='fa fa-info-circle'></i> Please proceed to the counter or ask a waiter for payment.
    </div>";
}

echo json_encode(['success' => true, 'html' => $html]);
