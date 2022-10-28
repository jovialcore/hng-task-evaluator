<?php

declare(strict_types=1);

use App\Http\Controllers\SlackBotController;

/** @var \Illuminate\Routing\Router $router */
$router->post('/slackbot', SlackBotController::class);
$router->get('/', fn () => 'Hello World');
