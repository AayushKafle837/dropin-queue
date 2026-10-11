<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function test_it_runs_the_matching_route(): void
    {
        $router = new Router();
        $router->get('/', fn () => 'home');

        $this->assertSame(['status' => 200, 'body' => 'home'], $router->dispatch('GET', '/'));
    }

    public function test_it_passes_named_parameters_to_the_handler(): void
    {
        $router = new Router();
        $router->post('/tickets/{id}/claim', fn (string $id) => "claimed {$id}");

        $this->assertSame('claimed 42', $router->dispatch('POST', '/tickets/42/claim')['body']);
    }

    public function test_it_ignores_query_strings_and_trailing_slashes(): void
    {
        $router = new Router();
        $router->get('/facilitator', fn () => 'board');

        $this->assertSame('board', $router->dispatch('GET', '/facilitator/?tab=all')['body']);
    }

    public function test_unknown_paths_return_404(): void
    {
        $this->assertSame(404, (new Router())->dispatch('GET', '/nope')['status']);
    }

    public function test_wrong_method_returns_405(): void
    {
        $router = new Router();
        $router->post('/join', fn () => 'joined');

        $this->assertSame(405, $router->dispatch('GET', '/join')['status']);
    }
}
