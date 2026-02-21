<?php
require_once __DIR__ . '/../config/config.php';
requireAdminLogin();

$pageTitle = 'Table Management';
$pageSubtitle = 'Monitor and manage all hotel tables';
$activePage = 'tables';
$topbarActions = '<a href="javascript:void(0)" onclick="openAddTableModal()" class="topbar-btn btn-primary"><i class="fa fa-plus"></i> Add Table</a>';

// Fetch all tables with bill info
$tables = db()->fetchAll("
    SELECT t.*,
           b.id as bill_id,
           b.bill_number,
           b.payment_status,
           b.total_amount,
           b.customer_name,
           b.customer_mobile,
           o.id as order_id,
           o.order_number,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
    FROM tables t
    LEFT JOIN bills b ON t.id = b.table_id AND b.payment_status IN ('initiated','pending')
    LEFT JOIN orders o ON b.order_id = o.id
    WHERE t.is_active = 1
    ORDER BY t.floor, t.table_number
");

include __DIR__ . '/partials/header.php';
?>

<!-- Legend -->
<div style="display:flex;gap:16px;align-items:center;margin-bottom:20px;flex-wrap:wrap">
    <span style="font-size:13px;color:var(--text-muted)">Legend:</span>
    <span style="display:flex;align-items:center;gap:6px;font-size:12px"><span style="width:14px;height:14px;border-radius:3px;background:var(--table-available);border:1px solid var(--border);display:inline-block"></span>Available</span>
    <span style="display:flex;align-items:center;gap:6px;font-size:12px"><span style="width:14px;height:14px;border-radius:3px;background:rgba(225,112,85,0.25);border:1px solid var(--danger);display:inline-block"></span>Unpaid</span>
    <span style="display:flex;align-items:center;gap:6px;font-size:12px"><span style="width:14px;height:14px;border-radius:3px;background:rgba(0,184,148,0.25);border:1px solid var(--success);display:inline-block"></span>Paid</span>
    <span style="display:flex;align-items:center;gap:6px;font-size:12px"><span style="width:14px;height:14px;border-radius:3px;background:rgba(116,185,255,0.15);border:1px solid var(--info);display:inline-block"></span>Occupied</span>
    <div style="margin-left:auto;display:flex;gap:8px">
        <button class="topbar-btn btn-secondary btn-sm" onclick="autoRefreshTables()"><i class="fa fa-arrows-rotate"></i> Refresh</button>
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;color:var(--text-secondary)">
            <input type="checkbox" id="autoRefresh" onchange="toggleAutoRefresh(this.checked)" style="accent-color:var(--primary)"> Auto-refresh (5s)
        </label>
    </div>
</div>

<!-- Tables Grid -->
<div class="tables-grid" id="tablesGrid">
<?php
$currentFloor = '';
foreach ($tables as $t):
    if ($t['floor'] !== $currentFloor) {
        $currentFloor = $t['floor'];
?>
</div>
<div style="grid-column:1/-1;margin-bottom:8px;margin-top:<?= $currentFloor==$tables[0]['floor']?'0':'16px' ?>">
    <h3 style="font-size:13px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:1px">📍 <?= $currentFloor ?></h3>
</div>
<?php } ?>
<?php
    $statusClass = '';
    $amountHtml = '';
    if ($t['payment_status'] === 'paid') {
        $statusClass = 'paid';
        $amountHtml = '<div class="table-amount" style="color:var(--success)">' . formatCurrency($t['total_amount'] ?? 0) . '</div>';
    } elseif (in_array($t['payment_status'], ['initiated', 'pending'])) {
        $statusClass = 'unpaid';
        $amountHtml = '<div class="table-amount" style="color:var(--danger)">' . formatCurrency($t['total_amount'] ?? 0) . '</div>';
    } elseif ($t['status'] === 'occupied') {
        $statusClass = 'occupied';
    }
?>
<div class="table-card <?= $statusClass ?>" onclick="openTableBill(<?= $t['id'] ?>, '<?= $t['table_number'] ?>', '<?= $t['floor'] ?>')" title="<?= $t['table_number'] ?> — <?= $t['floor'] ?>">
    <div class="table-status-dot"></div>
    <div class="table-number"><?= $t['table_number'] ?></div>
    <div class="table-label"><?= $t['capacity'] ?> seats</div>
    <?= $amountHtml ?>
    <?php if ($t['payment_status']): ?>
    <div style="margin-top:6px"><span class="badge badge-<?= $t['payment_status'] ?>" style="font-size:10px"><?= ucfirst($t['payment_status']) ?></span></div>
    <?php else: ?>
    <div style="margin-top:6px"><span class="badge badge-available" style="font-size:10px">Free</span></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<!-- Bill Modal -->
<div class="modal-overlay" id="billModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 id="billModalTitle">Table Bill</h3>
            <button class="btn-close" onclick="closeBillModal()"><i class="fa fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="billModalBody">
            <div style="text-align:center;padding:40px;color:var(--text-muted)"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>
        </div>
    </div>
</div>

<!-- Add Table Modal -->
<div class="modal-overlay" id="addTableModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Add New Table</h3>
            <button class="btn-close" onclick="document.getElementById('addTableModal').classList.remove('active')"><i class="fa fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form id="addTableForm">
                <div class="form-group">
                    <label>Table Number</label>
                    <input type="text" name="table_number" class="form-control" placeholder="e.g. T11" required>
                </div>
                <div class="form-group">
                    <label>Capacity (seats)</label>
                    <input type="number" name="capacity" class="form-control" value="4" min="1" max="20" required>
                </div>
                <div class="form-group">
                    <label>Floor</label>
                    <input type="text" name="floor" class="form-control" placeholder="e.g. Ground Floor" value="Ground Floor" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="topbar-btn btn-secondary" onclick="document.getElementById('addTableModal').classList.remove('active')">Cancel</button>
            <button class="topbar-btn btn-primary" onclick="submitAddTable()"><i class="fa fa-plus"></i> Add Table</button>
        </div>
    </div>
</div>

<script>
let autoRefreshTimer = null;

function openAddTableModal() {
    document.getElementById('addTableModal').classList.add('active');
}

function submitAddTable() {
    const form = document.getElementById('addTableForm');
    const data = new FormData(form);
    data.append('action', 'add_table');
    fetch(BASE_URL + '/api/update_payment.php', { method:'POST', body: data })
        .then(r=>r.json()).then(d=>{
            if (d.success) { showToast('Table added!', 'success'); setTimeout(()=>location.reload(),800); }
            else showToast(d.message||'Error', 'error');
        });
}

function openTableBill(tableId, tableNum, floor) {
    document.getElementById('billModal').classList.add('active');
    document.getElementById('billModalTitle').textContent = '🪑 ' + tableNum + ' — ' + floor;
    document.getElementById('billModalBody').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading...</div>';
    fetch(BASE_URL + '/api/get_table_bill.php?table_id=' + tableId)
        .then(r=>r.json())
        .then(d=>{ document.getElementById('billModalBody').innerHTML = d.html; });
}

function closeBillModal() {
    document.getElementById('billModal').classList.remove('active');
    setTimeout(()=>location.reload(), 300);
}

function autoRefreshTables() {
    fetch(BASE_URL + '/api/get_orders.php?summary=1')
        .then(r=>r.json())
        .then(d=>{ if(d.success) location.reload(); });
}

function toggleAutoRefresh(enabled) {
    if (enabled) {
        autoRefreshTimer = setInterval(autoRefreshTables, 5000);
        showToast('Auto-refresh enabled', 'success');
    } else {
        clearInterval(autoRefreshTimer);
        showToast('Auto-refresh disabled', 'warning');
    }
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
