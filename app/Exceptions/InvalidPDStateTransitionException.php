<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid state transition is attempted
 * on a PickupOrder or DeliveryOrder.
 */
class InvalidPDStateTransitionException extends Exception
{
    public function __construct(
        public readonly string $orderType,
        public readonly string $currentStatus,
        public readonly string $attemptedAction,
        ?string $message = null
    ) {
        $message ??= sprintf(
            'No se puede ejecutar "%s" en %s con estado "%s".',
            $attemptedAction,
            $orderType,
            $currentStatus
        );

        parent::__construct($message);
    }
}
