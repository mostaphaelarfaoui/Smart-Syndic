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
    margin: 0 0 1.75rem 0;
    word-break: break-word;
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
 */
(function() {
    let currentResolver = null;
    let pendingForm = null;

    const modal = document.getElementById('smart-confirm-modal');
    const titleEl = document.getElementById('smart-confirm-title');
    const msgEl = document.getElementById('smart-confirm-message');
    const confirmBtn = document.getElementById('smart-confirm-confirm-btn');
    const cancelBtn = document.getElementById('smart-confirm-cancel-btn');

    const icons = {
        danger: document.getElementById('smart-confirm-icon-danger'),
        warning: document.getElementById('smart-confirm-icon-warning'),
        info: document.getElementById('smart-confirm-icon-info'),
        success: document.getElementById('smart-confirm-icon-success'),
    };

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

    function openModal({ title, message, type, confirmText, cancelText }) {
        return new Promise((resolve) => {
            currentResolver = resolve;

            // Determine type
            const finalType = type || autoDetectType(message, title);

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

            // Show modal
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            confirmBtn.focus();
        });
    }

    function closeModal(confirmed) {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        if (currentResolver) {
            currentResolver(confirmed);
            currentResolver = null;
        }
    }

    // Expose global helper
    window.smartConfirm = openModal;

    // Handle cancel button & backdrop click
    cancelBtn.addEventListener('click', () => closeModal(false));
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal(false);
    });

    // Handle confirm button
    confirmBtn.addEventListener('click', () => {
        if (pendingForm) {
            const formToSubmit = pendingForm;
            pendingForm = null;
            closeModal(true);
            formToSubmit.__smartConfirmed = true;
            formToSubmit.submit();
        } else {
            closeModal(true);
        }
    });

    // Handle Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
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

            openModal({
                title: title,
                message: confirmMsg,
                type: type,
                confirmText: btnText
            }).then(confirmed => {
                if (!confirmed) {
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
