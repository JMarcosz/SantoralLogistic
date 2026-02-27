<?php

namespace App\Exceptions\Accounting;

use Exception;

class PeriodClosedException extends Exception
{
    protected $message = 'Accounting period is closed';
    protected $code = 422; // Unprocessable Entity
}
