<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Services\QueueService;
use App\Services\ValidationException;

/**
 * Student-facing pages: join the queue and check your place.
 */
final class QueueController extends Controller
{
    public function __construct(View $view, private readonly QueueService $queue)
    {
        parent::__construct($view);
    }

    public function home(): string
    {
        return $this->render('queue/join', [
            'title'  => 'Join the queue',
            'stats'  => $this->queue->stats(),
            'old'    => [],
            'errors' => [],
        ]);
    }

    public function join(): string
    {
        $this->requireCsrf();

        try {
            $ticket = $this->queue->join($_POST);
        } catch (ValidationException $e) {
            http_response_code(422);
            return $this->render('queue/join', [
                'title'  => 'Join the queue',
                'stats'  => $this->queue->stats(),
                'old'    => $_POST,
                'errors' => $e->errors,
            ]);
        }

        $_SESSION['my_tickets'][] = $ticket->id;
        $this->flash('success', "You're in the queue, {$ticket->studentName}. Keep this page open.");
        return $this->redirect('/tickets/' . $ticket->id);
    }

    public function show(string $id): string
    {
        $ticket = $this->queue->find((int) $id);

        // Students can only see tickets they created in this browser session.
        if ($ticket === null || !in_array($ticket->id, $_SESSION['my_tickets'] ?? [], true)) {
            http_response_code(404);
            return $this->render('error', ['title' => 'Not found', 'message' => "We couldn't find that ticket."]);
        }

        $position = $this->queue->positionOf($ticket->id);

        return $this->render('queue/ticket', [
            'title'    => 'Your place in the queue',
            'ticket'   => $ticket,
            'position' => $position,
            'estimate' => $position ? $this->queue->estimatedWaitMinutes($position) : null,
            'refresh'  => 20,
        ]);
    }
}
