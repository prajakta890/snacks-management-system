<?php
require_once __DIR__ . '/../config/config.php';
requireAdminLogin();

$pageTitle = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . (adminInfo()['full_name'] ?? 'Admin');
$activePage = 'dashboard';

// Stats
$totalTables = db()->fetchOne("SELECT COUNT(*) as cnt FROM tables WHERE is_active=1")['cnt'];
$activeTables = db()->fetchOne("SELECT COUNT(*) as cnt FROM tables WHERE status='occupied' AND is_active=1")['cnt'];
$pendingBills = db()->fetchOne("SELECT COUNT(*) as cnt FROM bills WHERE payment_status IN ('initiated','pending')")['cnt'];
$todayRevenue = db()->fetchOne("SELECT COALESCE(SUM(total_amount),0) as total FROM bills WHERE payment_status='paid' AND DATE(updated_at)=CURDATE()")['total'];
$totalOrders = db()->fetchOne("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at)=CURDATE()")['cnt'];
$pendingOrders = db()->fetchOne("SELECT COUNT(*) as cnt FROM orders WHERE status='placed'")['cnt'];

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

// Recent bills
$recentBills = db()->fetchAll("
    SELECT b.*, t.table_number
    FROM bills b
    JOIN tables t ON b.table_id = t.id
    ORDER BY b.updated_at DESC LIMIT 6
");

// Active table summary
$activeTblData = db()->fetchAll("
    SELECT t.*, b.total_amount, b.payment_status, b.bill_number
    FROM tables t
    LEFT JOIN bills b ON t.id = b.table_id AND b.payment_status IN ('initiated','pending')
    WHERE t.is_active=1 AND t.status='occupied'
    ORDER BY t.table_number
");

include __DIR__ . '/partials/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa fa-border-all"></i></div>
        <div class="stat-info">
            <h3><?= $totalTables ?></h3>
            <p>Total Tables</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa fa-circle-check"></i></div>
        <div class="stat-info">
            <h3><?= $activeTables ?></h3>
            <p>Occupied Tables</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa fa-clock"></i></div>
        <div class="stat-info">
            <h3><?= $pendingBills ?></h3>
            <p>Pending Bills</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fa fa-indian-rupee-sign"></i></div>
        <div class="stat-info">
            <h3><?= formatCurrency($todayRevenue) ?></h3>
            <p>Today's Revenue</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon pink"><i class="fa fa-receipt"></i></div>
        <div class="stat-info">
            <h3><?= $totalOrders ?></h3>
            <p>Today's Orders</p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Orders -->
    <div class="col" style="flex:2">
        <div class="card">
            <div class="card-header">
                <h2><i class="fa fa-receipt" style="color:var(--primary-light);margin-right:8px"></i>Recent Orders</h2>
                <a href="<?= BASE_URL ?>/admin/orders.php" class="topbar-btn btn-secondary btn-sm">View All</a>
            </div>
            <?php if (empty($recentOrders)): ?>
            <div class="empty-state" style="padding:40px">
                <div class="empty-icon">📋</div>
                <h3>No orders today</h3>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th><th>Table</th><th>Items</th><th>Total</th><th>Status</th><th>Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentOrders as $o): ?>
                <tr>
                    <td><strong><?= $o['order_number'] ?></strong></td>
                    <td><span class="badge badge-placed"><?= $o['table_number'] ?></span></td>
                    <td><?= $o['item_count'] ?> items</td>
                    <td><strong><?= formatCurrency($o['total']) ?></strong></td>
                    <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td style="color:var(--text-muted);font-size:12px"><?= date('h:i A', strtotime($o['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Tables -->
    <div class="col" style="flex:1;min-width:260px">
        <div class="card">
            <div class="card-header">
                <h2><i class="fa fa-border-all" style="color:var(--danger);margin-right:8px"></i>Active Tables</h2>
                <a href="<?= BASE_URL ?>/admin/tables.php" class="topbar-btn btn-secondary btn-sm">Manage</a>
            </div>
            <div style="padding:12px">
                <?php if (empty($activeTblData)): ?>
                <div class="empty-state" style="padding:30px">
                    <div class="empty-icon">🪑</div>
                    <h3>All tables free</h3>
                </div>
                <?php else: ?>
                <?php foreach ($activeTblData as $t): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 8px;border-bottom:1px solid var(--border);">
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="width:36px;height:36px;background:rgba(225,112,85,0.15);border:1px solid var(--danger);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;color:var(--danger)"><?= $t['table_number'] ?></div>
                        <div>
                            <div style="font-size:13px;font-weight:600"><?= $t['bill_number'] ?? 'No Bill' ?></div>
                            <span class="badge badge-<?= $t['payment_status'] ?? 'initiated' ?>"><?= ucfirst($t['payment_status'] ?? 'Active') ?></span>
                        </div>
                    </div>
                    <strong style="font-size:14px"><?= formatCurrency($t['total_amount'] ?? 0) ?></strong>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div style="margin-top:20px">
    <div class="card">
        <div class="card-header"><h2>Quick Actions</h2></div>
        <div class="card-body" style="display:flex;flex-wrap:wrap;gap:12px;">
            <a href="<?= BASE_URL ?>/admin/tables.php" class="topbar-btn btn-primary"><i class="fa fa-border-all"></i> Manage Tables</a>
            <a href="<?= BASE_URL ?>/admin/menu.php" class="topbar-btn btn-secondary"><i class="fa fa-utensils"></i> Add Menu Item</a>
            <a href="<?= BASE_URL ?>/admin/bills.php" class="topbar-btn btn-secondary"><i class="fa fa-magnifying-glass"></i> Search Bills</a>
            <a href="<?= BASE_URL ?>/admin/reports.php" class="topbar-btn btn-secondary"><i class="fa fa-chart-line"></i> View Reports</a>
            <a href="<?= BASE_URL ?>/customer/" target="_blank" class="topbar-btn btn-secondary"><i class="fa fa-mobile-screen"></i> Customer Menu</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
