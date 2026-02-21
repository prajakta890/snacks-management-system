<?php
require_once __DIR__ . '/../config/config.php';

$tables = db()->fetchAll("SELECT * FROM tables WHERE is_active=1 AND status='available' ORDER BY floor, table_number");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome — <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/customer.css">
</head>
<body>
<header class="cust-header">
    <div class="cust-logo">
        <span class="icon">🍽️</span>
        <div>
            <h1><?= APP_NAME ?></h1>
            <small><?= APP_TAGLINE ?></small>
        </div>
    </div>
</header>

<div class="table-select-page">
    <div class="table-select-card">
        <div class="hero-icon">🪑</div>
        <h2>Select Your Table</h2>
        <p>Choose your table number to browse the menu and place your order</p>

        <?php if (empty($tables)): ?>
        <div style="background:rgba(225,112,85,0.12);border:1px solid rgba(225,112,85,0.3);border-radius:12px;padding:16px;margin-bottom:20px;color:#e17055;font-size:14px">
            <i class="fa fa-circle-exclamation"></i> No available tables at the moment. Please wait for a table to be freed.
        </div>
        <?php else: ?>
        <select class="form-control" id="tableSelect">
            <option value="">— Choose a Table —</option>
            <?php foreach ($tables as $t): ?>
            <option value="<?= $t['id'] ?>" data-num="<?= $t['table_number'] ?>">
                🪑 <?= $t['table_number'] ?> · <?= $t['floor'] ?> (<?= $t['capacity'] ?> seats)
            </option>
            <?php endforeach; ?>
        </select>
        <button class="btn-start" onclick="proceed()"><i class="fa fa-utensils"></i> &nbsp;View Menu</button>
        <?php endif; ?>

        <div style="margin-top:24px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.06)">
            <p style="font-size:12px;color:rgba(255,255,255,0.3)">Having trouble? Ask our staff for assistance.</p>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script src="<?= BASE_URL ?>/assets/js/toast.js"></script>
<script>
function proceed() {
    const sel = document.getElementById('tableSelect');
    const tableId = sel.value;
    const tableNum = sel.options[sel.selectedIndex]?.getAttribute('data-num');
    if (!tableId) {
        showToast('Please select a table first!', 'error');
        return;
    }
    window.location.href = '<?= BASE_URL ?>/customer/menu.php?table_id=' + tableId + '&table_num=' + tableNum;
}
document.getElementById('tableSelect')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') proceed();
});
</script>
</body>
</html>
