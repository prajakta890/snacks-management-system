<?php
require_once __DIR__ . '/../config/config.php';
requireAdminLogin();

$pageTitle = 'Orders';
$pageSubtitle = 'View and manage all orders';
$activePage = 'orders';

$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? date('Y-m-d'); // Default to today

$sql = "SELECT o.*, t.table_number,
               b.payment_status, b.bill_number
        FROM orders o
        JOIN tables t ON o.table_id = t.id
        LEFT JOIN bills b ON b.order_id = o.id
        WHERE 1=1";
$params = [];

if ($date_filter) {
    if ($date_filter !== 'all') {
        $sql .= " AND DATE(o.created_at)=?"; 
        $params[] = $date_filter;
    }
}
if ($status_filter) { $sql .= " AND o.status=?"; $params[] = $status_filter; }
$sql .= " ORDER BY o.created_at DESC LIMIT 100";
$orders = db()->fetchAll($sql, $params);

include __DIR__ . '/partials/header.php';
?>

<!-- Status Filters -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;align-items:center">
    <?php foreach([''=>'All','placed'=>'Placed','preparing'=>'Preparing','served'=>'Served','cancelled'=>'Cancelled'] as $k=>$v): ?>
    <a href="orders.php?status=<?= $k ?>&date=<?= $date_filter ?>" class="topbar-btn <?= $status_filter===$k?'btn-primary':'btn-secondary' ?> btn-sm"><?= $v ?></a>
    <?php endforeach; ?>
    
    <div style="margin-left:auto; display:flex; gap:8px; align-items:center;">
        <span style="font-size:12px;color:var(--text-muted)">Date:</span>
        <input type="date" id="orderDateFilter" class="form-control" style="padding:4px 8px;font-size:12px;width:130px;height:auto" value="<?= $date_filter==='all'?'':$date_filter ?>" onchange="filterOrdersByDate(this.value)">
        <button class="topbar-btn btn-secondary btn-sm" onclick="filterOrdersByDate('all')">All Time</button>
        <span style="margin-left:8px;font-size:12px;color:var(--text-muted)"><?= count($orders) ?> orders</span>
    </div>
</div>

<div class="card">
    <?php if (empty($orders)): ?>
    <div class="empty-state"><div class="empty-icon">📋</div><h3>No orders found</h3></div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Order #</th><th>Table</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Bill Status</th><th>Time</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
        <?php $items = db()->fetchAll("SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON oi.menu_item_id=mi.id WHERE oi.order_id=?", [$o['id']]); ?>
        <tr>
            <td><strong><?= $o['order_number'] ?></strong></td>
            <td><span class="badge badge-placed"><?= $o['table_number'] ?></span></td>
            <td>
                <?= $o['customer_name'] ? htmlspecialchars($o['customer_name']) : '<span style="color:var(--text-muted)">—</span>' ?>
                <?php if ($o['customer_mobile']): ?>
                <br><small style="color:var(--text-muted)"><?= $o['customer_mobile'] ?></small>
                <?php endif; ?>
            </td>
            <td>
                <?php foreach ($items as $it): ?>
                <div style="font-size:12px;color:var(--text-secondary)">× <?= $it['quantity'] ?> <?= htmlspecialchars($it['name']) ?> <span class="badge badge-<?= $it['status'] ?>" style="font-size:9px"><?= $it['status'] ?></span></div>
                <?php endforeach; ?>
            </td>
            <td><strong><?= formatCurrency($o['total']) ?></strong></td>
            <td>
                <select class="form-control" style="padding:5px 8px;font-size:12px;width:120px" onchange="updateOrderStatus(<?= $o['id'] ?>, this.value)">
                    <?php foreach(['placed','preparing','served','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <?php if ($o['payment_status']): ?>
                <span class="badge badge-<?= $o['payment_status'] ?>"><?= ucfirst($o['payment_status']) ?></span>
                <?php else: ?>
                <span style="color:var(--text-muted);font-size:12px">No Bill</span>
                <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--text-muted)"><?= date('d M, h:i A', strtotime($o['created_at'])) ?></td>
            <td>
                <?php if ($o['bill_number']): ?>
                <a href="bills.php?bill=<?= $o['bill_number'] ?>" class="topbar-btn btn-secondary btn-sm"><i class="fa fa-file-invoice"></i></a>
                <?php else: ?>
                <button class="topbar-btn btn-primary btn-sm" onclick="createBill(<?= $o['id'] ?>, <?= $o['table_id'] ?>)"><i class="fa fa-plus"></i> Bill</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
function filterOrdersByDate(dateVal) {
    const urlParams = new URLSearchParams(window.location.search);
    if (dateVal === 'all') {
        urlParams.set('date', 'all');
    } else {
        urlParams.set('date', dateVal);
    }
    window.location.href = 'orders.php?' + urlParams.toString();
}

function updateOrderStatus(orderId, status) {
    fetch(BASE_URL + '/api/update_order_status.php', {
        method: 'POST',
        body: new URLSearchParams({ order_id: orderId, status: status })
    }).then(r=>r.json()).then(d=>{
        showToast(d.success ? 'Status updated' : (d.message||'Error'), d.success?'success':'error');
    });
}
function createBill(orderId, tableId) {
    fetch(BASE_URL + '/api/save_bill.php', {
        method: 'POST',
        body: new URLSearchParams({ order_id: orderId, table_id: tableId, action: 'create' })
    }).then(r=>r.json()).then(d=>{
        if (d.success) { showToast('Bill created!', 'success'); setTimeout(()=>location.reload(),800); }
        else showToast(d.message||'Error', 'error');
    });
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
