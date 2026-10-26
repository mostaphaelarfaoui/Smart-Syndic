{{-- Financial Report PDF Generator Modal Component --}}
<div id="financialReportModal" class="fin-modal-overlay" style="display:none;" onclick="if(event.target === this) closeFinancialReportModal()">
    <div class="fin-modal-container" role="dialog" aria-modal="true" aria-labelledby="finModalTitle">
        {{-- Modal Header --}}
        <div class="fin-modal-header">
            <div class="fin-modal-title-box">
                <div class="fin-modal-icon-badge">📑</div>
                <div>
                    <h3 id="finModalTitle" class="fin-modal-title">توليد وتحميل التقرير المالي الرسمي (PDF)</h3>
                    <p class="fin-modal-subtitle">اختر الفترة الزمنية ونوع البيانات المالية المطلوبة لاستخراج تقرير محاسبي معتمد</p>
                </div>
            </div>
            <button type="button" class="fin-modal-close-btn" onclick="closeFinancialReportModal()" title="إغلاق">&times;</button>
        </div>

        {{-- Form --}}
        <form method="GET" action="{{ route('portal.reports.financial-pdf') }}" target="_blank" id="finReportForm" onsubmit="setTimeout(closeFinancialReportModal, 500)">
            <div class="fin-modal-body">
                
                {{-- Step 1: Content Scope Selector (Cards) --}}
                <div class="fin-form-group">
                    <label class="fin-label">1. نطاق المحتوى المالي المراد تضمينه بالتقرير:</label>
                    <div class="fin-scope-grid">
                        {{-- Option 1: Both (Comprehensive) --}}
                        <label class="fin-scope-card">
                            <input type="radio" name="scope" value="both" checked class="fin-radio-input" onchange="updateScopeUI(this.value)">
                            <div class="fin-scope-card-content">
                                <div class="fin-scope-icon">⚖️</div>
                                <div class="fin-scope-text">
                                    <div class="fin-scope-title">تقرير مالي شامل (موصى به)</div>
                                    <div class="fin-scope-desc">كشف وضعية الشقق (الخالصين والمتأخرين) + جدول المداخيل والمصاريف + المركز المالي</div>
                                </div>
                                <span class="fin-scope-tag recommended">شامل</span>
                            </div>
                        </label>

                        {{-- Option 2: Revenues only --}}
                        <label class="fin-scope-card">
                            <input type="radio" name="scope" value="revenues" class="fin-radio-input" onchange="updateScopeUI(this.value)">
                            <div class="fin-scope-card-content">
                                <div class="fin-scope-icon">📈</div>
                                <div class="fin-scope-text">
                                    <div class="fin-scope-title">المداخيل والتحصيلات فقط</div>
                                    <div class="fin-scope-desc">كشف الشقق التي سددت والتي لم تسدد + جدول دفعات ووصولات الشقق وتفصيل التحصيل</div>
                                </div>
                                <span class="fin-scope-tag green">مداخيل</span>
                            </div>
                        </label>

                        {{-- Option 3: Expenses only --}}
                        <label class="fin-scope-card">
                            <input type="radio" name="scope" value="expenses" class="fin-radio-input" onchange="updateScopeUI(this.value)">
                            <div class="fin-scope-card-content">
                                <div class="fin-scope-icon">📉</div>
                                <div class="fin-scope-text">
                                    <div class="fin-scope-title">المصاريف والنفقات فقط</div>
                                    <div class="fin-scope-desc">جدول فواتير الصيانة، النظافة، الكهرباء، الحراسة، والتوزيع حسب الأصناف</div>
                                </div>
                                <span class="fin-scope-tag rose">مصاريف</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Step 2: Period Selection & Presets --}}
                <div class="fin-form-group">
                    <label class="fin-label">2. النطاق الزمني والفترة (شهر واحد أو عدة أشهر):</label>
                    
                    {{-- Quick Preset Buttons --}}
                    <div class="fin-presets-wrap">
                        <button type="button" class="fin-preset-btn active" onclick="applyFinPreset('current_month', this)">الشهر الحالي</button>
                        <button type="button" class="fin-preset-btn" onclick="applyFinPreset('last_month', this)">الشهر الماضي</button>
                        <button type="button" class="fin-preset-btn" onclick="applyFinPreset('last_3_months', this)">آخر 3 أشهر</button>
                        <button type="button" class="fin-preset-btn" onclick="applyFinPreset('current_year', this)">السنة الحالية كاملة</button>
                        <button type="button" class="fin-preset-btn" onclick="applyFinPreset('custom', this)">فترة مخصصة...</button>
                    </div>

                    {{-- Month Pickers Inputs Row --}}
                    <div class="fin-dates-row">
                        <div class="fin-date-col">
                            <label class="fin-sublabel" for="reportStartMonth">من شهر (بداية الفترة):</label>
                            <input type="month" id="reportStartMonth" name="start_month" class="fin-input-month" required value="{{ date('Y-m') }}" onchange="validateMonthsOrder()">
                        </div>
                        <div class="fin-date-arrow">⬅</div>
                        <div class="fin-date-col">
                            <label class="fin-sublabel" for="reportEndMonth">إلى شهر (نهاية الفترة):</label>
                            <input type="month" id="reportEndMonth" name="end_month" class="fin-input-month" required value="{{ date('Y-m') }}" onchange="validateMonthsOrder()">
                        </div>
                    </div>
                    <small id="periodNoteText" class="fin-note-text">سيتم استخراج التقرير لشهر {{ date('m/Y') }} كاملاً.</small>
                </div>

                {{-- Step 3: Export Format & Destination --}}
                <div class="fin-form-group" style="margin-bottom:0;">
                    <label class="fin-label">3. طريقة الفتح والتحميل:</label>
                    <div class="fin-mode-row">
                        <label class="fin-mode-option">
                            <input type="radio" name="mode" value="download" checked>
                            <span>📥 تحميل مباشر إلى الجهاز (Direct Download)</span>
                        </label>
                        <label class="fin-mode-option">
                            <input type="radio" name="mode" value="preview">
                            <span>👁️ فتح ومعاينة في المتصفح قبل الطباعة (Preview)</span>
                        </label>
                    </div>
                </div>

            </div>

            {{-- Modal Footer --}}
            <div class="fin-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeFinancialReportModal()">إلغاء</button>
                <button type="submit" class="btn btn-primary fin-submit-btn">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>استخراج وتحميل تقرير PDF</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* ─── Modal Overlay & Container ──────────────── */
