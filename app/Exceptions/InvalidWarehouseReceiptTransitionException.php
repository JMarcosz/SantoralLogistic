<?php

namespace App\Exceptions;

use Exception;

class InvalidWarehouseReceiptTransitionException extends Exception
{
    public function __construct(
        public string $currentStatus,
        public string $attemptedTransition,
        ?string $customMessage = null
    ) {
        $message = $customMessage ?? "No se puede ejecutar '$attemptedTransition' desde el estado '$currentStatus'.";
        parent::__construct($message);
    }
}
