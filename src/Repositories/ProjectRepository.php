<?php
namespace App\Repositories;

use PDO;

class ProjectRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function create(array $data)
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO projects
                (name, type, goal, reflection, due_date, start_date, user_id)
            VALUES
                (:name, :type, :goal, :reflection, :due_date, :start_date, :user_id)
            RETURNING *;"
        );
        $stmt->execute($data);
        return $stmt->fetch();
    }

    public function findById(int $id)
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM projects WHERE id = :id;"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByIdAndUser(int $id, int $userId)
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM projects WHERE id = :id AND user_id = :user_id;"
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function findAllByUser(int $userId): array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM projects
             WHERE user_id = :user_id
             ORDER BY created_at ASC;"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function update(int $id, array $data)
    {
        $query = "UPDATE projects SET ";
        $size = count($data);
        foreach ($data as $key => $value) {
            $query .= "$key = :$key";
            $size--;
            $query .= $size > 0 ? ", " : "";
        }
        $query .= " WHERE id = :id RETURNING *;";
        $data['id'] = $id;
        $stmt = $this->connection->prepare($query);
        $stmt->execute($data);
        return $stmt->fetch() ?: null;
    }

    public function delete(int $id): int
    {
        $stmt = $this->connection->prepare(
            "DELETE FROM projects WHERE id = :id;"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount();
    }

    public function exists(int $id): bool
    {
        $stmt = $this->connection->prepare(
            "SELECT EXISTS(SELECT 1 FROM projects WHERE id = :id);"
        );
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function isSystem(int $id): bool
    {
        $stmt = $this->connection->prepare(
            "SELECT type FROM projects WHERE id = :id;"
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetchColumn();
        return $result === 'SYSTEM';
    }
    public function getDashboardStats(int $userId): array
    {
        $stmt = $this->connection->prepare(
            "SELECT
                COUNT(*)::int AS total,
                COUNT(*) FILTER (WHERE due_date >= CURRENT_DATE)::int AS active,
                COUNT(*) FILTER (WHERE due_date < CURRENT_DATE)::int AS overdue
             FROM projects
             WHERE type = 'NORMAL' AND user_id = :user_id;"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }
}
