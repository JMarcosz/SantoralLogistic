<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Exceptions\InvalidQuoteStateTransitionException;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

/**
 * State machine for Quote status transitions.
 *
 * Valid transitions:
 * - draft → sent
 * - sent → approved
 * - sent → rejected
 *
 * Invalid transitions throw InvalidQuoteStateTransitionException.
 */
class QuoteStateMachine
{
    /**
     * Transition matrix defining valid transitions.
     * Format: [from_status => [allowed_to_statuses]]
     */
    private const TRANSITIONS = [
        'draft' => ['sent'],
        'sent' => ['approved', 'rejected'],
        'approved' => [],  // Terminal state
        'rejected' => [],  // Terminal state
        'expired' => [],   // Terminal state
    ];

    /**
     * Check if a transition is valid.
     */
    public function canTransition(QuoteStatus $from, QuoteStatus $to): bool
    {
        $allowedTransitions = self::TRANSITIONS[$from->value] ?? [];

        return in_array($to->value, $allowedTransitions);
    }

    /**
     * Get valid transitions from a given status.
     *
     * @return QuoteStatus[]
     */
    public function getValidTransitions(QuoteStatus $from): array
    {
        $transitions = self::TRANSITIONS[$from->value] ?? [];

        return array_map(fn($s) => QuoteStatus::from($s), $transitions);
    }

    /**
     * Transition a quote to a new status (with validation).
     *
     * @throws InvalidQuoteStateTransitionException
     */
    public function transition(Quote $quote, QuoteStatus $to): Quote
    {
        $from = $quote->status;

        if (!$this->canTransition($from, $to)) {
            throw new InvalidQuoteStateTransitionException(
                $from->value,
                $to->value,
                $this->getTransitionError($from, $to)
            );
        }

        return DB::transaction(function () use ($quote, $to) {
            $quote->status = $to;
            $quote->save();

            return $quote;
        });
    }

    /**
     * Send a quote (draft → sent).
     * Captures terms snapshots for legal traceability.
     *
     * @throws InvalidQuoteStateTransitionException
     */
    public function send(Quote $quote): Quote
    {
        if ($quote->status !== QuoteStatus::Draft) {
            throw InvalidQuoteStateTransitionException::cannotSend($quote->status->value);
        }

        return DB::transaction(function () use ($quote) {
            // Capture terms snapshots before transition
            $termsResolver = app(TermsResolverService::class);
            $termsResolver->captureQuoteSnapshots($quote);
            $quote->save();

            // Transition status
            $quote->status = QuoteStatus::Sent;
            $quote->save();

            return $quote;
        });
    }

    /**
     * Approve a quote (sent → approved).
     *
     * @throws InvalidQuoteStateTransitionException
     */
    public function approve(Quote $quote): Quote
    {
        if ($quote->status !== QuoteStatus::Sent) {
            throw InvalidQuoteStateTransitionException::cannotApprove($quote->status->value);
        }

        return $this->transition($quote, QuoteStatus::Approved);
    }

    /**
     * Reject a quote (sent → rejected).
     *
     * @throws InvalidQuoteStateTransitionException
     */
    public function reject(Quote $quote): Quote
    {
        if ($quote->status !== QuoteStatus::Sent) {
            throw InvalidQuoteStateTransitionException::cannotReject($quote->status->value);
        }

        return $this->transition($quote, QuoteStatus::Rejected);
    }

    /**
     * Check if a quote is in a terminal (finalized) state.
     */
    public function isFinalized(Quote $quote): bool
    {
        return in_array($quote->status, [
            QuoteStatus::Approved,
            QuoteStatus::Rejected,
            QuoteStatus::Expired,
        ]);
    }

    /**
     * Check if the quote can still be edited.
     */
    public function canEdit(Quote $quote): bool
    {
        return $quote->status === QuoteStatus::Draft;
    }

    /**
     * Get human-readable error for invalid transition.
     */
    private function getTransitionError(QuoteStatus $from, QuoteStatus $to): string
    {
        if ($this->isTerminalState($from)) {
            return "Quote is in terminal state '{$from->value}' and cannot be changed";
        }

        $validTargets = self::TRANSITIONS[$from->value] ?? [];

        if (empty($validTargets)) {
            return "No transitions allowed from '{$from->value}'";
        }

        return "From '{$from->value}', only transitions to: " . implode(', ', $validTargets);
    }

    private function isTerminalState(QuoteStatus $status): bool
    {
        return empty(self::TRANSITIONS[$status->value] ?? []);
    }
}
