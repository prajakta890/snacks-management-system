<?php
require_once __DIR__ . '/../config/config.php';
requireAdminLogin();

$pageTitle = 'Daily Reports';
$pageSubtitle = 'Sales analytics and revenue summary';
$activePage = 'reports';

$date = sanitize($_GET['date'] ?? date('Y-m-d'));

// Today's summary
$summary = db()->fetchOne("
    SELECT
        COUNT(*) as total_bills,
        COALESCE(SUM(CASE WHEN payment_status='paid' THEN total_amount ELSE 0 END),0) as revenue,
        COALESCE(SUM(CASE WHEN payment_status='paid' THEN 1 ELSE 0 END),0) as paid_count,
        COALESCE(SUM(CASE WHEN payment_status IN('initiated','pending') THEN 1 ELSE 0 END),0) as pending_count,
        COALESCE(SUM(CASE WHEN payment_status IN('initiated','pending') THEN total_amount ELSE 0 END),0) as pending_amount
    FROM bills WHERE DATE(created_at)=?", [$date]);

// Hourly revenue for chart
$hourly = db()->fetchAll("
    SELECT HOUR(created_at) as hr, COALESCE(SUM(total_amount),0) as total
    FROM bills WHERE DATE(created_at)=? AND payment_status='paid'
    GROUP BY HOUR(created_at) ORDER BY hr", [$date]);

$hourlyData = array_fill(0, 24, 0);
foreach ($hourly as $h) { $hourlyData[$h['hr']] = floatval($h['total']); }

// Category revenue
$catRevenue = db()->fetchAll("
    SELECT mc.name, mc.icon, COALESCE(SUM(oi.subtotal),0) as total, COUNT(oi.id) as qty
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id=mi.id
    JOIN menu_categories mc ON mi.category_id=mc.id
    JOIN orders o ON oi.order_id=o.id
    JOIN bills b ON b.order_id=o.id
    WHERE DATE(b.created_at)=? AND b.payment_status='paid'
    GROUP BY mc.id ORDER BY total DESC", [$date]);

// Top Items
$topItems = db()->fetchAll("
    SELECT mi.name, SUM(oi.quantity) as qty, SUM(oi.subtotal) as total
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id=mi.id
    JOIN orders o ON oi.order_id=o.id
    JOIN bills b ON b.order_id=o.id
    WHERE DATE(b.created_at)=? AND b.payment_status='paid'
    GROUP BY mi.id ORDER BY qty DESC LIMIT 10", [$date]);

// Last 7 days
$weekly = db()->fetchAll("
    SELECT DATE(created_at) as day, COALESCE(SUM(CASE WHEN payment_status='paid' THEN total_amount ELSE 0 END),0) as revenue
    FROM bills WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at) ORDER BY day");

include __DIR__ . '/partials/header.php';
?>

<!-- Date Selector -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
    <div class="form-group" style="margin:0;display:flex;align-items:center;gap:10px">
        <label style="color:var(--text-muted);font-size:13px;white-space:nowrap">Report Date:</label>
        <input type="date" class="form-control" id="reportDate" value="<?= $date ?>" max="<?= date('Y-m-d') ?>" style="width:180px" onchange="window.location.href='reports.php?date='+this.value">
    </div>
    <button class="topbar-btn btn-secondary btn-sm" onclick="window.print()"><i class="fa fa-print"></i> Print Report</button>
    <a href="reports.php?date=<?= date('Y-m-d') ?>" class="topbar-btn btn-secondary btn-sm">Today</a>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa fa-indian-rupee-sign"></i></div>
        <div class="stat-info">
            <h3><?= formatCurrency($summary['revenue']) ?></h3>
            <p>Today's Revenue</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa fa-file-invoice"></i></div>
        <div class="stat-info">
            <h3><?= $summary['total_bills'] ?></h3>
            <p>Total Bills</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa fa-circle-check"></i></div>
        <div class="stat-info">
            <h3><?= $summary['paid_count'] ?></h3>
            <p>Paid Bills</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa fa-clock"></i></div>
        <div class="stat-info">
            <h3><?= $summary['pending_count'] ?></h3>
            <p>Pending Bills</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fa fa-hourglass-half"></i></div>
        <div class="stat-info">
            <h3><?= formatCurrency($summary['pending_amount']) ?></h3>
            <p>Pending Amount</p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Hourly Chart -->
    <div class="col" style="flex:2">
        <div class="card">
            <div class="card-header"><h2>Hourly Revenue — <?= date('d M Y', strtotime($date)) ?></h2></div>
            <div class="card-body"><canvas id="hourlyChart" height="200"></canvas></div>
        </div>
    </div>
    <!-- Category Breakdown -->
    <div class="col" style="flex:1;min-width:220px">
        <div class="card">
            <div class="card-header"><h2>Category Revenue</h2></div>
            <div style="padding:12px">
                <?php if (empty($catRevenue)): ?>
                <div class="empty-state" style="padding:30px"><div class="empty-icon">📊</div><h3>No data</h3></div>
                <?php else: ?>
                <?php foreach($catRevenue as $cr): $pct = $summary['revenue'] > 0 ? round($cr['total']/$summary['revenue']*100) : 0; ?>
                <div style="margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:5px">
                        <span><?= $cr['icon'] ?> <?= $cr['name'] ?> <span style="color:var(--text-muted);font-size:11px">(<?= $cr['qty'] ?> items)</span></span>
                        <strong><?= formatCurrency($cr['total']) ?></strong>
                    </div>
                    <div style="height:6px;background:rgba(255,255,255,0.06);border-radius:3px;overflow:hidden">
                        <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(to right,var(--primary),var(--accent));border-radius:3px;transition:width 0.5s"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row" style="margin-top:20px">
    <!-- Weekly Trend -->
    <div class="col">
        <div class="card">
            <div class="card-header"><h2>Last 7 Days Revenue</h2></div>
            <div class="card-body"><canvas id="weeklyChart" height="180"></canvas></div>
        </div>
    </div>
    <!-- Top Items -->
    <div class="col">
        <div class="card">
            <div class="card-header"><h2>Top Selling Items</h2></div>
            <?php if (empty($topItems)): ?>
            <div class="empty-state" style="padding:30px"><div class="empty-icon">🏆</div><h3>No data</h3></div>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>#</th><th>Item</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
                <tbody>
                <?php foreach($topItems as $i=>$item): ?>
                <tr>
                    <td><span style="color:<?= $i<3?'var(--warning)':'var(--text-muted)' ?>;font-weight:700"><?= $i+1 ?></span></td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= $item['qty'] ?></td>
                    <td><strong><?= formatCurrency($item['total']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = 'rgba(255,255,255,0.5)';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

// Hourly Chart
const hourlyCtx = document.getElementById('hourlyChart');
new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: Array.from({length:24}, (_,i) => i===0?'12am': i<12?i+'am': i===12?'12pm':(i-12)+'pm'),
        datasets: [{
            label: 'Revenue (₹)',
            data: <?= json_encode(array_values($hourlyData)) ?>,
            backgroundColor: 'rgba(108,92,231,0.6)',
            borderColor: 'rgba(108,92,231,1)',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero:true, ticks:{ callback: v=>'₹'+v } },
            x: { ticks:{ maxTicksLimit:12 } }
        }
    }
});

// Weekly Chart
<?php
$weekLabels = [];
$weekValues = [];
$weekMap = [];
foreach ($weekly as $w) { $weekMap[$w['day']] = $w['revenue']; }
for ($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $weekLabels[] = date('M d', strtotime($d));
    $weekValues[] = $weekMap[$d] ?? 0;
}
?>
const weeklyCtx = document.getElementById('weeklyChart');
new Chart(weeklyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($weekLabels) ?>,
        datasets: [{
            label: 'Revenue (₹)',
            data: <?= json_encode($weekValues) ?>,
            borderColor: '#fd79a8',
            backgroundColor: 'rgba(253,121,168,0.1)',
            borderWidth: 2,
            pointBackgroundColor: '#fd79a8',
            pointRadius: 5,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend:{ display:false } },
        scales: { y:{ beginAtZero:true, ticks:{ callback:v=>'₹'+v } } }
    }
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
