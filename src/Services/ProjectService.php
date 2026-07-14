<?php
namespace App\Services;

use App\Repositories\ProjectRepository;

class ProjectService
{
    public function __construct(private ProjectRepository $projectRepository)
    {
    }

    public function createProject(int $userId, array $data)
    {
        $this->validateName($data);
        $dueDate = $this->validateDueDate($data);

        return $this->projectRepository->create([
            'name'      => trim($data['name']),
            'type'       => 'NORMAL',
            'due_date'   => $dueDate,
            'user_id'   => $userId,
        ]);
    }

    public function getProject(int $id, int $userId)
    {
        $project = $this->projectRepository->findByIdAndUser($id, $userId);
        if (!$project) {
            throw new \Exception("Project not found or you are not the owner", 404);
        }
        return $project;
    }

    public function getUserProjects(int $userId): array
    {
        return $this->projectRepository->findAllByUser($userId);
    }

    public function updateProject(int $id, int $userId, array $data)
    {
        $project = $this->projectRepository->findByIdAndUser($id, $userId);
        if (!$project) {
            throw new \Exception("Project not found or you are not the owner", 404);
        }

        $filterData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'due_date'])) {
                $filterData[$key] = $value;
            }
        }

        if (empty($filterData)) {
            throw new \Exception("No valid fields to update", 400);
        }

        if (isset($filterData['name'])) {
            $this->validateName($filterData);
        }
        if (array_key_exists('due_date', $filterData)) {
            $filterData['due_date'] = $this->validateDueDate($filterData);
        }

        $updated = $this->projectRepository->update($id, $filterData);
        if (!$updated) {
            throw new \Exception("Project not found or you are not the owner", 404);
        }
        return $updated;
    }

    public function deleteProject(int $id, int $userId): void
    {
        $project = $this->projectRepository->findByIdAndUser($id, $userId);
        if (!$project) {
            throw new \Exception("Project not found or you are not the owner", 404);
        }

        if ($this->projectRepository->isSystem($id)) {
            throw new \Exception("Cannot delete the Inbox project", 403);
        }

        $this->projectRepository->delete($id);
    }

    private function validateName(array $data): void
    {
        if (!isset($data['name']) || trim($data['name']) === '') {
            throw new \Exception("Name is required", 400);
        }
        $len = mb_strlen(trim($data['name']));
        if ($len < 1 || $len > 100) {
            throw new \Exception("Name must be between 1 and 100 characters", 400);
        }
    }

    private function validateDueDate(array $data): ?string
    {
        if (!array_key_exists('due_date', $data) || $data['due_date'] === null || $data['due_date'] === '') {
            return null;
        }
        $due = $data['due_date'];
        $parsed = date('Y-m-d', strtotime($due));
        if (!$parsed || $parsed === '1970-01-01') {
            throw new \Exception("Invalid due date format", 400);
        }
        $today = date('Y-m-d');
        if ($parsed < $today) {
            throw new \Exception("Due date must not be in the past", 400);
        }
        return $parsed;
    }
}