.fin-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(6px);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem;
    animation: finFadeIn 0.2s ease-out;
}

@keyframes finFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.fin-modal-container {
    background: var(--bg-surface, #1e293b);
    border: 1px solid var(--border-color, rgba(255, 255, 255, 0.12));
    border-radius: var(--radius-lg, 16px);
    width: 620px;
    max-width: 100%;
    max-height: 94vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    overflow: hidden;
    color: var(--text-primary, #f1f5f9);
    direction: rtl;
    text-align: right;
    animation: finSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes finSlideUp {
    from { transform: translateY(20px) scale(0.97); }
    to { transform: translateY(0) scale(1); }
}

/* ─── Form Flex Container ────────────────────── */
#finReportForm {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

/* ─── Header ─────────────────────────────────── */
.fin-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
    background: var(--bg-surface-elevated, #334155);
    flex-shrink: 0;
}

.fin-modal-title-box {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.fin-modal-icon-badge {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(13, 148, 136, 0.18);
    border: 1px solid rgba(13, 148, 136, 0.35);
    color: var(--brand-primary, #0d9488);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.fin-modal-title {
    font-size: 1.05rem;
    font-weight: 800;
    margin: 0;
    color: #ffffff;
}

.fin-modal-subtitle {
    font-size: 0.78rem;
    color: var(--text-secondary, #94a3b8);
    margin: 0.15rem 0 0;
}

.fin-modal-close-btn {
    background: transparent;
    border: none;
    color: var(--text-secondary, #94a3b8);
    font-size: 1.6rem;
    cursor: pointer;
    line-height: 1;
    padding: 0 0.4rem;
    border-radius: 6px;
    transition: all 0.15s ease;
}

.fin-modal-close-btn:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.1);
}

/* ─── Body & Form ────────────────────────────── */
.fin-modal-body {
    padding: 1rem 1.25rem;
    overflow-y: auto;
    overscroll-behavior: contain;
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    gap: 0.95rem;
}

.fin-modal-body::-webkit-scrollbar {
    width: 6px;
}
.fin-modal-body::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.15);
    border-radius: 4px;
}
.fin-modal-body::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}
.fin-modal-body::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.35);
}

