<?php
require_once __DIR__ . '/../config/config.php';
requireAdminLogin();

$pageTitle = 'Bills & Payments';
$pageSubtitle = 'Search and manage all bills';
$activePage = 'bills';

// Handle search
$search_name = sanitize($_GET['name'] ?? '');
$search_mobile = sanitize($_GET['mobile'] ?? '');
$search_date = sanitize($_GET['date'] ?? '');
$search_status = sanitize($_GET['status'] ?? '');

$sql = "SELECT b.*, t.table_number, o.order_number
        FROM bills b
        JOIN tables t ON b.table_id = t.id
        JOIN orders o ON b.order_id = o.id
        WHERE 1=1";
$params = [];
if ($search_name) { $sql .= " AND b.customer_name LIKE ?"; $params[] = "%$search_name%"; }
if ($search_mobile) { $sql .= " AND b.customer_mobile LIKE ?"; $params[] = "%$search_mobile%"; }
if ($search_date) { $sql .= " AND DATE(b.created_at)=?"; $params[] = $search_date; }
if ($search_status) { $sql .= " AND b.payment_status=?"; $params[] = $search_status; }
$sql .= " ORDER BY b.updated_at DESC LIMIT 200";
$bills = db()->fetchAll($sql, $params);

// Single bill view
$viewBill = null;
if (isset($_GET['bill'])) {
    $viewBill = db()->fetchOne(
        "SELECT b.*, t.table_number, o.order_number, o.status as order_status FROM bills b JOIN tables t ON b.table_id=t.id JOIN orders o ON b.order_id=o.id WHERE b.bill_number=?",
        [sanitize($_GET['bill'])]
    );
}

include __DIR__ . '/partials/header.php';
?>

