<?php

namespace App\Exceptions;

use App\Enums\ShippingOrderStatus;
use DomainException;

/**
 * Exception thrown when an invalid shipping order state transition is attempted.
 */
class InvalidShippingOrderStateTransitionException extends DomainException
{
    public function __construct(
        public readonly ShippingOrderStatus $fromStatus,
        public readonly ShippingOrderStatus $toStatus,
        public readonly ?string $reason = null
    ) {
        $message = "Cannot transition shipping order from '{$fromStatus->value}' to '{$toStatus->value}'";

        if ($reason) {
            $message .= ": {$reason}";
        }

        parent::__construct($message);
    }

    public static function cannotBook(ShippingOrderStatus $currentStatus): self
    {
        return new self(
            $currentStatus,
            ShippingOrderStatus::Booked,
            'Order must be in draft status to be booked'
        );
    }

    public static function cannotStartTransit(ShippingOrderStatus $currentStatus): self
    {
        return new self(
            $currentStatus,
            ShippingOrderStatus::InTransit,
            'Order must be in booked status to start transit'
        );
    }

    public static function cannotArrive(ShippingOrderStatus $currentStatus): self
    {
        return new self(
            $currentStatus,
            ShippingOrderStatus::Arrived,
            'Order must be in transit to mark as arrived'
        );
    }

    public static function cannotDeliver(ShippingOrderStatus $currentStatus): self
    {
        return new self(
            $currentStatus,
            ShippingOrderStatus::Delivered,
            'Order must have arrived to mark as delivered'
        );
    }

    public static function cannotClose(ShippingOrderStatus $currentStatus): self
    {
        return new self(
            $currentStatus,
            ShippingOrderStatus::Closed,
            'Order must be delivered to be closed'
        );
    }

    public static function cannotCancel(ShippingOrderStatus $currentStatus): self
    {
        return new self(
            $currentStatus,
            ShippingOrderStatus::Cancelled,
            'Order cannot be cancelled from current status'
        );
    }

    public static function alreadyTerminal(ShippingOrderStatus $currentStatus): self
    {
        return new self(
            $currentStatus,
            $currentStatus,
            'Order is in a terminal state and cannot be modified'
        );
    }
}
