<?php

namespace App\Http\Controllers\Api\V1\Syndic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Syndic\CreatePollRequest;
use App\Events\PollCreated;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Suggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuggestionPollController extends Controller
{
    // ─── Suggestions ──────────────────────────────────────────────────

    /**
     * GET /api/v1/syndic/suggestions
     */
    public function suggestions(Request $request): JsonResponse
    {
        $suggestions = Suggestion::where('tenant_id', $request->user()->tenant_id)
            ->with(['user:id,first_name,last_name', 'apartment:id,unit_number'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($suggestions);
    }

    /**
     * POST /api/v1/syndic/suggestions/{id}/convert-to-poll
     */
    public function convertToPoll(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'options'   => ['required', 'array', 'min:2', 'max:10'],
            'options.*' => ['required', 'string', 'max:255'],
            'expires_at' => ['required', 'date', 'after:now'],
        ]);

        $suggestion = Suggestion::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $poll = DB::transaction(function () use ($request, $suggestion) {
            $suggestion->convertToPoll();

            $poll = Poll::create([
                'tenant_id'   => $request->user()->tenant_id,
                'created_by'  => $request->user()->id,
                'title'       => $suggestion->title,
                'description' => $suggestion->description,
                'expires_at'  => $request->expires_at,
                'is_active'   => true,
            ]);

            foreach ($request->options as $optionText) {
                PollOption::create([
                    'poll_id'     => $poll->id,
                    'option_text' => $optionText,
                ]);
            }

            return $poll;
        });

        event(new PollCreated($poll));

        return response()->json([
            'message' => __('suggestions.converted_to_poll'),
            'poll'    => $poll->load('options'),
        ], 201);
    }

    // ─── Polls ────────────────────────────────────────────────────────

    /**
     * POST /api/v1/syndic/polls
     */
    public function createPoll(CreatePollRequest $request): JsonResponse
    {
        $poll = DB::transaction(function () use ($request) {
            $poll = Poll::create([
                'tenant_id'   => $request->user()->tenant_id,
                'created_by'  => $request->user()->id,
                'title'       => $request->title,
                'description' => $request->description,
                'expires_at'  => $request->expires_at,
                'is_active'   => true,
            ]);

            foreach ($request->options as $optionText) {
                PollOption::create([
                    'poll_id'     => $poll->id,
                    'option_text' => $optionText,
                ]);
            }

            return $poll;
        });

        event(new PollCreated($poll));

        return response()->json([
            'message' => __('polls.created'),
            'poll'    => $poll->load('options'),
        ], 201);
    }
}