<!-- Search Filters -->
<div class="filter-bar" style="margin-bottom:20px">
    <div class="form-group">
        <label>Customer Name</label>
        <input type="text" class="form-control" id="f_name" placeholder="Search name..." value="<?= $search_name ?>">
    </div>
    <div class="form-group">
        <label>Mobile Number</label>
        <input type="text" class="form-control" id="f_mobile" placeholder="Search mobile..." value="<?= $search_mobile ?>">
    </div>
    <div class="form-group">
        <label>Date</label>
        <input type="date" class="form-control" id="f_date" value="<?= $search_date ?>">
    </div>
    <div class="form-group">
        <label>Payment Status</label>
        <select class="form-control" id="f_status">
            <option value="">All</option>
            <?php foreach(['initiated','pending','paid','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $search_status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="display:flex;gap:8px;align-items:flex-end">
        <button class="topbar-btn btn-primary" onclick="searchBills()"><i class="fa fa-magnifying-glass"></i> Search</button>
        <a href="bills.php" class="topbar-btn btn-secondary">Clear</a>
    </div>
</div>

<?php if ($viewBill): ?>
<!-- Bill Detail View -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h2>Bill: <?= $viewBill['bill_number'] ?></h2>
        <div style="display:flex;gap:8px">
            <button class="topbar-btn btn-primary btn-sm" onclick="window.open('<?= BASE_URL ?>/admin/print_bill.php?bill=<?= $viewBill['bill_number'] ?>', '_blank')"><i class="fa fa-print"></i> Print</button>
            <button class="topbar-btn btn-sm" style="background:#25D366;color:#fff;border:none" onclick="window.open('<?= BASE_URL ?>/admin/print_bill.php?bill=<?= $viewBill['bill_number'] ?>#whatsapp-text', '_blank')"><i class="fa fa-comment"></i> Text</button>
            <button class="topbar-btn btn-sm" style="background:#075e54;color:#fff;border:none" onclick="window.open('<?= BASE_URL ?>/admin/print_bill.php?bill=<?= $viewBill['bill_number'] ?>#whatsapp-pdf', '_blank')"><i class="fa fa-file-pdf"></i> PDF</button>
            <a href="bills.php" class="topbar-btn btn-secondary btn-sm">Back</a>
        </div>
    </div>
    <div class="modal-body">
        <?php
        $bItems = db()->fetchAll("SELECT oi.* FROM order_items oi WHERE oi.order_id=?", [$viewBill['order_id']]);
        ?>
        <div class="grid-2">
            <div>
                <div class="form-group">
                    <label>Bill Number</label>
                    <div style="font-size:15px;font-weight:700"><?= $viewBill['bill_number'] ?></div>
                </div>
                <div class="form-group">
                    <label>Table</label>
                    <div><?= $viewBill['table_number'] ?></div>
                </div>
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" class="form-control" id="edit_name" value="<?= htmlspecialchars($viewBill['customer_name']??'') ?>">
                </div>
                <div class="form-group">
                    <label>Mobile</label>
                    <input type="text" class="form-control" id="edit_mobile" value="<?= htmlspecialchars($viewBill['customer_mobile']??'') ?>">
                </div>
            </div>
            <div>
                <div class="form-group">
                    <label>Payment Status</label>
                    <select class="form-control" id="edit_status">
                        <?php foreach(['initiated','pending','paid','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $viewBill['payment_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Order Status</label>
                    <select class="form-control" id="edit_order_status">
                        <?php foreach(['placed','preparing','served','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($viewBill['order_status'] ?? 'placed')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select class="form-control" id="edit_method">
                        <?php foreach(['cash','card','upi','online'] as $m): ?>
                        <option value="<?= $m ?>" <?= ($viewBill['payment_method']??'cash')===$m?'selected':'' ?>><?= ucfirst($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <div style="font-size:13px;color:var(--text-secondary)"><?= date('d M Y, h:i A', strtotime($viewBill['created_at'])) ?></div>
                </div>
                <button class="topbar-btn btn-success" style="width:100%" onclick="saveBillEdit('<?= $viewBill['bill_number'] ?>')"><i class="fa fa-floppy-disk"></i> Save Changes</button>
            </div>
        </div>
        <hr style="border-color:var(--border);margin:16px 0">
        <h4 style="margin-bottom:12px">Order Items</h4>
        <table class="bill-items-table">
            <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach($bItems as $bi): ?>
            <tr>
                <td><?= htmlspecialchars($bi['item_name']) ?></td>
                <td><?= $bi['quantity'] ?></td>
                <td><?= formatCurrency($bi['item_price']) ?></td>
                <td><strong><?= formatCurrency($bi['subtotal']) ?></strong></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="bill-total-section">
            <div class="total-row"><span>Subtotal</span><span><?= formatCurrency($viewBill['subtotal']) ?></span></div>
            <div class="total-row"><span>Tax (<?= $viewBill['tax_percent'] ?>%)</span><span><?= formatCurrency($viewBill['tax_amount']) ?></span></div>
            <?php if ($viewBill['discount'] > 0): ?>
            <div class="total-row"><span>Discount</span><span>-<?= formatCurrency($viewBill['discount']) ?></span></div>
            <?php endif; ?>
            <div class="total-row grand"><span>TOTAL</span><span><?= formatCurrency($viewBill['total_amount']) ?></span></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Bills Table -->
<div class="card">
    <div class="card-header">
        <h2><i class="fa fa-file-invoice" style="color:var(--primary-light);margin-right:8px"></i>Bills (<?= count($bills) ?>)</h2>
    </div>
    <?php if (empty($bills)): ?>
    <div class="empty-state"><div class="empty-icon">🧾</div><h3>No bills found</h3><p>Try adjusting your search filters.</p></div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Bill #</th><th>Table</th><th>Customer</th><th>Mobile</th><th>Total</th><th>Status</th><th>Method</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($bills as $b): ?>
        <tr>
            <td><strong><?= $b['bill_number'] ?></strong></td>
            <td><span class="badge badge-placed"><?= $b['table_number'] ?></span></td>
            <td><?= $b['customer_name'] ? htmlspecialchars($b['customer_name']) : '<span style="color:var(--text-muted)">—</span>' ?></td>
            <td style="color:var(--text-secondary)"><?= $b['customer_mobile'] ?: '—' ?></td>
            <td><strong><?= formatCurrency($b['total_amount']) ?></strong></td>
            <td>
                <select style="background:transparent;border:1px solid var(--border);border-radius:6px;color:#fff;padding:4px 8px;font-size:11px;cursor:pointer" onchange="quickUpdatePayment('<?= $b['bill_number'] ?>', this.value, this)">
                    <?php foreach(['initiated','pending','paid','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $b['payment_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="color:var(--text-secondary);font-size:12px"><?= ucfirst($b['payment_method']??'cash') ?></td>
            <td style="font-size:12px;color:var(--text-muted)"><?= date('d M, h:i A', strtotime($b['updated_at'])) ?></td>
            <td style="display:flex;gap:5px">
                <a href="bills.php?bill=<?= $b['bill_number'] ?>" class="topbar-btn btn-secondary btn-sm" title="View"><i class="fa fa-eye"></i></a>
                <a href="print_bill.php?bill=<?= $b['bill_number'] ?>" target="_blank" class="topbar-btn btn-secondary btn-sm" title="Print"><i class="fa fa-print"></i></a>
                <a href="print_bill.php?bill=<?= $b['bill_number'] ?>#whatsapp-text" target="_blank" class="topbar-btn btn-sm" style="background:#25D366;color:#fff;border:none" title="Send Text"><i class="fa fa-comment"></i></a>
                <a href="print_bill.php?bill=<?= $b['bill_number'] ?>#whatsapp-pdf" target="_blank" class="topbar-btn btn-sm" style="background:#075e54;color:#fff;border:none" title="Share PDF"><i class="fa fa-file-pdf"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
function searchBills() {
    const params = new URLSearchParams({
        name: document.getElementById('f_name').value,
        mobile: document.getElementById('f_mobile').value,
        date: document.getElementById('f_date').value,
        status: document.getElementById('f_status').value
    });
    window.location.href = 'bills.php?' + params.toString();
}
function quickUpdatePayment(billNum, status, el) {
    fetch(BASE_URL + '/api/update_payment.php', {
        method:'POST',
        body: new URLSearchParams({ bill_number: billNum, payment_status: status })
    }).then(r=>r.json()).then(d=>{
        showToast(d.success ? 'Payment status updated' : (d.message||'Error'), d.success?'success':'error');
    });
}
function saveBillEdit(billNum) {
    fetch(BASE_URL + '/api/save_bill.php', {
        method:'POST',
        body: new URLSearchParams({
            bill_number: billNum,
            customer_name: document.getElementById('edit_name').value,
            customer_mobile: document.getElementById('edit_mobile').value,
            payment_status: document.getElementById('edit_status').value,
            payment_method: document.getElementById('edit_method').value,
            order_status: document.getElementById('edit_order_status').value,
            action: 'update'
        })
    }).then(r=>r.json()).then(d=>{
        showToast(d.success ? 'Bill updated!' : (d.message||'Error'), d.success?'success':'error');
        if (d.success) setTimeout(()=>location.reload(), 800);
    });
}
document.addEventListener('keydown', e=>{ if(e.key==='Enter') searchBills(); });
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
