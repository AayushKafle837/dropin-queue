<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

/**
 * One student's request for help in a drop-in session.
 */
final class Ticket
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $studentName,
        public readonly string $unitCode,
        public readonly string $topic,
        public readonly TicketStatus $status,
        public readonly DateTimeImmutable $createdAt,
        public readonly ?string $facilitator = null,
        public readonly ?DateTimeImmutable $claimedAt = null,
        public readonly ?DateTimeImmutable $resolvedAt = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            studentName: $row['student_name'],
            unitCode: $row['unit_code'],
            topic: $row['topic'],
            status: TicketStatus::from($row['status']),
            createdAt: new DateTimeImmutable($row['created_at']),
            facilitator: $row['facilitator'] ?? null,
            claimedAt: !empty($row['claimed_at']) ? new DateTimeImmutable($row['claimed_at']) : null,
            resolvedAt: !empty($row['resolved_at']) ? new DateTimeImmutable($row['resolved_at']) : null,
        );
    }

    /** Minutes from joining the queue to being picked up, or null if not claimed yet. */
    public function waitMinutes(): ?int
    {
        if ($this->claimedAt === null) {
            return null;
        }
        return intdiv($this->claimedAt->getTimestamp() - $this->createdAt->getTimestamp(), 60);
    }
}
