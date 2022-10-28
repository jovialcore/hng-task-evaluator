<?php

declare(strict_types=1);

use App\Http\Controllers\Stage1;

/** @var \Illuminate\Routing\Router $router */
$router->post('/stage1/evaluate', Stage1\API\EvaluateController::class);
