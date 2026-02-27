<?php

namespace App\Exceptions;

use DomainException;

/**
 * Exception thrown when no valid fiscal sequence is available for the requested NCF type/series.
 */
class NoFiscalSequenceAvailableException extends DomainException
{
    public function __construct(
        public readonly string $ncfType,
        public readonly ?string $series = null
    ) {
        $seriesInfo = $series ? " (series: {$series})" : '';
        $message = "No hay rango de NCF disponible para el tipo '{$ncfType}'{$seriesInfo}. Verifique que exista un rango activo y vigente.";

        parent::__construct($message);
    }
}
