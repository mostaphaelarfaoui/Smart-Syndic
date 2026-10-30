<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resident\CastVoteRequest;
use App\Http\Requests\Resident\SubmitSuggestionRequest;
use App\Events\SuggestionSubmitted;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResidentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly FileUploadService $uploadService,
    ) {}

    /**
     * GET /api/v1/resident/profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $apartment = $user->apartments()->with('building')->first();
        $tenant = $user->tenant;

        $syndic = User::where('tenant_id', $tenant->id)
            ->where('role', 'ADMIN_SYNDIC')
            ->first();

        $balance = null;
        $totalPaid = 0.0;
        $paymentsCount = 0;

        if ($apartment) {
            $balance = $this->paymentService->getApartmentBalance($apartment);
            $payments = Payment::where('apartment_id', $apartment->id)->where('status', '!=', 'DRAFT');
            $totalPaid = (float) $payments->sum('amount_paid');
            $paymentsCount = (int) $payments->count();
        }

        return response()->json([
            'user' => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'full_name'  => trim($user->first_name . ' ' . $user->last_name),
                'email'      => $user->email,
                'phone'      => $user->phone,
                'role'       => $user->role,
                'status'     => $user->status,
                'created_at' => $user->created_at?->format('Y-m-d'),
            ],
            'apartment' => $apartment ? [
                'id'                 => $apartment->id,
                'unit_number'        => $apartment->unit_number,
                'floor'              => $apartment->floor,
                'monthly_fee'        => (float) $apartment->monthly_fee,
                'is_billable'        => (bool) $apartment->is_billable,
                'building_name'      => $apartment->building?->name,
                'occupancy_type'     => $apartment->pivot?->occupancy_type ?? 'OWNER',
                'is_primary_contact' => (bool) ($apartment->pivot?->is_primary_contact ?? true),
            ] : null,
            'residence' => [
                'id'                      => $tenant->id,
                'name'                    => $tenant->name,
                'bank_rib'                => $tenant->bank_rib,
                'is_transparency_enabled' => (bool) $tenant->is_transparency_enabled,
            ],
            'syndic' => $syndic ? [
                'name'  => trim($syndic->first_name . ' ' . $syndic->last_name),
                'phone' => $syndic->phone,
                'email' => $syndic->email,
            ] : null,
            'financial_summary' => [
                'total_due'       => $balance ? (float) $balance['arrears'] : 0.0, // Amount currently owed
                'total_expected'  => $balance ? (float) $balance['total_due'] : 0.0,
                'total_paid'      => $totalPaid,
                'payments_count'  => $paymentsCount,
                'is_up_to_date'   => $balance ? ((float) $balance['arrears'] <= 0) : true,
                'unpaid_months'       => $balance['unpaid_months'] ?? [],
                'paid_months'         => $balance['paid_months'] ?? [],
                'advance_paid_months' => $balance['advance_paid_months'] ?? [], // Feature 7
                'is_current_paid'     => $balance['is_current_paid'] ?? true, // Feature 7
            ],
            'active_announcements' => \App\Models\PlatformAnnouncement::currentlyActive('RESIDENT', $tenant->id)->get()->map(fn ($ann) => [
                'id'             => $ann->id,
                'title'          => $ann->title,
                'message'        => $ann->message,
                'type'           => $ann->type,
                'is_dismissible' => (bool) $ann->is_dismissible,
                'created_at'     => $ann->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    // ─── Dashboard / Balance ──────────────────────────────────────────

    /**
     * GET /api/v1/resident/my-balance
     */
    public function myBalance(Request $request): JsonResponse
    {
        $user      = $request->user();
        $apartment = $user->apartments()->first();

        if (! $apartment) {
            return response()->json([
                'apartment' => [
                    'id'          => null,
                    'unit_number' => '-',
                    'building'    => $user->tenant?->name ?? 'الإقامة',
                    'floor'       => '-',
                ],
                'balance' => [
                    'monthly_fee'         => 0.0,
                    'total_due'           => 0.0,
                    'total_paid'          => 0.0,
                    'arrears'             => 0.0,
                    'is_settled'          => true,
                    'unpaid_months'       => [],
                    'paid_months'         => [],
                    'advance_paid_months' => [],
                ],
            ]);
        }

        $balance = $this->paymentService->getApartmentBalance($apartment);

        return response()->json([
            'apartment' => [
                'id'          => $apartment->id,
                'unit_number' => $apartment->unit_number,
                'building'    => $apartment->building->name,
                'floor'       => $apartment->floor,
            ],
            'balance' => $balance,
        ]);
    }

    // ─── Payment History ──────────────────────────────────────────────

    /**
     * GET /api/v1/resident/payments
     */
    public function payments(Request $request): JsonResponse
    {
        $user         = $request->user();
        $apartmentIds = $user->apartments()->pluck('apartments.id');

        $payments = Payment::whereIn('apartment_id', $apartmentIds)
            ->whereIn('status', ['CONFIRMED', 'SENT_TO_RESIDENT'])
            ->with('apartment.building')
            ->orderByDesc('payment_date')
            ->paginate(20);

        $payments->getCollection()->transform(function ($payment) {
            $payment->receipt_download_url = $payment->receipt_url;
            return $payment;
        });

        return response()->json($payments);
    }

    // ─── Transparency Ledger ──────────────────────────────────────────

    /**
     * GET /api/v1/resident/transparency-ledger
     */
    public function transparencyLedger(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (! $tenant || ! $tenant->is_transparency_enabled) {
            return response()->json([
                'enabled'              => false,
                'transparency_enabled' => false,
                'message'              => __('resident.transparency_disabled'),
            ]);
        }

        $currentMonth = now()->format('Y-m');

        // Total payments collected this month or covering this month (excluding drafts)
        $monthInflow = (float) Payment::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'DRAFT')
            ->where(function ($q) use ($currentMonth) {
                $q->where(function ($sq) {
                    $sq->whereMonth('payment_date', now()->month)
                       ->whereYear('payment_date', now()->year);
                })->orWhereJsonContains('covered_months', $currentMonth);
            })
            ->sum('amount_paid');

        // Total expenses this month
        $monthOutflow = (float) Expense::where('tenant_id', $tenant->id)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        // If this month has no expenses yet, get overall all-time expenses sum or month outflow
        $allTimeOutflow = (float) Expense::where('tenant_id', $tenant->id)->sum('amount');
        $displayOutflow = $monthOutflow > 0 ? $monthOutflow : $allTimeOutflow;

        $allTimeInflow = (float) Payment::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'DRAFT')
            ->sum('amount_paid');
        $displayInflow = $monthInflow > 0 ? $monthInflow : $allTimeInflow;

        $balance = $displayInflow - $displayOutflow;

        // Detailed expenses list for resident transparency
        $expenses = Expense::where('tenant_id', $tenant->id)
            ->with('building')
            ->orderByDesc('expense_date')
            ->limit(50)
            ->get()
            ->map(function ($e) {
                return [
                    'id'           => $e->id,
                    'title'        => $e->title,
                    'amount'       => (float) $e->amount,
                    'category'     => $e->category,
                    'expense_date' => $e->expense_date?->format('Y-m-d') ?? $e->created_at?->format('Y-m-d'),
                    'building'     => $e->building?->name,
                    'invoice_url'  => $e->invoice_proof_url,
                ];
            });

        // Expense breakdown by category
        $expenseBreakdown = Expense::where('tenant_id', $tenant->id)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get();

        // Get all apartments and their payment status with detailed breakdown (masking personal resident details)
        $apartments = \App\Models\Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->with(['building', 'residents'])
            ->orderBy('building_id')
            ->orderBy('floor')
            ->get()
            ->map(function ($apt) use ($currentMonth) {
                $isBillable = (bool) $apt->is_billable;

                // Expected months calculation
                $firstResident = $apt->residents()->orderBy('resident_apartments.created_at', 'asc')->first();
                if ($firstResident && $firstResident->pivot && $firstResident->pivot->created_at) {
                    $start = \Carbon\Carbon::parse($firstResident->pivot->created_at)->startOfMonth();
                } else {
                    $start = $apt->created_at ? $apt->created_at->copy()->startOfMonth() : now()->startOfMonth();
                }
                $end = now()->startOfMonth();
                $expectedMonths = [];
                while ($start->lte($end)) {
                    $expectedMonths[] = $start->format('Y-m');
                    $start->addMonth();
                }

                // Confirmed payments
                $payments = $apt->payments()->where('status', '!=', 'DRAFT')->get();

                $fullyPaidMonths = [];
                $partialMonths = [];
                $totalPaid = 0.0;

                foreach ($payments as $p) {
                    $totalPaid += (float) $p->amount_paid;
                    $covered = is_array($p->covered_months) ? $p->covered_months : [];

                    if ($p->is_partial) {
                        foreach ($covered as $m) {
                            $partialMonths[$m] = [
                                'month'             => $m,
                                'amount_paid'       => (float) $p->amount_paid,
                                'remaining_balance' => (float) $p->remaining_balance,
                            ];
                        }
                    } else {
                        foreach ($covered as $m) {
                            $fullyPaidMonths[] = $m;
                        }
                    }
                }

                $fullyPaidMonths = array_values(array_unique($fullyPaidMonths));
                foreach ($fullyPaidMonths as $fpm) {
                    unset($partialMonths[$fpm]);
                }
                $partialMonthsList = array_values($partialMonths);

                $accountedMonths = array_merge($fullyPaidMonths, array_keys($partialMonths));
                $unpaidMonths = array_values(array_diff($expectedMonths, $accountedMonths));
                $advancePaidMonths = array_values(array_filter($fullyPaidMonths, fn ($m) => $m > $currentMonth));

                $totalDue = count($expectedMonths) * (float) $apt->monthly_fee;
                $arrears = max(0, $totalDue - $totalPaid);

                if (! $isBillable) {
                    $status = 'EXEMPT';
                    $statusLabel = 'معفية (شاغرة)';
                    $arrears = 0.0;
                    $unpaidMonths = [];
                } elseif ($arrears <= 0 && count($partialMonthsList) === 0) {
                    $status = 'PAID';
                    $statusLabel = 'خالصة بالكامل';
                    $unpaidMonths = [];
                } elseif (count($partialMonthsList) > 0 && count($unpaidMonths) === 0) {
                    $status = 'PARTIAL';
                    $statusLabel = 'أداء جزئي';
                } elseif (count($partialMonthsList) > 0 && count($unpaidMonths) > 0) {
                    $status = 'PARTIAL';
                    $statusLabel = 'جزئي ومتأخرات';
                } else {
                    $status = 'ARREARS';
                    $statusLabel = 'متأخرات';
                }

                return [
                    'id'                  => $apt->id,
                    'unit'                => $apt->unit_number,
                    'building'            => $apt->building?->name,
                    'floor'               => $apt->floor,
                    'monthly_fee'         => (float) $apt->monthly_fee,
                    'total_paid'          => $totalPaid,
                    'arrears'             => $arrears,
                    'status'              => $status, // 'PAID', 'PARTIAL', 'ARREARS', 'EXEMPT'
                    'status_label'        => $statusLabel,
                    'is_billable'         => $isBillable,
                    'has_residents'       => $apt->residents->isNotEmpty(),
                    'paid_months'         => $fullyPaidMonths,
                    'partial_months'      => $partialMonthsList,
                    'unpaid_months'       => $unpaidMonths,
                    'advance_paid_months' => $advancePaidMonths,
                    'expected_months'     => $expectedMonths,
                ];
            });

        $totalApartments = $apartments->count();
        $paidApartmentsCount = $apartments->where('status', 'PAID')->count();
        $partialApartmentsCount = $apartments->where('status', 'PARTIAL')->count();
        $arrearsApartmentsCount = $apartments->where('status', 'ARREARS')->count();
        $exemptApartmentsCount = $apartments->where('status', 'EXEMPT')->count();
        $collectionRate = $totalApartments > 0 ? round(($paidApartmentsCount / $totalApartments) * 100, 1) : 0;

        return response()->json([
            'enabled'                  => true,
            'transparency_enabled'     => true,
            'total_inflow'             => $displayInflow,
            'total_outflow'            => $displayOutflow,
            'balance'                  => $balance,
            'month_inflow'             => $monthInflow,
            'month_outflow'            => $monthOutflow,
            'expenses'                 => $expenses,
            'expense_breakdown'        => $expenseBreakdown,
            'collection_status'        => $apartments,
            'collection_rate'          => $collectionRate,
            'paid_apartments_count'    => $paidApartmentsCount,
            'partial_apartments_count' => $partialApartmentsCount,
            'arrears_apartments_count' => $arrearsApartmentsCount,
            'exempt_apartments_count'  => $exemptApartmentsCount,
            'total_apartments_count'   => $totalApartments,
            'current_month'            => $currentMonth,
        ]);
    }

    // ─── Suggestions ──────────────────────────────────────────────────

    /**
     * GET /api/v1/resident/suggestions
     */
    public function mySuggestions(Request $request): JsonResponse
    {
        $suggestions = Suggestion::where('user_id', $request->user()->id)
            ->where('tenant_id', $request->user()->tenant_id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $suggestions]);
    }

    /**
     * POST /api/v1/resident/suggestions
     */
    public function submitSuggestion(SubmitSuggestionRequest $request): JsonResponse
    {
        $data = $request->validated();

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $this->uploadService->upload($request->file('attachment'), 'suggestions');
        }

        $suggestion = Suggestion::create([
            'tenant_id'      => $request->user()->tenant_id,
            'user_id'        => $request->user()->id,
            'apartment_id'   => $data['apartment_id'],
            'title'          => $data['title'],
            'description'    => $data['description'],
            'attachment_url' => $attachmentPath,
            'status'         => 'PENDING',
        ]);

        event(new SuggestionSubmitted($suggestion));

        return response()->json([
            'message'    => __('suggestions.submitted'),
            'suggestion' => $suggestion,
        ], 201);
    }

    // ─── Polls & Voting ───────────────────────────────────────────────

    /**
     * GET /api/v1/resident/polls
     */
    public function polls(Request $request): JsonResponse
    {
        $polls = Poll::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->with(['options.votes', 'votes.user', 'votes.apartment.building'])
            ->orderByDesc('created_at')
            ->get();

        $userApartmentIds = $request->user()->apartments()->pluck('apartments.id')->toArray();
        $userId = $request->user()->id;

        $polls->transform(function ($poll) use ($userApartmentIds, $userId) {
            $poll->has_voted = $poll->votes
                ->whereIn('apartment_id', $userApartmentIds)
                ->isNotEmpty();

            $poll->options->transform(function ($option) use ($poll) {
                $option->vote_count = $option->votes->count();
                $totalVotes = $poll->votes->count();
                $option->vote_percentage = $totalVotes > 0
                    ? round(($option->vote_count / $totalVotes) * 100, 1)
                    : 0;
                unset($option->votes); // Don't leak individual voter data
                return $option;
            });

            $poll->comments = $poll->votes
                ->whereNotNull('comment')
                ->map(function ($vote) use ($userId) {
                    $isAnonymous = $vote->is_anonymous_comment;
                    return [
                        'apartment'     => $isAnonymous ? 'مجهول' : ($vote->apartment->unit_number ?? 'غير معروف'),
                        'building'      => $isAnonymous ? '' : ($vote->apartment->building->name ?? 'غير معروف'),
                        'comment'       => $vote->comment,
                        'created_at'    => $vote->created_at,
                        'is_anonymous'  => $isAnonymous,
                        'is_mine'       => $vote->user_id === $userId,
                    ];
                })->values();

            unset($poll->votes);
            return $poll;
        });

        return response()->json(['polls' => $polls]);
    }

    /**
     * POST /api/v1/resident/polls/{id}/vote
     */
    public function castVote(CastVoteRequest $request, string $id): JsonResponse
    {
        $poll = Poll::where('tenant_id', $request->user()->tenant_id)
            ->where('id', $id)
            ->firstOrFail();

        if (! $poll->isOpen()) {
            return response()->json(['message' => __('polls.closed')], 422);
        }

        // Verify user owns this apartment
        $userApartmentIds = $request->user()->apartments()->pluck('apartments.id')->toArray();
        if (! in_array($request->apartment_id, $userApartmentIds)) {
            return response()->json(['message' => __('auth.forbidden')], 403);
        }

        // Check one-vote-per-apartment constraint
        if ($poll->hasApartmentVoted($request->apartment_id)) {
            return response()->json(['message' => __('polls.already_voted')], 422);
        }

        PollVote::create([
            'poll_id'              => $poll->id,
            'poll_option_id'       => $request->poll_option_id,
            'user_id'              => $request->user()->id,
            'apartment_id'         => $request->apartment_id,
            'comment'              => $request->comment,
            'is_anonymous_comment' => $request->boolean('is_anonymous_comment'),
        ]);

        return response()->json(['message' => __('polls.vote_cast')]);
    }
}
