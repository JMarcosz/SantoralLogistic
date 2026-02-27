<?php

namespace App\Exceptions;

use DomainException;

/**
 * Exception thrown when attempting to create or update a fiscal sequence
 * that overlaps with an existing range for the same ncf_type + series.
 */
class FiscalSequenceOverlapException extends DomainException
{
    public function __construct(
        string $ncfType,
        ?string $series,
        string $ncfFrom,
        string $ncfTo,
        ?string $conflictingRange = null
    ) {
        $seriesText = $series ? "serie '{$series}'" : "sin serie";
        $message = "El rango NCF propuesto [{$ncfFrom} - {$ncfTo}] para el tipo {$ncfType} ({$seriesText}) " .
            "se solapa con un rango existente.";

        if ($conflictingRange) {
            $message .= " Rango conflictivo: {$conflictingRange}.";
        }

        $message .= " Por favor, verifique que no existan rangos duplicados o solapados.";

        parent::__construct($message);
    }

    public static function forRange(
        string $ncfType,
        ?string $series,
        string $ncfFrom,
        string $ncfTo,
        ?string $conflictingRange = null
    ): self {
        return new self($ncfType, $series, $ncfFrom, $ncfTo, $conflictingRange);
    }
}
