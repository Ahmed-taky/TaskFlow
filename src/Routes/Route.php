<?php
namespace App\Routes;

use App\Helpers\Request;

class Route
{
    public string $method;
    public string $path;
    public array $middleware;
    public $handler = null;

    public function __construct(string $method, string $path, array $middleware, callable $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->middleware = $middleware;
        $this->handler = $handler;
    }

    public function middleware(Request $request, callable $next)
    {
        $stack = $this->middleware;
        $runner = $next;

        while ($mw = array_pop($stack)) {
            $runner = function ($request) use ($mw, $runner) {

                return $mw($request, $runner);
            };
        }

        return $runner($request);
    }
}
