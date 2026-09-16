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

    // Attach strict client-side validation to file inputs
    document.addEventListener('change', function(e) {
        if (e.target && e.target.matches('input[type="file"][accept*="image"]')) {
            validateImageInput(e.target);
        }
    });

    // Auto-wire phone input helpers
    initPhoneInputHelpers();
});

/**
 * Auto-format Philippine Phone Numbers (+639XXXXXXXXX)
 */
function formatPhPhone(value) {
    if (!value) return '+63';
    
    let digits = value.replace(/[^\d]/g, '');
    
    if (digits.startsWith('09')) {
        digits = '63' + digits.substring(1);
    } else if (digits.startsWith('9')) {
        digits = '63' + digits;
    } else if (!digits.startsWith('63')) {
        digits = '63' + digits;
    }

    digits = digits.substring(0, 12);
    return '+' + digits;
}

function initPhoneInputHelpers() {
    const phoneInputs = document.querySelectorAll('input[name="phone"], input[type="tel"], .phone-input');
    
    phoneInputs.forEach(input => {
        if (!input.placeholder || input.placeholder === '09123456789') {
            input.placeholder = '+639171234567';
        }

        input.addEventListener('focus', function() {
            if (!this.value || this.value.trim() === '') {
                this.value = '+63';
            }
        });

        input.addEventListener('input', function() {
            if (this.value.length > 0) {
                this.value = formatPhPhone(this.value);
            }
        });

        input.addEventListener('blur', function() {
            if (this.value.trim() === '+63' || this.value.trim() === '+') {
                this.value = '';
            }
        });
    });
}

/**
 * Validate Image Uploads (PNG, JPEG, JPG, WEBP <= 5MB)
 */
function validateImageInput(input) {
    if (!input || !input.files || input.files.length === 0) return true;
    const file = input.files[0];
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    const maxSize = 5 * 1024 * 1024; // 5MB

    if (!allowedTypes.includes(file.type.toLowerCase())) {
        alert('Invalid image format! Only JPEG, JPG, PNG, and WEBP images are allowed.');
        input.value = '';
        return false;
    }

    if (file.size > maxSize) {
        alert('File is too large! Maximum allowed photo size is 5MB.');
        input.value = '';
        return false;
    }

    return true;
}
