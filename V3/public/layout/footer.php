    </div> <!-- .content-area -->
</div> <!-- .main-content -->
</div> <!-- .sidebar-layout -->

<!-- Global Custom Confirm Modal -->
<div class="modal-overlay" id="globalConfirmModal" style="z-index: 1050;">
    <div class="modal-content" style="max-width: 400px; text-align: center; padding: 2rem;">
        <i class='bx bx-error-circle text-danger' style="font-size: 3rem; margin-bottom: 1rem;"></i>
        <h4 id="globalConfirmTitle" class="mb-3">Are you sure?</h4>
        <p id="globalConfirmMessage" class="text-muted mb-4">This action cannot be undone.</p>
        <div class="d-flex justify-content-center gap-3">
            <button type="button" class="btn btn-secondary px-4" onclick="closeGlobalConfirm()">Cancel</button>
            <button type="button" class="btn btn-danger px-4" id="globalConfirmBtn">Confirm</button>
        </div>
    </div>
</div>

<script>
    // Update real-time clock in header
    function updateClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        document.getElementById('currentTime').textContent = now.toLocaleDateString('en-US', options);
    }
    
    updateClock();
    setInterval(updateClock, 1000);

    // Custom Confirm Logic
    let currentConfirmCallback = null;

    function confirmAction(message, callback, title = 'Are you sure?') {
        document.getElementById('globalConfirmMessage').textContent = message;
        document.getElementById('globalConfirmTitle').textContent = title;
        currentConfirmCallback = callback;
        document.getElementById('globalConfirmModal').classList.add('active');
    }

    function closeGlobalConfirm() {
        document.getElementById('globalConfirmModal').classList.remove('active');
        currentConfirmCallback = null;
    }

    document.getElementById('globalConfirmBtn').addEventListener('click', function() {
        if (typeof currentConfirmCallback === 'function') {
            currentConfirmCallback();
        }
        closeGlobalConfirm();
    });
</script>

<script src="assets/js/enhanced-select.js"></script>
</body>
</html>
