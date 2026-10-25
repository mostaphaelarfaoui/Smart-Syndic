@extends('layouts.dashboard')

@section('title', 'لوحة القيادة — ' . $tenant->name)

@section('content')

<div x-data="dashboardPage()">

{{-- Direct Modal 1: Record Payment / Quick Collection --}}
<div class="modal-overlay" x-show="paymentModal" x-cloak style="z-index:99990;">
    <div class="modal-dialog" style="max-width:680px; width:95%;" @click.away="paymentModal = false">
        <div class="modal-header">
            <h3 class="modal-title">استخلاص واجب شهري فوري</h3>
            <button type="button" class="icon-btn" @click="paymentModal = false">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.payments.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                {{-- Apartment Selection --}}
                <div class="form-group">
                    <label class="form-label" style="font-weight:700;">الشقة المعنية</label>
                    <select name="apartment_id" class="form-select" required x-model="selectedApartmentId" @change="onApartmentChange($event)">
                        <option value="">-- اختر الشقة المعنية --</option>
                        @php
                            $groupedApts = $allApartments->groupBy(function($a) {
                                $b = $a->building;
                                if (!$b) return 'العمارة العامة';
                                $block = $b->block ? " (بلوك {$b->block})" : '';
                                return $b->name . $block;
                            });
                        @endphp
                        @foreach($groupedApts as $groupName => $apts)
                            <optgroup label="🏢 {{ $groupName }}">
                                @foreach($apts as $apt)
                                    @php
                                        $cleanUnit = str_starts_with(trim($apt->unit_number), 'شقة') ? $apt->unit_number : 'شقة ' . $apt->unit_number;
                                        $residentName = $apt->residents->first()?->full_name;
                                    @endphp
                                    <option value="{{ $apt->id }}" data-fee="{{ (float) $apt->monthly_fee }}">
                                        {{ $cleanUnit }} {{ $residentName ? "({$residentName})" : '(شاغرة)' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                {{-- Interactive Multi-Month Selection --}}
                <div class="form-group" style="background:var(--bg-secondary); padding:1.15rem; border-radius:12px; border:1px solid var(--border-color); margin-bottom:1.25rem;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.85rem;">
                        <label class="form-label" style="margin:0; font-weight:800; font-size:0.92rem; color:var(--text-primary);">
                            الشهور المستخلصة
                            <span style="font-size:0.78rem; font-weight:normal; color:var(--text-muted); margin-right:4px;">(يمكنك تحديد عدة أشهر)</span>
                        </label>
                        <div style="display:flex; align-items:center; gap:0.4rem; background:var(--bg-surface); padding:3px 6px; border-radius:8px; border:1px solid var(--border-color);">
                            <button type="button" class="btn btn-sm btn-secondary" style="padding:0.2rem 0.55rem; font-size:0.75rem; border-radius:6px; cursor:pointer;" @click="changeYear(-1)">◀</button>
                            <strong class="num" style="font-size:0.95rem; min-width:55px; text-align:center; color:var(--text-primary); font-weight:800;" x-text="selectedYear"></strong>
                            <button type="button" class="btn btn-sm btn-secondary" style="padding:0.2rem 0.55rem; font-size:0.75rem; border-radius:6px; cursor:pointer;" @click="changeYear(1)">▶</button>
                        </div>
                    </div>

                    {{-- Month Pills Grid --}}
                    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:0.45rem; margin-bottom:0.85rem;">
                        <template x-for="(monthObj, idx) in currentYearMonths" :key="monthObj.key">
                            <button type="button" 
                                    @click="toggleMonth(monthObj.key)"
                                    :style="isMonthSelected(monthObj.key) 
                                        ? 'background:linear-gradient(135deg, #0d9488 0%, #059669 100%); color:#ffffff; border-color:#0d9488; font-weight:800; box-shadow:0 2px 6px rgba(13,148,136,0.35);' 
                                        : 'background:var(--bg-surface); color:var(--text-primary); border-color:var(--border-color); font-weight:600;'"
                                    style="padding:0.55rem 0.35rem; border:1px solid; border-radius:8px; font-size:0.84rem; cursor:pointer; text-align:center; transition:all 0.15s ease;">
                                <span x-text="monthObj.name"></span>
                                <span x-show="isMonthSelected(monthObj.key)" style="font-size:0.75rem; margin-right:3px;">✓</span>
                            </button>
                        </template>
                    </div>

                    <input type="hidden" name="covered_months" :value="selectedMonths.join(',')">

                    <div style="display:flex; align-items:center; justify-content:space-between; font-size:0.84rem; padding-top:0.6rem; border-top:1px dashed var(--border-color);">
                        <span style="color:var(--brand-primary); font-weight:700;">
                            ✨ عدد الأشهر المحددة: <span class="num" style="font-weight:900;" x-text="selectedMonths.length"></span> شهر
                        </span>
                        <button type="button" class="btn btn-link btn-sm" style="padding:0; font-size:0.8rem; color:var(--brand-secondary); text-decoration:underline; font-weight:700; cursor:pointer;" @click="selectCurrentMonthOnly()">
                            إعادة ضبط للشهر الحالي فقط
                        </button>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">المبلغ الإجمالي المؤدى (درهم)</label>
                        <input type="number" step="0.01" name="amount_paid" class="form-control" x-model="amountPaid" placeholder="المبلغ الإجمالي" required>
                        <small style="color:var(--text-muted); font-size:0.72rem;" x-show="selectedApartmentFee > 0">
                            الواجب الشهري للشقة: <strong class="num" x-text="selectedApartmentFee + ' د.م'"></strong>
                        </small>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">طريقة الأداء</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="CASH">💵 نقداً (صندوق الإقامة)</option>
                            <option value="BANK_TRANSFER">🏦 تحويل بنكي</option>
                            <option value="CHEQUE">📝 شيك بنكي</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">تاريخ الأداء</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">إثبات الأداء أو التوصيل البنكي (اختياري)</label>
                        <input type="file" name="bank_receipt_proof" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">ملاحظات إضافية (اختياري)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات حول طريقة الأداء أو رقم الشيك..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="paymentModal = false">إلغاء</button>
                <button type="submit" class="btn btn-primary" style="font-weight:700;">
                    <span>تأكيد واستخراج التوصيل ✓</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Direct Modal 2: Record Expense --}}
