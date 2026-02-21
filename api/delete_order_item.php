<?php
require_once __DIR__ . '/../config/config.php';
requireAdminLogin(); // Only admins can delete

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonError('Invalid method', 405); }

$itemId = (int)($_POST['order_item_id'] ?? 0);
if (!$itemId) { jsonError('Invalid item ID'); }

// Fetch item to know the order
$item = db()->fetchOne("SELECT * FROM order_items WHERE id=?", [$itemId]);
if (!$item) { jsonError('Item not found'); }

$orderId = $item['order_id'];

// Delete the item
db()->execute("DELETE FROM order_items WHERE id=?", [$itemId]);

// Recalculate bill ignoring cancelled items
$newSub = db()->fetchOne("SELECT COALESCE(SUM(subtotal),0) as total FROM order_items WHERE order_id=? AND status != 'cancelled'", [$orderId])['total'];
$newTax = round($newSub * TAX_PERCENT / 100, 2);
$newTotal = $newSub + $newTax;

db()->execute("UPDATE orders SET subtotal=?, tax=?, total=? WHERE id=?", [$newSub, $newTax, $newTotal, $orderId]);
db()->execute("UPDATE bills SET subtotal=?, tax_amount=?, total_amount=? WHERE order_id=?", [$newSub, $newTax, $newTotal, $orderId]);

jsonSuccess([], 'Item removed successfully');
