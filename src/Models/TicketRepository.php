<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use PDO;

/**
 * All SQL for tickets lives here. Every query uses prepared statements.
 */
final class TicketRepository
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly PDO $db)
    {
    }

    public function create(string $studentName, string $unitCode, string $topic, DateTimeImmutable $now): Ticket
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tickets (student_name, unit_code, topic, status, created_at)
             VALUES (:name, :unit, :topic, :status, :created)'
        );
        $stmt->execute([
            'name'    => $studentName,
            'unit'    => $unitCode,
            'topic'   => $topic,
            'status'  => TicketStatus::Waiting->value,
            'created' => $now->format(self::DATE_FORMAT),
        ]);

        return $this->find((int) $this->db->lastInsertId());
    }

    public function find(int $id): ?Ticket
    {
        $stmt = $this->db->prepare('SELECT * FROM tickets WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Ticket::fromRow($row) : null;
    }

    /** @return Ticket[] Open tickets, oldest first, so the queue is first-come first-served. */
    public function open(): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM tickets WHERE status IN (:waiting, :helping) ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute([
            'waiting' => TicketStatus::Waiting->value,
            'helping' => TicketStatus::Helping->value,
        ]);

        return array_map([Ticket::class, 'fromRow'], $stmt->fetchAll());
    }

    /** @return Ticket[] */
    public function resolvedSince(DateTimeImmutable $since): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM tickets WHERE status = :status AND resolved_at >= :since ORDER BY resolved_at DESC'
        );
        $stmt->execute([
            'status' => TicketStatus::Resolved->value,
            'since'  => $since->format(self::DATE_FORMAT),
        ]);

        return array_map([Ticket::class, 'fromRow'], $stmt->fetchAll());
    }

    /**
     * Moves a ticket from one status to another. The WHERE clause on the current status
     * means two facilitators clicking "claim" at the same moment can't both win.
     */
    public function transition(
        int $id,
        TicketStatus $from,
        TicketStatus $to,
        DateTimeImmutable $now,
        ?string $facilitator = null
    ): bool {
        $column = $to === TicketStatus::Helping ? 'claimed_at' : 'resolved_at';

        $stmt = $this->db->prepare(
            "UPDATE tickets
                SET status = :to, {$column} = :now, facilitator = COALESCE(:facilitator, facilitator)
              WHERE id = :id AND status = :from"
        );
        $stmt->execute([
            'to'          => $to->value,
            'now'         => $now->format(self::DATE_FORMAT),
            'facilitator' => $facilitator,
            'id'          => $id,
            'from'        => $from->value,
        ]);

        return $stmt->rowCount() === 1;
    }
}
