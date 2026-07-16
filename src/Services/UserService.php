<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\TasksRepository;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private ProjectRepository $projectRepository,
        private TasksRepository $tasksRepository,
    ) {
    }

    public function getUserById(int $id)
    {
        return $this->userRepository->getById($id);
    }

    public function updateProfile(int $id, array $data)
    {
        $allowedFields = ['name', 'email'];
        $filterData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $filterData[$key] = $value;
            }
        }

        if (empty($filterData))
            throw new \Exception("No valid fields to update.");
        $res = $this->userRepository->update($id, $filterData);
        unset($res['password']);
        return $res;
    }

    public function getDashboard(int $userId): array
    {
        $user = $this->userRepository->getById($userId);
        $projects = $this->projectRepository->getDashboardStats($userId);
        $tasks = $this->tasksRepository->getDashboardStats($userId);
        $todayCompleted = $this->tasksRepository->getTodayCompletedCount($userId);

        return [
            'user' => [
                'name' => $user['name'],
            ],
            'projects' => $projects,
            'tasks' => $tasks,
            'today' => [
                'completed_tasks' => $todayCompleted,
            ],
        ];
    }
}
