<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonError('Invalid method', 405); }

$billNumber = sanitize($_POST['bill_number'] ?? '');
$paymentStatus = sanitize($_POST['payment_status'] ?? '');
$action = sanitize($_POST['action'] ?? 'update_status');

// Add table action
if ($action === 'add_table' && isAdminLoggedIn()) {
    $tableNumber = sanitize($_POST['table_number'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 4);
    $floor = sanitize($_POST['floor'] ?? 'Ground Floor');
    if (!$tableNumber) { jsonError('Table number required'); }
    $exists = db()->fetchOne("SELECT id FROM tables WHERE table_number=?", [$tableNumber]);
    if ($exists) { jsonError('Table number already exists'); }
    db()->insert("INSERT INTO tables (table_number,capacity,floor) VALUES(?,?,?)", [$tableNumber, $capacity, $floor]);
    jsonSuccess([], 'Table added successfully');
}

if (!$billNumber) { jsonError('Bill number required'); }

$bill = db()->fetchOne("SELECT * FROM bills WHERE bill_number=?", [$billNumber]);
if (!$bill) { jsonError('Bill not found'); }

$validStatuses = ['initiated','pending','paid','cancelled'];
if (!in_array($paymentStatus, $validStatuses)) { jsonError('Invalid payment status'); }

db()->execute("UPDATE bills SET payment_status=? WHERE bill_number=?", [$paymentStatus, $billNumber]);

// If paid, reset table status
if ($paymentStatus === 'paid') {
    db()->execute("UPDATE tables SET status='available' WHERE id=?", [$bill['table_id']]);
    // Record payment
    $method = sanitize($_POST['payment_method'] ?? 'cash');
    db()->insert("INSERT INTO payments (bill_id,amount,payment_method) VALUES(?,?,?)", [$bill['id'], $bill['total_amount'], $method]);
    // Auto-reset: mark order as served
    db()->execute("UPDATE orders SET status='served' WHERE id=?", [$bill['order_id']]);
}

jsonSuccess([], 'Payment status updated to ' . ucfirst($paymentStatus));
