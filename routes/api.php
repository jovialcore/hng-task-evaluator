<?php

declare(strict_types=1);

/** @var \Illuminate\Routing\Router $router */

use App\Http\Controllers\API\EvaluateController;

$router->post('/backend/evaluate/{stage}', EvaluateController::class)->whereNumber('stage');
$router->post('/backend/evaluate', EvaluateController::class);
