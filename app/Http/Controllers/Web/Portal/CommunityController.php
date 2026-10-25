<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Suggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'suggestions');
        return redirect()->route('portal.residents.index', array_merge(['tab' => $tab], $request->all()));
    }

    public function updateSuggestionStatus(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;

        $suggestion = Suggestion::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'status'      => ['required', 'in:PENDING,IN_PROGRESS,RESOLVED,REJECTED'],
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $suggestion->update($validated);

        if ($suggestion->user) {
            $statusLabels = [
                'IN_PROGRESS' => 'قيد المعالجة',
                'RESOLVED'    => 'تمت معالجتها بنجاح',
                'REJECTED'    => 'تم رفضها',
                'PENDING'     => 'قيد المراجعة',
            ];
            $type = match($validated['status']) {
                'RESOLVED' => 'success',
                'REJECTED' => 'danger',
                default    => 'info',
            };

            app(\App\Services\NotificationService::class)->send(
                user: $suggestion->user,
                title: 'تحديث بخصوص مقترحك / ملاحظتك 💬',
                message: "تم تحديث حالة المقترح \"{$suggestion->title}\" إلى: " . ($statusLabels[$validated['status']] ?? $validated['status']),
                type: $type,
                url: route('portal.dashboard'),
                icon: '💬',
                extra: [
                    'target_screen' => 'community',
                    'target_id'     => $suggestion->id,
                ]
            );
        }

        return back()->with('success', 'تم تحديث حالة الملاحظة / الشكاية بنجاح.');
    }

    public function convertToPoll(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $suggestion = Suggestion::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'title'                 => ['required', 'string', 'max:255'],
            'description'           => ['nullable', 'string', 'max:1000'],
            'expires_at'            => ['nullable', 'date'],
            'options'               => ['required', 'array', 'min:2'],
            'options.*'             => ['required', 'string', 'max:255'],
            'include_resident_info' => ['nullable'],
        ]);

        $finalDescription = $validated['description'] ?? '';
        if ($request->boolean('include_resident_info') || $request->input('include_resident_info') == '1') {
            $unit = $suggestion->apartment ? $suggestion->apartment->unit_number : 'غير محددة';
            $name = $suggestion->user ? $suggestion->user->full_name : 'أحد السكان';
            $credit = "\n\n(مُقترح من طرف: {$name} - شقة {$unit})";
            $finalDescription .= $credit;
        }

        $poll = DB::transaction(function () use ($tenantId, $suggestion, $validated, $finalDescription) {
            $suggestion->convertToPoll();

            $poll = Poll::create([
                'tenant_id'   => $tenantId,
                'created_by'  => auth()->id(),
                'title'       => $validated['title'],
                'description' => trim($finalDescription) ?: null,
                'expires_at'  => $validated['expires_at'] ?? now()->addDays(7),
                'is_active'   => true,
            ]);

            foreach ($validated['options'] as $optionText) {
                if (trim($optionText) !== '') {
                    $poll->options()->create([
                        'option_text' => trim($optionText),
                    ]);
                }
            }

            return $poll;
        });

        // 1. Notify all residents in the building/tenant
        app(\App\Services\NotificationService::class)->sendToTenantResidents(
            tenantId: $tenantId,
            title: 'استطلاع رأي وتصويت جديد 📊',
            message: "تم طرح تصويت جديد في الإقامة بناءً على مقترح تم تقديمه: \"{$poll->title}\". شارك بصوتك الآن!",
            type: 'info',
            url: route('portal.residents.index', ['tab' => 'polls']),
            icon: '🗳️',
            extra: [
                'target_screen' => 'polls',
                'target_id'     => $poll->id,
            ]
        );

        // 2. Notify the suggesting resident specifically
        if ($suggestion->user) {
            app(\App\Services\NotificationService::class)->send(
                user: $suggestion->user,
                title: 'تم تحويل مقترحك إلى تصويت عام 🎉',
                message: "وافق السانديك على مقترحك وتم تحويله إلى تصويت عام لجميع السكان: \"{$poll->title}\"",
                type: 'success',
                url: route('portal.dashboard'),
                icon: '🗳️',
                extra: [
                    'target_screen' => 'polls',
                    'target_id'     => $poll->id,
                ]
            );
        }

        return redirect()->route('portal.residents.index', ['tab' => 'polls'])
            ->with('success', 'تم تحويل المقترح إلى استطلاع رأي وتصويت عام بنجاح.');
    }

    public function storePoll(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'expires_at'  => ['nullable', 'date'],
            'options'     => ['required', 'array', 'min:2'],
            'options.*'   => ['required', 'string', 'max:255'],
        ]);

        $poll = DB::transaction(function () use ($tenantId, $validated) {
            $poll = Poll::create([
                'tenant_id'   => $tenantId,
                'created_by'  => auth()->id(),
                'title'       => $validated['title'],
                'description' => $validated['description'] ?? null,
                'expires_at'  => $validated['expires_at'] ?? now()->addDays(7),
                'is_active'   => true,
            ]);

            foreach ($validated['options'] as $optionText) {
                if (trim($optionText) !== '') {
                    $poll->options()->create([
                        'option_text' => trim($optionText),
                    ]);
                }
            }

            return $poll;
        });

        // Notify all active residents in the tenant
        app(\App\Services\NotificationService::class)->sendToTenantResidents(
            tenantId: $tenantId,
            title: 'استطلاع رأي وتصويت جديد 📊',
            message: "تم طرح تصويت جديد في الإقامة: \"{$poll->title}\". شارك بصوتك الآن!",
            type: 'info',
            url: route('portal.community.index', ['tab' => 'polls']),
            icon: '🗳️',
            extra: [
                'target_screen' => 'polls',
                'target_id'     => $poll->id,
            ]
        );

        return back()->with('success', 'تم إنشاء استطلاع الرأي وطرحه للسكان بنجاح.');
    }

    public function togglePoll($id)
    {
        $tenantId = auth()->user()->tenant_id;

        $poll = Poll::where('tenant_id', $tenantId)->findOrFail($id);
        $poll->update(['is_active' => ! $poll->is_active]);

        $statusText = $poll->is_active ? 'تفعيل' : 'إغلاق';
        return back()->with('success', "تم {$statusText} استطلاع الرأي.");
    }
}
