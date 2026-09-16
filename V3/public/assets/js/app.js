/**
 * God's Family CMS - Application & Privacy Consent JavaScript Utilities
 */

function openPrivacyModal(targetCheckboxId) {
    const modal = document.getElementById('privacyConsentModal');
    if (!modal) return;

    modal.classList.add('active');

    const acceptBtn = document.getElementById('privacyAcceptBtn');
    if (acceptBtn) {
        acceptBtn.onclick = function () {
            if (targetCheckboxId) {
                const chk = document.getElementById(targetCheckboxId);
                if (chk) {
                    chk.checked = true;
                }
            }
            closePrivacyModal();
        };
    }
}

function closePrivacyModal() {
    const modal = document.getElementById('privacyConsentModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Intercept escape key to close privacy modal
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closePrivacyModal();
        }
    });

    // Close on overlay click
    const modal = document.getElementById('privacyConsentModal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closePrivacyModal();
            }
        });
    }
});
