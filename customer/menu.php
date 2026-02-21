<?php
require_once __DIR__ . '/../config/config.php';

$tableId = (int)($_GET['table_id'] ?? 0);
$tableNum = sanitize($_GET['table_num'] ?? '');

if (!$tableId) {
    header('Location: ' . BASE_URL . '/customer/');
    exit;
}

$table = db()->fetchOne("SELECT * FROM tables WHERE id=? AND is_active=1", [$tableId]);
if (!$table) {
    header('Location: ' . BASE_URL . '/customer/');
    exit;
}

$categories = db()->fetchAll("SELECT * FROM menu_categories WHERE is_active=1 ORDER BY sort_order");
$menuItems = db()->fetchAll("SELECT m.*, c.name as cat_name, c.icon as cat_icon FROM menu_items m JOIN menu_categories c ON m.category_id=c.id WHERE m.is_deleted=0 AND m.is_available=1 ORDER BY c.sort_order, m.sort_order, m.name");

// Group by category
$grouped = [];
foreach ($menuItems as $item) {
    $grouped[$item['category_id']]['cat'] = ['name'=>$item['cat_name'],'icon'=>$item['cat_icon']];
    $grouped[$item['category_id']]['items'][] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu — <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/customer.css">
</head>
<body>

<!-- Header -->
<header class="cust-header">
    <div class="cust-logo">
        <span class="icon">🍽️</span>
        <div>
            <h1><?= APP_NAME ?></h1>
            <small><?= APP_TAGLINE ?></small>
        </div>
    </div>
    <div class="table-badge">
        <i class="fa fa-border-all"></i> Table <?= htmlspecialchars($table['table_number']) ?>
    </div>
</header>

<!-- Category Filter -->
<div class="cat-bar">
    <button class="cat-btn active" onclick="filterCat('all', this)">🍽️ All</button>
    <?php foreach ($categories as $cat): ?>
    <button class="cat-btn" onclick="filterCat(<?= $cat['id'] ?>, this)"><?= $cat['icon'] ?> <?= $cat['name'] ?></button>
    <?php endforeach; ?>
</div>

<!-- Menu Sections -->
<main id="menuMain">
    <?php foreach ($grouped as $catId => $group): ?>
    <div class="menu-section" data-cat="<?= $catId ?>">
        <div class="menu-section-title">
            <span><?= $group['cat']['icon'] ?></span>
            <span><?= $group['cat']['name'] ?></span>
        </div>
        <div class="menu-cards">
            <?php foreach ($group['items'] as $item): ?>
            <div class="menu-card" id="menu-item-<?= $item['id'] ?>">
                <div class="card-img">
                    <?php if ($item['image'] && file_exists(UPLOAD_PATH . $item['image'])): ?>
                    <img src="<?= UPLOAD_URL . $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    <?php else: ?>
                    <?= $item['is_veg'] ? '🥗' : '🍗' ?>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
                        <span class="veg-indicator <?= $item['is_veg']?'veg':'nonveg' ?>"></span>
                        <div class="card-title"><?= htmlspecialchars($item['name']) ?></div>
                    </div>
                    <div class="card-desc"><?= $item['description'] ? htmlspecialchars(substr($item['description'],0,70)) . (strlen($item['description'])>70?'...':'') : '&nbsp;' ?></div>
                    <div class="card-footer">
                        <div class="price">₹<?= number_format($item['price'],2) ?></div>
                        <div id="cart-ctrl-<?= $item['id'] ?>">
                            <button class="add-btn" onclick="addToCart(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>', <?= $item['price'] ?>, <?= $item['is_veg'] ?>)">
                                <i class="fa fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</main>

<!-- Cart Float Button -->
<button class="cart-float" id="cartFloat" onclick="openCart()" style="display:none">
    <i class="fa fa-shopping-cart"></i>
    <span>Cart</span>
    <span class="cart-count" id="cartCountBadge">0</span>
    <span id="cartTotalBadge" style="font-size:13px;opacity:0.8">₹0</span>
</button>

<!-- Overlay -->
<div class="overlay" id="cartOverlay" onclick="closeCart()"></div>

<!-- Cart Panel -->
<div class="cart-panel" id="cartPanel">
    <div class="cart-header">
        <h2>🛒 Your Order</h2>
        <button class="btn-close" onclick="closeCart()"><i class="fa fa-xmark"></i></button>
    </div>
    <div class="cart-items" id="cartItemsContainer">
        <div class="empty-cart">
            <div class="icon">🛒</div>
            <p>Your cart is empty.<br>Add items from the menu!</p>
        </div>
    </div>
    <div class="cart-footer">
        <div class="cart-total-block" id="cartTotalsBlock" style="display:none">
            <div class="cart-total-row"><span>Subtotal</span><span id="cartSubtotal">₹0</span></div>
            <div class="cart-total-row"><span>GST (<?= TAX_PERCENT ?>%)</span><span id="cartTax">₹0</span></div>
            <div class="cart-total-row grand"><span>TOTAL</span><span id="cartGrand">₹0</span></div>
        </div>
        <div class="cart-form">
            <input type="text" class="form-control" id="custName" placeholder="Your Name (optional)">
            <input type="text" class="form-control" id="custMobile" placeholder="Mobile Number (optional)">
        </div>
        <button class="btn-order" id="placeOrderBtn" onclick="placeOrder()" disabled>
            <i class="fa fa-paper-plane"></i> Place Order
        </button>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script src="<?= BASE_URL ?>/assets/js/toast.js"></script>
<script>
const TABLE_ID = <?= $tableId ?>;
const TAX_RATE = <?= TAX_PERCENT ?>;
const BASE_URL = '<?= BASE_URL ?>';
let cart = JSON.parse(sessionStorage.getItem('cart_' + TABLE_ID) || '{}');

// ---- CART FUNCTIONS ----
function addToCart(id, name, price, isVeg) {
    if (cart[id]) {
        cart[id].qty++;
    } else {
        cart[id] = { id, name, price, qty: 1, isVeg };
    }
    saveCart();
    renderCartControls();
    renderCart();
    showToast(name + ' added to cart!', 'success');
}

function removeFromCart(id) {
    if (!cart[id]) return;
    cart[id].qty--;
    if (cart[id].qty <= 0) delete cart[id];
    saveCart();
    renderCartControls();
    renderCart();
}

function saveCart() {
    sessionStorage.setItem('cart_' + TABLE_ID, JSON.stringify(cart));
}

function renderCartControls() {
    // Reset all
    document.querySelectorAll('[id^="cart-ctrl-"]').forEach(el => {
        const id = el.id.replace('cart-ctrl-', '');
        if (cart[id]) {
            el.innerHTML = `
                <div class="qty-controls">
                    <button class="qty-btn" onclick="removeFromCart('${id}')">−</button>
                    <span class="qty-display">${cart[id].qty}</span>
                    <button class="qty-btn" onclick="addToCart('${id}', ${JSON.stringify(cart[id].name)}, ${cart[id].price}, ${cart[id].isVeg})">+</button>
                </div>`;
        } else {
            // Find the item from page
            const card = document.getElementById('menu-item-' + id);
            if (card) {
                const title = card.querySelector('.card-title')?.textContent;
                const priceEl = card.querySelector('.price')?.textContent.replace('₹','').replace(',','');
                const isVeg = card.querySelector('.veg-indicator')?.classList.contains('veg') ? 1 : 0;
                el.innerHTML = `<button class="add-btn" onclick="addToCart('${id}', ${JSON.stringify(title)}, ${priceEl}, ${isVeg})"><i class="fa fa-plus"></i> Add</button>`;
            }
        }
    });
}

function renderCart() {
    const items = Object.values(cart);
    const container = document.getElementById('cartItemsContainer');
    const totalsBlock = document.getElementById('cartTotalsBlock');
    const orderBtn = document.getElementById('placeOrderBtn');
    const cartFloat = document.getElementById('cartFloat');

    if (items.length === 0) {
        container.innerHTML = '<div class="empty-cart"><div class="icon">🛒</div><p>Your cart is empty.<br>Add items from the menu!</p></div>';
        totalsBlock.style.display = 'none';
        orderBtn.disabled = true;
        cartFloat.style.display = 'none';
        return;
    }

    let subtotal = 0;
    let html = '';
    let totalQty = 0;
    items.forEach(item => {
        const lineTotal = item.price * item.qty;
        subtotal += lineTotal;
        totalQty += item.qty;
        const emoji = item.isVeg ? '🥗' : '🍗';
        html += `
        <div class="cart-item">
            <div class="item-emoji">${emoji}</div>
            <div class="item-info">
                <div class="item-name">${item.name}</div>
                <div class="item-price">₹${item.price.toFixed(2)} each</div>
            </div>
            <div class="item-controls">
                <button class="qty-btn" onclick="removeFromCart('${item.id}')">−</button>
                <span class="qty-display">${item.qty}</span>
                <button class="qty-btn" onclick="addToCart('${item.id}', ${JSON.stringify(item.name)}, ${item.price}, ${item.isVeg})">+</button>
            </div>
        </div>`;
    });

    const tax = subtotal * TAX_RATE / 100;
    const grand = subtotal + tax;

    container.innerHTML = html;
    totalsBlock.style.display = 'block';
    document.getElementById('cartSubtotal').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('cartTax').textContent = '₹' + tax.toFixed(2);
    document.getElementById('cartGrand').textContent = '₹' + grand.toFixed(2);
    orderBtn.disabled = false;

    // Update float button
    cartFloat.style.display = 'flex';
    document.getElementById('cartCountBadge').textContent = totalQty;
    document.getElementById('cartTotalBadge').textContent = '₹' + grand.toFixed(2);
}

function openCart() {
    document.getElementById('cartPanel').classList.add('open');
    document.getElementById('cartOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCart() {
    document.getElementById('cartPanel').classList.remove('open');
    document.getElementById('cartOverlay').classList.remove('active');
    document.body.style.overflow = '';
}

// ---- CATEGORY FILTER ----
function filterCat(catId, btn) {
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.menu-section').forEach(sec => {
        if (catId === 'all' || sec.dataset.cat == catId) {
            sec.style.display = 'block';
        } else {
            sec.style.display = 'none';
        }
    });
}

// ---- PLACE ORDER ----
function placeOrder() {
    const items = Object.values(cart);
    if (items.length === 0) { showToast('Cart is empty!', 'error'); return; }

    const btn = document.getElementById('placeOrderBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Placing order...';

    const payload = new FormData();
    payload.append('table_id', TABLE_ID);
    payload.append('items', JSON.stringify(items.map(i => ({ id: i.id, qty: i.qty }))));
    payload.append('customer_name', document.getElementById('custName').value);
    payload.append('customer_mobile', document.getElementById('custMobile').value);

    fetch(BASE_URL + '/api/place_order.php', { method: 'POST', body: payload })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast('🎉 Order placed! Our team will serve you shortly.', 'success', 4000);
                // Clear cart
                cart = {};
                saveCart();
                renderCartControls();
                renderCart();
                closeCart();
            } else {
                showToast(d.message || 'Failed to place order. Please try again.', 'error');
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-paper-plane"></i> Place Order';
        })
        .catch(() => {
            showToast('Network error. Please check your connection.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-paper-plane"></i> Place Order';
        });
}

// Init
renderCartControls();
renderCart();
</script>
</body>
</html>
