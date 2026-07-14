<?php
namespace App\Controllers;
use App\Services\UserService;
use App\Helpers\Response;
use App\Helpers\Request;
class UserController
{
    public function __construct(private UserService $userService)
    {
    }

    public function me(Request $request): void
    {
        $user = $request->getAttribute('user') ?? null;
        if (!$user) {
            Response::json(false, "Unauthorized", 401);
            return;
        }
        unset($user['password']);
        Response::json(true, "User retrieved successfully", 200, [
            "user" => $user
        ]);
    }
    public function updateProfile(Request $request)
    {
        $user = $request->getAttribute('user') ?? null;
        if (!$user) {
            Response::json(false, "Unauthorized", 401);
            return;
        }

        if (empty($request->body)) {
            Response::json(false, "No valid fields to update.", 400);
            return;
        }

        $res = $this->userService->updateProfile($user['id'], $request->body);
        Response::json(true, "Profile Updated successfully", 200, $res);
    }
}
?>