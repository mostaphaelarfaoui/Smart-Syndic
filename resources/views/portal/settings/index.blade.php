@extends('layouts.dashboard')

@section('title', 'إعدادات الإقامة وطابع السانديك')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">⚙️ إعدادات الإقامة وخاتم السانديك</h1>
        <p class="page-subtitle">تعديل بيانات الإقامة، الحساب البنكي، وتعيين طابع وخاتم السانديك المعتمد في التوصيلات الرسمية</p>
    </div>
</div>

<div class="grid-2">
    {{-- General Information Card --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>🏢 المعلومات العامة والحساب البنكي</span>
            </div>
        </div>
        <form method="POST" action="{{ route('portal.settings.update') }}">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">اسم الإقامة أو المجمع</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $tenant->name) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">مدينة الإقامة أو المجمع</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $tenant->city) }}" placeholder="مثال: الدار البيضاء، طنجة، الرباط...">
                    <small style="color:var(--text-muted); font-size:0.75rem;">تظهر تلقائياً في التقارير الرسمية والوثائق المالية (حرر بتاريخ ... بمدينة ...)</small>
                </div>

                <div class="form-group">
                    <label class="form-label">نوع الملكية العقارية</label>
                    <select name="property_type" class="form-select" required>
                        <option value="RESIDENTIAL_COMPLEX" {{ $tenant->property_type == 'RESIDENTIAL_COMPLEX' ? 'selected' : '' }}>مجمع سكني مغلق</option>
                        <option value="SINGLE_BUILDING" {{ $tenant->property_type == 'SINGLE_BUILDING' ? 'selected' : '' }}>عمارة منفردة</option>
                        <option value="HOUSING_ESTATE_VILLAS" {{ $tenant->property_type == 'HOUSING_ESTATE_VILLAS' ? 'selected' : '' }}>تجزئة فيلات</option>
                        <option value="COMMERCIAL_RESIDENTIAL" {{ $tenant->property_type == 'COMMERCIAL_RESIDENTIAL' ? 'selected' : '' }}>مجمع تجاري وسكني</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">رقم الحساب البنكي للإقامة (RIB - 24 رقماً)</label>
                    <input type="text" name="bank_rib" class="form-control num" value="{{ old('bank_rib', $tenant->bank_rib) }}" placeholder="مثال: 123456789012345678901234">
                    <small style="color:var(--text-muted); font-size:0.75rem;">يظهر للسكان في تطبيق الهاتف للتحويلات البنكية المباشرة</small>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">الرصيد الافتتاحي للصندوق (درهم)</label>
                        <input type="number" step="0.01" name="initial_treasury_balance" class="form-control num" value="{{ $tenant->initial_treasury_balance }}" {{ $tenant->initial_balance_locked ? 'disabled' : '' }}>
                    </div>
                    <div class="form-group">
                        <label class="form-label">الرصيد الافتتاحي للبنك (درهم)</label>
                        <input type="number" step="0.01" name="initial_bank_balance" class="form-control num" value="{{ $tenant->initial_bank_balance }}" {{ $tenant->initial_balance_locked ? 'disabled' : '' }}>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                        <input type="checkbox" name="is_transparency_enabled" value="1" {{ $tenant->is_transparency_enabled ? 'checked' : '' }} style="accent-color:var(--brand-primary);">
                        <span style="font-weight:700;">تفعيل وضع الشفافية المالية (السكان يمكنهم رؤية تقرير المصاريف الشهري)</span>
                    </label>
                </div>
            </div>
            <div class="card-footer" style="text-align:left;">
                <button type="submit" class="btn btn-primary">حفظ التعديلات ✓</button>
            </div>
        </form>
    </div>

    {{-- Official Stamp Card --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>🖋️ طابع وخاتم السانديك المعتمد</span>
            </div>
        </div>
        <div class="card-body" style="text-align:center;">
            @if($tenant->stamp_image_path)
                <div style="margin-bottom:1.5rem; padding:1.5rem; background:var(--bg-app); border-radius:var(--radius-lg); display:inline-block; border:1px dashed var(--border-color);">
                    <img src="{{ asset('storage/' . $tenant->stamp_image_path) }}" alt="طابع السانديك" style="max-height:160px; max-width:220px; object-fit:contain; filter:drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
                </div>
                <div style="margin-bottom:1.5rem;">
                    <span class="badge badge-success">✓ الخاتم مفعل ويظهر على جميع التوصيلات PDF</span>
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
                <div style="padding:2.5rem 1.5rem; background:var(--bg-app); border-radius:var(--radius-lg); border:2px dashed var(--border-color); margin-bottom:1.5rem;">
                    <div style="font-size:3rem; margin-bottom:0.5rem; opacity:0.6;">🖋️</div>
                    <h4 style="font-size:1.05rem; font-weight:800; margin-bottom:0.35rem;">لم يتم رفع خاتم السانديك بعد</h4>
                    <p style="font-size:0.85rem; color:var(--text-muted); max-width:320px; margin:0 auto;">
                        ارفع صورة واضحة لخاتمك (يفضل بخلفية شفافة PNG) ليتم تضمينها تلقائياً على وصولات الأداء PDF.
                    </p>
                </div>
            @endif

            <hr style="border:0; border-top:1px solid var(--border-color); margin:1.75rem 0;">

            <form method="POST" action="{{ route('portal.settings.stamp.upload') }}" enctype="multipart/form-data" style="text-align:right;">
                @csrf
                <div class="form-group">
                    <label class="form-label">{{ $tenant->stamp_image_path ? 'تغيير صورة الخاتم' : 'اختر ملف صورة الخاتم' }}</label>
                    <input type="file" name="stamp" class="form-control" accept="image/png,image/jpeg,image/webp" required>
                    <small style="color:var(--text-muted); font-size:0.75rem;">صيغ مقبولة: PNG, JPG, WEBP (الحجم الأقصى 2MB)</small>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    {{ $tenant->stamp_image_path ? 'تحديث وحفظ الخاتم الجديد' : 'رفع وتفعيل الخاتم ➔' }}
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
