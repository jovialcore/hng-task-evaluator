<?php

require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;

$container = new Container();

$request = Request::capture();

// ---------------------------------------------------------------------------------------------------------------------
// Register service container bindings
// ---------------------------------------------------------------------------------------------------------------------

$container->instance(Request::class, $request);
$container->instance(CallableDispatcherContract::class, new CallableDispatcher($container));

// ---------------------------------------------------------------------------------------------------------------------
// Create the router instance and define the routes
// ---------------------------------------------------------------------------------------------------------------------

$router = new Router(new Dispatcher($container), $container);
$router->group(
    ['namespace' => 'App\Http\Controllers'],
    static fn (Router $router) => require_once './routes/web.php'
);

// ---------------------------------------------------------------------------------------------------------------------
// Send the response
// ---------------------------------------------------------------------------------------------------------------------

$response = $router->dispatch($request);
$response->send();
