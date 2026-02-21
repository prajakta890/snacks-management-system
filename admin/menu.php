<?php
require_once __DIR__ . '/../config/config.php';
requireAdminLogin();

$pageTitle = 'Menu Management';
$pageSubtitle = 'Add, edit and manage food items';
$activePage = 'menu';
$topbarActions = '<a href="javascript:void(0)" onclick="openAddItemModal()" class="topbar-btn btn-primary"><i class="fa fa-plus"></i> Add Item</a>';

$categories = db()->fetchAll("SELECT * FROM menu_categories WHERE is_active=1 ORDER BY sort_order");
$selectedCat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

$sql = "SELECT m.*, c.name as category_name FROM menu_items m JOIN menu_categories c ON m.category_id=c.id WHERE m.is_deleted=0";
$params = [];
if ($selectedCat) { $sql .= " AND m.category_id=?"; $params[] = $selectedCat; }
$sql .= " ORDER BY c.sort_order, m.sort_order, m.name";
$items = db()->fetchAll($sql, $params);

include __DIR__ . '/partials/header.php';
?>

<!-- Category Filter Tabs -->
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;align-items:center">
    <a href="menu.php" class="topbar-btn <?= $selectedCat?'btn-secondary':'btn-primary' ?> btn-sm">All</a>
    <?php foreach($categories as $c): ?>
    <a href="menu.php?cat=<?= $c['id'] ?>" class="topbar-btn <?= $selectedCat==$c['id']?'btn-primary':'btn-secondary' ?> btn-sm"><?= $c['icon'] ?> <?= $c['name'] ?></a>
    <?php endforeach; ?>
    <span style="margin-left:auto;color:var(--text-muted);font-size:12px"><?= count($items) ?> items</span>
</div>

<!-- Menu Grid -->
<?php if (empty($items)): ?>
<div class="empty-state"><div class="empty-icon">🍽️</div><h3>No items found</h3><p>Add your first menu item using the button above.</p></div>
<?php else: ?>
<div class="menu-grid">
<?php foreach ($items as $item): ?>
<div class="menu-item-card <?= !$item['is_available']?'unavailable':'' ?>" id="item-<?= $item['id'] ?>">
    <div class="item-img">
        <?php if ($item['image'] && file_exists(UPLOAD_PATH . $item['image'])): ?>
        <img src="<?= UPLOAD_URL . $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>">
        <?php else: ?>
        <?= $item['is_veg'] ? '🥗' : '🍗' ?>
        <?php endif; ?>
    </div>
    <?php if (!$item['is_available']): ?>
    <div style="background:rgba(225,112,85,0.9);color:#fff;font-size:10px;font-weight:700;text-align:center;padding:3px;text-transform:uppercase;letter-spacing:1px">Unavailable</div>
    <?php endif; ?>
    <div class="item-body">
        <div style="display:flex;align-items:flex-start;gap:6px;margin-bottom:4px">
            <span class="veg-dot <?= $item['is_veg']?'veg':'nonveg' ?>"></span>
            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
        </div>
        <div class="item-cat"><?= $item['category_name'] ?></div>
        <?php if ($item['description']): ?>
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:8px;line-height:1.4"><?= htmlspecialchars(substr($item['description'],0,60)) ?>...</div>
        <?php endif; ?>
        <div class="item-footer">
            <div class="item-price"><?= formatCurrency($item['price']) ?></div>
            <div class="item-actions">
                <button class="topbar-btn btn-secondary btn-sm" onclick='editItem(<?= json_encode($item) ?>)' title="Edit"><i class="fa fa-pen"></i></button>
                <button class="topbar-btn btn-secondary btn-sm" onclick="toggleAvailability(<?= $item['id'] ?>, <?= $item['is_available'] ?>)" title="Toggle availability"><i class="fa fa-<?= $item['is_available']?'eye':'eye-slash' ?>"></i></button>
                <button class="topbar-btn btn-danger btn-sm" onclick="deleteItem(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>')" title="Delete"><i class="fa fa-trash"></i></button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add/Edit Item Modal -->
