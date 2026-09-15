    </div> <!-- .content-area -->
</div> <!-- .main-content -->
</div> <!-- .sidebar-layout -->

<!-- Global Custom Confirm Modal -->
<div class="modal-overlay" id="globalConfirmModal" style="z-index: 9999 !important; backdrop-filter: blur(8px);">
    <div class="modal-content" style="max-width: 440px; text-align: center; padding: 2.25rem; border-radius: 16px; border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35);">
        <div style="width: 64px; height: 64px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.25rem auto;">
            <i class='bx bx-error-circle' id="globalConfirmIcon"></i>
        </div>
        <h4 id="globalConfirmTitle" style="font-weight: 800; font-size: 1.35rem; color: #1e293b; margin-bottom: 0.5rem;">Are you sure?</h4>
        <p id="globalConfirmMessage" style="color: #64748b; font-size: 0.95rem; margin-bottom: 1.25rem; line-height: 1.5;">This action cannot be undone.</p>
        <div id="globalConfirmDetails" style="display: none;"></div>
        <div class="d-flex justify-content-center gap-3 mt-2">
            <button type="button" class="btn btn-outline-secondary px-4 py-2" id="globalConfirmCancelBtn" style="font-weight: 600; border-radius: 8px;" onclick="closeGlobalConfirm()">Cancel</button>
            <button type="button" class="btn btn-danger px-4 py-2 shadow-sm" id="globalConfirmBtn" style="font-weight: 600; border-radius: 8px;">Confirm</button>
        </div>
    </div>
</div>

<script>
    // Update real-time clock in header
    function updateClock() {
        const clockEl = document.getElementById('currentTime');
        if (!clockEl) return;
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        clockEl.textContent = now.toLocaleDateString('en-US', options);
    }
    
    updateClock();
    setInterval(updateClock, 1000);

    // Custom Confirm Logic
    let currentConfirmCallback = null;

    function confirmAction(message, callback, title = 'Are you sure?', details = '', confirmText = 'Confirm', cancelText = 'Cancel', iconClass = 'bx bx-error-circle') {
        document.getElementById('globalConfirmTitle').textContent = title;
        document.getElementById('globalConfirmMessage').textContent = message;
        
        const iconEl = document.getElementById('globalConfirmIcon');
        if (iconEl) iconEl.className = iconClass;

        const detailsEl = document.getElementById('globalConfirmDetails');
        if (detailsEl) {
            if (details) {
                detailsEl.innerHTML = `<div style="background: rgba(239, 68, 68, 0.07); border-left: 4px solid #ef4444; padding: 12px 14px; border-radius: 6px; text-align: left; margin-bottom: 1.25rem; font-size: 0.9rem; color: #1e293b; font-weight: 600;">${details}</div>`;
                detailsEl.style.display = 'block';
            } else {
                detailsEl.style.display = 'none';
                detailsEl.innerHTML = '';
            }
        }

        const confirmBtn = document.getElementById('globalConfirmBtn');
        if (confirmBtn) confirmBtn.textContent = confirmText;

        const cancelBtn = document.getElementById('globalConfirmCancelBtn');
        if (cancelBtn) cancelBtn.textContent = cancelText;

        currentConfirmCallback = callback;
        const modal = document.getElementById('globalConfirmModal');
        if (modal) {
            modal.style.zIndex = '9999';
            modal.classList.add('active');
        }
    }

    function closeGlobalConfirm() {
        const modal = document.getElementById('globalConfirmModal');
        if (modal) modal.classList.remove('active');
        currentConfirmCallback = null;
    }

    document.getElementById('globalConfirmBtn').addEventListener('click', function() {
        if (typeof currentConfirmCallback === 'function') {
            currentConfirmCallback();
        }
        closeGlobalConfirm();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGlobalConfirm();
        }
    });
</script>

<script src="assets/js/enhanced-select.js"></script>
</body>
</html>