.fin-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.fin-label {
    font-size: 0.88rem;
    font-weight: 800;
    color: #ffffff;
}

.fin-sublabel {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--text-secondary, #cbd5e1);
    margin-bottom: 0.25rem;
    display: block;
}

/* ─── Scope Cards ────────────────────────────── */
.fin-scope-grid {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.fin-scope-card {
    position: relative;
    cursor: pointer;
    display: block;
}

.fin-radio-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.fin-scope-card-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 0.85rem;
    border-radius: 10px;
    border: 1.5px solid var(--border-color, rgba(255, 255, 255, 0.1));
    background: var(--bg-surface-elevated, #243044);
    transition: all 0.18s ease;
}

.fin-scope-card:hover .fin-scope-card-content {
    border-color: rgba(13, 148, 136, 0.4);
    background: rgba(13, 148, 136, 0.08);
}

.fin-radio-input:checked + .fin-scope-card-content {
    border-color: var(--brand-primary, #0d9488);
    background: rgba(13, 148, 136, 0.14);
    box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.25);
}

.fin-scope-icon {
    font-size: 1.25rem;
}

.fin-scope-text {
    flex: 1;
}

.fin-scope-title {
    font-size: 0.9rem;
    font-weight: 800;
    color: #ffffff;
}

.fin-scope-desc {
    font-size: 0.74rem;
    color: var(--text-secondary, #94a3b8);
    margin-top: 0.1rem;
}

.fin-scope-tag {
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
}

.fin-scope-tag.recommended {
    background: rgba(13, 148, 136, 0.25);
    color: #2dd4bf;
    border: 1px solid rgba(13, 148, 136, 0.4);
}

.fin-scope-tag.green {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
}

.fin-scope-tag.rose {
    background: rgba(244, 63, 94, 0.2);
    color: #fb7185;
}

/* ─── Period Presets ─────────────────────────── */
.fin-presets-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 0.5rem;
}

.fin-preset-btn {
    background: var(--bg-surface-elevated, #243044);
    border: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
    color: var(--text-secondary, #cbd5e1);
    font-size: 0.8rem;
    font-weight: 700;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.fin-preset-btn:hover {
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.3);
}

.fin-preset-btn.active {
    background: var(--brand-primary, #0d9488);
    border-color: var(--brand-primary, #0d9488);
    color: #ffffff;
}

/* ─── Dates Row ──────────────────────────────── */
.fin-dates-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.fin-date-col {
    flex: 1;
}

.fin-date-arrow {
    font-size: 1.2rem;
    color: var(--text-secondary, #94a3b8);
    padding-top: 1.25rem;
}

.fin-input-month {
    width: 100%;
    background: var(--bg-surface-elevated, #243044);
    border: 1.5px solid var(--border-color, rgba(255, 255, 255, 0.15));
    border-radius: 8px;
    color: #ffffff;
    font-size: 0.95rem;
    font-weight: 700;
    padding: 0.55rem 0.85rem;
    direction: ltr;
    text-align: center;
}

.fin-input-month:focus {
    outline: none;
    border-color: var(--brand-primary, #0d9488);
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.25);
}

.fin-note-text {
    font-size: 0.8rem;
    color: #2dd4bf;
    margin-top: 0.3rem;
}

/* ─── Mode Row ───────────────────────────────── */
.fin-mode-row {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    background: var(--bg-surface-elevated, #243044);
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
}

.fin-mode-option {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.84rem;
    color: var(--text-secondary, #cbd5e1);
    cursor: pointer;
}

.fin-mode-option input {
    accent-color: var(--brand-primary, #0d9488);
}

/* ─── Footer ─────────────────────────────────── */
.fin-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 0.85rem 1.25rem;
    background: var(--bg-surface-elevated, #334155);
    border-top: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
    flex-shrink: 0;
}

.fin-submit-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 800;
    padding: 0.55rem 1.25rem;
}
</style>

<script>
window.openFinancialReportModal = function(defaultScope = 'both') {
    const modal = document.getElementById('financialReportModal');
    if (!modal) return;

    // Set scope
    const radio = modal.querySelector(`input[name="scope"][value="${defaultScope}"]`);
    if (radio) {
        radio.checked = true;
    }

    // Default to current month preset
    applyFinPreset('current_month');

    modal.style.display = 'flex';
};

window.closeFinancialReportModal = function() {
    const modal = document.getElementById('financialReportModal');
    if (modal) modal.style.display = 'none';
};

window.applyFinPreset = function(presetKey, targetBtn = null) {
    // UI active state
    if (targetBtn) {
        document.querySelectorAll('.fin-preset-btn').forEach(btn => btn.classList.remove('active'));
        targetBtn.classList.add('active');
    }

    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonthNum = now.getMonth() + 1;

    const startInput = document.getElementById('reportStartMonth');
    const endInput = document.getElementById('reportEndMonth');
    if (!startInput || !endInput) return;

    const formatMonth = (y, m) => `${y}-${String(m).padStart(2, '0')}`;

    if (presetKey === 'current_month') {
        const ym = formatMonth(currentYear, currentMonthNum);
        startInput.value = ym;
        endInput.value = ym;
    } else if (presetKey === 'last_month') {
        let prevM = currentMonthNum - 1;
        let prevY = currentYear;
        if (prevM === 0) { prevM = 12; prevY--; }
        const ym = formatMonth(prevY, prevM);
        startInput.value = ym;
        endInput.value = ym;
    } else if (presetKey === 'last_3_months') {
        const endYm = formatMonth(currentYear, currentMonthNum);
        let startM = currentMonthNum - 2;
        let startY = currentYear;
        if (startM <= 0) { startM += 12; startY--; }
        startInput.value = formatMonth(startY, startM);
        endInput.value = endYm;
    } else if (presetKey === 'current_year') {
        startInput.value = `${currentYear}-01`;
        endInput.value = formatMonth(currentYear, currentMonthNum);
    }

    validateMonthsOrder();
};

window.validateMonthsOrder = function() {
    const startInput = document.getElementById('reportStartMonth');
    const endInput = document.getElementById('reportEndMonth');
    const note = document.getElementById('periodNoteText');
    if (!startInput || !endInput || !note) return;

    if (startInput.value > endInput.value) {
        endInput.value = startInput.value;
    }

    if (startInput.value === endInput.value) {
        note.textContent = `سيتم استخراج التقرير لشهر ${startInput.value} كاملاً.`;
    } else {
        note.textContent = `سيتم استخراج التقرير لفترة متعددة الشهور من ${startInput.value} إلى ${endInput.value}.`;
    }
};

window.updateScopeUI = function(scopeVal) {
    // Optional hook if extra UI fields needed
};

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeFinancialReportModal();
    }
});
</script>
