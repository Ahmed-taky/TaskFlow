<?php

namespace App\Middleware;

use App\Helpers\Request;

class CORSMiddleware
{
    private array $allowedOrigins = [];

    public function __construct()
    {
        $origins = $_ENV['ALLOWED_ORIGINS'] ?? '*';

        $this->allowedOrigins = array_map(
            'trim',
            explode(',', $origins)
        );
    }

    public function handle(Request $request, callable $next)
    {
        $origin = $request->headers['Origin'] ?? '';

        $allowAll = in_array('*', $this->allowedOrigins, true);
        $isAllowed = $allowAll || in_array($origin, $this->allowedOrigins, true);

        if ($isAllowed) {
            header('Access-Control-Allow-Origin: ' . ($allowAll ? '*' : $origin));
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Vary: Origin');
        }

        if ($request->method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        return $next($request);
    }
}