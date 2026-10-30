@extends('layouts.dashboard')

@section('title', 'مركز الإعدادات الشاملة')

@section('content')

@php
    $initialTab = 'residence';
    if ($errors->has('current_password') || $errors->has('password') || $errors->has('password_confirmation')) {
        $initialTab = 'password';
    } elseif ($errors->has('first_name') || $errors->has('last_name') || $errors->has('phone')) {
        $initialTab = 'profile';
    } elseif ($errors->has('stamp')) {
        $initialTab = 'stamp';
    }
@endphp

<div x-data="{
    activeTab: window.location.hash ? window.location.hash.replace('#', '') : '{{ $initialTab }}',
    setTab(tab) {
        this.activeTab = tab;
        window.location.hash = tab;
    }
}">

    {{-- Page Header --}}
    <div class="page-header" style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 class="page-title">⚙️ مركز إعدادات الإقامة والحساب</h1>
            <p class="page-subtitle">إدارة شاملة وموحدة لبيانات الإقامة، الحساب البنكي، طابع السانديك، ومعلومات الحساب والأمان.</p>
        </div>
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <span class="badge badge-primary" style="font-size:0.85rem; padding:0.4rem 0.85rem;">
                🏢 {{ $tenant->name }}
            </span>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div style="background:rgba(16, 185, 129, 0.15); border:1px solid #10b981; color:#10b981; padding:0.85rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-weight:700; display:flex; align-items:center; gap:0.6rem;">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div style="background:rgba(239, 68, 68, 0.15); border:1px solid #ef4444; color:#ef4444; padding:0.85rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-weight:700; display:flex; align-items:center; gap:0.6rem;">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Profile & Residence Quick Overview Card --}}
    <div class="card" style="margin-bottom:1.5rem; padding:1.25rem 1.5rem; background:linear-gradient(135deg, rgba(15, 118, 110, 0.12) 0%, rgba(14, 165, 233, 0.08) 100%); border:1px solid rgba(15, 118, 110, 0.25);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.25rem;">
            <div style="display:flex; align-items:center; gap:1rem;">
                <div style="width:54px; height:54px; border-radius:14px; background:var(--brand-primary, #0F766E); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:1.6rem; font-weight:bold; box-shadow:0 4px 12px rgba(15, 118, 110, 0.35);">
                    {{ mb_substr($user->first_name ?? 'س', 0, 1) }}
                </div>
                <div>
                    <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                        <h3 style="margin:0; font-size:1.15rem; font-weight:800; color:var(--text-main);">{{ $user->full_name }}</h3>
                        <span class="badge badge-success" style="font-size:0.75rem;">سانديك الإقامة</span>
                        @if($tenant->stamp_image_path)
                            <span class="badge badge-primary" style="font-size:0.75rem;">🖋️ الخاتم مفعل</span>
                        @endif
                    </div>
                    <div style="margin-top:0.25rem; font-size:0.85rem; color:var(--text-muted); display:flex; gap:1rem; flex-wrap:wrap;">
                        <span>📧 {{ $user->email }}</span>
                        @if($user->phone)
                            <span>📱 {{ $user->phone }}</span>
                        @endif
                        <span>📍 {{ $tenant->city ?? 'المدينة غير محددة' }}</span>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
                <button type="button" @click="setTab('residence')" class="btn btn-sm btn-outline" style="border-radius:8px;">
                    🏢 الإقامة
                </button>
                <button type="button" @click="setTab('profile')" class="btn btn-sm btn-outline" style="border-radius:8px;">
                    👤 حسابي
                </button>
                <button type="button" @click="setTab('password')" class="btn btn-sm btn-outline" style="border-radius:8px;">
                    🔐 كلمة المرور
                </button>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation Bar --}}
    <div style="display:flex; gap:0.5rem; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.6rem; overflow-x:auto;">
        <button type="button" @click="setTab('residence')"
                :class="activeTab === 'residence' ? 'btn-primary' : 'btn-secondary'"
                class="btn" style="padding:0.6rem 1.25rem; font-weight:700; white-space:nowrap; border-radius:10px;">
            🏢 بيانات الإقامة والبنك
        </button>
        <button type="button" @click="setTab('stamp')"
                :class="activeTab === 'stamp' ? 'btn-primary' : 'btn-secondary'"
                class="btn" style="padding:0.6rem 1.25rem; font-weight:700; white-space:nowrap; border-radius:10px;">
            🖋️ طابع وخاتم السانديك
        </button>
        <button type="button" @click="setTab('profile')"
                :class="activeTab === 'profile' ? 'btn-primary' : 'btn-secondary'"
                class="btn" style="padding:0.6rem 1.25rem; font-weight:700; white-space:nowrap; border-radius:10px;">
            👤 الحساب الشخصي (المسير)
        </button>
        <button type="button" @click="setTab('password')"
                :class="activeTab === 'password' ? 'btn-primary' : 'btn-secondary'"
                class="btn" style="padding:0.6rem 1.25rem; font-weight:700; white-space:nowrap; border-radius:10px;">
            🔐 الأمان وكلمة المرور
        </button>
        <button type="button" @click="setTab('info')"
                :class="activeTab === 'info' ? 'btn-primary' : 'btn-secondary'"
                class="btn" style="padding:0.6rem 1.25rem; font-weight:700; white-space:nowrap; border-radius:10px;">
            📥 تصدير الأرشيف ومعلومات النظام
        </button>
    </div>

    {{-- ───────────────────────────────────────────────────────────── --}}
    {{-- TAB 1: Residence Settings & Bank Info                         --}}
    {{-- ───────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'residence'" x-cloak>
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>🏢 المعلومات العامة للإقامة والحساب البنكي (RIB)</span>
                </div>
            </div>
            <form method="POST" action="{{ route('portal.settings.update') }}">
                @csrf
                <div class="card-body">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
                        <div class="form-group">
                            <label class="form-label">اسم الإقامة أو المجمع السكني <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $tenant->name) }}" required placeholder="مثال: إقامة النخيل، برج الياسمين...">
                        </div>

                        <div class="form-group">
                            <label class="form-label">مدينة الإقامة</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city', $tenant->city) }}" placeholder="مثال: الدار البيضاء، الرباط، طنجة، مراكش...">
                            <small style="color:var(--text-muted); font-size:0.75rem;">تظهر تلقائياً في التوصيلات الرسمية والتقارير المالية (حرر بمدينة ...)</small>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem; margin-top:0.75rem;">
                        <div class="form-group">
                            <label class="form-label">نوع الملكية العقارية <span style="color:#ef4444;">*</span></label>
                            <select name="property_type" class="form-select" required>
                                <option value="RESIDENTIAL_COMPLEX" {{ $tenant->property_type == 'RESIDENTIAL_COMPLEX' ? 'selected' : '' }}>مجمع سكني مغلق (Résidence fermée)</option>
                                <option value="SINGLE_BUILDING" {{ $tenant->property_type == 'SINGLE_BUILDING' ? 'selected' : '' }}>عمارة منفردة (Immeuble)</option>
                                <option value="HOUSING_ESTATE_VILLAS" {{ $tenant->property_type == 'HOUSING_ESTATE_VILLAS' ? 'selected' : '' }}>تجزئة فيلات (Lotissement de villas)</option>
                                <option value="COMMERCIAL_RESIDENTIAL" {{ $tenant->property_type == 'COMMERCIAL_RESIDENTIAL' ? 'selected' : '' }}>مجمع تجاري وسكني مختلط</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">رقم الحساب البنكي للإقامة (RIB - 24 رقماً)</label>
                            <input type="text" name="bank_rib" class="form-control num" value="{{ old('bank_rib', $tenant->bank_rib) }}" placeholder="24 رقماً للتحويلات البنكية">
                            <small style="color:var(--text-muted); font-size:0.75rem;">يظهر للسكان في تطبيق الهاتف لتسهيل التحويلات البنكية المباشرة للحساب الرسمي</small>
                        </div>
                    </div>

                    <hr style="border:0; border-top:1px solid var(--border-color); margin:1.5rem 0;">

                    <h4 style="font-size:1rem; font-weight:800; margin-bottom:0.75rem; color:var(--text-main);">
                        💵 القواعد المالية ومساهمات الشقق (Cotisations)
                    </h4>
                    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1rem;">
                        المعايير المعتمدة لاحتساب الواجبات الشهرية وتاريخ حلول أجل الأداء للشقق داخل الإقامة.
                    </p>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:1.25rem;">
                        <div class="form-group">
                            <label class="form-label">المبلغ الافتراضي للواجب الشهري (درهم) <span style="color:#ef4444;">*</span></label>
                            <input type="number" step="0.01" name="default_monthly_fee" class="form-control num" value="{{ old('default_monthly_fee', $tenant->default_monthly_fee ?? 200.00) }}" required placeholder="مثال: 200.00">
                            <small style="color:var(--text-muted); font-size:0.75rem;">يُطبق تلقائياً على كل شقة جديدة تُنشأ داخل الإقامة</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">يوم الاستحقاق الشهري للأداء <span style="color:#ef4444;">*</span></label>
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                <span style="font-size:0.9rem; color:var(--text-muted);">كل يوم</span>
                                <input type="number" name="payment_due_day" class="form-control num" style="max-width:110px; text-align:center;" min="1" max="28" value="{{ old('payment_due_day', $tenant->payment_due_day ?? 5) }}" required>
                                <span style="font-size:0.9rem; color:var(--text-muted);">من كل شهر</span>
                            </div>
                            <small style="color:var(--text-muted); font-size:0.75rem;">تُعتبر الدفعة متأخرة وتُطلق تنبيهات الأداء بعد حلول هذا اليوم</small>
                        </div>
                    </div>

                    <h4 style="font-size:1rem; font-weight:800; margin-bottom:0.75rem; color:var(--text-main);">
                        💰 الأرصدة الافتتاحية للميزانية
                    </h4>
                    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1rem;">
                        يتم ضبط الأرصدة الافتتاحية عند بدء استخدام النظام. بمجرد تسجيل أول معاملة مالية فعلية، يتم قفلها تلقائياً للحفاظ على نزاهة الحسابات.
                    </p>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:1.25rem;">
                        <div class="form-group">
                            <label class="form-label">الرصيد الافتتاحي للصندوق والخزينة (درهم)</label>
                            <input type="number" step="0.01" name="initial_treasury_balance" class="form-control num" value="{{ $tenant->initial_treasury_balance }}" {{ $tenant->initial_balance_locked ? 'disabled' : '' }}>
                            @if($tenant->initial_balance_locked)
                                <small style="color:#10b981; font-size:0.75rem; font-weight:bold;">🔒 مقفل تلقائياً لاحتساب المعاملات</small>
                            @endif
                        </div>

                        <div class="form-group">
                            <label class="form-label">الرصيد الافتتاحي للبنك (درهم)</label>
                            <input type="number" step="0.01" name="initial_bank_balance" class="form-control num" value="{{ $tenant->initial_bank_balance }}" {{ $tenant->initial_balance_locked ? 'disabled' : '' }}>
                            @if($tenant->initial_balance_locked)
                                <small style="color:#10b981; font-size:0.75rem; font-weight:bold;">🔒 مقفل تلقائياً لاحتساب المعاملات</small>
                            @endif
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:1.25rem; background:rgba(15, 118, 110, 0.05); padding:1rem; border-radius:10px; border:1px solid rgba(15, 118, 110, 0.2);">
                        <label style="display:flex; align-items:center; gap:0.75rem; cursor:pointer;">
                            <input type="checkbox" name="is_transparency_enabled" value="1" {{ $tenant->is_transparency_enabled ? 'checked' : '' }} style="width:20px; height:20px; accent-color:var(--brand-primary);">
                            <div>
                                <span style="font-weight:800; font-size:0.95rem; color:var(--text-main);">تفعيل وضع الشفافية المالية (Financial Transparency)</span>
                                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.2rem;">
                                    يسمح لجميع السكان بالاطلاع على دفتر المصاريف الشهرية ومرفقات الفواتير في هواتفهم، مما يعزز الثقة ويزيد نسبة الاستخلاص.
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="card-footer" style="display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary" style="padding:0.65rem 2rem; font-weight:700;">
                        حفظ إعدادات الإقامة ✓
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ───────────────────────────────────────────────────────────── --}}
    {{-- TAB 2: Official Stamp, Logo & Receipt Customization           --}}
    {{-- ───────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'stamp'" x-cloak>
        {{-- Section 1: Official Stamp --}}
        <div style="margin-bottom:1.5rem;">
            <h3 style="font-size:1.1rem; font-weight:800; margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem; color:var(--text-main);">
                <span>🖋️</span>
                <span>طابع وخاتم السانديك المعتمد (Cachet du Syndic)</span>
            </h3>
            <div class="grid-2">
                {{-- Current Stamp Preview Card --}}
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <span>معاينة الخاتم الحالي</span>
                        </div>
                    </div>
                    <div class="card-body" style="text-align:center;">
                        @if($tenant->stamp_image_path)
                            <div style="margin-bottom:1.25rem; padding:1.5rem; background:var(--bg-app); border-radius:var(--radius-lg); display:inline-block; border:2px dashed var(--border-color);">
                                <img src="{{ asset('storage/' . $tenant->stamp_image_path) }}" alt="طابع السانديك" style="max-height:130px; max-width:200px; object-fit:contain; filter:drop-shadow(0 4px 8px rgba(0,0,0,0.12));">
                            </div>
                            <div style="margin-bottom:1.25rem;">
                                <span class="badge badge-success" style="font-size:0.8rem; padding:0.35rem 0.75rem;">
                                    ✓ الخاتم مفعل ومثبت على التوصيلات PDF
                                </span>
                            </div>

                            <form method="POST" action="{{ route('portal.settings.stamp.delete') }}"
                                  data-confirm="هل تؤكد حذف خاتم السانديك؟ لن يظهر الخاتم على التوصيلات الجديدة بعد الحذف."
                                  data-confirm-title="تأكيد حذف الخاتم"
                                  data-confirm-type="danger"
                                  data-confirm-password="true"
                                  data-confirm-btn="حذف الخاتم">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    حذف الخاتم الحالي 🗑️
                                </button>
                            </form>
                        @else
                            <div style="padding:2.5rem 1.5rem; background:var(--bg-app); border-radius:var(--radius-lg); border:2px dashed var(--border-color); margin-bottom:1rem;">
                                <div style="font-size:3rem; margin-bottom:0.5rem; opacity:0.6;">🖋️</div>
                                <h4 style="font-size:1.05rem; font-weight:800; margin-bottom:0.35rem;">لم يتم رفع خاتم السانديك بعد</h4>
                                <p style="font-size:0.82rem; color:var(--text-muted); max-width:300px; margin:0 auto;">
                                    ارفع صورة واضحة لخاتم الإقامة ليتم تضمينها تلقائياً على وصولات الأداء PDF لإعطائها صبغة قانونية ورسمية.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Upload / Change Stamp Card --}}
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <span>📤 {{ $tenant->stamp_image_path ? 'تغيير صورة الخاتم' : 'رفع خاتم جديد' }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('portal.settings.stamp.upload') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">اختر ملف صورة الخاتم <span style="color:#ef4444;">*</span></label>
                                <input type="file" name="stamp" class="form-control" accept="image/png,image/jpeg,image/webp" required>
                                <small style="color:var(--text-muted); font-size:0.75rem;">يفضل صورة PNG بخلفية شفافة (الحجم الأقصى: 2MB)</small>
                                @error('stamp')
                                    <small style="color:#ef4444; font-size:0.75rem; display:block; margin-top:0.35rem; font-weight:700;">{{ $message }}</small>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.7rem; font-weight:700; margin-top:1rem;">
                                {{ $tenant->stamp_image_path ? 'تحديث وتثبيت الخاتم الجديد ➔' : 'رفع وتفعيل الخاتم الرسمي ➔' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Residence Logo --}}
        <div style="margin-bottom:1.5rem;">
            <h3 style="font-size:1.1rem; font-weight:800; margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem; color:var(--text-main);">
                <span>🏢</span>
                <span>شعار الإقامة الرسمي (Logo de la Résidence)</span>
            </h3>
            <div class="grid-2">
                {{-- Logo Preview Card --}}
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <span>معاينة شعار الإقامة الحالي</span>
                        </div>
                    </div>
                    <div class="card-body" style="text-align:center;">
                        @if($tenant->logo_path)
                            <div style="margin-bottom:1.25rem; padding:1.5rem; background:var(--bg-app); border-radius:var(--radius-lg); display:inline-block; border:2px dashed var(--border-color);">
                                <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="شعار الإقامة" style="max-height:110px; max-width:200px; object-fit:contain;">
                            </div>
                            <div style="margin-bottom:1.25rem;">
                                <span class="badge badge-success" style="font-size:0.8rem; padding:0.35rem 0.75rem;">
                                    ✓ الشعار مفعل ويظهر في ترويسة الوصولات والتقارير
                                </span>
                            </div>

                            <form method="POST" action="{{ route('portal.settings.logo.delete') }}"
                                  data-confirm="هل تؤكد حذف شعار الإقامة؟"
                                  data-confirm-title="تأكيد حذف الشعار"
                                  data-confirm-type="danger"
                                  data-confirm-password="true"
                                  data-confirm-btn="حذف الشعار">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    حذف الشعار الحالي 🗑️
                                </button>
                            </form>
                        @else
                            <div style="padding:2.5rem 1.5rem; background:var(--bg-app); border-radius:var(--radius-lg); border:2px dashed var(--border-color); margin-bottom:1rem;">
                                <div style="font-size:3rem; margin-bottom:0.5rem; opacity:0.6;">🏛️</div>
                                <h4 style="font-size:1.05rem; font-weight:800; margin-bottom:0.35rem;">لم يتم رفع شعار خاص بالإقامة</h4>
                                <p style="font-size:0.82rem; color:var(--text-muted); max-width:300px; margin:0 auto;">
                                    ارفع شعار أو لوجو الإقامة ليظهر بجانب اسمها في رأس وصل الأداء PDF والتقارير المالية الدورية.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Upload / Change Logo Card --}}
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <span>📤 {{ $tenant->logo_path ? 'تغيير شعار الإقامة' : 'رفع شعار جديد' }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('portal.settings.logo.upload') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">اختر ملف صورة الشعار <span style="color:#ef4444;">*</span></label>
                                <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/webp" required>
                                <small style="color:var(--text-muted); font-size:0.75rem;">الصيغ المقبولة: PNG, JPG, WEBP (الحجم الأقصى: 2MB)</small>
                                @error('logo')
                                    <small style="color:#ef4444; font-size:0.75rem; display:block; margin-top:0.35rem; font-weight:700;">{{ $message }}</small>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.7rem; font-weight:700; margin-top:1rem;">
                                {{ $tenant->logo_path ? 'تحديث وتثبيت الشعار الجديد ➔' : 'رفع وتفعيل شعار الإقامة ➔' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Receipt Footer Note / Legal Mentions --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>📜 الملاحظات القانونية وتذييل وصل الأداء (Mentions Légales du Reçu)</span>
                </div>
            </div>
            <form method="POST" action="{{ route('portal.settings.update') }}">
                @csrf
                {{-- Hidden required fields to reuse update endpoint --}}
                <input type="hidden" name="name" value="{{ $tenant->name }}">
                <input type="hidden" name="city" value="{{ $tenant->city }}">
                <input type="hidden" name="property_type" value="{{ $tenant->property_type }}">
                <input type="hidden" name="bank_rib" value="{{ $tenant->bank_rib }}">
                <input type="hidden" name="default_monthly_fee" value="{{ $tenant->default_monthly_fee }}">
                <input type="hidden" name="payment_due_day" value="{{ $tenant->payment_due_day }}">
                <input type="hidden" name="is_transparency_enabled" value="{{ $tenant->is_transparency_enabled ? '1' : '0' }}">

                <div class="card-body">
                    <p style="font-size:0.875rem; color:var(--text-muted); margin-bottom:1rem;">
                        يمكنك كتابة نص مخصص يظهر في أسفل كل وصل أداء PDF يتم تحميله أو إرساله للساكن.
                    </p>

                    <div class="form-group">
                        <label class="form-label">نص تذييل الوصل المخصص:</label>
                        <textarea id="receipt_footer_note" name="receipt_footer_note" rows="3" class="form-control" placeholder="مثال: شكراً لالتزامكم بمواعيد الأداء ومساهمتكم في نظافة وصيانة إقامتكم.">{{ old('receipt_footer_note', $tenant->receipt_footer_note) }}</textarea>
                    </div>

                    <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; margin-top:0.75rem;">
                        <span style="font-size:0.8rem; font-weight:700; color:var(--text-muted);">اقتراحات سريعة بنقرة واحدة:</span>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('receipt_footer_note').value='شكراً لالتزامكم بمواعيد الأداء ومساهمتكم المستمرة في صيانة وحسن تدبير الإقامة.'">
                            ✨ عبارة شكر وتقدير
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('receipt_footer_note').value='لا يُعتبر هذا التوصيل مبرئاً للذمة بصفة نهائية إلا بعد تحصيل الشيك البنكي أو تأكيد التحويل.'">
                            ⚖️ إبراء الذمة بعد الصرف
                        </button>
                    </div>
                </div>
                <div class="card-footer" style="display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary" style="padding:0.65rem 2rem; font-weight:700;">
                        حفظ نص تذييل الوصل ✓
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ───────────────────────────────────────────────────────────── --}}
    {{-- TAB 3: Account Owner Profile Settings                         --}}
    {{-- ───────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'profile'" x-cloak>
        <div class="card" style="max-width:760px;">
            <div class="card-header">
                <div class="card-title">
                    <span>👤 المعلومات الشخصية لمسير الحساب (السانديك)</span>
                </div>
            </div>
            <form method="POST" action="{{ route('portal.settings.profile.update') }}">
                @csrf
                <div class="card-body">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:1.25rem;">
                        <div class="form-group">
                            <label class="form-label">الاسم الشخصي <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" required>
                            @error('first_name')
                                <small style="color:#ef4444; font-size:0.75rem; font-weight:700;">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">الاسم العائلي <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}" required>
                            @error('last_name')
                                <small style="color:#ef4444; font-size:0.75rem; font-weight:700;">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:1.25rem; margin-top:0.75rem;">
                        <div class="form-group">
                            <label class="form-label">رقم الهاتف للتواصل</label>
                            <input type="text" name="phone" class="form-control num" value="{{ old('phone', $user->phone) }}" placeholder="مثال: 0612345678">
                            <small style="color:var(--text-muted); font-size:0.75rem;">يمكن أن يظهر للسكان للاتصال المباشر أو عبر واتساب</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">البريد الإلكتروني (اسم المستخدم)</label>
                            <input type="email" class="form-control" value="{{ $user->email }}" disabled style="background:var(--bg-app); opacity:0.85; cursor:not-allowed;">
                            <small style="color:var(--text-muted); font-size:0.75rem;">البريد الإلكتروني ثابت ويستخدم كمعرف وحيد للدخول للنظام</small>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:0.75rem;">
                        <label class="form-label">نوع وصلاحية الحساب</label>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <span class="badge badge-primary">سانديك رئيسي (ADMIN_SYNDIC)</span>
                            <span style="font-size:0.85rem; color:var(--text-muted);">كامل صلاحيات إدارة الإقامة والشقق والمالية</span>
                        </div>
                    </div>

                    {{-- Section: Language & Theme Preferences --}}
                    <div style="margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid var(--border-color);" x-data="{
                        selectedLocale: '{{ old('locale', $user->locale ?? 'ar') }}',
                        selectedTheme: '{{ old('theme_preference', $user->theme_preference ?? 'dark') }}',
                        setTheme(th) {
                            this.selectedTheme = th;
                            document.getElementById('theme_preference_input').value = th;
                            document.documentElement.setAttribute('data-theme', th);
                            localStorage.setItem('theme', th);
                        }
                    }">
                        <h4 style="font-size:0.95rem; font-weight:800; color:var(--text-main); margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem;">
                            <span>🌐</span>
                            <span>تفضيلات اللغة والمظهر البصري (Langue & Thème)</span>
                        </h4>

                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:1.25rem;">
                            {{-- Language Selector --}}
                            <div>
                                <label class="form-label" style="margin-bottom:0.6rem;">لغة واجهة المنصة <span style="color:#ef4444;">*</span></label>
                                <input type="hidden" name="locale" :value="selectedLocale" id="locale_input">
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                    <div @click="selectedLocale = 'ar'; document.getElementById('locale_input').value = 'ar'"
                                         :style="selectedLocale === 'ar' ? 'border-color:var(--brand-primary); background:rgba(15, 118, 110, 0.1);' : 'border-color:var(--border-color); background:var(--bg-app);'"
                                         style="padding:0.85rem; border-radius:10px; border:2px solid; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:0.6rem;">
                                        <span style="font-size:1.4rem;">🇲🇦</span>
                                        <div>
                                            <div style="font-weight:700; font-size:0.88rem;">العربية</div>
                                            <small style="color:var(--text-muted); font-size:0.72rem;">الافتراضية</small>
                                        </div>
                                    </div>

                                    <div @click="selectedLocale = 'fr'; document.getElementById('locale_input').value = 'fr'"
                                         :style="selectedLocale === 'fr' ? 'border-color:var(--brand-primary); background:rgba(15, 118, 110, 0.1);' : 'border-color:var(--border-color); background:var(--bg-app);'"
                                         style="padding:0.85rem; border-radius:10px; border:2px solid; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:0.6rem;">
                                        <span style="font-size:1.4rem;">🇫🇷</span>
                                        <div>
                                            <div style="font-weight:700; font-size:0.88rem;">Français</div>
                                            <small style="color:var(--text-muted); font-size:0.72rem;">Français</small>
                                        </div>
                                    </div>
                                </div>
                                @error('locale')
                                    <small style="color:#ef4444; font-size:0.75rem; font-weight:700; display:block; margin-top:0.35rem;">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Theme Selector --}}
                            <div>
                                <label class="form-label" style="margin-bottom:0.6rem;">المظهر العام للواجهة <span style="color:#ef4444;">*</span></label>
                                <input type="hidden" name="theme_preference" :value="selectedTheme" id="theme_preference_input">
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
                                    <div @click="setTheme('dark')"
                                         :style="selectedTheme === 'dark' ? 'border-color:var(--brand-primary); background:rgba(15, 118, 110, 0.1);' : 'border-color:var(--border-color); background:var(--bg-app);'"
                                         style="padding:0.85rem; border-radius:10px; border:2px solid; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:0.6rem;">
                                        <span style="font-size:1.4rem;">🌙</span>
                                        <div>
                                            <div style="font-weight:700; font-size:0.88rem;">الوضع الليلي</div>
                                            <small style="color:var(--text-muted); font-size:0.72rem;">Dark Mode</small>
                                        </div>
                                    </div>

                                    <div @click="setTheme('light')"
                                         :style="selectedTheme === 'light' ? 'border-color:var(--brand-primary); background:rgba(15, 118, 110, 0.1);' : 'border-color:var(--border-color); background:var(--bg-app);'"
                                         style="padding:0.85rem; border-radius:10px; border:2px solid; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:0.6rem;">
                                        <span style="font-size:1.4rem;">☀️</span>
                                        <div>
                                            <div style="font-weight:700; font-size:0.88rem;">الوضع النهاري</div>
                                            <small style="color:var(--text-muted); font-size:0.72rem;">Light Mode</small>
                                        </div>
                                    </div>
                                </div>
                                @error('theme_preference')
                                    <small style="color:#ef4444; font-size:0.75rem; font-weight:700; display:block; margin-top:0.35rem;">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Section: Notification Preferences --}}
                    @php
                        $notifPrefs = $user->notification_preferences ?? [
                            'payments'      => true,
                            'residents'     => true,
                            'polls'         => true,
                            'announcements' => true,
                        ];
                    @endphp
                    <div style="margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid var(--border-color);">
                        <h4 style="font-size:0.95rem; font-weight:800; color:var(--text-main); margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem;">
                            <span>🔔</span>
                            <span>تفضيلات التنبيهات والإشعارات الفورية (Préférences des Notifications)</span>
                        </h4>

                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:1rem;">
                            {{-- Payments Notifications --}}
                            <label style="display:flex; align-items:flex-start; gap:0.75rem; padding:0.9rem; border-radius:10px; border:1px solid var(--border-color); background:var(--bg-app); cursor:pointer;">
                                <input type="checkbox" name="notify_payments" value="1" {{ old('notify_payments', $notifPrefs['payments'] ?? true) ? 'checked' : '' }} style="margin-top:0.25rem; width:1.15rem; height:1.15rem; accent-color:var(--brand-primary);">
                                <div>
                                    <div style="font-weight:700; font-size:0.88rem; color:var(--text-main);">💰 إشعارات الواجبات والأداءات المالية</div>
                                    <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:0.2rem;">تلقي تنبيهات عند تسجيل أداء واجب شهري أو تأكيد وصل دفع جديد للساكن.</small>
                                </div>
                            </label>

                            {{-- Residents Registration --}}
                            <label style="display:flex; align-items:flex-start; gap:0.75rem; padding:0.9rem; border-radius:10px; border:1px solid var(--border-color); background:var(--bg-app); cursor:pointer;">
                                <input type="checkbox" name="notify_residents" value="1" {{ old('notify_residents', $notifPrefs['residents'] ?? true) ? 'checked' : '' }} style="margin-top:0.25rem; width:1.15rem; height:1.15rem; accent-color:var(--brand-primary);">
                                <div>
                                    <div style="font-weight:700; font-size:0.88rem; color:var(--text-main);">👥 طلبات انضمام وربط السكان الجدد</div>
                                    <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:0.2rem;">تنبيه فوري عند تسجيل ساكن جديد بواسطة كود الإقامة لطلب الموافقة.</small>
                                </div>
                            </label>

                            {{-- Suggestions & Polls --}}
                            <label style="display:flex; align-items:flex-start; gap:0.75rem; padding:0.9rem; border-radius:10px; border:1px solid var(--border-color); background:var(--bg-app); cursor:pointer;">
                                <input type="checkbox" name="notify_polls" value="1" {{ old('notify_polls', $notifPrefs['polls'] ?? true) ? 'checked' : '' }} style="margin-top:0.25rem; width:1.15rem; height:1.15rem; accent-color:var(--brand-primary);">
                                <div>
                                    <div style="font-weight:700; font-size:0.88rem; color:var(--text-main);">💡 الاستطلاعات والمقترحات التشاركية</div>
                                    <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:0.2rem;">إشعار عند إضافة مقترح مجتمعي جديد أو تصويت من أحد سكان الإقامة.</small>
                                </div>
                            </label>

                            {{-- Announcements --}}
                            <label style="display:flex; align-items:flex-start; gap:0.75rem; padding:0.9rem; border-radius:10px; border:1px solid var(--border-color); background:var(--bg-app); cursor:pointer;">
                                <input type="checkbox" name="notify_announcements" value="1" {{ old('notify_announcements', $notifPrefs['announcements'] ?? true) ? 'checked' : '' }} style="margin-top:0.25rem; width:1.15rem; height:1.15rem; accent-color:var(--brand-primary);">
                                <div>
                                    <div style="font-weight:700; font-size:0.88rem; color:var(--text-main);">📢 الإعلانات الرسمية وتنبيهات النظام الهامة</div>
                                    <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:0.2rem;">إشعارات فورية حول إعلانات الصيانة، الجمع العام وتحديثات المنصة.</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-footer" style="display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary" style="padding:0.65rem 2rem; font-weight:700;">
                        تحديث المعلومات والتفضيلات ✓
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ───────────────────────────────────────────────────────────── --}}
    {{-- TAB 4: Security & Password Change                             --}}
    {{-- ───────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'password'" x-cloak>
        <div class="card" style="max-width:760px;">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <div class="card-title">
                    <span>🔐 تغيير كلمة المرور وتأمين الحساب</span>
                </div>
                <span class="badge badge-primary" style="font-size:0.75rem;">
                    إجراء أمني إلزامي
                </span>
            </div>
            <form method="POST" action="{{ route('portal.settings.password.update') }}">
                @csrf
                <div class="card-body">
                    <div style="background:rgba(99, 102, 241, 0.08); border:1px solid rgba(99, 102, 241, 0.2); border-radius:10px; padding:0.9rem 1.1rem; margin-bottom:1.5rem; display:flex; align-items:flex-start; gap:0.75rem;">
                        <span style="font-size:1.3rem;">🛡️</span>
                        <div style="font-size:0.875rem; color:var(--text-main); line-height:1.6;">
                            <strong>حماية حسابك:</strong> لتغيير كلمة المرور الخاصة بحسابك، يجب إدخال <strong>كلمة المرور الحالية</strong> للتأكد من هويتك، ثم تحديد وتأكيد كلمة المرور الجديدة.
                        </div>
                    </div>

                    @if ($errors->has('current_password'))
                        <div style="background:rgba(239, 68, 68, 0.12); border:1px solid #ef4444; color:#ef4444; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1.25rem; font-size:0.875rem; font-weight:700; display:flex; align-items:center; gap:0.5rem;">
                            <span>⚠️</span>
                            <span>{{ $errors->first('current_password') }}</span>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
                            <span>كلمة المرور الحالية <span style="color:#ef4444;">*</span></span>
                        </label>
                        <div style="position:relative;">
                            <input type="password" id="current_password" name="current_password" class="form-control" required placeholder="أدخل كلمة مرورك الحالية..." style="padding-left:2.75rem;">
                            <button type="button" onclick="togglePasswordVisibility('current_password', this)" style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-muted); font-size:1.15rem; padding:0; display:flex; align-items:center;" title="إظهار/إخفاء">
                                👁️
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">كلمة المرور الجديدة (8 أحرف على الأقل) <span style="color:#ef4444;">*</span></label>
                        <div style="position:relative;">
                            <input type="password" id="new_password" name="password" class="form-control" required minlength="8" placeholder="أدخل كلمة المرور الجديدة..." style="padding-left:2.75rem;">
                            <button type="button" onclick="togglePasswordVisibility('new_password', this)" style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-muted); font-size:1.15rem; padding:0; display:flex; align-items:center;" title="إظهار/إخفاء">
                                👁️
                            </button>
                        </div>
                        @error('password')
                            <small style="color:#ef4444; font-size:0.75rem; margin-top:0.35rem; display:block; font-weight:600;">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">تأكيد كلمة المرور الجديدة <span style="color:#ef4444;">*</span></label>
                        <div style="position:relative;">
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="8" placeholder="أعد إدخال كلمة المرور الجديدة..." style="padding-left:2.75rem;">
                            <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)" style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-muted); font-size:1.15rem; padding:0; display:flex; align-items:center;" title="إظهار/إخفاء">
                                👁️
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-footer" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                    <span style="font-size:0.8rem; color:var(--text-muted);">
                        🔒 تغيير كلمة المرور يلغي تلقائياً جلسات الأجهزة المحمولة السابقة لضمان الأمان.
                    </span>
                    <button type="submit" class="btn btn-primary" style="padding:0.65rem 1.75rem; font-weight:700;">
                        حفظ كلمة المرور الجديدة 🔐
                    </button>
                </div>
            </form>
        </div>

        {{-- Section 2: Active Sessions & Connected Devices Management --}}
        <div class="card" style="max-width:760px; margin-top:1.5rem;">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                <div class="card-title">
                    <span>🛡️ إدارة الأجهزة والجلسات النشطة (Sessions & Appareils Actifs)</span>
                </div>
                @php
                    $otherCount = (isset($webSessions) ? $webSessions->where('is_current', false)->count() : 0) + (isset($mobileTokens) ? $mobileTokens->count() : 0);
                @endphp
                <span class="badge {{ $otherCount > 0 ? 'badge-warning' : 'badge-success' }}" style="font-size:0.8rem;">
                    {{ $otherCount > 0 ? $otherCount . ' أجهزة أخرى متصلة' : 'فقط هذا الجهاز متصل' }}
                </span>
            </div>
            <div class="card-body">
                <p style="font-size:0.875rem; color:var(--text-muted); margin-bottom:1.25rem;">
                    قائمة بجميع الأجهزة والمتصفحات التي قامت بتسجيل الدخول إلى حسابك مؤخراً. إذا لاحظت أي نشاط غير مألوف، يمكنك إنهاء جميع الجلسات الأخرى فوراً.
                </p>

                <div style="display:flex; flex-direction:column; gap:0.85rem;">
                    {{-- Web Sessions --}}
                    @if(isset($webSessions) && $webSessions->count() > 0)
                        @foreach($webSessions as $session)
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.9rem 1.1rem; border-radius:10px; border:1px solid {{ $session->is_current ? 'var(--brand-primary)' : 'var(--border-color)' }}; background:{{ $session->is_current ? 'rgba(15, 118, 110, 0.08)' : 'var(--bg-app)' }};">
                                <div style="display:flex; align-items:center; gap:0.85rem;">
                                    <div style="width:40px; height:40px; border-radius:10px; background:rgba(99, 102, 241, 0.15); color:#6366f1; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
                                        💻
                                    </div>
                                    <div>
                                        <div style="font-weight:700; font-size:0.9rem; color:var(--text-main); display:flex; align-items:center; gap:0.5rem;">
                                            <span>متصفح الويب (لوحة التحكم)</span>
                                            @if($session->is_current)
                                                <span class="badge badge-success" style="font-size:0.7rem; padding:0.2rem 0.5rem;">هذا الجهاز الحالي ✓</span>
                                            @endif
                                        </div>
                                        <div style="font-size:0.78rem; color:var(--text-muted); margin-top:0.2rem;">
                                            <span>IP: {{ $session->ip_address ?? '127.0.0.1' }}</span> &bull;
                                            <span>آخر نشاط: {{ $session->last_activity_human ?? 'الآن' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    @if($session->is_current)
                                        <span style="font-size:0.8rem; font-weight:700; color:var(--color-success);">🟢 نشط الآن</span>
                                    @else
                                        <span style="font-size:0.8rem; color:var(--text-muted);">جلسة ويب مسجلة</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif

                    {{-- Mobile Tokens --}}
                    @if(isset($mobileTokens) && $mobileTokens->count() > 0)
                        @foreach($mobileTokens as $token)
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.9rem 1.1rem; border-radius:10px; border:1px solid var(--border-color); background:var(--bg-app);">
                                <div style="display:flex; align-items:center; gap:0.85rem;">
                                    <div style="width:40px; height:40px; border-radius:10px; background:rgba(16, 185, 129, 0.15); color:#10b981; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
                                        📱
                                    </div>
                                    <div>
                                        <div style="font-weight:700; font-size:0.9rem; color:var(--text-main);">
                                            <span>تطبيق الهاتف الذكي (Smart Syndic Mobile)</span>
                                        </div>
                                        <div style="font-size:0.78rem; color:var(--text-muted); margin-top:0.2rem;">
                                            <span>رمز الوصول: {{ $token->name }}</span> &bull;
                                            <span>تاريخ الربط: {{ $token->created_at ? $token->created_at->locale('ar')->diffForHumans() : 'مؤخراً' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="badge badge-primary" style="font-size:0.75rem;">تطبيق مصرح</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="card-footer" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <span style="font-size:0.8rem; color:var(--text-muted);">
                    🛡️ زر الطوارئ: إنهاء كل الأجهزة الأخرى فوراً في حال الشك في اختراق الحساب.
                </span>
                <form method="POST" action="{{ route('portal.settings.logout-other-devices') }}" onsubmit="return confirm('هل أنت متأكد من رغبتك في تسجيل الخروج من جميع الأجهزة والجلسات الأخرى؟ سيتعين عليك إعادة تسجيل الدخول في الهواتف والمتصفحات الأخرى.');">
                    @csrf
                    <button type="submit" class="btn btn-danger" style="padding:0.6rem 1.5rem; font-weight:700; display:flex; align-items:center; gap:0.4rem;">
                        <span>🚪</span>
                        <span>تسجيل الخروج من كل الأجهزة الأخرى</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ───────────────────────────────────────────────────────────── --}}
    {{-- TAB 5: System Info & Preferences                              --}}
    {{-- ───────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'info'" x-cloak>
        {{-- Section 1: Data & Records Export (Archivage & Téléchargements) --}}
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                <div class="card-title">
                    <span>📥 تصدير أرشيف وسجلات الإقامة (Export des Données & Archives)</span>
                </div>
                <span class="badge badge-primary" style="font-size:0.78rem;">
                    نسخ احتياطي فوري بصيغة Excel / CSV
                </span>
            </div>
            <div class="card-body">
                <p style="font-size:0.875rem; color:var(--text-muted); margin-bottom:1.25rem;">
                    يمكنك تحميل نسخ رقمية مطابقة لجميع سجلات الإقامة بصيغة CSV المتوافقة تماماً مع Microsoft Excel و Google Sheets بالترميز العربي UTF-8:
                </p>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
                    {{-- Apartments Export --}}
                    <div style="padding:1.15rem; border-radius:12px; border:1px solid var(--border-color); background:var(--bg-app); display:flex; flex-direction:column; justify-content:space-between;">
                        <div>
                            <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.5rem;">
                                <span style="font-size:1.35rem;">🏢</span>
                                <strong style="font-size:0.95rem; color:var(--text-main);">سجل الشقق والوحدات</strong>
                            </div>
                            <p style="font-size:0.8rem; color:var(--text-muted); line-height:1.5; margin-bottom:1rem;">
                                جدول أرقام الشقق، الطوابق، الواجب الشهري المحدد، حالة الفوترة، وأسماء وأرقام هواتف الشاغلين.
                            </p>
                        </div>
                        <a href="{{ route('portal.settings.export', ['type' => 'apartments']) }}" class="btn btn-secondary btn-sm" style="width:100%; justify-content:center; font-weight:700;">
                            تحميل سجل الشقق (CSV) ⬇️
                        </a>
                    </div>

                    {{-- Residents Export --}}
                    <div style="padding:1.15rem; border-radius:12px; border:1px solid var(--border-color); background:var(--bg-app); display:flex; flex-direction:column; justify-content:space-between;">
                        <div>
                            <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.5rem;">
                                <span style="font-size:1.35rem;">👥</span>
                                <strong style="font-size:0.95rem; color:var(--text-main);">دليل السكان والاتصال</strong>
                            </div>
                            <p style="font-size:0.8rem; color:var(--text-muted); line-height:1.5; margin-bottom:1rem;">
                                دليل كامل بأسماء الملاك والمكترين، الشقق التابعة لهم، أرقام الهواتف، البريد، وتاريخ الانضمام.
                            </p>
                        </div>
                        <a href="{{ route('portal.settings.export', ['type' => 'residents']) }}" class="btn btn-secondary btn-sm" style="width:100%; justify-content:center; font-weight:700;">
                            تحميل دليل السكان (CSV) ⬇️
                        </a>
                    </div>

                    {{-- Payments Export --}}
                    <div style="padding:1.15rem; border-radius:12px; border:1px solid var(--border-color); background:var(--bg-app); display:flex; flex-direction:column; justify-content:space-between;">
                        <div>
                            <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.5rem;">
                                <span style="font-size:1.35rem;">💰</span>
                                <strong style="font-size:0.95rem; color:var(--text-main);">سجل أداء الواجبات الشهرية</strong>
                            </div>
                            <p style="font-size:0.8rem; color:var(--text-muted); line-height:1.5; margin-bottom:1rem;">
                                جدول تفصيلي بجميع المداخيل، أرقام التوصيلات، طريقة الدفع (كاش/شيك/تحويل)، وحالة التأكيد.
                            </p>
                        </div>
                        <a href="{{ route('portal.settings.export', ['type' => 'payments']) }}" class="btn btn-secondary btn-sm" style="width:100%; justify-content:center; font-weight:700;">
                            تحميل سجل المداخيل (CSV) ⬇️
                        </a>
                    </div>

                    {{-- Expenses Export --}}
                    <div style="padding:1.15rem; border-radius:12px; border:1px solid var(--border-color); background:var(--bg-app); display:flex; flex-direction:column; justify-content:space-between;">
                        <div>
                            <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.5rem;">
                                <span style="font-size:1.35rem;">📉</span>
                                <strong style="font-size:0.95rem; color:var(--text-main);">سجل المصاريف والفواتير</strong>
                            </div>
                            <p style="font-size:0.8rem; color:var(--text-muted); line-height:1.5; margin-bottom:1rem;">
                                جدول بكافة نفقات الصيانة والنظافة والكهرباء والمصاعد وتواريخ الدفع والمبالغ المؤداة.
                            </p>
                        </div>
                        <a href="{{ route('portal.settings.export', ['type' => 'expenses']) }}" class="btn btn-secondary btn-sm" style="width:100%; justify-content:center; font-weight:700;">
                            تحميل سجل المصاريف (CSV) ⬇️
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-footer" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <span style="font-size:0.8rem; color:var(--text-muted);">
                    📊 هل تحتاج إلى كشف مالي وحساب ختامي رسمي مختوم بتقرير PDF؟
                </span>
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-financial-report-modal'))" class="btn btn-primary" style="padding:0.55rem 1.4rem; font-weight:700;">
                    📑 استخراج التقرير المالي الرسمي (PDF)
                </button>
            </div>
        </div>

        <div class="grid-2">
            {{-- Building Invitation Code Card --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span>🔑 رمز انضمام السكان للإقامة</span>
                    </div>
                </div>
                <div class="card-body">
                    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1.25rem;">
                        شارك هذا الرمز مع سكان الإقامة ليتمكنوا من تحميل تطبيق الهاتف والانضمام مباشرة إلى عمارتهم وتقديم طلبات الربط بشققهم:
                    </p>
                    <div style="display:flex; align-items:center; gap:0.75rem; background:var(--bg-app); padding:1rem 1.25rem; border-radius:12px; border:1px solid var(--border-color);">
                        <code style="font-size:1.4rem; font-weight:900; letter-spacing:2px; color:var(--brand-primary); flex:1; direction:ltr; text-align:center;">
                            {{ $tenant->invitation_code ?? 'غير معين' }}
                        </code>
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $tenant->invitation_code }}'); alert('تم نسخ رمز الإقامة بنجاح!');" class="btn btn-sm btn-secondary">
                            📋 نسخ الرمز
                        </button>
                    </div>
                </div>
            </div>

            {{-- System & Support Card --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span>ℹ️ معلومات النظام والدعم التقني</span>
                    </div>
                </div>
                <div class="card-body">
                    <div style="display:flex; flex-direction:column; gap:0.85rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:0.75rem; border-bottom:1px solid var(--border-color);">
                            <span style="color:var(--text-muted); font-size:0.85rem;">إصدار المنصة:</span>
                            <span style="font-weight:700; font-size:0.9rem;">Smart Syndic Cloud v1.0 PRO</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:0.75rem; border-bottom:1px solid var(--border-color);">
                            <span style="color:var(--text-muted); font-size:0.85rem;">حالة الحساب:</span>
                            <span class="badge badge-success">نشط ومفعل كلياً ✓</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="color:var(--text-muted); font-size:0.85rem;">الدعم الفني المباشر:</span>
                            <span style="font-weight:700; color:var(--brand-primary); font-size:0.85rem;">support@smartsyndic.ma</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁️';
    }
}
</script>

@endsection

