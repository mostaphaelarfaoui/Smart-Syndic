@extends('layouts.dashboard')

@section('title', 'الشكاوى والتصويت المجتمعي')

@section('content')

<div class="page-header" x-data="{ pollModal: false }">
    <div>
        <h1 class="page-title">💬 الشكاوى والتصويت المجتمعي</h1>
        <p class="page-subtitle">متابعة ملاحظات السكان، معالجة الشكايات، وإشراك الملاك في اتخاذ القرارات عبر الاستطلاعات</p>
    </div>

    <div class="page-actions">
        <div style="display:flex; align-items:center; gap:0.5rem; background:var(--bg-surface); padding:0.35rem; border:1px solid var(--border-color); border-radius:var(--radius-lg);">
            <a href="{{ route('portal.community.index', ['tab' => 'suggestions']) }}" class="btn btn-sm {{ $tab === 'suggestions' ? 'btn-primary' : 'btn-secondary' }}">
                📩 شكاوى وملاحظات السكان
            </a>
            <a href="{{ route('portal.community.index', ['tab' => 'polls']) }}" class="btn btn-sm {{ $tab === 'polls' ? 'btn-primary' : 'btn-secondary' }}">
                🗳️ استطلاعات الرأي والتصويت
            </a>
        </div>

        @if($tab === 'polls')
            <button type="button" class="btn btn-primary" @click="pollModal = true">
                <span>+ إنشاء تصويت جديد</span>
            </button>
        @endif
    </div>

    {{-- Modal: Create Poll --}}
    <div class="modal-overlay" x-show="pollModal" x-cloak>
        <div class="modal-dialog" @click.away="pollModal = false" x-data="{ options: ['', ''] }">
            <div class="modal-header">
                <h3 class="modal-title">إنشاء استطلاع رأي جديد</h3>
                <button type="button" class="icon-btn" @click="pollModal = false">✕</button>
            </div>
            <form method="POST" action="{{ route('portal.community.polls.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">موضوع أو سؤال الاستطلاع</label>
                        <input type="text" name="title" class="form-control" placeholder="مثال: هل توافق على تركيب كاميرات مراقبة إضافية بالمرآب؟" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">توضيح إضافي (اختياري)</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="تفاصيل الميزانية المقترحة أو سبب التصويت..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">تاريخ انتهاء التصويت</label>
                        <input type="date" name="expires_at" class="form-control" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">خيارات التصويت (2 على الأقل)</label>
                        <template x-for="(opt, index) in options" :key="index">
                            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                                <input type="text" name="options[]" class="form-control" :placeholder="'الخيار ' + (index + 1)" required>
                                <button type="button" class="btn btn-sm btn-secondary" @click="if(options.length > 2) options.splice(index, 1)" style="color:#ef4444;">✕</button>
                            </div>
                        </template>
                        <button type="button" class="btn btn-sm btn-secondary" @click="options.push('')" style="margin-top:0.5rem;">
                            + إضافة خيار إضافي
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="pollModal = false">إلغاء</button>
                    <button type="submit" class="btn btn-primary">نشر الاستطلاع</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($tab === 'suggestions')
    {{-- Suggestions / Complaints Section --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="min-width:140px;">المرسل والشقة</th>
                        <th style="min-width:170px;">موضوع الشكاية / الملاحظة</th>
                        <th style="min-width:240px;">التفاصيل</th>
                        <th style="text-align:center; min-width:110px;">الحالة الحالية</th>
                        <th style="min-width:110px;">التاريخ</th>
                        <th style="min-width:160px;">تحديث الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suggestions as $sug)
                        <tr>
                            <td>
                                <strong>{{ $sug->user?->full_name ?? 'الساكن' }}</strong>
                                <div style="font-size:0.75rem; color:var(--text-muted);">
                                    @if($sug->apartment)
                                        شقة {{ $sug->apartment->unit_number }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                <strong style="font-size:0.95rem;">{{ $sug->title }}</strong>
                            </td>
                            <td style="max-width:300px; color:var(--text-secondary); font-size:0.88rem;">
                                {{ $sug->description }}
                            </td>
                            <td>
                                @if($sug->status === 'RESOLVED')
                                    <span class="badge badge-success">✓ تم الحل</span>
                                @elseif($sug->status === 'IN_PROGRESS')
                                    <span class="badge badge-info">⚙️ قيد المعالجة</span>
                                @elseif($sug->status === 'REJECTED')
                                    <span class="badge badge-danger">مرفوضة</span>
                                @else
                                    <span class="badge badge-warning">⏳ في الانتظار</span>
                                @endif
                            </td>
                            <td class="num" style="color:var(--text-muted); font-size:0.85rem;">
                                {{ $sug->created_at->format('Y/m/d') }}
                            </td>
                            <td>
                                <form method="POST" action="{{ route('portal.community.suggestions.update', $sug->id) }}" style="display:flex; align-items:center; gap:0.4rem;">
                                    @csrf
                                    <select name="status" class="form-select" style="padding:0.35rem 0.65rem; font-size:0.8rem; width:auto;">
                                        <option value="PENDING" {{ $sug->status == 'PENDING' ? 'selected' : '' }}>في الانتظار</option>
                                        <option value="IN_PROGRESS" {{ $sug->status == 'IN_PROGRESS' ? 'selected' : '' }}>قيد المعالجة</option>
                                        <option value="RESOLVED" {{ $sug->status == 'RESOLVED' ? 'selected' : '' }}>تم الحل</option>
                                        <option value="REJECTED" {{ $sug->status == 'REJECTED' ? 'selected' : '' }}>مرفوضة</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-secondary" title="حفظ التعديل">
                                        تحديث
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">
                                لا توجد شكاوى أو ملاحظات مسجلة حالياً.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($suggestions->hasPages())
            <div class="card-footer">
                {{ $suggestions->links() }}
            </div>
        @endif
    </div>

@else
    {{-- Polls Section --}}
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap:1.5rem;">
        @forelse($polls as $poll)
            @php
                $totalVotes = $poll->options->sum(fn($o) => $o->votes->count());
            @endphp
            <div class="card" style="margin-bottom:0;">
                <div class="card-header">
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <span class="badge {{ $poll->is_active ? 'badge-success' : 'badge-neutral' }}">
                            {{ $poll->is_active ? '✓ تصويت نشط' : 'مغلق' }}
                        </span>
                        <span class="num" style="font-size:0.8rem; color:var(--text-muted);">
                            {{ $totalVotes }} صوت
                        </span>
                    </div>

                    <form method="POST" action="{{ route('portal.community.polls.toggle', $poll->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary" style="font-size:0.75rem;">
                            {{ $poll->is_active ? 'إغلاق التصويت' : 'إعادة التفعيل' }}
                        </button>
                    </form>
                </div>

                <div class="card-body">
                    <h3 style="font-size:1.15rem; font-weight:800; margin-bottom:0.4rem;">
                        {{ $poll->title }}
                    </h3>

                    @if($poll->description)
                        <p style="color:var(--text-secondary); font-size:0.88rem; margin-bottom:1.25rem;">
                            {{ $poll->description }}
                        </p>
                    @endif

                    <div style="display:flex; flex-direction:column; gap:0.85rem; margin-top:1rem;">
                        @foreach($poll->options as $opt)
                            @php
                                $optVotes = $opt->votes->count();
                                $pct = $totalVotes > 0 ? round(($optVotes / $totalVotes) * 100) : 0;
                            @endphp
                            <div>
                                <div style="display:flex; align-items:center; justify-content:space-between; font-size:0.85rem; margin-bottom:0.25rem;">
                                    <strong>{{ $opt->option_text }}</strong>
                                    <span class="num" style="font-weight:700;">{{ $optVotes }} صوت ({{ $pct }}%)</span>
                                </div>
                                <div style="height:8px; border-radius:var(--radius-full); background:var(--border-color); overflow:hidden;">
                                    <div style="height:100%; border-radius:var(--radius-full); background:var(--brand-primary); width: {{ $pct }}%; transition: width 0.3s ease;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card-footer" style="display:flex; justify-content:space-between; font-size:0.8rem; color:var(--text-muted);">
                    <span>نُشر: {{ $poll->created_at->format('Y/m/d') }}</span>
                    @if($poll->expires_at)
                        <span>ينتهي: {{ $poll->expires_at->format('Y/m/d') }}</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="card" style="grid-column: 1 / -1; text-align:center; padding:3rem; color:var(--text-muted);">
                <div style="font-size:2.5rem; margin-bottom:0.5rem;">🗳️</div>
                <p>لا توجد استطلاعات رأي منشورة حالياً. انقر على "+ إنشاء تصويت جديد" للبدء.</p>
            </div>
        @endforelse
    </div>
@endif

@endsection
