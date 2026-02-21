<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonError('Invalid method', 405); }

$orderId = (int)($_POST['order_id'] ?? 0);
$status = sanitize($_POST['status'] ?? '');
$validStatuses = ['placed','preparing','served','cancelled'];

if (!$orderId || !in_array($status, $validStatuses)) { jsonError('Invalid data'); }

db()->execute("UPDATE orders SET status=? WHERE id=?", [$status, $orderId]);

// Also update order items if cancelling
if ($status === 'cancelled') {
    db()->execute("UPDATE order_items SET status='cancelled' WHERE order_id=? AND status='pending'", [$orderId]);
}

jsonSuccess([], 'Order status updated to ' . ucfirst($status));