<div class="modal-overlay" id="itemModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="itemModalTitle">Add Menu Item</h3>
            <button class="btn-close" onclick="closeItemModal()"><i class="fa fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form id="itemForm" enctype="multipart/form-data">
                <input type="hidden" name="item_id" id="itemId">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Item Name *</label>
                        <input type="text" name="name" id="itemName" class="form-control" required placeholder="e.g. Paneer Tikka">
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" id="itemCategory" class="form-control" required>
                            <?php foreach($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= $c['icon'] ?> <?= $c['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="itemDesc" class="form-control" placeholder="Short description of the item..." rows="2"></textarea>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Price (₹) *</label>
                        <input type="number" name="price" id="itemPrice" class="form-control" min="0" step="0.50" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="is_veg" id="itemVeg" class="form-control">
                            <option value="1">🟢 Vegetarian</option>
                            <option value="0">🔴 Non-Vegetarian</option>
                        </select>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Item Image</label>
                        <input type="file" name="image" id="itemImage" class="form-control" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <div class="form-group">
                        <label>Preview</label>
                        <div class="img-preview" id="imgPreview">🍽️</div>
                    </div>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_available" id="itemAvail" value="1" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <span>Available for ordering</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="topbar-btn btn-secondary" onclick="closeItemModal()">Cancel</button>
            <button class="topbar-btn btn-primary" onclick="submitItem()" id="itemSubmitBtn"><i class="fa fa-check"></i> Save Item</button>
        </div>
    </div>
</div>

<script>
let editMode = false;

function openAddItemModal() {
    editMode = false;
    document.getElementById('itemModalTitle').textContent = 'Add Menu Item';
    document.getElementById('itemForm').reset();
    document.getElementById('itemId').value = '';
    document.getElementById('imgPreview').innerHTML = '🍽️';
    document.getElementById('itemModal').classList.add('active');
}

function closeItemModal() { document.getElementById('itemModal').classList.remove('active'); }

function editItem(item) {
    editMode = true;
    document.getElementById('itemModalTitle').textContent = 'Edit: ' + item.name;
    document.getElementById('itemId').value = item.id;
    document.getElementById('itemName').value = item.name;
    document.getElementById('itemCategory').value = item.category_id;
    document.getElementById('itemPrice').value = item.price;
    document.getElementById('itemDesc').value = item.description || '';
    document.getElementById('itemVeg').value = item.is_veg;
    document.getElementById('itemAvail').checked = item.is_available == 1;
    if (item.image) {
        document.getElementById('imgPreview').innerHTML = '<img src="<?= UPLOAD_URL ?>' + item.image + '" style="width:100%;height:100%;object-fit:cover;border-radius:8px">';
    } else {
        document.getElementById('imgPreview').innerHTML = item.is_veg ? '🥗' : '🍗';
    }
    document.getElementById('itemModal').classList.add('active');
}

function previewImage(input) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => { document.getElementById('imgPreview').innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:8px">'; };
        reader.readAsDataURL(file);
    }
}

function submitItem() {
    const form = document.getElementById('itemForm');
    const data = new FormData(form);
    const url = editMode ? BASE_URL+'/api/edit_menu_item.php' : BASE_URL+'/api/add_menu_item.php';
    const btn = document.getElementById('itemSubmitBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
    fetch(url, { method: 'POST', body: data })
        .then(r=>r.json())
        .then(d=>{
            btn.disabled = false; btn.innerHTML = '<i class="fa fa-check"></i> Save Item';
            if (d.success) { showToast(d.message, 'success'); closeItemModal(); setTimeout(()=>location.reload(), 800); }
            else showToast(d.message||'Error saving item', 'error');
        }).catch(()=>{ btn.disabled=false; btn.innerHTML='<i class="fa fa-check"></i> Save Item'; showToast('Network error','error'); });
}

function toggleAvailability(id, current) {
    fetch(BASE_URL + '/api/edit_menu_item.php', {
        method: 'POST',
        body: new URLSearchParams({ item_id: id, toggle_availability: 1, is_available: current == 1 ? 0 : 1 })
    }).then(r=>r.json()).then(d=>{
        if (d.success) { showToast(d.message, 'success'); setTimeout(()=>location.reload(), 600); }
        else showToast(d.message, 'error');
    });
}

function deleteItem(id, name) {
    if (!confirm('Delete "' + name + '"? This action cannot be undone.')) return;
    fetch(BASE_URL + '/api/delete_menu_item.php', {
        method: 'POST',
        body: new URLSearchParams({ item_id: id })
    }).then(r=>r.json()).then(d=>{
        if (d.success) { showToast('Item deleted', 'success'); document.getElementById('item-'+id).remove(); }
        else showToast(d.message, 'error');
    });
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
