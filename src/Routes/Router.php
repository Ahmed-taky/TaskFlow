<?php
namespace App\Routes;

use App\Helpers\Request;
use App\Helpers\Response;
use FastRoute\Dispatcher;
use App\Routes\Route;

use App\Routes\Pipeline;
use function FastRoute\simpleDispatcher;

class Router
{
    private array $routes = [];
    private ?Dispatcher $dispatcher = null;
    private array $globalMiddleware = [];
    public function __construct()
    {

    }

    public function get(string $path, callable $handler, array $middleware = [])
    {
        $route = new Route('GET', $path, $middleware, $handler);
        $this->routes[] = $route;
    }
    public function post(string $path, callable $handler, array $middleware = [])
    {
        $route = new Route('POST', $path, $middleware, $handler);
        $this->routes[] = $route;
    }
    public function put(string $path, callable $handler, array $middleware = [])
    {
        $route = new Route('PUT', $path, $middleware, $handler);
        $this->routes[] = $route;
    }
    public function patch(string $path, callable $handler, array $middleware = [])
    {
        $route = new Route('PATCH', $path, $middleware, $handler);
        $this->routes[] = $route;
    }
    public function delete(string $path, callable $handler, array $middleware = [])
    {
        $route = new Route('DELETE', $path, $middleware, $handler);
        $this->routes[] = $route;
    }
    public function use(callable $middleware)
    {
        $this->globalMiddleware[] = $middleware;
    }

    public function buildDispatcher()
    {
        $this->dispatcher = simpleDispatcher(function ($r) {
            foreach ($this->routes as $route) {
                $r->addRoute(
                    $route->method,
                    $route->path,
                    function ($request) use ($route) {
                        return Pipeline::run($route->middleware, $request, function ($request) use ($route) {
                            return ($route->handler)($request);
                        });
                    }
                );
            }
        });
    }
    public function handle(Request $request)
    {
        if ($this->dispatcher === null) {
            $this->buildDispatcher();
        }
        $dispatcherHandler = function () use ($request) {
            $info = $this->dispatcher->dispatch($request->method, $request->uri);
            switch ($info[0]) {
                case Dispatcher::FOUND:
                    $handler = $info[1];
                    $request->params = $info[2];
                    $handler($request);
                    break;
                case Dispatcher::METHOD_NOT_ALLOWED:
                    Response::json(false, 'Method Not Allowed', 405);
                    break;
                default:
                    Response::json(false, 'Route Not Found', 404);


            }
        };
        Pipeline::run($this->globalMiddleware, $request, $dispatcherHandler);
    }
    public function dispatcherHandler(Request $request)
    {

    }

}