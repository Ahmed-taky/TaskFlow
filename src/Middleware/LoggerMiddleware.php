<?php
namespace App\Middleware;
use App\Core\Logger;
use App\Helpers\Request;
class LoggerMiddleware
{
    public function __construct(private Logger $logger)
    {
    }
    public function logRequestTime(Request $request, callable $next)
    {
        $start = microtime(true);
        try {
            return $next($request);
        } finally {
            $duration = microtime(true) - $start;
            $this->logger->info("Requset Completed", [
                'time_ms' => $duration * 100,
                'method' => $request->method,
                'uri' => $request->uri,
                'status' => http_response_code(),
            ]);
        }
    }
}
?>