<div class="modal-overlay" x-show="expenseModal" x-cloak style="z-index:99990;">
    <div class="modal-dialog" @click.away="expenseModal = false">
        <div class="modal-header">
            <h3 class="modal-title">تسجيل مصروف أو فاتورة جديدة</h3>
            <button type="button" class="icon-btn" @click="expenseModal = false">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.expenses.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">عنوان المصروف أو الخدمة</label>
                    <input type="text" name="title" class="form-control" placeholder="مثال: إصلاح عطل إنارة المدخل" required>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">المبلغ (درهم)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="350.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">الصنف / النوع</label>
                        <select name="category" class="form-select" required>
                            <option value="CLEANING">🧹 النظافة ومواد التطهير</option>
                            <option value="ELEVATOR">🛗 صيانة المصعد</option>
                            <option value="SECURITY">🔒 الحراسة والأمن</option>
                            <option value="WATER_ELECTRICITY">💡 فاتورة الماء والكهرباء</option>
                            <option value="GARDENING">🌿 العناية بالحديقة</option>
                            <option value="MAINTENANCE">🔧 صيانة عامة وإصلاحات</option>
                            <option value="OTHER">📋 مصاريف إدارية أخرى</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">طريقة الأداء والخصم</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="CASH">💵 نقداً (يخصم من الصندوق)</option>
                            <option value="BANK_TRANSFER">🏦 تحويل (يخصم من البنك)</option>
                            <option value="CHEQUE">📝 شيك بنكي</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">تاريخ الصرف</label>
                        <input type="date" name="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">صورة الفاتورة أو وصل الشراء (اختياري)</label>
                    <input type="file" name="invoice_proof" class="form-control" accept="image/*,application/pdf">
                    <small style="color:var(--text-muted); font-size:0.75rem;">يمكنك إرفاق صورة للفاتورة لحفظها في الأرشيف المالي</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="expenseModal = false">إلغاء</button>
                <button type="submit" class="btn btn-danger">تسجيل وخصم المصروف</button>
            </div>
        </form>
    </div>
</div>

