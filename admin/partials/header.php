<?php
// Shared admin header/sidebar — include at top of each admin page
// Expects $pageTitle, $pageSubtitle, $activePage to be set before include
if (!isset($pageTitle)) $pageTitle = 'Dashboard';
if (!isset($pageSubtitle)) $pageSubtitle = APP_NAME;
if (!isset($activePage)) $activePage = '';

// Count pending orders for badge
$pendingOrders = db()->fetchOne("SELECT COUNT(*) as cnt FROM orders WHERE status = 'placed'")['cnt'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">🍽️</div>
        <div class="logo-text">
            <h2><?= APP_NAME ?></h2>
            <span><?= APP_TAGLINE ?></span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-title">Main</div>
        <a href="<?= BASE_URL ?>/admin/index.php" class="nav-link <?= $activePage==='dashboard'?'active':'' ?>">
            <i class="fa fa-gauge-high"></i> Dashboard
        </a>
        <a href="<?= BASE_URL ?>/admin/tables.php" class="nav-link <?= $activePage==='tables'?'active':'' ?>">
            <i class="fa fa-border-all"></i> Tables
        </a>
        <a href="<?= BASE_URL ?>/admin/orders.php" class="nav-link <?= $activePage==='orders'?'active':'' ?>">
            <i class="fa fa-receipt"></i> Orders
            <?php if ($pendingOrders > 0): ?>
            <span class="badge"><?= $pendingOrders ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/bills.php" class="nav-link <?= $activePage==='bills'?'active':'' ?>">
            <i class="fa fa-file-invoice"></i> Bills
        </a>
        <div class="nav-section-title">Management</div>
        <a href="<?= BASE_URL ?>/admin/menu.php" class="nav-link <?= $activePage==='menu'?'active':'' ?>">
            <i class="fa fa-utensils"></i> Menu Items
        </a>
        <a href="<?= BASE_URL ?>/admin/reports.php" class="nav-link <?= $activePage==='reports'?'active':'' ?>">
            <i class="fa fa-chart-line"></i> Reports
        </a>
        <div class="nav-section-title">Customer</div>
        <a href="<?= BASE_URL ?>/customer/" class="nav-link" target="_blank">
            <i class="fa fa-mobile-screen"></i> Customer View <i class="fa fa-arrow-up-right-from-square" style="font-size:10px;margin-left:auto;color:var(--text-muted)"></i>
        </a>
    </nav>
    <div class="sidebar-footer">
        <?php $admin = adminInfo(); ?>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($admin['full_name'] ?? 'A', 0, 1)) ?></div>
            <div class="user-details">
                <h4><?= htmlspecialchars($admin['full_name'] ?? 'Admin') ?></h4>
                <span><?= htmlspecialchars($admin['role'] ?? 'admin') ?></span>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/admin/logout.php" class="btn-logout"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </div>
</aside>

<!-- MAIN CONTENT -->
<div class="main-content">
    <header class="topbar">
        <div class="topbar-left" style="display:flex;align-items:center;gap:12px;">
            <button class="topbar-btn btn-secondary" id="sidebarToggle" style="display:none;padding:8px 12px;" onclick="document.getElementById('sidebar').classList.toggle('open')">
                <i class="fa fa-bars"></i>
            </button>
            <div class="page-title">
                <h1><?= $pageTitle ?></h1>
                <p><?= $pageSubtitle ?></p>
            </div>
        </div>
        <div class="topbar-actions">
            <span style="font-size:12px;color:var(--text-muted);" id="currentTime"></span>
            <?php if (isset($topbarActions)) echo $topbarActions; ?>
        </div>
    </header>
    <div class="page-body">
