{{-- Smart Syndic Unified Confirmation Dialog Component --}}
<div id="smart-confirm-modal" class="smart-confirm-backdrop" style="display:none;" aria-hidden="true">
    <div class="smart-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="smart-confirm-title">
        {{-- Icon Container --}}
        <div class="smart-confirm-icon-wrap" id="smart-confirm-icon-wrap">
            <div id="smart-confirm-icon-danger" class="smart-confirm-icon-box danger" style="display:none;">
                <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div id="smart-confirm-icon-warning" class="smart-confirm-icon-box warning" style="display:none;">
                <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div id="smart-confirm-icon-info" class="smart-confirm-icon-box info" style="display:none;">
                <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div id="smart-confirm-icon-success" class="smart-confirm-icon-box success" style="display:none;">
                <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
        </div>

        {{-- Dialog Text --}}
        <h3 class="smart-confirm-title" id="smart-confirm-title">تأكيد الإجراء</h3>
        <p class="smart-confirm-message" id="smart-confirm-message">هل أنت متأكد من تنفيذ هذا الإجراء؟</p>

        {{-- Sensitive Password Confirmation Section --}}
        <div class="smart-confirm-password-wrap" id="smart-confirm-password-wrap" style="display:none;">
            <label for="smart-confirm-password-input" class="smart-confirm-password-label">
                <span class="smart-confirm-lock-icon">🔒</span>
                <span>تأكيد الهوية: أدخل كلمة المرور لحسابك للمتابعة</span>
            </label>
            <div class="smart-confirm-input-group">
                <input type="password"
                       id="smart-confirm-password-input"
                       class="smart-confirm-password-input"
                       placeholder="كلمة المرور الحالية للحساب"
                       autocomplete="current-password" />
                <button type="button" class="smart-confirm-toggle-pw" id="smart-confirm-toggle-pw" tabindex="-1" title="إظهار / إخفاء كلمة المرور">
                    <svg id="smart-pw-eye-open" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg id="smart-pw-eye-closed" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
            <div class="smart-confirm-password-hint">
                لحماية البيانات ومنع الحذف غير المصرح به، يتطلب هذا الإجراء إدخال الرقم السري لحسابك.
            </div>
            <div id="smart-confirm-password-error" class="smart-confirm-password-error" style="display:none;"></div>
        </div>

        {{-- Dialog Actions --}}
        <div class="smart-confirm-actions">
            <button type="button" class="btn btn-secondary smart-confirm-cancel-btn" id="smart-confirm-cancel-btn">
                إلغاء الأمر
            </button>
            <button type="button" class="btn smart-confirm-confirm-btn" id="smart-confirm-confirm-btn">
                تأكيد ومتابعة
            </button>
        </div>
    </div>
</div>

<style>
/* ─── Smart Confirm Styles (Seamless Theme Integration) ─────────────────── */
.smart-confirm-backdrop {
    position: fixed;
    inset: 0;
    z-index: 999999;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    animation: smartConfirmFadeIn 0.18s cubic-bezier(0.16, 1, 0.3, 1);
}

