<?php

namespace App\Enum;

enum OrderStatusEnum: int
{
    case PENDING = 1;
    case DONE = 2;
    case CANCELLED = 3;

    public function getStatus(): int
    {
        return match ($this) {
            OrderStatusEnum::PENDING => 1,
            OrderStatusEnum::DONE => 2,
            OrderStatusEnum::CANCELLED => 3,
        };
    }
}