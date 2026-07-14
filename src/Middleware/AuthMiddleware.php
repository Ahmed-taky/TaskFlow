<?php
namespace App\Middleware;

use App\Helpers\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Helpers\Response;
use App\Repositories\UserRepository;
class AuthMiddleware
{
    public function __construct(private UserRepository $userRepository)
    {
    }
    public function handle(Request $request, callable $next)
    {
        $authHeader = $request->headers['Authorization'] ?? '';
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            Response::json(false, "Unauthorized", 401);
            exit;
        }

        $token = $matches[1];
        try {
            $decoded = JWT::decode(
                $token,
                new Key($_ENV['JWT_SECRET'], 'HS256')
            );
            $user = $this->userRepository->getById($decoded->id);
            if (!$user) {

                Response::json(false, "Unauthorized", 401);
                return;
            }
            $request->setAttribute('user', (array) $user);

        } catch (\Exception $e) {
            Response::json(false, "Unauthorized", 401);
            exit;
        }

        return $next($request);
    }
}