.smart-confirm-dialog {
    background: var(--bg-card, #1e293b);
    border: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
    border-radius: var(--radius-lg, 18px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
    width: 100%;
    max-width: 440px;
    padding: 2rem 1.75rem 1.5rem 1.75rem;
    text-align: center;
    animation: smartConfirmScaleIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    color: var(--text-primary, #f8fafc);
}

.smart-confirm-icon-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 1.25rem;
}

.smart-confirm-icon-box {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
}

.smart-confirm-icon-box.danger {
    background: rgba(239, 68, 68, 0.14);
    color: #ef4444;
    border: 2px solid rgba(239, 68, 68, 0.3);
}

.smart-confirm-icon-box.warning {
    background: rgba(245, 158, 11, 0.14);
    color: #f59e0b;
    border: 2px solid rgba(245, 158, 11, 0.3);
}

.smart-confirm-icon-box.info {
    background: rgba(99, 102, 241, 0.14);
    color: #6366f1;
    border: 2px solid rgba(99, 102, 241, 0.3);
}

.smart-confirm-icon-box.success {
    background: rgba(16, 185, 129, 0.14);
    color: #10b981;
    border: 2px solid rgba(16, 185, 129, 0.3);
}

.smart-confirm-title {
    font-size: 1.2rem;
    font-weight: 800;
    margin: 0 0 0.5rem 0;
    color: var(--text-primary, #ffffff);
    letter-spacing: -0.01em;
}

.smart-confirm-message {
    font-size: 0.95rem;
    color: var(--text-secondary, #94a3b8);
    line-height: 1.6;
    margin: 0 0 1.25rem 0;
    word-break: break-word;
}

/* Password confirmation section */
.smart-confirm-password-wrap {
    margin: 0.5rem 0 1.5rem 0;
    text-align: right;
    background: rgba(0, 0, 0, 0.18);
    border: 1px solid var(--border-color, rgba(255, 255, 255, 0.12));
    border-radius: var(--radius-md, 12px);
    padding: 0.9rem 1rem;
}

.smart-confirm-password-label {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text-primary, #f8fafc);
    margin-bottom: 0.5rem;
}

.smart-confirm-lock-icon {
    font-size: 0.95rem;
}

.smart-confirm-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.smart-confirm-password-input {
    width: 100%;
    background: var(--bg-main, #0f172a);
    border: 1.5px solid var(--border-color, rgba(255, 255, 255, 0.2));
    border-radius: 8px;
    padding: 0.65rem 2.5rem 0.65rem 0.85rem;
    color: var(--text-primary, #ffffff);
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.2s ease;
    direction: ltr;
    text-align: right;
}

.smart-confirm-password-input:focus {
    outline: none;
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25);
}

.smart-confirm-password-input.input-error {
    border-color: #ef4444 !important;
    animation: smartShake 0.3s ease-in-out;
}

.smart-confirm-toggle-pw {
    position: absolute;
    left: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-muted, #94a3b8);
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: color 0.15s ease;
}

.smart-confirm-toggle-pw:hover {
    color: var(--text-primary, #ffffff);
}

.smart-confirm-password-hint {
    font-size: 0.75rem;
    color: var(--text-muted, #94a3b8);
    margin-top: 0.4rem;
    line-height: 1.4;
}

.smart-confirm-password-error {
    margin-top: 0.45rem;
    font-size: 0.8rem;
    color: #ef4444;
    font-weight: 700;
    text-align: right;
}

@keyframes smartShake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-6px); }
    40%, 80% { transform: translateX(6px); }
}

.smart-confirm-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    justify-content: stretch;
}

.smart-confirm-actions button {
    flex: 1;
    padding: 0.75rem 1rem;
    font-weight: 700;
    font-size: 0.92rem;
    border-radius: var(--radius-md, 10px);
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.smart-confirm-cancel-btn {
    background: var(--bg-surface-elevated, rgba(255, 255, 255, 0.05));
    border: 1px solid var(--border-color, rgba(255, 255, 255, 0.15));
    color: var(--text-primary, #ffffff);
}

.smart-confirm-cancel-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}

.smart-confirm-confirm-btn {
    border: none;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
}

.smart-confirm-confirm-btn.danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
}
.smart-confirm-confirm-btn.danger:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
}

.smart-confirm-confirm-btn.warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
}
.smart-confirm-confirm-btn.warning:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
}

.smart-confirm-confirm-btn.info {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
}
.smart-confirm-confirm-btn.info:hover {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
}

.smart-confirm-confirm-btn.success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
}
.smart-confirm-confirm-btn.success:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

