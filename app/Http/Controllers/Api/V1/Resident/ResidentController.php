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

    // ─── Dashboard / Balance ──────────────────────────────────────────

    /**
     * GET /api/v1/resident/my-balance
     */
    public function myBalance(Request $request): JsonResponse
    {
        $user      = $request->user();
        $apartment = $user->apartments()->first();

        if (! $apartment) {
            return response()->json(['message' => __('resident.no_apartment')], 404);
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

        if (! $tenant->is_transparency_enabled) {
            return response()->json([
                'enabled' => false,
                'message' => __('resident.transparency_disabled'),
            ]);
        }

        $currentMonth = now()->format('Y-m');

        // Get all apartments and their payment status for current month (mask personal details)
        $apartments = \App\Models\Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->with('building')
            ->get()
            ->map(function ($apt) use ($currentMonth) {
                $isPaid = Payment::where('apartment_id', $apt->id)
                    ->whereJsonContains('covered_months', $currentMonth)
                    ->exists();

                return [
                    'unit'     => $apt->unit_number,
                    'building' => $apt->building->name,
                    'floor'    => $apt->floor,
                    'status'   => $isPaid ? 'PAID' : 'OUTSTANDING',
                ];
            });

        // Expense breakdown for donut chart
        $monthExpenses = Expense::where('tenant_id', $tenant->id)
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get();

        return response()->json([
            'enabled'           => true,
            'collection_status' => $apartments,
            'expense_breakdown' => $monthExpenses,
        ]);
    }

    // ─── Suggestions ──────────────────────────────────────────────────

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
            ->with(['options.votes', 'votes'])
            ->orderByDesc('created_at')
            ->get();

        $userApartmentIds = $request->user()->apartments()->pluck('apartments.id')->toArray();

        $polls->transform(function ($poll) use ($userApartmentIds) {
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
            'poll_id'        => $poll->id,
            'poll_option_id' => $request->poll_option_id,
            'user_id'        => $request->user()->id,
            'apartment_id'   => $request->apartment_id,
        ]);

        return response()->json(['message' => __('polls.vote_cast')]);
    }
}
