<?php
namespace App\Routes;
use App\Helpers\Request;
class Pipeline
{
    public static function run(array $middleware, Request $request, callable $next)
    {
        $stack = $middleware;
        $runner = $next;

        while ($mw = array_pop($stack)) {
            $runner = function ($request) use ($mw, $runner) {

                return $mw($request, $runner);
            };
        }

        return $runner($request);

    }
}
?>