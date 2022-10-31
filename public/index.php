<?php

require_once '../vendor/autoload.php';

use App\Validator;
use Illuminate\Http\Request;
use Whoops\Run as WhoopsRun;
use Illuminate\Routing\Router;
use Illuminate\Routing\Pipeline;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use App\Http\Middleware\TrimStrings;
use Illuminate\Filesystem\Filesystem;
use Whoops\Handler\PrettyPageHandler;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;

defined('PROJECT_ROOT_PATH') or define('PROJECT_ROOT_PATH', __DIR__.'/..');

$container = new Container();
$container->setInstance($container);

$request = Request::capture();
$router = new Router(new Dispatcher($container), $container);
$translator = new Translator(new FileLoader(new Filesystem(), '../lang'), 'en');

// ---------------------------------------------------------------------------------------------------------------------
// Error
// ---------------------------------------------------------------------------------------------------------------------

$whoops = new WhoopsRun();
$whoops->pushHandler(new PrettyPageHandler());
$whoops->register();

// ---------------------------------------------------------------------------------------------------------------------
// Validator
// ---------------------------------------------------------------------------------------------------------------------

Validator::setInstance(new Validator($container, $translator));

// ---------------------------------------------------------------------------------------------------------------------
// Register service container bindings
// ---------------------------------------------------------------------------------------------------------------------

$container->instance(Request::class, $request);
$container->instance(TranslatorContract::class, $translator);
$container->instance(Validator::class, Validator::getInstance());
$container->instance(CallableDispatcherContract::class, new CallableDispatcher($container));

// ---------------------------------------------------------------------------------------------------------------------
// Middleware
// ---------------------------------------------------------------------------------------------------------------------

collect([
    // 'trimStrings' => TrimStrings::class,
])->each(
    fn ($middleware, $key) => $router->aliasMiddleware($key, $middleware)
);

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
    ->through([
        TrimStrings::class,
    ])
    ->then(function ($request) use ($router) {
        return $router->dispatch($request);
    });

$response->send();
