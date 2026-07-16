<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\ProjectRepository;
use Firebase\JWT\JWT;
use PDO;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private ProjectRepository $projectRepository,
        private PDO $pdo
    ) {
    }

    public function register(array $user)
    {
        if (!isset($user["email"]) || empty($user["email"])) {
            throw new \Exception("Email is required", 400);
        }
        if (!isset($user["password"]) || empty($user["password"])) {
            throw new \Exception("Password is required", 400);
        }
        if (!isset($user["name"]) || empty($user["name"])) {
            throw new \Exception("Name is required", 400);
        }
        if ($this->userRepository->emailExists($user["email"])) {
            throw new \Exception('Email already exists', 400);
        }
        if (!preg_match("/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[^\w\d\s:])([^\s]){8,16}$/", $user["password"])) {
            throw new \Exception("Make a Stronger Password", 400);
        }

        $user['password'] = password_hash($user['password'], PASSWORD_DEFAULT);

        return $this->createUserWithInbox($user);
    }

    private function createUserWithInbox(array $user)
    {
        $this->pdo->beginTransaction();
        try {
            $newUser = $this->userRepository->create($user);
            if (!$newUser || empty($newUser['id'])) {
                throw new \Exception("Failed to create user", 500);
            }

            $this->projectRepository->create([
                'name' => 'Inbox',
                'type' => 'SYSTEM',
                'due_date' => null,
                'user_id' => $newUser['id'],
            ]);

            $this->pdo->commit();
            return $newUser;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function login(string $email, string $password)
    {
        $user = $this->userRepository->getUserByEmail($email);
        if (!$user) {
            throw new \Exception("Invalid email or password", 400);
        }
        if (!password_verify($password, $user['password'])) {
            throw new \Exception("Invalid email or password", 400);
        }
        $token = JWT::encode([
            "id" => $user["id"],
        ], $_ENV['JWT_SECRET'], "HS256");
        unset($user['password']);
        return ["user" => $user, "token" => $token];
    }
}
