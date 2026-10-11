<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Services\QueueService;
use App\Services\ValidationException;

/**
 * Facilitator board: see the queue, claim the next student, mark them as helped.
 * Protected by a shared session PIN, which is enough for a room of tutors on one shift.
 */
final class FacilitatorController extends Controller
{
    public function __construct(
        View $view,
        private readonly QueueService $queue,
        private readonly string $pin
    ) {
        parent::__construct($view);
    }

    public function loginForm(): string
    {
        return $this->render('facilitator/login', ['title' => 'Facilitator sign-in', 'error' => null]);
    }

    public function login(): string
    {
        $this->requireCsrf();

        $name = trim((string) ($_POST['name'] ?? ''));
        $pin  = (string) ($_POST['pin'] ?? '');

        if ($name === '' || !hash_equals($this->pin, $pin)) {
            http_response_code(401);
            return $this->render('facilitator/login', [
                'title' => 'Facilitator sign-in',
                'error' => 'That name and PIN combination did not work.',
            ]);
        }

        session_regenerate_id(true);
        $_SESSION['facilitator'] = $name;
        return $this->redirect('/facilitator');
    }

    public function logout(): string
    {
        $this->requireCsrf();
        unset($_SESSION['facilitator']);
        return $this->redirect('/');
    }

    public function board(): string
    {
        if (!$this->signedIn()) {
            return $this->redirect('/facilitator/login');
        }

        return $this->render('facilitator/board', [
            'title'   => 'Queue board',
            'me'      => $_SESSION['facilitator'],
            'tickets' => $this->queue->openTickets(),
            'stats'   => $this->queue->stats(),
            'refresh' => 15,
        ]);
    }

    public function claim(string $id): string
    {
        $this->requireCsrf();
        if (!$this->signedIn()) {
            return $this->redirect('/facilitator/login');
        }

        try {
            $claimed = $this->queue->claim((int) $id, $_SESSION['facilitator']);
        } catch (ValidationException $e) {
            $claimed = false;
        }

        $this->flash(
            $claimed ? 'success' : 'warning',
            $claimed ? 'Ticket claimed. Head over.' : 'Someone else already claimed that one.'
        );
        return $this->redirect('/facilitator');
    }

    public function resolve(string $id): string
    {
        $this->requireCsrf();
        if (!$this->signedIn()) {
            return $this->redirect('/facilitator/login');
        }

        $this->queue->resolve((int) $id);
        $this->flash('success', 'Marked as helped.');
        return $this->redirect('/facilitator');
    }

    private function signedIn(): bool
    {
        return !empty($_SESSION['facilitator']);
    }
}
