<?php
require_once __DIR__ . '/../config/config.php';
requireAdminLogin();

$tableId = (int)($_GET['table_id'] ?? 0);
if (!$tableId) {
    header('Location: ' . BASE_URL . '/admin/tables.php');
    exit;
}

$table = db()->fetchOne("SELECT * FROM tables WHERE id=? AND is_active=1", [$tableId]);
if (!$table) {
    header('Location: ' . BASE_URL . '/admin/tables.php');
    exit;
}

$pageTitle = 'Point of Sale (Table ' . htmlspecialchars($table['table_number']) . ')';
$pageSubtitle = 'Add items to order for Table ' . htmlspecialchars($table['table_number']);
$activePage = 'tables'; // Highlight tables in sidebar

$categories = db()->fetchAll("SELECT * FROM menu_categories WHERE is_active=1 ORDER BY sort_order");
$menuItems = db()->fetchAll("SELECT m.*, c.name as cat_name, c.icon as cat_icon FROM menu_items m JOIN menu_categories c ON m.category_id=c.id WHERE m.is_deleted=0 AND m.is_available=1 ORDER BY c.sort_order, m.sort_order, m.name");

// Group by category
$grouped = [];
foreach ($menuItems as $item) {
    $grouped[$item['category_id']]['cat'] = ['name'=>$item['cat_name'],'icon'=>$item['cat_icon']];
    $grouped[$item['category_id']]['items'][] = $item;
}

include __DIR__ . '/partials/header.php';
?>

<div class="row">
    <!-- Left: Menu Items -->
    <div class="col" style="flex:2">
        <div class="card">
            <div class="card-header" style="display:flex; gap: 8px; overflow-x: auto; padding: 12px 20px;">
                <button class="topbar-btn btn-primary cat-btn" onclick="filterCat('all', this)" style="padding: 6px 12px;">🍽️ All</button>
                <?php foreach ($categories as $cat): ?>
                <button class="topbar-btn btn-secondary cat-btn" onclick="filterCat(<?= $cat['id'] ?>, this)" style="padding: 6px 12px; white-space: nowrap;"><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?></button>
                <?php endforeach; ?>
            </div>
            
            <div class="card-body" id="menuMain" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                <?php foreach ($grouped as $catId => $group): ?>
                <div class="menu-section" data-cat="<?= $catId ?>" style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 16px; font-size: 16px; font-weight: 700; color: var(--text-secondary); border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                        <?= $group['cat']['icon'] ?> <?= htmlspecialchars($group['cat']['name']) ?>
                    </h3>
                    <div class="menu-grid">
                        <?php foreach ($group['items'] as $item): ?>
                        <div class="menu-item-card" id="menu-item-<?= $item['id'] ?>">
                            <div class="item-img" style="height: 100px;">
                                <?php if ($item['image'] && file_exists(UPLOAD_PATH . $item['image'])): ?>
                                <img src="<?= UPLOAD_URL . $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                <?php else: ?>
                                <?= $item['is_veg'] ? '🥗' : '🍗' ?>
                                <?php endif; ?>
                            </div>
                            <div class="item-body">
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
                                    <span class="veg-dot <?= $item['is_veg']?'veg':'nonveg' ?>"></span>
                                    <div class="item-name" style="margin-bottom: 0;"><?= htmlspecialchars($item['name']) ?></div>
                                </div>
                                <div class="item-footer" style="margin-top: 12px;">
                                    <div class="item-price">₹<?= number_format($item['price'],2) ?></div>
                                    <div id="cart-ctrl-<?= $item['id'] ?>">
                                        <button class="topbar-btn btn-primary btn-sm" onclick="addToCart(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>', <?= $item['price'] ?>, <?= $item['is_veg'] ?>)">
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
            </div>
        </div>
    </div>

    <!-- Right: Cart -->
    <div class="col" style="flex:1;min-width:300px">
        <div class="card" style="position: sticky; top: 90px;">
            <div class="card-header">
                <h2>🛒 Current Order</h2>
            </div>
            <div class="card-body">
                <div id="cartItemsContainer" style="max-height: 40vh; overflow-y: auto; margin-bottom: 16px; padding-right: 8px;">
                    <div class="empty-state" style="padding: 20px;">
                        <div class="empty-icon" style="font-size: 32px; margin-bottom: 10px;">🛒</div>
                        <p>Order is empty</p>
                    </div>
                </div>
                
                <div id="cartTotalsBlock" style="display:none; border-top: 1px solid var(--border); padding-top: 16px;">
                    <div style="display:flex; justify-content: space-between; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
                        <span>Subtotal</span><span id="cartSubtotal">₹0</span>
                    </div>
                    <div style="display:flex; justify-content: space-between; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">
                        <span>GST (<?= TAX_PERCENT ?>%)</span><span id="cartTax">₹0</span>
                    </div>
                    <div style="display:flex; justify-content: space-between; font-size: 16px; font-weight: 800; border-top: 1px dashed var(--border); padding-top: 8px; margin-bottom: 16px;">
                        <span>TOTAL</span><span id="cartGrand">₹0</span>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" class="form-control" id="custName" placeholder="Customer Name (optional)" style="margin-bottom: 8px; padding: 8px 12px; font-size: 13px;">
                        <input type="text" class="form-control" id="custMobile" placeholder="Mobile Number (optional)" style="padding: 8px 12px; font-size: 13px;">
                    </div>
                    
                    <button class="topbar-btn btn-primary" id="placeOrderBtn" onclick="placeOrder()" style="width: 100%; justify-content: center; padding: 12px;">
                        <i class="fa fa-paper-plane"></i> Place Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom mini styling for POS */
