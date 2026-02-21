<?php
require_once __DIR__ . '/../config/config.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonError('Invalid method', 405); }

$itemId = (int)($_POST['item_id'] ?? 0);
if (!$itemId) { jsonError('Item ID required'); }

$item = db()->fetchOne("SELECT * FROM menu_items WHERE id=? AND is_deleted=0", [$itemId]);
if (!$item) { jsonError('Item not found'); }

// Soft delete
db()->execute("UPDATE menu_items SET is_deleted=1, is_available=0 WHERE id=?", [$itemId]);

jsonSuccess([], 'Menu item deleted');
