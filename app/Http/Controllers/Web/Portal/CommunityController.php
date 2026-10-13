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
        $tenantId = auth()->user()->tenant_id;
        $tab      = $request->input('tab', 'suggestions');

        $suggestions = Suggestion::where('tenant_id', $tenantId)
            ->with(['user', 'apartment'])
            ->latest('created_at')
            ->paginate(15, ['*'], 'suggestions_page');

        $polls = Poll::where('tenant_id', $tenantId)
            ->with(['options.votes'])
            ->latest('created_at')
            ->paginate(15, ['*'], 'polls_page');

        return view('portal.community.index', compact('tab', 'suggestions', 'polls'));
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

        return back()->with('success', 'تم تحديث حالة الملاحظة / الشكاية بنجاح.');
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

        DB::transaction(function () use ($tenantId, $validated) {
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
        });

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
