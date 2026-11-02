<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnnouncementApiController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * GET /api/v1/tenant-announcements
     *
     * List all announcements of the current tenant (for Syndic and Residents).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $announcements = Announcement::forTenant($user->tenant_id)
            ->with('creator')
            ->ordered()
            ->paginate(30);

        return response()->json([
            'status' => 'success',
            'data'   => $announcements,
        ]);
    }

    /**
     * POST /api/v1/syndic/announcements
     *
     * Publish a new announcement to all residents and send notification.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSyndic()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح لك بنشر الإعلانات.'], 403);
        }

        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'content'   => ['required', 'string', 'max:10000'],
            'priority'  => ['nullable', 'in:NORMAL,URGENT,INFO'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $announcement = Announcement::create([
            'tenant_id' => $user->tenant_id,
            'created_by' => $user->id,
            'title'     => trim($validated['title']),
            'content'   => trim($validated['content']),
            'priority'  => $validated['priority'] ?? 'NORMAL',
            'is_pinned' => $request->boolean('is_pinned'),
        ]);

        // Send instant notification to all active residents of this tenant
        $this->notificationService->sendToTenantResidents(
            tenantId: $user->tenant_id,
            title: '📢 إعلان جديد من السانديك: ' . $announcement->title,
            message: Str::limit($announcement->content, 140),
            type: $announcement->priority === 'URGENT' ? 'warning' : 'info',
            url: '/announcements',
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

        return response()->json([
            'status'  => 'success',
            'message' => 'تم نشر الإعلان بنجاح وإرسال إشعار فوري لكافة السكان ✓',
            'data'    => $announcement->load('creator'),
        ], 201);
    }

    /**
     * DELETE /api/v1/syndic/announcements/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSyndic()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح لك بحذف الإعلانات.'], 403);
        }

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

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حذف الإعلان بنجاح.',
        ]);
    }
}
