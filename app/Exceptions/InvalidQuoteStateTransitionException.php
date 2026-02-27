<?php

namespace App\Exceptions;

use DomainException;

/**
 * Exception thrown when an invalid quote state transition is attempted.
 */
class InvalidQuoteStateTransitionException extends DomainException
{
    public function __construct(
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly ?string $reason = null
    ) {
        $message = "Cannot transition quote from '{$fromStatus}' to '{$toStatus}'";

        if ($reason) {
            $message .= ": {$reason}";
        }

        parent::__construct($message);
    }

    public static function cannotSend(string $currentStatus): self
    {
        return new self($currentStatus, 'sent', 'Quote must be in draft status to be sent');
    }

    public static function cannotApprove(string $currentStatus): self
    {
        return new self($currentStatus, 'approved', 'Quote must be in sent status to be approved');
    }

    public static function cannotReject(string $currentStatus): self
    {
        return new self($currentStatus, 'rejected', 'Quote must be in sent status to be rejected');
    }

    public static function alreadyFinalized(string $currentStatus): self
    {
        return new self($currentStatus, 'any', 'Quote is already finalized and cannot be modified');
    }

    public static function cannotRevertToDraft(string $currentStatus): self
    {
        return new self($currentStatus, 'draft', 'Cannot revert quote back to draft once sent');
    }
}