.qty-controls {
    display: inline-flex;
    align-items: center;
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--border);
    border-radius: 6px;
    overflow: hidden;
}
.qty-btn {
    background: transparent;
    border: none;
    color: var(--text-primary);
    width: 26px;
    height: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}
.qty-btn:hover { background: rgba(255,255,255,0.1); }
.qty-display {
    font-size: 12px;
    font-weight: 700;
    width: 24px;
    text-align: center;
}
.cart-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
</style>

<script>
const TABLE_ID = <?= $tableId ?>;
const TAX_RATE = <?= TAX_PERCENT ?>;
let cart = {}; // Admin POS shouldn't strictly require session storage across refreshes, but we can

function addToCart(id, name, price, isVeg) {
    if (cart[id]) cart[id].qty++;
    else cart[id] = { id, name, price, qty: 1, isVeg };
    renderCartControls();
    renderCart();
}

function removeFromCart(id) {
    if (!cart[id]) return;
    cart[id].qty--;
    if (cart[id].qty <= 0) delete cart[id];
    renderCartControls();
    renderCart();
}

function renderCartControls() {
    document.querySelectorAll('[id^="cart-ctrl-"]').forEach(el => {
        const id = el.id.replace('cart-ctrl-', '');
        if (cart[id]) {
            el.innerHTML = `
                <div class="qty-controls">
                    <button class="qty-btn" onclick="removeFromCart('${id}')">−</button>
                    <span class="qty-display">${cart[id].qty}</span>
                    <button class="qty-btn" onclick="addToCart('${id}', null, null, null)">+</button>
                </div>`;
        } else {
            const card = document.getElementById('menu-item-' + id);
            if (card) {
                const title = card.querySelector('.item-name')?.textContent;
                const priceEl = card.querySelector('.item-price')?.textContent.replace('₹','').replace(',','');
                const isVeg = card.querySelector('.veg-dot')?.classList.contains('veg') ? 1 : 0;
                el.innerHTML = `<button class="topbar-btn btn-primary btn-sm" onclick="addToCart('${id}', ${JSON.stringify(title)}, ${priceEl}, ${isVeg})"><i class="fa fa-plus"></i> Add</button>`;
            }
        }
    });
}

function renderCart() {
    const items = Object.values(cart);
    const container = document.getElementById('cartItemsContainer');
    const totalsBlock = document.getElementById('cartTotalsBlock');
    const orderBtn = document.getElementById('placeOrderBtn');

    if (items.length === 0) {
        container.innerHTML = '<div class="empty-state" style="padding: 20px;"><div class="empty-icon" style="font-size: 32px; margin-bottom: 10px;">🛒</div><p>Order is empty</p></div>';
        totalsBlock.style.display = 'none';
        return;
    }

    let subtotal = 0;
    let html = '';
    items.forEach(item => {
        const lineTotal = item.price * item.qty;
        subtotal += lineTotal;
        const emoji = item.isVeg ? '🥗' : '🍗';
        html += `
        <div class="cart-item-row">
            <div style="flex:1">
                <div style="font-size:13px; font-weight:600; margin-bottom:4px">${emoji} ${item.name}</div>
                <div style="font-size:11px; color:var(--text-muted)">₹${item.price.toFixed(2)}</div>
            </div>
            <div style="display:flex; align-items:center; gap: 10px;">
                <div class="qty-controls">
                    <button class="qty-btn" onclick="removeFromCart('${item.id}')">−</button>
                    <span class="qty-display">${item.qty}</span>
                    <button class="qty-btn" onclick="addToCart('${item.id}', null, null, null)">+</button>
                </div>
                <div style="font-weight:700; font-size:13px; min-width: 50px; text-align:right">₹${lineTotal.toFixed(2)}</div>
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
}

function filterCat(catId, btn) {
    document.querySelectorAll('.cat-btn').forEach(b => {
        b.classList.remove('btn-primary');
        b.classList.add('btn-secondary');
    });
    btn.classList.remove('btn-secondary');
    btn.classList.add('btn-primary');
    
    document.querySelectorAll('.menu-section').forEach(sec => {
        if (catId === 'all' || sec.dataset.cat == catId) {
            sec.style.display = 'block';
        } else {
            sec.style.display = 'none';
        }
    });
}

function placeOrder() {
    const items = Object.values(cart);
    if (items.length === 0) return;

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
                showToast('Order placed successfully!', 'success');
                setTimeout(() => window.location.href = BASE_URL + '/admin/tables.php', 1000);
            } else {
                showToast(d.message || 'Failed to place order.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-paper-plane"></i> Place Order';
            }
        })
        .catch(() => {
            showToast('Network error.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-paper-plane"></i> Place Order';
        });
}

// Init
renderCartControls();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
