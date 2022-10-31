<?php

declare(strict_types=1);

/** @var \Illuminate\Routing\Router $router */

use App\Http\Controllers\API\EvaluateController;

$router->post('/backend/evaluate/{stage}', EvaluateController::class)->whereNumber('stage');
$router->redirect('/backend/evaluate', '/backend/evaluate/1');
