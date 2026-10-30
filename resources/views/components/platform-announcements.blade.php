{{-- Platform Announcements Prominent Banner Component --}}
@php
    $currentUser = auth()->user();
    $activeAnnouncements = $currentUser
        ? \App\Models\PlatformAnnouncement::currentlyActive($currentUser->role, $currentUser->tenant_id)->get()
        : collect();
@endphp

@if($activeAnnouncements->isNotEmpty())
    <div class="platform-announcements-wrapper" style="display: flex; flex-direction: column; gap: 0.85rem; margin-bottom: 1.5rem;">
        @foreach($activeAnnouncements as $ann)
            @php
                $type = strtoupper($ann->type);
                $config = match($type) {
                    'DANGER' => [
                        'bg'          => 'linear-gradient(135deg, rgba(239, 68, 68, 0.14) 0%, rgba(185, 28, 28, 0.22) 100%)',
                        'border'      => 'rgba(239, 68, 68, 0.45)',
                        'badge_bg'    => 'rgba(239, 68, 68, 0.25)',
                        'badge_color' => '#f87171',
                        'badge_text'  => 'تحذير عاجل من الإدارة العامة',
                        'icon'        => '🚨',
                        'shadow'      => '0 4px 20px rgba(239, 68, 68, 0.15)',
                    ],
                    'WARNING' => [
                        'bg'          => 'linear-gradient(135deg, rgba(245, 158, 11, 0.14) 0%, rgba(217, 119, 6, 0.22) 100%)',
                        'border'      => 'rgba(245, 158, 11, 0.45)',
                        'badge_bg'    => 'rgba(245, 158, 11, 0.25)',
                        'badge_color' => '#fbbf24',
                        'badge_text'  => 'تنبيه هام من المنصة',
                        'icon'        => '⚠️',
                        'shadow'      => '0 4px 20px rgba(245, 158, 11, 0.15)',
                    ],
                    'SUCCESS' => [
                        'bg'          => 'linear-gradient(135deg, rgba(16, 185, 129, 0.14) 0%, rgba(5, 150, 105, 0.22) 100%)',
                        'border'      => 'rgba(16, 185, 129, 0.45)',
                        'badge_bg'    => 'rgba(16, 185, 129, 0.25)',
                        'badge_color' => '#34d399',
                        'badge_text'  => 'إعلان وترقية سعيدة',
                        'icon'        => '✨',
                        'shadow'      => '0 4px 20px rgba(16, 185, 129, 0.15)',
                    ],
                    default => [
                        'bg'          => 'linear-gradient(135deg, rgba(99, 102, 241, 0.14) 0%, rgba(79, 70, 229, 0.22) 100%)',
                        'border'      => 'rgba(99, 102, 241, 0.45)',
                        'badge_bg'    => 'rgba(99, 102, 241, 0.25)',
                        'badge_color' => '#818cf8',
                        'badge_text'  => 'إعلان رسمي من إدارة المنصة',
                        'icon'        => '📢',
                        'shadow'      => '0 4px 20px rgba(99, 102, 241, 0.15)',
                    ],
                };
            @endphp

            <div class="platform-announcement-banner"
                 x-data="{
                     id: '{{ $ann->id }}',
                     isDismissible: {{ $ann->is_dismissible ? 'true' : 'false' }},
                     dismissed: false,
                     init() {
                         if (this.isDismissible && localStorage.getItem('announcement_dismissed_' + this.id)) {
                             this.dismissed = true;
                         }
                     },
                     dismiss() {
                         this.dismissed = true;
                         if (this.isDismissible) {
                             localStorage.setItem('announcement_dismissed_' + this.id, 'true');
                         }
                     }
                 }"
                 x-show="!dismissed"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
                 style="background: {{ $config['bg'] }}; border: 1px solid {{ $config['border'] }}; box-shadow: {{ $config['shadow'] }}; border-radius: var(--radius-lg, 16px); padding: 1.25rem 1.5rem; backdrop-filter: blur(14px); position: relative; direction: rtl; text-align: right; width: 100%;">
                 
                 <div style="display: flex; align-items: flex-start; gap: 1.15rem; width: 100%; padding-left: 3.5rem; direction: rtl; text-align: right;">
                     <div style="width: 48px; height: 48px; min-width: 48px; border-radius: 12px; background: {{ $config['badge_bg'] }}; border: 1px solid {{ $config['border'] }}; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15); flex-shrink: 0;">
                         {{ $config['icon'] }}
                     </div>

                     <div style="flex: 1; text-align: right;">
                         <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap; margin-bottom: 0.35rem;">
                             <span style="background: {{ $config['badge_bg'] }}; color: {{ $config['badge_color'] }}; font-weight: 800; font-size: 0.72rem; padding: 0.2rem 0.65rem; border-radius: 999px; border: 1px solid {{ $config['border'] }}; letter-spacing: 0.02em;">
                                 {{ $config['badge_text'] }}
                             </span>
                             <span style="font-size: 0.75rem; color: var(--text-muted, #94a3b8);">
                                 🕒 {{ $ann->created_at->diffForHumans() }}
                             </span>
                             @if(!$ann->is_dismissible)
                                 <span style="background: rgba(0,0,0,0.25); color: #e2e8f0; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 6px;">
                                     📌 إعلان مثبت
                                 </span>
                             @endif
                         </div>

                         <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--text-primary, #ffffff); line-height: 1.4;">
                             {{ $ann->title }}
                         </h4>

                         <div style="margin-top: 0.4rem; font-size: 0.92rem; line-height: 1.65; color: var(--text-secondary, #cbd5e1); white-space: pre-line;">
                             {{ $ann->message }}
                         </div>
                     </div>
                 </div>

                 @if($ann->is_dismissible)
                     <button type="button" 
                             @click="dismiss()"
                             title="إغلاق وقراءة الإعلان"
                             style="position: absolute; left: 1.25rem; top: 1.25rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: var(--text-muted, #cbd5e1); border-radius: 8px; width: 32px; height: 32px; min-width: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; z-index: 10;"
                             onmouseover="this.style.background='rgba(255,255,255,0.2)'; this.style.color='#ffffff';"
                             onmouseout="this.style.background='rgba(255,255,255,0.08)'; this.style.color='#cbd5e1';">
                         <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                         </svg>
                     </button>
                 @endif
            </div>
        @endforeach
    </div>
@endif
