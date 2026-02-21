    </div><!-- end page-body -->
</div><!-- end main-content -->
</div><!-- end admin-layout -->

<div class="toast-container" id="toastContainer"></div>

<script src="<?= BASE_URL ?>/assets/js/toast.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
// Update clock
function updateClock() {
    const now = new Date();
    document.getElementById('currentTime').textContent =
        now.toLocaleDateString('en-IN') + ' ' + now.toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit'});
}
updateClock();
setInterval(updateClock, 1000);
// Responsive sidebar
if (window.innerWidth <= 768) {
    document.getElementById('sidebarToggle').style.display = 'inline-flex';
}
</script>
</body>
</html>
