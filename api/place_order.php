<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonError('Invalid method', 405); }

$tableId = (int)($_POST['table_id'] ?? 0);
$items = json_decode($_POST['items'] ?? '[]', true);
$customerName = sanitize($_POST['customer_name'] ?? '');
$customerMobile = sanitize($_POST['customer_mobile'] ?? '');
if (empty($customerName) && empty($customerMobile)) {
    $customerName = 'Chandrakant';
    $customerMobile = '9860444609';
}
$notes = sanitize($_POST['notes'] ?? '');

if (!$tableId) { jsonError('Invalid table'); }
if (empty($items)) { jsonError('No items in order'); }

// Validate table
$table = db()->fetchOne("SELECT * FROM tables WHERE id=? AND is_active=1", [$tableId]);
if (!$table) { jsonError('Table not found'); }

// Check if there's already an open order for this table
$existingOrder = db()->fetchOne("
    SELECT o.* FROM orders o
    JOIN bills b ON b.order_id=o.id
    WHERE o.table_id=? AND b.payment_status IN('initiated','pending')
    ORDER BY o.created_at DESC LIMIT 1", [$tableId]);

try {
    $pdo = db()->getConnection();
    $pdo->beginTransaction();

    $subtotal = 0;
    $orderItems = [];

    foreach ($items as $item) {
        $menuItem = db()->fetchOne("SELECT * FROM menu_items WHERE id=? AND is_available=1 AND is_deleted=0", [(int)$item['id']]);
        if (!$menuItem) continue;
        $qty = max(1, (int)$item['qty']);
        $lineTotal = $menuItem['price'] * $qty;
        $subtotal += $lineTotal;
        $orderItems[] = ['menu_item_id'=>$menuItem['id'], 'name'=>$menuItem['name'], 'price'=>$menuItem['price'], 'qty'=>$qty, 'total'=>$lineTotal];
    }

    if (empty($orderItems)) { $pdo->rollBack(); jsonError('No valid items'); }

    $taxAmount = round($subtotal * TAX_PERCENT / 100, 2);
    $totalAmount = $subtotal + $taxAmount;

    if ($existingOrder) {
        // Add items to existing order
        $orderId = $existingOrder['id'];
        foreach ($orderItems as $oi) {
            $existing = db()->fetchOne("SELECT * FROM order_items WHERE order_id=? AND menu_item_id=? AND status != 'cancelled'", [$orderId, $oi['menu_item_id']]);
            if ($existing) {
                db()->execute("UPDATE order_items SET quantity=quantity+?, subtotal=subtotal+? WHERE id=?", [$oi['qty'], $oi['total'], $existing['id']]);
            } else {
                db()->insert("INSERT INTO order_items (order_id,menu_item_id,item_name,item_price,quantity,subtotal,status) VALUES(?,?,?,?,?,?,?)",
                    [$orderId, $oi['menu_item_id'], $oi['name'], $oi['price'], $oi['qty'], $oi['total'], 'served']);
            }
        }
        // Recalculate order total ignoring cancelled items
        $newSub = db()->fetchOne("SELECT COALESCE(SUM(subtotal),0) as total FROM order_items WHERE order_id=? AND status != 'cancelled'", [$orderId])['total'];
        $newTax = round($newSub * TAX_PERCENT / 100, 2);
        $newTotal = $newSub + $newTax;
        db()->execute("UPDATE orders SET subtotal=?, tax=?, total=? WHERE id=?", [$newSub, $newTax, $newTotal, $orderId]);
        // Update bill
        db()->execute("UPDATE bills SET subtotal=?, tax_amount=?, total_amount=? WHERE order_id=?", [$newSub, $newTax, $newTotal, $orderId]);
    } else {
        // Create new order
        $orderNumber = generateOrderNumber();
        $orderId = db()->insert("INSERT INTO orders (table_id,order_number,customer_name,customer_mobile,status,subtotal,tax,total,notes) VALUES(?,?,?,?,?,?,?,?,?)",
            [$tableId, $orderNumber, $customerName, $customerMobile, 'served', $subtotal, $taxAmount, $totalAmount, $notes]);

        foreach ($orderItems as $oi) {
            db()->insert("INSERT INTO order_items (order_id,menu_item_id,item_name,item_price,quantity,subtotal,status) VALUES(?,?,?,?,?,?,?)",
                [$orderId, $oi['menu_item_id'], $oi['name'], $oi['price'], $oi['qty'], $oi['total'], 'served']);
        }

        // Create bill
        $billNumber = generateBillNumber();
        db()->insert("INSERT INTO bills (bill_number,table_id,order_id,customer_name,customer_mobile,subtotal,tax_percent,tax_amount,total_amount,payment_status) VALUES(?,?,?,?,?,?,?,?,?,?)",
            [$billNumber, $tableId, $orderId, $customerName, $customerMobile, $subtotal, TAX_PERCENT, $taxAmount, $totalAmount, 'initiated']);

        // Update table status
        db()->execute("UPDATE tables SET status='occupied' WHERE id=?", [$tableId]);
    }

    $pdo->commit();
    jsonSuccess(['order_id' => $orderId], 'Order placed successfully!');

} catch (Exception $e) {
    $pdo->rollBack();
    jsonError('Order failed: ' . $e->getMessage());
}
