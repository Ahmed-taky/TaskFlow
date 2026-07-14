<?php
namespace App\Controllers;

use App\Helpers\Request;
use App\Services\AuthService;
use App\Helpers\Response;

class AuthController
{
    public function __construct(private AuthService $authService)
    {
    }

    public function register(Request $request): void
    {
        $user = [
            "name" => $request->body["name"] ?? "",
            "email" => $request->body["email"] ?? "",
            "password" => $request->body["password"] ?? "",
        ];
        $result = $this->authService->register($user);
        Response::json(true, "User registered successfully", 201, [
            "success" => true,
            "userId" => $result
        ]);
    }

    public function login(Request $request): void
    {
        $email = $request->body["email"] ?? "";
        $password = $request->body["password"] ?? "";
        $result = $this->authService->login($email, $password);
        Response::json(true, "Login successful", 200, [
            "user" => $result["user"],
            "token" => $result["token"],
            "iat" => time(),
            "exp" => time() + 3600
        ]);
    }
}