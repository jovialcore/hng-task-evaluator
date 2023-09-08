<?php

declare(strict_types=1);

use Illuminate\Http\Response;
use App\Http\Controllers\SlackBotController;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

/** @var \Illuminate\Routing\Router $router */
$router->post('/slackbot/{stage}', SlackBotController::class);
$router->post('/slackbot', SlackBotController::class);

$router->get('/passed', fn () => new Response(
    file_get_contents(PROJECT_ROOT_PATH . '/storage/passed.csv'),
    HttpFoundationResponse::HTTP_OK,
    [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="passed.csv"',
    ]
));

$router->get('/', fn () => 'Hello World');
