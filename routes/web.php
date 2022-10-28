<?php

declare(strict_types=1);

use App\Http\Controllers\Stage1;

/** @var \Illuminate\Routing\Router $router */
$router->get('/', Stage1\EvaluateController::class);
