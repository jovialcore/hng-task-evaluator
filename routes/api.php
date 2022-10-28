<?php

declare(strict_types=1);

/** @var \Illuminate\Routing\Router $router */
$router->post('/backend/evaluate', \App\Http\Controllers\API\EvaluateController::class);
