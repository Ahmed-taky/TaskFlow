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
        $data['task_id']  = $taskId;
        $data['user_id']  = $userId;
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
}
