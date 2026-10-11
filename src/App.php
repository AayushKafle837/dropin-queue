<?php

declare(strict_types=1);

namespace App;

use App\Controllers\FacilitatorController;
use App\Controllers\QueueController;
use App\Core\Router;
use App\Core\View;
use App\Models\TicketRepository;
use App\Services\QueueService;
use PDO;

/**
 * Wires the app together: one place to see every route and dependency.
 */
final class App
{
    public static function routes(PDO $db, string $facilitatorPin, string $viewPath): Router
    {
        $view  = new View($viewPath);
        $queue = new QueueService(new TicketRepository($db));

        $students     = new QueueController($view, $queue);
        $facilitators = new FacilitatorController($view, $queue, $facilitatorPin);

        $router = new Router();

        $router->get('/', $students->home(...));
        $router->post('/join', $students->join(...));
        $router->get('/tickets/{id}', $students->show(...));

        $router->get('/facilitator/login', $facilitators->loginForm(...));
        $router->post('/facilitator/login', $facilitators->login(...));
        $router->post('/facilitator/logout', $facilitators->logout(...));
        $router->get('/facilitator', $facilitators->board(...));
        $router->post('/tickets/{id}/claim', $facilitators->claim(...));
        $router->post('/tickets/{id}/resolve', $facilitators->resolve(...));

        return $router;
    }
}
