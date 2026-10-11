<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Models\TicketRepository;
use App\Models\TicketStatus;
use App\Services\QueueService;
use App\Services\ValidationException;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Runs the real repository against in-memory SQLite, with a fake clock
 * so wait-time maths is deterministic.
 */
final class QueueServiceTest extends TestCase
{
    private PDO $db;
    private DateTimeImmutable $now;
    private QueueService $queue;

    protected function setUp(): void
    {
        $this->db = Database::connect('sqlite::memory:');
        $this->db->exec(
            "CREATE TABLE tickets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_name TEXT NOT NULL,
                unit_code TEXT NOT NULL,
                topic TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'waiting',
                facilitator TEXT NULL,
                created_at TEXT NOT NULL,
                claimed_at TEXT NULL,
                resolved_at TEXT NULL
            )"
        );

        $this->now = new DateTimeImmutable('2026-10-12 10:00:00');
        $this->queue = new QueueService(new TicketRepository($this->db), fn () => $this->now);
    }

    private function join(string $name = 'Sam', string $unit = 'CAB201', string $topic = 'Null reference in my loop'): int
    {
        return $this->queue->join(['student_name' => $name, 'unit_code' => $unit, 'topic' => $topic])->id;
    }

    private function advance(int $minutes): void
    {
        $this->now = $this->now->modify("+{$minutes} minutes");
    }

    public function test_joining_normalises_input_and_starts_as_waiting(): void
    {
        $ticket = $this->queue->join([
            'student_name' => '  Priya ',
            'unit_code'    => 'ifb104',
            'topic'        => 'Recursion is confusing me',
        ]);

        $this->assertSame('Priya', $ticket->studentName);
        $this->assertSame('IFB104', $ticket->unitCode);
        $this->assertSame(TicketStatus::Waiting, $ticket->status);
    }

    public function test_invalid_input_reports_every_bad_field(): void
    {
        try {
            $this->queue->join(['student_name' => '', 'unit_code' => 'CAB2O1', 'topic' => 'hi']);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(['student_name', 'unit_code', 'topic'], array_keys($e->errors));
        }
    }

    public function test_positions_are_first_come_first_served(): void
    {
        $first  = $this->join('A');
        $this->advance(1);
        $second = $this->join('B');

        $this->assertSame(1, $this->queue->positionOf($first));
        $this->assertSame(2, $this->queue->positionOf($second));
    }

    public function test_claiming_removes_a_ticket_from_the_waiting_line(): void
    {
        $first  = $this->join('A');
        $second = $this->join('B');

        $this->assertTrue($this->queue->claim($first, 'Aayush'));

        $this->assertNull($this->queue->positionOf($first));
        $this->assertSame(1, $this->queue->positionOf($second));
        $this->assertSame('Aayush', $this->queue->find($first)->facilitator);
    }

    public function test_a_ticket_cannot_be_claimed_twice(): void
    {
        $id = $this->join();

        $this->assertTrue($this->queue->claim($id, 'Aayush'));
        $this->assertFalse($this->queue->claim($id, 'Someone else'));
        $this->assertSame('Aayush', $this->queue->find($id)->facilitator);
    }

    public function test_only_claimed_tickets_can_be_resolved(): void
    {
        $id = $this->join();

        $this->assertFalse($this->queue->resolve($id));

        $this->queue->claim($id, 'Aayush');
        $this->assertTrue($this->queue->resolve($id));
        $this->assertSame(TicketStatus::Resolved, $this->queue->find($id)->status);
    }

    public function test_average_help_time_falls_back_to_default_with_no_history(): void
    {
        $this->assertSame(QueueService::DEFAULT_HELP_MINUTES, $this->queue->averageHelpMinutes());
    }

    public function test_average_help_time_uses_todays_resolved_tickets(): void
    {
        foreach ([6, 10] as $minutes) {
            $id = $this->join();
            $this->queue->claim($id, 'Aayush');
            $this->advance($minutes);
            $this->queue->resolve($id);
        }

        $this->assertSame(8, $this->queue->averageHelpMinutes());
    }

    public function test_wait_estimate_scales_with_people_ahead_and_facilitators_on_shift(): void
    {
        // Default 8 minutes per student
        $this->assertSame(0, $this->queue->estimatedWaitMinutes(1));
        $this->assertSame(24, $this->queue->estimatedWaitMinutes(4));
        $this->assertSame(12, $this->queue->estimatedWaitMinutes(4, facilitatorsOnShift: 2));
    }

    public function test_stats_summarise_the_session(): void
    {
        $a = $this->join('A');
        $b = $this->join('B');
        $this->join('C');

        $this->queue->claim($a, 'Aayush');
        $this->queue->claim($b, 'Aayush');
        $this->advance(5);
        $this->queue->resolve($b);

        $this->assertSame(
            ['waiting' => 1, 'helping' => 1, 'resolved_today' => 1, 'avg_help_minutes' => 5],
            $this->queue->stats()
        );
    }
}
