<?php

namespace App\Exceptions;

use App\Models\FiscalSequence;
use DomainException;

/**
 * Exception thrown when a fiscal sequence range has been exhausted.
 */
class FiscalSequenceExhaustedException extends DomainException
{
    public function __construct(
        public readonly FiscalSequence $sequence
    ) {
        $message = "El rango de NCF {$sequence->ncf_type} ha sido agotado. " .
            "Último NCF asignado: {$sequence->current_ncf}. " .
            "Rango: {$sequence->ncf_from} - {$sequence->ncf_to}. " .
            "Por favor, configure un nuevo rango autorizado por DGII.";

        parent::__construct($message);
    }

    public static function forSequence(FiscalSequence $sequence): self
    {
        return new self($sequence);
    }
}