@keyframes smartConfirmFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes smartConfirmScaleIn {
    from { opacity: 0; transform: scale(0.92) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>

<script>
/**
 * Smart Syndic Unified Confirmation Engine
 * Automatically intercepts forms with data-confirm or converts inline confirm()
 * Supports secure password confirmation for sensitive deletions (data-confirm-password="true")
 */
(function() {
    let currentResolver = null;
    let pendingForm = null;
    let isPasswordRequired = false;

    const modal = document.getElementById('smart-confirm-modal');
    const titleEl = document.getElementById('smart-confirm-title');
    const msgEl = document.getElementById('smart-confirm-message');
    const confirmBtn = document.getElementById('smart-confirm-confirm-btn');
    const cancelBtn = document.getElementById('smart-confirm-cancel-btn');

    // Password elements
    const passwordWrap = document.getElementById('smart-confirm-password-wrap');
    const passwordInput = document.getElementById('smart-confirm-password-input');
    const passwordError = document.getElementById('smart-confirm-password-error');
    const togglePwBtn = document.getElementById('smart-confirm-toggle-pw');
    const eyeOpen = document.getElementById('smart-pw-eye-open');
    const eyeClosed = document.getElementById('smart-pw-eye-closed');

    const icons = {
        danger: document.getElementById('smart-confirm-icon-danger'),
        warning: document.getElementById('smart-confirm-icon-warning'),
        info: document.getElementById('smart-confirm-icon-info'),
        success: document.getElementById('smart-confirm-icon-success'),
    };

    // Toggle password visibility
    if (togglePwBtn && passwordInput) {
        togglePwBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                if (eyeOpen) eyeOpen.style.display = 'none';
                if (eyeClosed) eyeClosed.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                if (eyeOpen) eyeOpen.style.display = 'block';
                if (eyeClosed) eyeClosed.style.display = 'none';
            }
        });
    }

    // Submit on Enter key inside password input
    if (passwordInput) {
        passwordInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmBtn.click();
            }
        });
        passwordInput.addEventListener('input', function() {
            passwordInput.classList.remove('input-error');
            if (passwordError) passwordError.style.display = 'none';
        });
    }

    function autoDetectType(message, title) {
        const text = (message + ' ' + (title || '')).toLowerCase();
        if (text.includes('حذف') || text.includes('رفض') || text.includes('destroy') || text.includes('delete') || text.includes('reject')) {
            return 'danger';
        }
        if (text.includes('فك الحظر') || text.includes('تنشيط') || text.includes('تفعيل') || text.includes('موافقة') || text.includes('approve') || text.includes('unblock')) {
            return 'success';
        }
        if (text.includes('دخول') || text.includes('تقمص') || text.includes('impersonate') || text.includes('vacuum') || text.includes('تحسين')) {
            return 'info';
        }
        return 'warning';
    }

    function openModal({ title, message, type, confirmText, cancelText, requirePassword }) {
        return new Promise((resolve) => {
            currentResolver = resolve;

            // Determine type
            const finalType = type || autoDetectType(message, title);
            isPasswordRequired = !!requirePassword;

            // Update text
            titleEl.textContent = title || (finalType === 'danger' ? 'تأكيد الحذف والإلغاء' : (finalType === 'info' ? 'تأكيد المتابعة والدخول' : 'تأكيد الإجراء'));
            msgEl.textContent = message || 'هل تؤكد متابعة تنفيذ هذا الإجراء؟';
            confirmBtn.textContent = confirmText || (finalType === 'danger' ? 'نعم، حذف وتأكيد' : 'تأكيد ومتابعة');
            cancelBtn.textContent = cancelText || 'إلغاء الأمر';

            // Reset icon visibility
            Object.values(icons).forEach(icon => { if (icon) icon.style.display = 'none'; });
            if (icons[finalType]) {
                icons[finalType].style.display = 'flex';
            } else if (icons.warning) {
                icons.warning.style.display = 'flex';
            }

            // Set button classes
            confirmBtn.className = 'btn smart-confirm-confirm-btn ' + finalType;

            // Password field handling
            if (passwordWrap) {
                if (isPasswordRequired) {
                    passwordWrap.style.display = 'block';
                    if (passwordInput) {
                        passwordInput.value = '';
                        passwordInput.type = 'password';
                        passwordInput.classList.remove('input-error');
                    }
                    if (eyeOpen) eyeOpen.style.display = 'block';
                    if (eyeClosed) eyeClosed.style.display = 'none';
                    if (passwordError) {
                        passwordError.style.display = 'none';
                        passwordError.textContent = '';
                    }
                } else {
                    passwordWrap.style.display = 'none';
                }
            }

            // Show modal
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');

            if (isPasswordRequired && passwordInput) {
                setTimeout(() => passwordInput.focus(), 60);
            } else {
                confirmBtn.focus();
            }
        });
    }

    function closeModal(confirmed, passwordVal = null) {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        if (passwordInput) {
            passwordInput.value = '';
            passwordInput.classList.remove('input-error');
            if (passwordError) passwordError.style.display = 'none';
        }
        if (currentResolver) {
            currentResolver(confirmed ? { confirmed: true, password: passwordVal } : false);
            currentResolver = null;
        }
    }

    // Expose global helper
    window.smartConfirm = openModal;

    // Handle cancel button & backdrop click
    cancelBtn.addEventListener('click', () => {
        pendingForm = null;
        closeModal(false);
    });
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            pendingForm = null;
            closeModal(false);
        }
    });

    // Handle confirm button
    confirmBtn.addEventListener('click', () => {
        let passwordVal = null;

        if (isPasswordRequired && passwordInput) {
            passwordVal = passwordInput.value.trim();
            if (!passwordVal) {
                passwordInput.classList.add('input-error');
                if (passwordError) {
                    passwordError.textContent = '⚠️ يرجى إدخال كلمة المرور لحسابك لتأكيد هذا الإجراء الحساس.';
                    passwordError.style.display = 'block';
                }
                passwordInput.focus();
                return;
            }
        }

        if (pendingForm) {
            const formToSubmit = pendingForm;
            pendingForm = null;

            if (isPasswordRequired && passwordVal) {
                let pwHidden = formToSubmit.querySelector('input[name="confirm_password"]');
                if (!pwHidden) {
                    pwHidden = document.createElement('input');
                    pwHidden.type = 'hidden';
                    pwHidden.name = 'confirm_password';
                    formToSubmit.appendChild(pwHidden);
                }
                pwHidden.value = passwordVal;
            }

            closeModal(true, passwordVal);
            formToSubmit.__smartConfirmed = true;
            formToSubmit.submit();
        } else {
            closeModal(true, passwordVal);
        }
    });

    // Handle Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            pendingForm = null;
            closeModal(false);
        }
    });

    // Intercept form submissions
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || form.__smartConfirmed) return;

        let confirmMsg = form.getAttribute('data-confirm');

        // Check if form has inline onsubmit with confirm()
        if (!confirmMsg && form.getAttribute('onsubmit')) {
            const match = form.getAttribute('onsubmit').match(/confirm\s*\(\s*['"`](.*?)['"`]\s*\)/);
            if (match && match[1]) {
                confirmMsg = match[1];
                form.removeAttribute('onsubmit');
                form.setAttribute('data-confirm', confirmMsg);
            }
        }

        if (confirmMsg) {
            e.preventDefault();
            e.stopImmediatePropagation();

            pendingForm = form;
            const title = form.getAttribute('data-confirm-title');
            const type = form.getAttribute('data-confirm-type');
            const btnText = form.getAttribute('data-confirm-btn');
            const requirePw = form.getAttribute('data-confirm-password') === 'true'
                || form.dataset.confirmPassword === 'true'
                || form.getAttribute('data-require-password') === 'true';

            openModal({
                title: title,
                message: confirmMsg,
                type: type,
                confirmText: btnText,
                requirePassword: requirePw
            }).then(result => {
                if (!result) {
                    pendingForm = null;
                }
            });
        }
    }, true);

    // Scan for and modernize any remaining onsubmit="return confirm(...)" upon page load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form[onsubmit*="confirm"]').forEach(form => {
            const attr = form.getAttribute('onsubmit');
            const match = attr.match(/confirm\s*\(\s*['"`](.*?)['"`]\s*\)/);
            if (match && match[1]) {
                form.setAttribute('data-confirm', match[1]);
                form.removeAttribute('onsubmit');
            }
        });
    });
})();
</script>
