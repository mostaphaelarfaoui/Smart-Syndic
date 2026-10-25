<?php

namespace App\Http\Controllers\Web\Portal;

use App\Events\ResidentApproved;
use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Poll;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResidentController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $tab = $request->input('tab', 'residents');

        // Residents Query
        $residentsQuery = User::where('tenant_id', $tenantId)
            ->where('role', 'RESIDENT')
            ->with(['apartments.building']);

        if ($request->filled('status')) {
            $residentsQuery->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $residentsQuery->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $residents = $residentsQuery->latest('created_at')->paginate(20, ['*'], 'residents_page')->withQueryString();

        $pendingCount = User::where('tenant_id', $tenantId)->where('role', 'RESIDENT')->where('status', 'PENDING')->count();
        $activeCount  = User::where('tenant_id', $tenantId)->where('role', 'RESIDENT')->where('status', 'ACTIVE')->count();

        // Suggestions / Complaints Query
        $suggestions = Suggestion::where('tenant_id', $tenantId)
            ->with(['user', 'apartment'])
            ->latest('created_at')
            ->paginate(15, ['*'], 'suggestions_page')
            ->withQueryString();

        $pendingSuggestionsCount = Suggestion::where('tenant_id', $tenantId)->where('status', 'PENDING')->count();

        // Polls Query
        $polls = Poll::where('tenant_id', $tenantId)
            ->with([
                'options.votes',
                'votes' => function ($q) {
                    $q->with(['user', 'apartment.building', 'option'])->latest();
                }
            ])
            ->latest('created_at')
            ->paginate(15, ['*'], 'polls_page')
            ->withQueryString();

        $activePollsCount = Poll::where('tenant_id', $tenantId)->where('is_active', true)->count();

        $tenant = auth()->user()->tenant;

        return view('portal.residents.index', compact(
            'tab',
            'residents',
            'pendingCount',
            'activeCount',
            'suggestions',
            'pendingSuggestionsCount',
            'polls',
            'activePollsCount',
            'tenant'
        ));
    }

    public function approve($id)
    {
        $tenantId = auth()->user()->tenant_id;

        $user = User::where('tenant_id', $tenantId)
            ->where('role', 'RESIDENT')
            ->where('status', 'PENDING')
            ->findOrFail($id);

        $user->update(['status' => 'ACTIVE']);

        // Fire event so notifications or webhooks dispatch
        event(new ResidentApproved($user));

        return back()->with('success', "تمت الموافقة وتفعيل حساب الساكن {$user->full_name} بنجاح.");
    }

    public function reject($id)
    {
        $tenantId = auth()->user()->tenant_id;

        $user = User::where('tenant_id', $tenantId)
            ->where('role', 'RESIDENT')
            ->findOrFail($id);

        $name = $user->full_name;
        $user->delete();

        return back()->with('success', "تم رفض وحذف طلب انضمام {$name}.");
    }

    public function regenerateInviteCode()
    {
        $tenant = auth()->user()->tenant;

        $newCode = strtoupper(Str::random(6));
        $tenant->update(['invitation_code' => $newCode]);

        return back()->with('success', "تم تجديد كود انضمام السكان إلى: {$newCode}");
    }

    public function destroy(Request $request, $id)
    {
        if ($error = $this->verifySensitivePassword($request, 'حذف حساب الساكن وتفريغ الشقة')) {
            return $error;
        }

        $tenantId = auth()->user()->tenant_id;

        $user = User::where('tenant_id', $tenantId)
            ->where('role', 'RESIDENT')
            ->findOrFail($id);

        $name = $user->full_name;

        // User boot model deleting hook automatically detaches apartments, vacates them, and resets balances
        $user->delete();

        return back()->with('success', "تم حذف حساب الساكن {$name} وإعادة الشقة شاغرة بنجاح.");
    }
}
