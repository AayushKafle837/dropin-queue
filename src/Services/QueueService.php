<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketRepository;
use App\Models\TicketStatus;
use Closure;
use DateTimeImmutable;

/**
 * Business rules for the queue: who can join, who's next, and how long the wait is.
 * Controllers stay thin and call into this; the database stays behind the repository.
 */
final class QueueService
{
    /** Used for wait estimates until there's real data from the session. */
    public const DEFAULT_HELP_MINUTES = 8;

    private Closure $clock;

    public function __construct(private readonly TicketRepository $tickets, ?Closure $clock = null)
    {
        $this->clock = $clock ?? static fn (): DateTimeImmutable => new DateTimeImmutable();
    }

    /** @param array<string, mixed> $input */
    public function join(array $input): Ticket
    {
        $name  = trim((string) ($input['student_name'] ?? ''));
        $unit  = strtoupper(trim((string) ($input['unit_code'] ?? '')));
        $topic = trim((string) ($input['topic'] ?? ''));

        $errors = [];
        if ($name === '' || mb_strlen($name) > 60) {
            $errors['student_name'] = 'Enter your first name (up to 60 characters).';
        }
        if (!preg_match('/^[A-Z]{3}\d{3}$/', $unit)) {
            $errors['unit_code'] = 'Unit codes look like CAB201 or IFB104.';
        }
        if (mb_strlen($topic) < 5 || mb_strlen($topic) > 280) {
            $errors['topic'] = 'Say a little about what you need help with (5 to 280 characters).';
        }
        if ($errors) {
            throw new ValidationException($errors);
        }

        return $this->tickets->create($name, $unit, $topic, $this->now());
    }

    public function claim(int $id, string $facilitator): bool
    {
        $facilitator = trim($facilitator);
        if ($facilitator === '') {
            throw new ValidationException(['facilitator' => 'Enter your name before claiming a ticket.']);
        }

        return $this->tickets->transition($id, TicketStatus::Waiting, TicketStatus::Helping, $this->now(), $facilitator);
    }

    public function resolve(int $id): bool
    {
        return $this->tickets->transition($id, TicketStatus::Helping, TicketStatus::Resolved, $this->now());
    }

    /** @return Ticket[] */
    public function openTickets(): array
    {
        return $this->tickets->open();
    }

    public function find(int $id): ?Ticket
    {
        return $this->tickets->find($id);
    }

    /** 1-based position among waiting tickets, or null once the ticket has been picked up. */
    public function positionOf(int $id): ?int
    {
        $waiting = array_values(array_filter(
            $this->tickets->open(),
            static fn (Ticket $t): bool => $t->status === TicketStatus::Waiting
        ));

        foreach ($waiting as $index => $ticket) {
            if ($ticket->id === $id) {
                return $index + 1;
            }
        }
        return null;
    }

    /**
     * Average minutes a facilitator spends per student today, from claimed to resolved.
     * Falls back to a sensible default when nobody has been helped yet.
     */
    public function averageHelpMinutes(): int
    {
        $today = $this->now()->setTime(0, 0);
        $durations = [];

        foreach ($this->tickets->resolvedSince($today) as $ticket) {
            if ($ticket->claimedAt && $ticket->resolvedAt) {
                $durations[] = ($ticket->resolvedAt->getTimestamp() - $ticket->claimedAt->getTimestamp()) / 60;
            }
        }

        if (!$durations) {
            return self::DEFAULT_HELP_MINUTES;
        }
        return max(1, (int) round(array_sum($durations) / count($durations)));
    }

    /** Rough estimate: people ahead of you, shared across the facilitators on shift. */
    public function estimatedWaitMinutes(int $position, int $facilitatorsOnShift = 1): int
    {
        $ahead = max(0, $position - 1);
        return (int) ceil($ahead * $this->averageHelpMinutes() / max(1, $facilitatorsOnShift));
    }

    /** @return array{waiting: int, helping: int, resolved_today: int, avg_help_minutes: int} */
    public function stats(): array
    {
        $open = $this->tickets->open();
        $count = static fn (TicketStatus $s): int => count(array_filter($open, static fn (Ticket $t) => $t->status === $s));

        return [
            'waiting'          => $count(TicketStatus::Waiting),
            'helping'          => $count(TicketStatus::Helping),
            'resolved_today'   => count($this->tickets->resolvedSince($this->now()->setTime(0, 0))),
            'avg_help_minutes' => $this->averageHelpMinutes(),
        ];
    }

    private function now(): DateTimeImmutable
    {
        return ($this->clock)();
    }
}
