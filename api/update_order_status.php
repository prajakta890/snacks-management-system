<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonError('Invalid method', 405); }

$orderId = (int)($_POST['order_id'] ?? 0);
$status = sanitize($_POST['status'] ?? '');
$validStatuses = ['placed','preparing','served','cancelled'];

if (!$orderId && !isset($_POST['order_item_id'])) { jsonError('Invalid data'); }

if (isset($_POST['order_item_id'])) {
    $itemId = (int)$_POST['order_item_id'];
    $itemStatus = sanitize($_POST['item_status'] ?? '');
    
    // Validate it exists
    $item = db()->fetchOne("SELECT order_id FROM order_items WHERE id=?", [$itemId]);
    if (!$item) jsonError('Item not found');
    $orderId = $item['order_id'];
    
    db()->execute("UPDATE order_items SET status=? WHERE id=?", [$itemStatus, $itemId]);

    // Recalculate bill
    $newSub = db()->fetchOne("SELECT COALESCE(SUM(subtotal),0) as total FROM order_items WHERE order_id=? AND status != 'cancelled'", [$orderId])['total'];
    $newTax = round($newSub * TAX_PERCENT / 100, 2);
    $newTotal = $newSub + $newTax;
    
    db()->execute("UPDATE orders SET subtotal=?, tax=?, total=? WHERE id=?", [$newSub, $newTax, $newTotal, $orderId]);
    db()->execute("UPDATE bills SET subtotal=?, tax_amount=?, total_amount=? WHERE order_id=?", [$newSub, $newTax, $newTotal, $orderId]);
    
    jsonSuccess([], 'Item status updated');
} else {
    if (!in_array($status, $validStatuses)) { jsonError('Invalid status'); }
    db()->execute("UPDATE orders SET status=? WHERE id=?", [$status, $orderId]);

    // Also update order items if cancelling
    if ($status === 'cancelled') {
        db()->execute("UPDATE order_items SET status='cancelled' WHERE order_id=? AND status='pending'", [$orderId]);
        
        $newSub = 0; $newTax = 0; $newTotal = 0;
        db()->execute("UPDATE orders SET subtotal=?, tax=?, total=? WHERE id=?", [$newSub, $newTax, $newTotal, $orderId]);
        db()->execute("UPDATE bills SET subtotal=?, tax_amount=?, total_amount=? WHERE order_id=?", [$newSub, $newTax, $newTotal, $orderId]);
    }

    jsonSuccess([], 'Order status updated to ' . ucfirst($status));
}
