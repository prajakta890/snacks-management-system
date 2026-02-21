<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonError('Invalid method', 405); }

$orderId = (int)($_POST['order_id'] ?? 0);
$tableId = (int)($_POST['table_id'] ?? 0);
$action = sanitize($_POST['action'] ?? 'create');
$billNumber = sanitize($_POST['bill_number'] ?? '');

if ($action === 'create') {
    if (!$orderId || !$tableId) { jsonError('Missing order or table'); }

    // Check if bill already exists
    $existing = db()->fetchOne("SELECT * FROM bills WHERE order_id=?", [$orderId]);
    if ($existing) { jsonSuccess(['bill_number'=>$existing['bill_number']], 'Bill already exists'); }

    $order = db()->fetchOne("SELECT * FROM orders WHERE id=?", [$orderId]);
    if (!$order) { jsonError('Order not found'); }

    $taxAmount = round($order['subtotal'] * TAX_PERCENT / 100, 2);
    $totalAmount = $order['subtotal'] + $taxAmount;
    $bn = generateBillNumber();

    db()->insert("INSERT INTO bills (bill_number,table_id,order_id,customer_name,customer_mobile,subtotal,tax_percent,tax_amount,total_amount,payment_status) VALUES(?,?,?,?,?,?,?,?,?,?)",
        [$bn, $tableId, $orderId, $order['customer_name'], $order['customer_mobile'], $order['subtotal'], TAX_PERCENT, $taxAmount, $totalAmount, 'initiated']);

    db()->execute("UPDATE tables SET status='occupied' WHERE id=?", [$tableId]);
    jsonSuccess(['bill_number'=>$bn], 'Bill created');

} elseif ($action === 'update') {
    if (!$billNumber) { jsonError('Bill number required'); }
    $customerName = sanitize($_POST['customer_name'] ?? '');
    $customerMobile = sanitize($_POST['customer_mobile'] ?? '');
    $paymentStatus = sanitize($_POST['payment_status'] ?? '');
    $paymentMethod = sanitize($_POST['payment_method'] ?? 'cash');
    $orderStatus = sanitize($_POST['order_status'] ?? '');

    $updates = [];
    $params = [];
    if ($customerName) { $updates[] = 'customer_name=?'; $params[] = $customerName; }
    if ($customerMobile) { $updates[] = 'customer_mobile=?'; $params[] = $customerMobile; }
    if ($paymentStatus) { $updates[] = 'payment_status=?'; $params[] = $paymentStatus; }
    if ($paymentMethod) { $updates[] = 'payment_method=?'; $params[] = $paymentMethod; }

    if (empty($updates)) { jsonError('Nothing to update'); }
    $params[] = $billNumber;
    db()->execute("UPDATE bills SET " . implode(',', $updates) . " WHERE bill_number=?", $params);

    if ($paymentStatus === 'paid') {
        $bill = db()->fetchOne("SELECT * FROM bills WHERE bill_number=?", [$billNumber]);
        if ($bill) {
            db()->execute("UPDATE tables SET status='available' WHERE id=?", [$bill['table_id']]);
            db()->execute("UPDATE orders SET status='served' WHERE id=?", [$bill['order_id']]);
            $exists = db()->fetchOne("SELECT id FROM payments WHERE bill_id=?", [$bill['id']]);
            if (!$exists) {
                db()->insert("INSERT INTO payments (bill_id,amount,payment_method) VALUES(?,?,?)", [$bill['id'], $bill['total_amount'], $paymentMethod]);
            }
        }
    }

    if ($orderStatus) {
        $billData = db()->fetchOne("SELECT order_id FROM bills WHERE bill_number=?", [$billNumber]);
        if ($billData) {
            db()->execute("UPDATE orders SET status=? WHERE id=?", [$orderStatus, $billData['order_id']]);
            if ($orderStatus === 'cancelled') {
                db()->execute("UPDATE order_items SET status='cancelled' WHERE order_id=? AND status='pending'", [$billData['order_id']]);
                // Zero out bill values since it's fully cancelled
                $newSub = 0; $newTax = 0; $newTotal = 0;
                db()->execute("UPDATE orders SET subtotal=?, tax=?, total=? WHERE id=?", [$newSub, $newTax, $newTotal, $billData['order_id']]);
                db()->execute("UPDATE bills SET subtotal=?, tax_amount=?, total_amount=? WHERE order_id=?", [$newSub, $newTax, $newTotal, $billData['order_id']]);
            }
        }
    }

    jsonSuccess([], 'Bill updated successfully');
}

jsonError('Unknown action');