{{-- 1. Executive Welcome & Property Status --}}
<div class="card" style="margin-bottom:1.5rem; background:linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.98) 100%); border:1px solid rgba(13, 148, 136, 0.25); border-radius:var(--radius-lg); padding:1.5rem 1.75rem; color:#fff; position:relative; overflow:hidden;">
    <div style="position:absolute; left:-30px; bottom:-30px; width:160px; height:160px; background:radial-gradient(circle, rgba(13,148,136,0.18) 0%, transparent 70%); border-radius:50%; pointer-events:none;"></div>
    
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1.25rem; position:relative; z-index:1;">
        <div>
            <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.4rem;">
                <h1 style="font-size:1.6rem; font-weight:900; margin:0; color:#fff; letter-spacing:-0.01em;">
                    مرحباً بك، {{ $user->first_name ?? $user->full_name }} 👋
                </h1>
                <span class="badge" style="background:rgba(16,185,129,0.2); color:#34d399; border:1px solid rgba(16,185,129,0.3); font-size:0.75rem; padding:0.2rem 0.6rem;">
                    ● نظام نشط
                </span>
            </div>
            <p style="margin:0 0 0.75rem 0; color:#cbd5e1; font-size:0.95rem;">
                لوحة المتابعة الشاملة لإدارة <strong style="color:#5eead4;">{{ $tenant->name }}</strong>
                <span style="color:#94a3b8; font-size:0.85rem;">({{ match ($tenant->property_type) {
                    'SINGLE_BUILDING'        => 'عمارة منفردة',
                    'RESIDENTIAL_COMPLEX'    => 'مجمع سكني مغلق',
                    'HOUSING_ESTATE_VILLAS' => 'تجزئة فيلات',
                    'COMMERCIAL_RESIDENTIAL' => 'مجمع تجاري وسكني',
                    default                  => 'إقامة سكنية',
                } }})</span>
            </p>

            <div style="display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap; font-size:0.85rem; color:#94a3b8;">
                <span style="display:inline-flex; align-items:center; gap:0.4rem;">
                    <span>🏢</span> <strong>{{ $totalBuildings }}</strong> عمارات
                </span>
                <span style="display:inline-flex; align-items:center; gap:0.4rem;">
                    <span>🚪</span> <strong>{{ $totalApartments }}</strong> شقق ({{ $billableCount }} خاضعة للواجبات)
                </span>
                <span style="display:inline-flex; align-items:center; gap:0.4rem;">
                    <span>👥</span> <strong>{{ $activeResidents }}</strong> ساكن مسجل
                </span>
            </div>
        </div>

        {{-- Invitation Code & WhatsApp Share --}}
        <div style="background:rgba(15,23,42,0.7); border:1px solid rgba(255,255,255,0.12); border-radius:12px; padding:0.85rem 1.15rem; min-width:280px;" x-data="{ copied: false }">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.4rem;">
                <span style="font-size:0.8rem; color:#94a3b8; font-weight:700;">كود انضمام السكان للتطبيق:</span>
                <span x-show="copied" x-cloak style="font-size:0.75rem; color:#10b981; font-weight:800;">تم النسخ! ✓</span>
            </div>
            <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem;">
                <span class="num" style="font-size:1.35rem; font-weight:900; letter-spacing:0.12em; color:#38bdf8; font-family:monospace;">
                    {{ $tenant->invitation_code }}
                </span>
                <div style="display:flex; align-items:center; gap:0.35rem;">
                    <button type="button" 
                            class="btn btn-sm btn-secondary" 
                            style="padding:0.25rem 0.6rem; font-size:0.75rem; border-radius:6px;"
                            x-on:click="navigator.clipboard.writeText('{{ $tenant->invitation_code }}'); copied = true; setTimeout(() => copied = false, 2500)">
                        📋 نسخ
                    </button>
                    <a href="https://api.whatsapp.com/send?text={{ urlencode('السلام عليكم، يرجى من جميع سكان ' . $tenant->name . ' تحميل تطبيق Smart Syndic واستخدام كود الانضمام الخاص بإقامتنا: ' . $tenant->invitation_code) }}" 
                       target="_blank" 
                       class="btn btn-sm btn-success" 
                       style="padding:0.25rem 0.65rem; font-size:0.75rem; border-radius:6px; background:#25d366; border-color:#25d366; color:#fff;"
                       title="مشاركة الكود مع السكان في مجموعة واتساب">
                        📲 واتساب
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 2. Daily Operations Launchpad (Direct Action Triggers) --}}
<div style="margin-bottom:1.5rem;">
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem;">
        {{-- Direct Action 1: Record Payment Modal --}}
        <div @click="openQuickPayment()" class="card action-launchpad-card" style="cursor:pointer; margin:0; padding:1.1rem 1.25rem; display:flex; align-items:center; gap:1rem; border:1px solid var(--border-color); border-radius:var(--radius-md); transition:all 0.2s ease; background:var(--bg-card);">
            <div style="width:46px; height:46px; border-radius:12px; background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0; box-shadow:0 3px 8px rgba(16,185,129,0.3);">
                💳
            </div>
            <div>
                <div style="font-weight:900; font-size:0.98rem; color:var(--text-primary); margin-bottom:0.2rem;">استخلاص واجب شهري</div>
                <div style="font-size:0.78rem; color:var(--color-success); font-weight:700;">فتح نافذة الاستخلاص الفوري ⚡</div>
            </div>
        </div>

        {{-- Direct Action 2: Record Expense Modal --}}
        <div @click="expenseModal = true" class="card action-launchpad-card" style="cursor:pointer; margin:0; padding:1.1rem 1.25rem; display:flex; align-items:center; gap:1rem; border:1px solid var(--border-color); border-radius:var(--radius-md); transition:all 0.2s ease; background:var(--bg-card);">
            <div style="width:46px; height:46px; border-radius:12px; background:linear-gradient(135deg, #f43f5e 0%, #e11d48 100%); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0; box-shadow:0 3px 8px rgba(244,63,94,0.3);">
                💸
            </div>
            <div>
                <div style="font-weight:900; font-size:0.98rem; color:var(--text-primary); margin-bottom:0.2rem;">تسجيل مصروف أو نفقة</div>
                <div style="font-size:0.78rem; color:var(--color-danger); font-weight:700;">تسجيل فاتورة جديدة ⚡</div>
            </div>
        </div>

        {{-- Action 3: Apartments Status --}}
        <a href="{{ route('portal.apartments.index') }}" class="card action-launchpad-card" style="text-decoration:none; margin:0; padding:1.1rem 1.25rem; display:flex; align-items:center; gap:1rem; border:1px solid var(--border-color); border-radius:var(--radius-md); transition:all 0.2s ease; background:var(--bg-card);">
            <div style="width:46px; height:46px; border-radius:12px; background:linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0; box-shadow:0 3px 8px rgba(2,132,199,0.3);">
                🏢
            </div>
            <div>
                <div style="font-weight:900; font-size:0.98rem; color:var(--text-primary); margin-bottom:0.2rem;">إدارة الشقق والتحصيل</div>
                <div style="font-size:0.78rem; color:var(--text-muted);">
                    @if($unpaidUnitsCount > 0)
                        <span style="color:var(--color-warning); font-weight:700;">{{ $unpaidUnitsCount }} شقة لم تسدد بعد</span>
                    @else
                        <span style="color:var(--color-success); font-weight:700;">تم سداد جميع الشقق ✓</span>
                    @endif
                </div>
            </div>
        </a>

        {{-- Direct Action 4: Financial Report PDF Modal --}}
        <div onclick="openFinancialReportModal('both')" class="card action-launchpad-card" style="margin:0; padding:1.1rem 1.25rem; display:flex; align-items:center; gap:1rem; border:1px solid var(--border-color); border-radius:var(--radius-md); transition:all 0.2s ease; background:var(--bg-card); cursor:pointer;">
            <div style="width:46px; height:46px; border-radius:12px; background:linear-gradient(135deg, #d97706 0%, #b45309 100%); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0; box-shadow:0 3px 8px rgba(217,119,6,0.3);">
                📑
            </div>
            <div>
                <div style="font-weight:900; font-size:0.98rem; color:var(--text-primary); margin-bottom:0.2rem;">تقرير الحسابات (PDF)</div>
                <div style="font-size:0.78rem; color:var(--color-warning); font-weight:700;">كشف معتمد ومختوم ⚡</div>
            </div>
        </div>
    </div>
