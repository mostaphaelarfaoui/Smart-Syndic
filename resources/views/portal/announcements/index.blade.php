@extends('layouts.dashboard')

@section('title', 'لوحة إعلانات الإقامة')

@section('content')
<div class="announcements-page" x-data="{ createModal: false }">

    {{-- Page Header --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 style="font-size:1.6rem; font-weight:900; margin:0 0 0.35rem; color:var(--text-primary); display:flex; align-items:center; gap:0.6rem;">
                <span>📢</span>
                <span>لوحة إعلانات وبيانات الإقامة</span>
            </h1>
            <p style="margin:0; font-size:0.88rem; color:var(--text-secondary);">
                نشر المستجدات والبيانات الرسمية لكافة السكان، وتصلهم مباشرة كإشعارات فورية في النظام والتطبيق.
            </p>
        </div>

        <button type="button" 
                class="btn btn-primary" 
                @click="createModal = true"
                style="display:flex; align-items:center; gap:0.5rem; padding:0.65rem 1.25rem; font-weight:800; border-radius:10px; box-shadow:0 4px 12px rgba(99, 102, 241, 0.25);">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>نشر إعلان جديد لكافة السكان</span>
        </button>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div style="background:rgba(16, 185, 129, 0.12); border:1px solid #10b981; color:#065f46; padding:0.9rem 1.2rem; border-radius:10px; margin-bottom:1.25rem; font-weight:700; display:flex; align-items:center; gap:0.6rem;">
            <span>✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div style="background:rgba(239, 68, 68, 0.12); border:1px solid #ef4444; color:#991b1b; padding:0.9rem 1.2rem; border-radius:10px; margin-bottom:1.25rem; font-weight:700; display:flex; align-items:center; gap:0.6rem;">
            <span>✕</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- KPI Cards --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
        <div class="card" style="padding:1.1rem; display:flex; align-items:center; gap:1rem;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(99, 102, 241, 0.12); color:#4f46e5; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                📢
            </div>
            <div>
                <div style="font-size:0.8rem; color:var(--text-secondary); font-weight:700;">إجمالي الإعلانات</div>
                <div style="font-size:1.4rem; font-weight:900; color:var(--text-primary);">{{ $totalCount }}</div>
            </div>
        </div>

        <div class="card" style="padding:1.1rem; display:flex; align-items:center; gap:1rem;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(245, 158, 11, 0.12); color:#d97706; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                📌
            </div>
            <div>
                <div style="font-size:0.8rem; color:var(--text-secondary); font-weight:700;">إعلانات مثبتة</div>
                <div style="font-size:1.4rem; font-weight:900; color:var(--text-primary);">{{ $pinnedCount }}</div>
            </div>
        </div>

        <div class="card" style="padding:1.1rem; display:flex; align-items:center; gap:1rem;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(239, 68, 68, 0.12); color:#dc2626; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                ⚡
            </div>
            <div>
                <div style="font-size:0.8rem; color:var(--text-secondary); font-weight:700;">إعلانات عاجلة</div>
                <div style="font-size:1.4rem; font-weight:900; color:var(--text-primary);">{{ $urgentCount }}</div>
            </div>
        </div>

        <div class="card" style="padding:1.1rem; display:flex; align-items:center; gap:1rem; background:linear-gradient(135deg, rgba(99, 102, 241, 0.06), rgba(16, 185, 129, 0.06)); border-color:rgba(99, 102, 241, 0.2);">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(16, 185, 129, 0.15); color:#059669; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                🔔
            </div>
            <div>
                <div style="font-size:0.78rem; color:var(--text-secondary); font-weight:700;">قناة الوصول</div>
                <div style="font-size:0.92rem; font-weight:900; color:#059669;">خدمة الإشعارات الفورية</div>
            </div>
        </div>
    </div>

    {{-- Announcements Feed --}}
    @if($announcements->isEmpty())
        <div class="card" style="padding:4rem 2rem; text-align:center; color:var(--text-muted);">
            <div style="font-size:3.5rem; margin-bottom:0.75rem; opacity:0.8;">📢</div>
            <h3 style="margin:0 0 0.5rem; font-size:1.2rem; font-weight:800; color:var(--text-primary);">لا توجد أي إعلانات منشورة حالياً</h3>
            <p style="margin:0 auto 1.5rem; max-width:480px; font-size:0.88rem; color:var(--text-secondary); line-height:1.6;">
                يمكنك نشر أول إعلان لإعلام كافة السكان بجديد الأشغال، مواعيد الجمع العام، أو تنبيهات الصيانة، وستصلهم فوراً كإشعار في النظام.
            </p>
            <button type="button" class="btn btn-primary" @click="createModal = true" style="font-weight:800;">
                + نشر أول إعلان الآن
            </button>
        </div>
    @else
        <div style="display:flex; flex-direction:column; gap:1rem;">
            @foreach($announcements as $announcement)
                <div class="card" style="padding:1.5rem; border-radius:14px; position:relative; {{ $announcement->is_pinned ? 'border:1.5px solid rgba(245, 158, 11, 0.5); background:linear-gradient(to bottom, rgba(245, 158, 11, 0.03), var(--bg-surface));' : '' }}">
                    
                    {{-- Header Row --}}
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.75rem; margin-bottom:0.75rem;">
                        <div style="display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap;">
                            {{-- Priority Badge --}}
                            @if($announcement->priority === 'URGENT')
                                <span style="background:rgba(239, 68, 68, 0.12); color:#dc2626; border:1px solid rgba(239, 68, 68, 0.3); padding:0.2rem 0.65rem; border-radius:6px; font-size:0.75rem; font-weight:800;">
                                    🚨 إعلان عاجل
                                </span>
                            @elseif($announcement->priority === 'INFO')
                                <span style="background:rgba(99, 102, 241, 0.12); color:#4f46e5; border:1px solid rgba(99, 102, 241, 0.3); padding:0.2rem 0.65rem; border-radius:6px; font-size:0.75rem; font-weight:800;">
                                    ℹ️ تنبيه هام
                                </span>
                            @else
                                <span style="background:rgba(100, 116, 139, 0.12); color:#475569; border:1px solid rgba(100, 116, 139, 0.3); padding:0.2rem 0.65rem; border-radius:6px; font-size:0.75rem; font-weight:800;">
                                    📢 إعلان عام
                                </span>
                            @endif

                            @if($announcement->is_pinned)
                                <span style="background:rgba(245, 158, 11, 0.15); color:#d97706; border:1px solid rgba(245, 158, 11, 0.3); padding:0.2rem 0.65rem; border-radius:6px; font-size:0.75rem; font-weight:800;">
                                    📌 مثبت في الأعلى
                                </span>
                            @endif

                            <h2 style="font-size:1.15rem; font-weight:900; margin:0; color:var(--text-primary);">
                                {{ $announcement->title }}
                            </h2>
                        </div>

                        {{-- Timestamp --}}
                        <div style="font-size:0.8rem; color:var(--text-secondary); display:flex; align-items:center; gap:0.4rem;">
                            <span>🕒</span>
                            <span>{{ $announcement->created_at->diffForHumans() }}</span>
                            <span style="opacity:0.5;">({{ $announcement->created_at->format('Y-m-d H:i') }})</span>
                        </div>
                    </div>

                    {{-- Announcement Body --}}
                    <div style="font-size:0.92rem; color:var(--text-primary); line-height:1.7; white-space:pre-line; margin-bottom:1rem; padding:0.5rem 0;">
                        {!! nl2br(e($announcement->content)) !!}
                    </div>

                    {{-- Footer Actions --}}
                    <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-color); padding-top:0.75rem; font-size:0.82rem; color:var(--text-muted); flex-wrap:wrap; gap:0.75rem;">
                        <div>
                            <span>نُشر بواسطة: </span>
                            <strong style="color:var(--text-primary);">{{ $announcement->creator_name }}</strong>
                        </div>

                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            {{-- Toggle Pin Form --}}
                            <form method="POST" action="{{ route('portal.announcements.toggle-pin', $announcement->id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm" style="font-size:0.78rem; padding:0.35rem 0.75rem; font-weight:700;">
                                    @if($announcement->is_pinned)
                                        <span>إلغاء التثبيت</span>
                                    @else
                                        <span>📌 تثبيت في الأعلى</span>
                                    @endif
                                </button>
                            </form>

                            {{-- Delete Form --}}
                            <form method="POST" action="{{ route('portal.announcements.destroy', $announcement->id) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا الإعلان؟');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" style="font-size:0.78rem; padding:0.35rem 0.75rem; font-weight:700;">
                                    🗑️ حذف
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach

            <div style="margin-top:1rem;">
                {{ $announcements->links() }}
            </div>
        </div>
    @endif

    {{-- Create Announcement Modal --}}
    <div class="modal-overlay" x-show="createModal" x-cloak style="z-index:99995;">
        <div class="modal-dialog" style="max-width:620px;" @click.away="createModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" style="margin:0;">📢 نشر إعلان جديد لكافة السكان</h3>
                    <p style="margin:0.25rem 0 0; font-size:0.82rem; color:var(--text-secondary);">
                        يصل هذا الإعلان فوراً إلى جميع سكان الإقامة عبر نظام الإشعارات
                    </p>
                </div>
                <button type="button" class="icon-btn" @click="createModal = false">✕</button>
            </div>

            <form method="POST" action="{{ route('portal.announcements.store') }}">
                @csrf
                <div class="modal-body">
                    {{-- Title --}}
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">عنوان الإعلان <span style="color:#ef4444;">*</span></label>
                        <input type="text" 
                               name="title" 
                               class="form-control" 
                               placeholder="مثال: انقطاع مؤقت للماء الصالح للشرب يوم الخميس" 
                               required 
                               maxlength="255">
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                        {{-- Priority --}}
                        <div class="form-group">
                            <label class="form-label" style="font-weight:700;">درجة الأهمية</label>
                            <select name="priority" class="form-select" required>
                                <option value="NORMAL">📢 عادي (إعلان روتيني)</option>
                                <option value="INFO">ℹ️ تنبيه هام (تذكير أو مستجدات)</option>
                                <option value="URGENT">🚨 عاجل (يتطلب انتباه فوري)</option>
                            </select>
                        </div>

                        {{-- Pinned Checkbox --}}
                        <div class="form-group" style="display:flex; flex-direction:column; justify-content:flex-end;">
                            <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; padding:0.65rem 0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-secondary);">
                                <input type="checkbox" name="is_pinned" value="1" style="accent-color:var(--brand-primary); width:18px; height:18px;">
                                <span style="font-weight:700; font-size:0.85rem;">📌 تثبيت في أعلى القائمة</span>
                            </label>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">نص أو بيان الإعلان <span style="color:#ef4444;">*</span></label>
                        <textarea name="content" 
                                  rows="6" 
                                  class="form-control" 
                                  placeholder="اكتب هنا التفاصيل الكاملة للإعلان الموجه لكافة سكان الإقامة..." 
                                  required 
                                  style="resize:vertical; line-height:1.6;"></textarea>
                    </div>

                    {{-- Notice Alert --}}
                    <div style="background:rgba(99, 102, 241, 0.08); border:1px solid rgba(99, 102, 241, 0.25); border-radius:8px; padding:0.75rem 0.9rem; font-size:0.8rem; color:#4338ca; display:flex; align-items:center; gap:0.5rem;">
                        <span style="font-size:1.1rem;">🔔</span>
                        <span>
                            <strong>ملاحظة هامة:</strong> فور الضغط على «نشر الإعلان»، سيصل إشعار فوري لجميع السكان المسجلين في الإقامة في جرس الإشعارات على الويب وتطبيق الهاتف.
                        </span>
                    </div>
                </div>

                <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:0.5rem;">
                    <button type="button" class="btn btn-secondary" @click="createModal = false">إلغاء</button>
                    <button type="submit" class="btn btn-primary" style="font-weight:800; padding:0.6rem 1.4rem;">
                        📢 نشر الإعلان وإرسال الإشعارات
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
