@extends('layouts.superadmin')
@section('title', 'إعدادات المنصة وعلامات الميزات')

@section('content')
<div x-data="{ activeTab: 'features' }">
    <div class="page-header" style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 class="page-title">⚙️ إعدادات النظام وعلامات الميزات (Feature Flags)</h1>
            <p class="page-subtitle">التحكم المركزي في ميزات المنصة، وضع الصيانة الشامل، ومعلومات الدعم الفني.</p>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div style="background:rgba(16, 185, 129, 0.15); border:1px solid #10b981; color:#10b981; padding:0.85rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-weight:600; display:flex; align-items:center; gap:0.6rem;">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div style="background:rgba(239, 68, 68, 0.15); border:1px solid #ef4444; color:#ef4444; padding:0.85rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-weight:600; display:flex; align-items:center; gap:0.6rem;">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Maintenance Mode Highlight Box --}}
    @php
        $isMaintenance = (bool)\App\Models\SystemSetting::getVal('maintenance_mode_enabled', false);
        $maintenanceMsg = (string)\App\Models\SystemSetting::getVal('maintenance_message', 'المنصة تخضع حالياً لأعمال صيانة دورية مجدولة. سنعود للعمل قريباً جداً.');
        $bypassSecret = (string)\App\Models\SystemSetting::getVal('maintenance_bypass_secret', 'smart_syndic_bypass_2026');
    @endphp

    <div class="card" style="margin-bottom:2rem; padding:1.5rem; border:2px solid {{ $isMaintenance ? '#ef4444' : 'rgba(99, 102, 241, 0.25)' }}; background: {{ $isMaintenance ? 'rgba(239, 68, 68, 0.05)' : 'var(--bg-card)' }};">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.2rem;">
            <div style="display:flex; align-items:center; gap:1rem;">
                <div style="font-size:2.5rem; width:60px; height:60px; border-radius:12px; display:flex; align-items:center; justify-content:center; background:{{ $isMaintenance ? 'rgba(239, 68, 68, 0.15)' : 'rgba(99, 102, 241, 0.15)' }};">
                    {{ $isMaintenance ? '🚨' : '🛡️' }}
                </div>
                <div>
                    <div style="display:flex; align-items:center; gap:0.6rem;">
                        <h3 style="margin:0; font-size:1.2rem; font-weight:800;">وضع الصيانة العام للمنصة (Platform Maintenance Mode)</h3>
                        @if($isMaintenance)
                            <span class="badge badge-danger" style="animation: pulse 2s infinite;">نشط حالياً (المنصة مغلقة)</span>
                        @else
                            <span class="badge badge-success">المنصة تعمل كلياً وبشكل طبيعي</span>
                        @endif
                    </div>
                    <p style="margin:0.35rem 0 0; font-size:0.875rem; color:var(--text-muted);">
                        عند تفعيل وضع الصيانة، يتم منع السانديك والملاك من الوصول للمنصة وعرض شاشة صيانة، باستثناء السوبر أدمن.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('superadmin.settings.maintenance.toggle') }}" style="display:flex; align-items:center; gap:0.75rem;">
                @csrf
                <button type="submit" class="btn {{ $isMaintenance ? 'btn-success' : 'btn-danger' }}" style="padding:0.7rem 1.5rem; font-weight:700;">
                    {{ $isMaintenance ? '🟢 إلغاء وضع الصيانة وتشغيل البوابة' : '🔴 تفعيل وضع الصيانة الفوري' }}
                </button>
            </form>
        </div>

        <div style="margin-top:1.25rem; padding-top:1.25rem; border-top:1px solid var(--border-color); display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
            <div>
                <span style="font-size:0.8rem; font-weight:600; color:var(--text-muted); display:block; margin-bottom:0.25rem;">رسالة الصيانة التي تظهر للمستخدمين:</span>
                <div style="font-size:0.9rem; color:var(--text-main); background:rgba(0,0,0,0.03); padding:0.6rem 0.85rem; border-radius:8px; border:1px solid var(--border-color);">
                    "{{ $maintenanceMsg }}"
                </div>
            </div>
            <div>
                <span style="font-size:0.8rem; font-weight:600; color:var(--text-muted); display:block; margin-bottom:0.25rem;">رمز تجاوز الصيانة السري (Bypass Key):</span>
                <code style="display:inline-block; font-size:0.85rem; background:rgba(99,102,241,0.1); color:#6366f1; padding:0.5rem 0.85rem; border-radius:8px; font-weight:bold;">
                    ?bypass={{ $bypassSecret }}
                </code>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div style="display:flex; gap:0.5rem; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;">
        <button type="button" @click="activeTab = 'features'" :class="{ 'btn-primary': activeTab === 'features', 'btn-secondary': activeTab !== 'features' }" class="btn" style="padding:0.6rem 1.25rem;">
            🚩 مفاتيح الميزات (Feature Flags)
        </button>
        <button type="button" @click="activeTab = 'support'" :class="{ 'btn-primary': activeTab === 'support', 'btn-secondary': activeTab !== 'support' }" class="btn" style="padding:0.6rem 1.25rem;">
            📞 الدعم الفني والتواصل الرسمي
        </button>
        <button type="button" @click="activeTab = 'maintenance_msg'" :class="{ 'btn-primary': activeTab === 'maintenance_msg', 'btn-secondary': activeTab !== 'maintenance_msg' }" class="btn" style="padding:0.6rem 1.25rem;">
            🛠️ تخصيص رسالة الصيانة
        </button>
        <button type="button" @click="activeTab = 'password'" :class="{ 'btn-primary': activeTab === 'password', 'btn-secondary': activeTab !== 'password' }" class="btn" style="padding:0.6rem 1.25rem;">
            🔐 كلمة مرور حسابي
        </button>
    </div>

    {{-- Global Settings Form --}}
    <form method="POST" action="{{ route('superadmin.settings.update') }}">
        @csrf

        {{-- Tab 1: Feature Flags --}}
        <div x-show="activeTab === 'features'" class="card" style="padding:1.5rem;">
            <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem;">
                <span>🚩</span>
                <span>علامات ومفاتيح ميزات المنصة العامة (Global Feature Toggles)</span>
            </h3>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1.5rem;">
                يمكنك تشغيل أو إيقاف أي ميزة برمجية على مستوى المنصة ككل بنقرة واحدة، لتطبيق سياسات العمل أو الصيانة الجزئية.
            </p>

            <div style="display:flex; flex-direction:column; gap:1.25rem;">
                <label style="display:flex; align-items:flex-start; gap:1rem; padding:1.1rem; border-radius:10px; border:1px solid var(--border-color); background:var(--bg-card); cursor:pointer; transition:border-color 0.2s;">
                    <input type="checkbox" name="allow_new_registrations" value="1" {{ \App\Models\SystemSetting::getVal('allow_new_registrations', true) ? 'checked' : '' }} style="width:20px; height:20px; accent-color:#6366f1; margin-top:0.2rem;">
                    <div>
                        <div style="font-weight:700; font-size:1rem; color:var(--text-main);">السماح بتسجيل سانديك وإقامات جديدة (Self Registration)</div>
                        <div style="font-size:0.85rem; color:var(--text-muted); margin-top:0.25rem;">
                            عند إلغاء هذا الخيار، سيتم إخفاء أو تعطيل صفحة تسجيل السانديك الجديد، مع إمكانية إضافة المنظمات يدوياً فقط بواسطة السوبر أدمن.
                        </div>
                    </div>
                </label>

                <label style="display:flex; align-items:flex-start; gap:1rem; padding:1.1rem; border-radius:10px; border:1px solid var(--border-color); background:var(--bg-card); cursor:pointer; transition:border-color 0.2s;">
                    <input type="checkbox" name="enable_online_payments" value="1" {{ \App\Models\SystemSetting::getVal('enable_online_payments', true) ? 'checked' : '' }} style="width:20px; height:20px; accent-color:#10b981; margin-top:0.2rem;">
                    <div>
                        <div style="font-weight:700; font-size:1rem; color:var(--text-main);">تفعيل بوابات الدفع الإلكتروني (Online Payments Gateway)</div>
                        <div style="font-size:0.85rem; color:var(--text-muted); margin-top:0.25rem;">
                            السماح للملاك بدفع واجبات السانديك الشهرية إلكترونياً عبر البطاقات البنكية وخدمات الدفع السريع.
                        </div>
                    </div>
                </label>

                <label style="display:flex; align-items:flex-start; gap:1rem; padding:1.1rem; border-radius:10px; border:1px solid var(--border-color); background:var(--bg-card); cursor:pointer; transition:border-color 0.2s;">
                    <input type="checkbox" name="enable_resident_polls" value="1" {{ \App\Models\SystemSetting::getVal('enable_resident_polls', true) ? 'checked' : '' }} style="width:20px; height:20px; accent-color:#f59e0b; margin-top:0.2rem;">
                    <div>
                        <div style="font-weight:700; font-size:1rem; color:var(--text-main);">تفعيل نظام التصويت واستطلاعات الرأي (Resident Polls & Voting)</div>
                        <div style="font-size:0.85rem; color:var(--text-muted); margin-top:0.25rem;">
                            إتاحة إجراء استطلاعات الرأي والتصويت الإلكتروني على قرارات الجمع العام والإصلاحات للملاك.
                        </div>
                    </div>
                </label>

                <label style="display:flex; align-items:flex-start; gap:1rem; padding:1.1rem; border-radius:10px; border:1px solid var(--border-color); background:var(--bg-card); cursor:pointer; transition:border-color 0.2s;">
                    <input type="checkbox" name="enable_incident_reporting" value="1" {{ \App\Models\SystemSetting::getVal('enable_incident_reporting', true) ? 'checked' : '' }} style="width:20px; height:20px; accent-color:#a855f7; margin-top:0.2rem;">
                    <div>
                        <div style="font-weight:700; font-size:1rem; color:var(--text-main);">تفعيل إرسال الشكاوى والمقترحات (Incidents & Suggestions Box)</div>
                        <div style="font-size:0.85rem; color:var(--text-muted); margin-top:0.25rem;">
                            السماح للسكان والملاك برفع الشكاوى، إبلاغ السانديك عن الأعطال، وإرسال المقترحات التطويرية.
                        </div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Tab 2: Support & Branding --}}
        <div x-show="activeTab === 'support'" style="display:none;" class="card" style="padding:1.5rem;">
            <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem;">
                <span>📞</span>
                <span>بيانات الدعم الفني والتواصل المركزي المعروضة للمستخدمين</span>
            </h3>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
                <div>
                    <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">رقم هاتف الدعم المركزي</label>
                    <input type="text" name="support_phone" value="{{ \App\Models\SystemSetting::getVal('support_phone', '+212 5 22 00 00 00') }}" class="form-input" placeholder="+212 5 22 00 00 00">
                </div>

                <div>
                    <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">رقم الواتساب الرسمي (WhatsApp)</label>
                    <input type="text" name="support_whatsapp" value="{{ \App\Models\SystemSetting::getVal('support_whatsapp', '+212 6 00 00 00 00') }}" class="form-input" placeholder="+212 6 00 00 00 00">
                </div>

                <div>
                    <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">البريد الإلكتروني للدعم الفني</label>
                    <input type="email" name="support_email" value="{{ \App\Models\SystemSetting::getVal('support_email', 'support@smartsyndic.ma') }}" class="form-input" placeholder="support@smartsyndic.ma">
                </div>

                <div style="grid-column: 1 / -1;">
                    <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">إشعار وتذييل البوابة العام (Footer Notice)</label>
                    <input type="text" name="platform_notice_footer" value="{{ \App\Models\SystemSetting::getVal('platform_notice_footer', 'منصة Smart Syndic — الإدارة الذكية والشفافة للإقامات المشتركة بالمغرب.') }}" class="form-input">
                </div>
            </div>
        </div>

        {{-- Tab 3: Maintenance Message Customization --}}
        <div x-show="activeTab === 'maintenance_msg'" style="display:none;" class="card" style="padding:1.5rem;">
            <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem;">
                <span>🛠️</span>
                <span>تخصيص رسالة شاشة الصيانة وتفاصيل المرور</span>
            </h3>

            <div style="display:flex; flex-direction:column; gap:1.25rem;">
                <div>
                    <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">نص رسالة الصيانة المعروضة للمستخدمين</label>
                    <textarea name="maintenance_message" rows="4" class="form-input">{{ \App\Models\SystemSetting::getVal('maintenance_message', 'المنصة تخضع حالياً لأعمال صيانة دورية مجدولة. سنعود للعمل قريباً جداً.') }}</textarea>
                </div>

                <div>
                    <label class="form-label" style="font-weight:600; margin-bottom:0.4rem;">رمز التجاوز السري (Bypass Secret Token)</label>
                    <input type="text" name="maintenance_bypass_secret" value="{{ \App\Models\SystemSetting::getVal('maintenance_bypass_secret', 'smart_syndic_bypass_2026') }}" class="form-input">
                    <p style="font-size:0.75rem; color:var(--text-muted); margin-top:0.4rem;">
                        يمكن لأي مهندس أو مسؤول الدخول أثناء الصيانة عبر فتح الرابط مع إضافة الباراميتر: <code>?bypass=القيمة_هنا</code>
                    </p>
                </div>
            </div>
        </div>

        <div style="margin-top:1.5rem; display:flex; justify-content:flex-end; gap:1rem;">
            <button type="submit" class="btn btn-primary" style="padding:0.75rem 2rem; font-weight:700; font-size:1rem;">
                💾 حفظ كافة الإعدادات والمفاتيح
            </button>
        </div>
    </form>

    {{-- Tab 4: Super Admin Password Change Form --}}
    <div x-show="activeTab === 'password'" style="display:none;" class="card" style="padding:1.75rem; max-width:650px; margin-top:0.5rem;">
        <h3 style="font-size:1.15rem; font-weight:800; margin-bottom:0.4rem; display:flex; align-items:center; gap:0.5rem;">
            <span>🔐</span>
            <span>تغيير كلمة مرور المشرف العام (Super Admin)</span>
        </h3>
        <p style="font-size:0.875rem; color:var(--text-muted); margin-bottom:1.5rem; line-height:1.5;">
            لأسباب أمنية مشددة، يجب إدخال <strong>كلمة المرور الحالية</strong> لتأكيد هويتك قبل تعيين كلمة مرور جديدة لحسابك.
        </p>

        @if ($errors->has('current_password'))
            <div style="background:rgba(239, 68, 68, 0.12); border:1px solid #ef4444; color:#ef4444; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1.25rem; font-size:0.875rem; font-weight:700; display:flex; align-items:center; gap:0.5rem;">
                <span>⚠️</span>
                <span>{{ $errors->first('current_password') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('superadmin.settings.password.update') }}">
            @csrf
            <div style="margin-bottom:1.25rem;">
                <label class="form-label" style="font-weight:700; margin-bottom:0.4rem; display:block;">كلمة المرور الحالية <span style="color:#ef4444;">*</span></label>
                <input type="password" name="current_password" required class="form-input" placeholder="••••••••" style="width:100%;">
            </div>

            <div style="margin-bottom:1.25rem;">
                <label class="form-label" style="font-weight:700; margin-bottom:0.4rem; display:block;">كلمة المرور الجديدة (8 أحرف على الأقل) <span style="color:#ef4444;">*</span></label>
                <input type="password" name="password" required minlength="8" class="form-input" placeholder="••••••••" style="width:100%;">
                @error('password')
                    <span style="color:#ef4444; font-size:0.8rem; margin-top:0.35rem; display:block; font-weight:600;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom:1.75rem;">
                <label class="form-label" style="font-weight:700; margin-bottom:0.4rem; display:block;">تأكيد كلمة المرور الجديدة <span style="color:#ef4444;">*</span></label>
                <input type="password" name="password_confirmation" required minlength="8" class="form-input" placeholder="••••••••" style="width:100%;">
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <span style="font-size:0.8rem; color:var(--text-muted);">
                    🔒 سيتم إنهاء أي جلسات نشطة أخرى فوراً عند الحفظ.
                </span>
                <button type="submit" class="btn btn-primary" style="padding:0.7rem 1.75rem; font-weight:700;">
                    حفظ وتحديث كلمة المرور 🔐
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