</div>

{{-- 3. Core Financial KPIs --}}
<div class="kpi-grid" style="margin-bottom:1.5rem;">
    {{-- KPI 1: Total Treasury --}}
    <div class="kpi-card">
        <div class="kpi-card-header">
            <span class="kpi-label">إجمالي رصيد الخزينة</span>
            <div class="kpi-icon-box emerald">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            </div>
        </div>
        <div class="kpi-value num">
            {{ number_format($totalTreasury, 2) }} <span class="unit">د.م</span>
        </div>
        <div class="kpi-footer" style="padding-top:0.75rem; border-top:1px dashed var(--border-color); margin-top:0.6rem; display:flex; justify-content:space-between; font-size:0.82rem;">
            <span>💵 الصندوق: <strong class="num">{{ number_format($cashBalance, 2) }} د.م</strong></span>
            <span>🏦 البنك: <strong class="num">{{ number_format($bankBalance, 2) }} د.م</strong></span>
        </div>
    </div>

    {{-- KPI 2: Monthly Collections & Collection Rate --}}
    <div class="kpi-card">
        <div class="kpi-card-header">
            <span class="kpi-label">مداخيل الشهر الحالي ({{ now()->locale('ar')->translatedFormat('F Y') }})</span>
            <div class="kpi-icon-box teal">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </div>
        </div>
        <div class="kpi-value num" style="display:flex; align-items:baseline; justify-content:space-between;">
            <span>{{ number_format($monthRevenue, 2) }} <span class="unit">د.م</span></span>
            <span class="badge {{ $collectionRate >= 70 ? 'badge-success' : ($collectionRate >= 40 ? 'badge-warning' : 'badge-danger') }}" style="font-size:0.8rem; padding:0.25rem 0.65rem;">
                {{ $collectionRate }}% محصل
            </span>
        </div>
        {{-- Progress Bar --}}
        <div style="background:var(--bg-secondary); height:6px; border-radius:999px; overflow:hidden; margin:0.65rem 0 0.5rem 0;">
            <div style="background:linear-gradient(90deg, #0d9488, #10b981); height:100%; border-radius:999px; width:{{ min(100, $collectionRate) }}%; transition:width 0.4s ease;"></div>
        </div>
        <div class="kpi-footer" style="display:flex; justify-content:space-between; font-size:0.82rem;">
            <span>المستهدف: <strong class="num">{{ number_format($monthlyExpectedTotal, 2) }} د.م</strong></span>
            <span>{{ $paidUnitsCount }} من أصل {{ $billableCount }} شقة</span>
        </div>
    </div>

    {{-- KPI 3: Monthly Expenses --}}
    <div class="kpi-card">
        <div class="kpi-card-header">
            <span class="kpi-label">مصاريف ونفقات الشهر الحالي</span>
            <div class="kpi-icon-box rose">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                </svg>
            </div>
        </div>
        <div class="kpi-value num">
            {{ number_format($monthExpenses, 2) }} <span class="unit">د.م</span>
        </div>
        <div class="kpi-footer" style="padding-top:0.75rem; border-top:1px dashed var(--border-color); margin-top:0.6rem; display:flex; justify-content:space-between; font-size:0.82rem;">
            <span>الصافي الشهري:</span>
            <strong class="num" style="color: {{ $netMonthly >= 0 ? 'var(--color-success)' : 'var(--color-danger)' }}; font-weight:800;">
                {{ $netMonthly >= 0 ? '+' : '' }}{{ number_format($netMonthly, 2) }} د.م ({{ $netMonthly >= 0 ? 'فائض' : 'عجز' }})
            </strong>
        </div>
    </div>

    {{-- KPI 4: Pending Arrears --}}
    <div class="kpi-card">
        <div class="kpi-card-header">
            <span class="kpi-label">المتأخرات غير المحصلة</span>
            <div class="kpi-icon-box amber">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>
        <div class="kpi-value num" style="color: {{ $unpaidUnitsCount > 0 ? 'var(--color-warning)' : 'var(--color-success)' }};">
            {{ number_format($totalArrears, 2) }} <span class="unit">د.م</span>
        </div>
        <div class="kpi-footer" style="padding-top:0.75rem; border-top:1px dashed var(--border-color); margin-top:0.6rem; display:flex; justify-content:space-between; font-size:0.82rem;">
            <span>الشقق في الانتظار:</span>
            <a href="{{ route('portal.apartments.index') }}" style="color:var(--brand-primary); font-weight:800; text-decoration:none;">
                <strong class="num">{{ $unpaidUnitsCount }}</strong> شقة ➔
            </a>
        </div>
    </div>
