<?php

require_once '../vendor/autoload.php';

use App\Validator;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Routing\Pipeline;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use App\Http\Middleware\TrimStrings;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;

$container = new Container();
$request = Request::capture();
$router = new Router(new Dispatcher($container), $container);

// ---------------------------------------------------------------------------------------------------------------------
// Validator
// ---------------------------------------------------------------------------------------------------------------------

Validator::setInstance(
    new Validator($container, new Translator(new FileLoader(new Filesystem(), 'lang'), 'en'))
);

// ---------------------------------------------------------------------------------------------------------------------
// Register service container bindings
// ---------------------------------------------------------------------------------------------------------------------

$container->instance(Request::class, $request);
$container->instance(CallableDispatcherContract::class, new CallableDispatcher($container));
$container->singleton(Validator::class, fn () => Validator::getInstance());

// ---------------------------------------------------------------------------------------------------------------------
// Middleware
// ---------------------------------------------------------------------------------------------------------------------

$globalMiddleware = [
    TrimStrings::class,
];

$routeMiddleware = [];

foreach ($routeMiddleware as $key => $middleware) {
    $router->aliasMiddleware($key, $middleware);
}

// ---------------------------------------------------------------------------------------------------------------------
// Define the routes
// ---------------------------------------------------------------------------------------------------------------------

$router->group([], static fn (Router $router) => require_once '../routes/web.php');
$router->group(['prefix' => 'api'], static fn (Router $router) => require_once '../routes/api.php');

// ---------------------------------------------------------------------------------------------------------------------
// Send the response
// ---------------------------------------------------------------------------------------------------------------------

$response = (new Pipeline($container))
    ->send($request)
    ->through($globalMiddleware)
    ->then(function ($request) use ($router) {
        return $router->dispatch($request);
    });

$response->send();
