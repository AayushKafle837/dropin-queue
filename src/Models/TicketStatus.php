<?php

declare(strict_types=1);

namespace App\Models;

enum TicketStatus: string
{
    case Waiting  = 'waiting';
    case Helping  = 'helping';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Waiting  => 'Waiting',
            self::Helping  => 'Being helped',
            self::Resolved => 'Resolved',
        };
    }
}