</div>

{{-- 4. Operational Dual-Column Grid --}}
<div style="display:grid; grid-template-columns: 1fr; gap:1.5rem; align-items:start;" class="dashboard-operational-grid">
    <style>
        @media (min-width: 1024px) {
            .dashboard-operational-grid {
                grid-template-columns: 2fr 1fr !important;
            }
        }
        .action-launchpad-card:hover {
            transform: translateY(-2px);
            border-color: var(--brand-primary) !important;
            box-shadow: var(--shadow-md) !important;
        }
    </style>

    {{-- Main Column (Right) --}}
    <div style="display:flex; flex-direction:column; gap:1.5rem;">
        {{-- Card 1: Recent Collections & Receipts --}}
        <div class="card" style="margin-bottom:0;">
            <div class="card-header" style="display:flex; align-items:center; justify-content:space-between;">
                <div class="card-title" style="display:flex; align-items:center; gap:0.5rem;">
                    <span>💳 آخر المقبوضات والوصولات المسجلة</span>
                </div>
                <a href="{{ route('portal.payments.index') }}" class="btn btn-sm btn-secondary" style="font-size:0.8rem; font-weight:700;">
                    جميع المقبوضات ({{ $paidUnitsCount }}) ➔
                </a>
            </div>
            <div class="table-responsive">
                <table class="table" style="margin:0; font-size:0.88rem;">
                    <thead>
                        <tr style="background:var(--bg-secondary);">
                            <th style="min-width:70px;">الوصل</th>
                            <th style="min-width:110px;">الشقة</th>
                            <th style="min-width:140px;">الساكن / المؤدي</th>
                            <th style="min-width:110px;">المبلغ</th>
                            <th style="min-width:95px;">طريقة الدفع</th>
                            <th style="min-width:90px;">التاريخ</th>
                            <th style="text-align:center; min-width:90px;">تحميل الوصل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                            <tr>
                                <td class="num" style="font-weight:800; color:var(--brand-primary);">
                                    #{{ $payment->receipt_number }}
                                </td>
                                <td>
                                    <span class="badge badge-neutral num" style="font-weight:800;">
                                        شقة {{ $payment->apartment?->unit_number ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <strong style="font-size:0.88rem; color:var(--text-primary);">
                                        {{ $payment->apartment?->residents?->first()?->full_name ?? 'المالك' }}
                                    </strong>
                                </td>
                                <td>
                                    <span class="num" style="font-weight:900; color:var(--color-success); font-size:0.95rem;">
                                        {{ number_format($payment->amount_paid, 2) }} د.م
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info" style="font-size:0.75rem;">
                                        {{ match($payment->payment_method) {
                                            'CASH' => '💵 نقداً',
                                            'BANK_TRANSFER' => '🏦 تحويل',
                                            'CHEQUE' => '📝 شيك',
                                            default => $payment->payment_method
                                        } }}
                                    </span>
                                </td>
                                <td class="num" style="color:var(--text-muted); font-size:0.82rem;">
                                    {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y/m/d') : '—' }}
                                </td>
                                <td style="text-align:center;">
                                    <a href="{{ route('receipts.download', ['payment' => $payment->id, 'download' => 1]) }}" 
                                       class="btn btn-sm btn-secondary" 
                                       style="padding:0.25rem 0.65rem; font-size:0.78rem; font-weight:700; display:inline-flex; align-items:center; gap:0.25rem;" 
                                       title="تحميل وصل الأداء بصيغة PDF">
                                        <span>📥</span>
                                        <span>PDF</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center; padding:2.5rem 1rem; color:var(--text-muted);">
                                    <div style="font-size:2rem; margin-bottom:0.4rem; opacity:0.8;">📭</div>
                                    <div style="font-weight:700; margin-bottom:0.2rem;">لا توجد مقبوضات مسجلة بعد لهذا الشهر</div>
                                    <div style="font-size:0.82rem;">يمكنك تسجيل استخلاص جديد من زر "استخلاص واجب شهري" بالأعلى.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Card 2: Compact Monthly Flow Chart --}}
        <div class="card" style="margin-bottom:0;">
            <div class="card-header" style="display:flex; align-items:center; justify-content:space-between;">
                <div class="card-title">
                    <span>📊 التدفق المالي: المداخيل والمصاريف (آخر 6 أشهر)</span>
                </div>
                <a href="{{ route('portal.analytics.index') }}" class="btn btn-sm btn-secondary" style="font-size:0.8rem;">
                    التحليلات المفصلة ➔
                </a>
            </div>
            <div class="card-body" style="padding:1.25rem;">
                <div style="height:250px; width:100%;">
                    <canvas id="monthlyFlowCanvas"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar Column (Left) --}}
    <div style="display:flex; flex-direction:column; gap:1.5rem;">
        {{-- Card 3: Pending Resident Join Requests --}}
        <div class="card" style="margin-bottom:0;">
            <div class="card-header" style="display:flex; align-items:center; justify-content:space-between;">
                <div class="card-title" style="display:flex; align-items:center; gap:0.4rem;">
                    <span>⏳ طلبات الانضمام المعلقة</span>
                </div>
                @if($pendingResidents > 0)
                    <span class="badge badge-warning num" style="font-weight:800;">{{ $pendingResidents }} طلب</span>
                @endif
            </div>
            <div class="card-body" style="padding:1rem;">
                @forelse($pendingUsers as $pUser)
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:0.75rem 0.85rem; border:1px solid var(--border-color); border-radius:10px; margin-bottom:0.6rem; background:var(--bg-secondary);">
                        <div>
                            <div style="font-weight:800; font-size:0.9rem; color:var(--text-primary);">{{ $pUser->full_name }}</div>
                            <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;">
                                <span>📞 {{ $pUser->phone }}</span>
                                @if($pUser->apartments->isNotEmpty())
                                    <span> • شقة {{ $pUser->apartments->first()->unit_number }}</span>
                                @endif
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.35rem;">
                            <form method="POST" action="{{ route('portal.residents.approve', $pUser->id) }}" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" style="padding:0.25rem 0.6rem; font-size:0.75rem; border-radius:6px; font-weight:700;">
                                    قبول ✓
                                </button>
                            </form>
                            <form method="POST" action="{{ route('portal.residents.reject', $pUser->id) }}" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger" style="padding:0.25rem 0.55rem; font-size:0.75rem; border-radius:6px;">
                                    ✕
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div style="text-align:center; padding:1.5rem 0.75rem; color:var(--text-muted);">
                        <div style="font-size:1.6rem; margin-bottom:0.3rem;">🎉</div>
                        <div style="font-weight:700; font-size:0.88rem; color:var(--text-primary); margin-bottom:0.15rem;">لا توجد طلبات انضمام معلقة</div>
                        <div style="font-size:0.78rem;">جميع السكان مسجلين ومفعلين في النظام.</div>
                    </div>
                @endforelse

                <div style="margin-top:0.75rem; text-align:center; padding-top:0.6rem; border-top:1px dashed var(--border-color);">
                    <a href="{{ route('portal.residents.index') }}" style="font-size:0.82rem; color:var(--brand-primary); font-weight:800; text-decoration:none;">
                        إدارة جميع السكان والبطاقات ➔
                    </a>
                </div>
            </div>
        </div>

        {{-- Card 4: Unpaid Apartments Followup --}}
        <div class="card" style="margin-bottom:0;">
            <div class="card-header" style="display:flex; align-items:center; justify-content:space-between;">
                <div class="card-title" style="display:flex; align-items:center; gap:0.4rem;">
                    <span>⚠️ شقق في انتظار أداء هذا الشهر</span>
                </div>
                <span class="badge badge-neutral num" style="font-weight:700; font-size:0.75rem;">
                    {{ $unpaidUnitsCount }} متبقية
                </span>
            </div>
            <div class="card-body" style="padding:1rem;">
                @if($unpaidApartments->isNotEmpty())
                    <div style="display:flex; flex-direction:column; gap:0.5rem;">
                        @foreach($unpaidApartments as $uApt)
                            <div style="display:flex; align-items:center; justify-content:space-between; padding:0.65rem 0.85rem; border:1px solid var(--border-color); border-radius:10px; background:var(--bg-secondary);">
                                <div>
                                    <div style="display:flex; align-items:center; gap:0.4rem;">
                                        <span class="badge badge-warning num" style="font-weight:800; font-size:0.75rem; padding:0.15rem 0.45rem;">
                                            شقة {{ $uApt->unit_number }}
                                        </span>
                                        <span style="font-size:0.85rem; font-weight:700; color:var(--text-primary);">
                                            {{ $uApt->residents->first()?->full_name ?? 'المالك' }}
                                        </span>
                                    </div>
                                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                                        الواجب: <strong class="num" style="color:var(--text-primary);">{{ number_format($uApt->monthly_fee, 2) }} د.م</strong>
                                        @if($uApt->residents->first()?->phone)
                                            • <span>📞 {{ $uApt->residents->first()->phone }}</span>
                                        @endif
                                    </div>
                                </div>
                                <button type="button" 
                                        @click="openPaymentForApartment('{{ $uApt->id }}', {{ (float) $uApt->monthly_fee }})" 
                                        class="btn btn-sm btn-secondary" 
                                        style="padding:0.25rem 0.55rem; font-size:0.75rem; border-radius:6px; font-weight:700; cursor:pointer;" 
                                        title="تسجيل استخلاص فوري لهذه الشقة">
                                    + استخلاص
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="text-align:center; padding:1.5rem 0.75rem; color:var(--text-muted);">
                        <div style="font-size:1.6rem; margin-bottom:0.3rem;">✨</div>
                        <div style="font-weight:700; font-size:0.88rem; color:var(--color-success); margin-bottom:0.15rem;">ممتاز! لا توجد متأخرات</div>
                        <div style="font-size:0.78rem;">تم تحصيل الواجبات الشهرية بالكامل لهذا الشهر.</div>
                    </div>
                @endif

                <div style="margin-top:0.75rem; text-align:center; padding-top:0.6rem; border-top:1px dashed var(--border-color);">
                    <a href="{{ route('portal.apartments.index') }}" style="font-size:0.82rem; color:var(--brand-primary); font-weight:800; text-decoration:none;">
                        كشف جميع الشقق وحالات الأداء ➔
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</div> {{-- End x-data="dashboardPage()" --}}

