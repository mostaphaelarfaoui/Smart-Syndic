<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Display all tenant announcements.
     */
    public function index(Request $request)
    {
        $user = auth('web')->user();

        $announcements = Announcement::forTenant($user->tenant_id)
            ->with('creator')
            ->ordered()
            ->paginate(15);

        $totalCount = Announcement::forTenant($user->tenant_id)->count();
        $pinnedCount = Announcement::forTenant($user->tenant_id)->where('is_pinned', true)->count();
        $urgentCount = Announcement::forTenant($user->tenant_id)->where('priority', 'URGENT')->count();

        return view('portal.announcements.index', compact(
            'announcements',
            'totalCount',
            'pinnedCount',
            'urgentCount'
        ));
    }

    /**
     * Store and publish a new announcement to all residents.
     */
    public function store(Request $request)
    {
        $user = auth('web')->user();

        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'content'   => ['required', 'string', 'max:10000'],
            'priority'  => ['required', 'in:NORMAL,URGENT,INFO'],
            'is_pinned' => ['nullable', 'boolean'],
        ], [
            'title.required'   => 'يرجى إدخال عنوان الإعلان.',
            'content.required' => 'يرجى كتابة نص أو تفاصيل الإعلان.',
        ]);

        $announcement = Announcement::create([
            'tenant_id' => $user->tenant_id,
            'created_by' => $user->id,
            'title'     => trim($validated['title']),
            'content'   => trim($validated['content']),
            'priority'  => $validated['priority'],
            'is_pinned' => $request->boolean('is_pinned'),
        ]);

        // Send instant notification to all active residents of the residence
        $this->notificationService->sendToTenantResidents(
            tenantId: $user->tenant_id,
            title: '📢 إعلان جديد من السانديك: ' . $announcement->title,
            message: Str::limit($announcement->content, 140),
            type: $announcement->priority === 'URGENT' ? 'warning' : 'info',
            url: route('portal.announcements.index'),
            icon: '📢',
            extra: [
                'category'        => 'announcement',
                'announcement_id' => $announcement->id,
                'title'           => $announcement->title,
                'content'         => $announcement->content,
                'priority'        => $announcement->priority,
            ]
        );

        AuditLog::record(
            actorId: $user->id,
            action: 'ANNOUNCEMENT_CREATE',
            targetTenantId: $user->tenant_id,
            metadata: [
                'announcement_id' => $announcement->id,
                'title'           => $announcement->title,
                'priority'        => $announcement->priority,
            ]
        );

        return redirect()->route('portal.announcements.index')
            ->with('success', 'تم نشر الإعلان بنجاح وإرسال إشعار فوري لكافة سكان الإقامة ✓');
    }

    /**
     * Toggle pinned status of an announcement.
     */
    public function togglePin(string $id)
    {
        $user = auth('web')->user();

        $announcement = Announcement::forTenant($user->tenant_id)->findOrFail($id);
        $announcement->update([
            'is_pinned' => ! $announcement->is_pinned,
        ]);

        $statusMsg = $announcement->is_pinned ? 'تم تثبيت الإعلان في الأعلى بنجاح.' : 'تم إلغاء تثبيت الإعلان.';

        return back()->with('success', $statusMsg);
    }

    /**
     * Delete an announcement.
     */
    public function destroy(string $id)
    {
        $user = auth('web')->user();

        $announcement = Announcement::forTenant($user->tenant_id)->findOrFail($id);
        $title = $announcement->title;
        $announcement->delete();

        AuditLog::record(
            actorId: $user->id,
            action: 'ANNOUNCEMENT_DELETE',
            targetTenantId: $user->tenant_id,
            metadata: [
                'announcement_id' => $id,
                'title'           => $title,
            ]
        );

        return back()->with('success', 'تم حذف الإعلان بنجاح.');
    }
}
