<?php
require_once __DIR__ . '/../config/config.php';
requireAdminLogin();

// Stats
$stats = [
    'totalTables' => db()->fetchOne("SELECT COUNT(*) as cnt FROM tables WHERE is_active=1")['cnt'],
    'activeTables' => db()->fetchOne("SELECT COUNT(*) as cnt FROM tables WHERE status='occupied' AND is_active=1")['cnt'],
    'pendingBills' => db()->fetchOne("SELECT COUNT(*) as cnt FROM bills WHERE payment_status IN ('initiated','pending')")['cnt'],
    'todayRevenue' => db()->fetchOne("SELECT COALESCE(SUM(total_amount),0) as total FROM bills WHERE payment_status='paid' AND DATE(updated_at)=CURDATE()")['total'],
    'totalOrders' => db()->fetchOne("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at)=CURDATE()")['cnt'],
    'pendingOrdersCount' => db()->fetchOne("SELECT COUNT(*) as cnt FROM orders WHERE status = 'placed'")['cnt'] ?? 0,
    'todayRevenueFormatted' => formatCurrency(db()->fetchOne("SELECT COALESCE(SUM(total_amount),0) as total FROM bills WHERE payment_status='paid' AND DATE(updated_at)=CURDATE()")['total'])
];

// Recent orders
$recentOrders = db()->fetchAll("
    SELECT o.*, t.table_number,
           COUNT(oi.id) as item_count
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC LIMIT 8
");

// Active table summary
$activeTblData = db()->fetchAll("
    SELECT t.*, b.total_amount, b.payment_status, b.bill_number
    FROM tables t
    LEFT JOIN bills b ON t.id = b.table_id AND b.payment_status IN ('initiated','pending')
    WHERE t.is_active=1 AND t.status='occupied'
    ORDER BY t.table_number
");

// Format orders for JSON
$formattedOrders = array_map(function($o) {
    return [
        'order_number' => $o['order_number'],
        'table_number' => $o['table_number'],
        'item_count' => $o['item_count'],
        'total_formatted' => formatCurrency($o['total']),
        'status' => $o['status'],
        'time' => date('h:i A', strtotime($o['created_at']))
    ];
}, $recentOrders);

// Format table data for JSON
$formattedTables = array_map(function($t) {
    return [
        'table_number' => $t['table_number'],
        'bill_number' => $t['bill_number'] ?? 'No Bill',
        'status' => $t['payment_status'] ?? 'Active',
        'total_formatted' => formatCurrency($t['total_amount'] ?? 0)
    ];
}, $activeTblData);

jsonSuccess([
    'stats' => $stats,
    'recentOrders' => $formattedOrders,
    'activeTables' => $formattedTables,
    'lastUpdated' => date('h:i A')
], 'Dashboard data fetched');
