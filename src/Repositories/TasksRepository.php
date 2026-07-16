<?php
namespace App\Repositories;

use PDO;

class TasksRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function create(array $data)
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO tasks
                (title, description, status, project_id)
            VALUES
                (:title, :description, :status, :project_id)
            RETURNING *;"
        );
        $stmt->execute($data);
        return $stmt->fetch();
    }

    public function findById(int $id, int $userId)
    {
        $stmt = $this->connection->prepare(
            "SELECT t.*
             FROM tasks t
             JOIN projects p ON p.id = t.project_id
             JOIN users u    ON u.id = p.user_id
             WHERE t.id = :id AND u.id = :user_id;"
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function findAllByProject(int $projectId, int $userId): array
    {
        $stmt = $this->connection->prepare(
            "SELECT t.*
             FROM tasks t
             JOIN projects p ON p.id = t.project_id
             JOIN users u    ON u.id = p.user_id
             WHERE t.project_id = :project_id AND u.id = :user_id
             ORDER BY t.created_at ASC;"
        );
        $stmt->execute(['project_id' => $projectId, 'user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function update(array $data, int $taskId, int $userId)
    {
        $query = "UPDATE tasks t SET ";
        $size = count($data);
        foreach ($data as $key => $value) {
            $query .= "$key = :$key";
            $size--;
            $query .= $size > 0 ? ", " : "";
        }
        $query .= " FROM projects p JOIN users u ON u.id = p.user_id
                    WHERE t.project_id = p.id
                      AND t.id = :task_id
                      AND u.id = :user_id
                    RETURNING t.*;";
        $data['task_id'] = $taskId;
        $data['user_id'] = $userId;
        $stmt = $this->connection->prepare($query);
        $stmt->execute($data);
        return $stmt->fetch() ?: null;
    }

    public function delete(int $taskId, int $userId): int
    {
        $stmt = $this->connection->prepare(
            "DELETE FROM tasks t
             USING projects p, users u
             WHERE t.project_id = p.id
               AND p.user_id    = u.id
               AND t.id         = :task_id
               AND u.id         = :user_id;"
        );
        $stmt->execute(['task_id' => $taskId, 'user_id' => $userId]);
        return $stmt->rowCount();
    }

    public function getCompletedCountsByDateRange(int $projectId, string $from, string $to): array
    {
        $stmt = $this->connection->prepare(
            "SELECT completed_at::date AS day, COUNT(*)::int AS count
             FROM tasks
             WHERE project_id = :project_id
               AND completed_at IS NOT NULL
               AND completed_at::date >= :from
               AND completed_at::date <= :to
             GROUP BY completed_at::date
             ORDER BY day;"
        );
        $stmt->execute([
            'project_id' => $projectId,
            'from' => $from,
            'to' => $to,
        ]);
        return $stmt->fetchAll();
    }
    public function getDashboardStats(int $userId): array
    {
        $stmt = $this->connection->prepare(
            "SELECT
                COUNT(*)::int AS total,
                COUNT(*) FILTER (WHERE t.status = 'PENDING')::int AS pending,
                COUNT(*) FILTER (WHERE t.status = 'IN_PROGRESS')::int AS in_progress,
                COUNT(*) FILTER (WHERE t.status = 'COMPLETED')::int AS completed
             FROM tasks t
             JOIN projects p ON p.id = t.project_id
             WHERE p.user_id = :user_id;"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }

    public function getTodayCompletedCount(int $userId): int
    {
        $stmt = $this->connection->prepare(
            "SELECT COUNT(*)::int
             FROM tasks t
             JOIN projects p ON p.id = t.project_id
             WHERE p.user_id = :user_id
               AND t.status = 'COMPLETED'
               AND t.completed_at::date = CURRENT_DATE;"
        );
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
