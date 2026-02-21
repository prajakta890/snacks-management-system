// ============================================================
// Admin JS — SNACS SHOP
// ============================================================

const BASE_URL = (typeof BASE_URL !== 'undefined') ? BASE_URL : '';

// Global bill modal save function (called from get_table_bill.php HTML)
function saveBillFromModal(billNumber) {
    const custName = document.getElementById('bCustName')?.value || '';
    const custMobile = document.getElementById('bCustMobile')?.value || '';
    const payStatus = document.getElementById('bPayStatus')?.value || 'pending';
    const payMethod = document.getElementById('bPayMethod')?.value || 'cash';

    let url = (typeof BASE_URL !== 'undefined') ? BASE_URL : '';
    fetch(url + '/api/save_bill.php', {
        method: 'POST',
        body: new URLSearchParams({
            bill_number: billNumber,
            customer_name: custName,
            customer_mobile: custMobile,
            payment_status: payStatus,
            payment_method: payMethod,
            action: 'update'
        })
    })
        .then(r => r.json())
        .then(d => {
            showToast(d.success ? 'Bill saved!' : (d.message || 'Error'), d.success ? 'success' : 'error');
            if (d.success && payStatus === 'paid') {
                setTimeout(() => location.reload(), 800);
            }
        })
        .catch(() => showToast('Network error', 'error'));
}

// Update individual order item status (called from get_table_bill.php HTML)
function updateItemStatus(itemId, status) {
    let url = (typeof BASE_URL !== 'undefined') ? BASE_URL : '';
    fetch(url + '/api/update_order_status.php', {
        method: 'POST',
        body: new URLSearchParams({ order_item_id: itemId, item_status: status })
    })
        .then(r => r.json())
        .then(d => showToast(d.success ? 'Status updated' : 'Error', d.success ? 'success' : 'error'))
        .catch(() => showToast('Network error', 'error'));
}

// Close modal on Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => {
            m.classList.remove('active');
        });
    }
});

// Close modal on overlay click
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
});
