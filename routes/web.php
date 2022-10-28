<?php

declare(strict_types=1);

/** @var \Illuminate\Routing\Router $router */
//

// No CSRF Protection
$router->post('/evaluate', \App\Http\Controllers\EvaluateController::class);

$router->get('/evaluate', function () {
    require_once './views/evaluate.html';
});

$router->get('/', fn () => 'Hello World');