@endsection

@push('scripts')
<script>
    function dashboardPage() {
        return {
            paymentModal: false,
            expenseModal: false,
            selectedApartmentId: '',
            selectedApartmentFee: 0,
            amountPaid: '',
            selectedMonths: ['{{ now()->format('Y-m') }}'],
            selectedYear: {{ now()->year }},
            arabicMonthNames: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليوز', 'غشت', 'شتنبر', 'أكتوبر', 'نونبر', 'دجنبر'],

            get currentYearMonths() {
                let months = [];
                for (let m = 1; m <= 12; m++) {
                    let mStr = m < 10 ? '0' + m : '' + m;
                    months.push({
                        key: this.selectedYear + '-' + mStr,
                        name: this.arabicMonthNames[m - 1]
                    });
                }
                return months;
            },

            openQuickPayment() {
                this.selectedApartmentId = '';
                this.selectedApartmentFee = 0;
                this.amountPaid = '';
                this.selectedMonths = ['{{ now()->format('Y-m') }}'];
                this.selectedYear = {{ now()->year }};
                this.paymentModal = true;
            },

            openPaymentForApartment(aptId, fee) {
                this.selectedApartmentId = aptId;
                this.selectedApartmentFee = parseFloat(fee);
                this.selectedMonths = ['{{ now()->format('Y-m') }}'];
                this.selectedYear = {{ now()->year }};
                this.recalculateTotal();
                this.paymentModal = true;
            },

            onApartmentChange(e) {
                let opt = e.target.selectedOptions[0];
                if (opt && opt.dataset.fee) {
                    this.selectedApartmentFee = parseFloat(opt.dataset.fee);
                    this.recalculateTotal();
                }
            },

            toggleMonth(mKey) {
                let idx = this.selectedMonths.indexOf(mKey);
                if (idx > -1) {
                    if (this.selectedMonths.length > 1) {
                        this.selectedMonths.splice(idx, 1);
                    }
                } else {
                    this.selectedMonths.push(mKey);
                }
                this.recalculateTotal();
            },

            isMonthSelected(mKey) {
                return this.selectedMonths.includes(mKey);
            },

            changeYear(delta) {
                this.selectedYear += delta;
            },

            selectCurrentMonthOnly() {
                this.selectedYear = {{ now()->year }};
                this.selectedMonths = ['{{ now()->format('Y-m') }}'];
                this.recalculateTotal();
            },

            recalculateTotal() {
                if (this.selectedApartmentFee > 0) {
                    this.amountPaid = (this.selectedApartmentFee * this.selectedMonths.length).toFixed(2);
                }
            }
        };
    }
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('monthlyFlowCanvas');
        if (!ctx) return;

        const labels = @json($chartLabels);
        const inflows = @json($chartInflows);
        const outflows = @json($chartOutflows);

        const textColor = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim() || '#f8fafc';
        const mutedColor = getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim() || '#94a3b8';
        const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim() || 'rgba(255, 255, 255, 0.08)';

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'المداخيل المحصلة (د.م)',
                        data: inflows,
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.55,
                    },
                    {
                        label: 'المصاريف والنفقات (د.م)',
                        data: outflows,
                        backgroundColor: 'rgba(244, 63, 94, 0.85)',
                        borderColor: '#f43f5e',
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.55,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        rtl: true,
                        labels: {
                            font: { family: 'Cairo', size: 12, weight: 'bold' },
                            color: textColor,
                            boxWidth: 14,
                            padding: 12
                        }
                    },
                    tooltip: {
                        rtl: true,
                        titleFont: { family: 'Cairo', weight: 'bold' },
                        bodyFont: { family: 'Cairo' },
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Cairo', size: 11 },
                            color: mutedColor
                        }
                    },
                    y: {
                        grid: { color: gridColor },
                        ticks: {
                            font: { family: 'Cairo', size: 11 },
                            color: mutedColor
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
