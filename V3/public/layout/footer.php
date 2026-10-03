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

<!-- Global Privacy Statement & Protection Consent Modal -->
<div class="modal-overlay" id="privacyConsentModal" role="dialog" aria-modal="true" aria-labelledby="privacyConsentTitle" style="z-index: 9999 !important; backdrop-filter: blur(8px);">
    <div class="modal-content" style="max-width: 520px !important; margin: auto !important; border-radius: 16px !important; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35) !important;">
        <div class="modal-header" style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h4 id="privacyConsentTitle" style="font-weight: 800; color: #1e293b; margin: 0; font-size: 1.25rem; display: flex; align-items: center; gap: 8px;">
                <i class='bx bxs-shield-alt-2' style="color:#1565C0;"></i> Church Privacy Notice
            </h4>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="closePrivacyModal()" style="border:none; font-size: 1.25rem; cursor: pointer;">&times;</button>
        </div>
        <div class="modal-body" style="font-size: 0.95rem; line-height: 1.65; color: #334155; padding: 24px;">
            <p style="margin-bottom: 16px;">
                The personal information collected will be used for church membership records, communication, attendance, events, and other legitimate church-related purposes. Access to member information is limited to authorized personnel such as the Admin and Secretary. Personal information will be kept secure and will not be disclosed to unauthorized persons.
            </p>
            
            <div style="background: rgba(21, 101, 192, 0.06); border-left: 4px solid #1565C0; padding: 14px 18px; border-radius: 8px; margin-top: 16px;">
                <p style="margin: 0; font-weight: 600; color: #1e293b; font-size: 0.93rem;">
                    &bull; <strong>Access Control:</strong> Member personal information can only be accessed by authorized users, specifically the <strong>Admin</strong> and <strong>Secretary</strong>.
                </p>
            </div>
        </div>
        <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; background: #f8fafc;">
            <button type="button" class="btn btn-outline-secondary" onclick="closePrivacyModal()" style="font-weight: 600; border-radius: 8px;">Close</button>
            <button type="button" class="btn btn-primary" id="privacyAcceptBtn" onclick="closePrivacyModal()" style="font-weight: 600; background: #1565C0; border-color: #1565C0; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                <i class='bx bx-check-shield'></i> I Agree &amp; Consent
            </button>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script src="assets/js/enhanced-select.js"></script>
<script src="assets/js/app.js?v=1.0"></script>
</body>
</html>
