<?php
namespace App\Services;

use App\Repositories\TasksRepository;
use App\Repositories\ProjectRepository;

class TasksService
{
    public function __construct(
        private TasksRepository $tasksRepository,
        private ProjectRepository $projectRepository
    ) {
    }

    public function createTask(int $userId, int $projectId, array $data)
    {
        $this->assertProjectOwnedByUser($projectId, $userId);

        $filterData = $this->validateTaskFields($data);
        $filterData['project_id'] = $projectId;
        if (!isset($data['description'])) {
            $filterData['description'] = '';
        }

        return $this->tasksRepository->create($filterData);
    }

    public function getTasksByProject(int $userId, int $projectId): array
    {
        $this->assertProjectOwnedByUser($projectId, $userId);
        return $this->tasksRepository->findAllByProject($projectId, $userId);
    }

    public function updateTask(int $userId, int $taskId, array $data)
    {
        $existing = $this->tasksRepository->findById($taskId, $userId);
        if (!$existing) {
            throw new \Exception("Task not found or you are not the owner", 404);
        }

        $allowedFields = ['title', 'description', 'status', 'project_id'];
        $filterData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $filterData[$key] = $value;
            }
        }

        if (empty($filterData)) {
            throw new \Exception("No valid fields to update", 400);
        }

        if (isset($filterData['title'])) {
            $this->validateTitle($filterData['title']);
        }
        if (isset($filterData['description'])) {
            $this->validateDescription($filterData['description']);
        }
        if (isset($filterData['status'])) {
            $filterData['status'] = $this->normalizeStatus($filterData['status']);
        }

        if (array_key_exists('project_id', $filterData)) {
            $this->assertProjectOwnedByUser((int) $filterData['project_id'], $userId);
        }

        $updated = $this->tasksRepository->update($filterData, $taskId, $userId);
        if (!$updated) {
            throw new \Exception("Task not found or you are not the owner", 404);
        }
        return $updated;
    }

    public function deleteTask(int $taskId, int $userId): void
    {
        $rowCount = $this->tasksRepository->delete($taskId, $userId);
        if ($rowCount === 0) {
            throw new \Exception("Task not found or you are not the owner", 404);
        }
    }

    private function assertProjectOwnedByUser(int $projectId, int $userId): void
    {
        $project = $this->projectRepository->findByIdAndUser($projectId, $userId);
        if (!$project) {
            throw new \Exception("Project not found or you are not the owner", 404);
        }
    }

    private function validateTaskFields(array $data): array
    {
        $filterData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['title', 'description', 'status'])) {
                $filterData[$key] = $value;
            }
        }

        $this->validateTitle($filterData['title'] ?? '');

        if (isset($filterData['description'])) {
            $this->validateDescription($filterData['description']);
        }

        if (isset($filterData['status'])) {
            $filterData['status'] = $this->normalizeStatus($filterData['status']);
        } else {
            $filterData['status'] = 'PENDING';
        }
        return $filterData;
    }

    private function validateTitle(string $title): void
    {
        $title = trim($title);
        if ($title === '') {
            throw new \Exception('Title is required', 400);
        }
        $len = mb_strlen($title);
        if ($len < 1 || $len > 100) {
            throw new \Exception('Title must be between 1 and 100 characters', 400);
        }
    }

    private function validateDescription(string $description): void
    {
        if (mb_strlen($description) > 255) {
            throw new \Exception('Description must be 255 characters or less', 400);
        }
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtoupper($status);
        if (!in_array($status, ['PENDING', 'IN_PROGRESS', 'COMPLETED'])) {
            throw new \Exception('Status must be PENDING, IN_PROGRESS, or COMPLETED', 400);
        }
        return $status;
    }
}
