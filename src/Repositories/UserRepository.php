<?php
namespace App\Repositories;
use PDO;
class UserRepository
{


    public function __construct(private PDO $connection)
    {
    }



    public function create(array $user)
    {
        $stmt = $this->connection->prepare('
        INSERT INTO users (name , email , password)
        VALUES(:name , :email , :password)
       RETURNING id, name, email, created_at;
        ');
        $stmt->execute($user);
        return $stmt->fetch();
    }
    public function emailExists(string $email)
    {
        $stmt = $this->connection->prepare('
        SELECT * FROM users WHERE email=:email');
        $stmt->execute(['email' => $email]);

        return
            $stmt->fetch() != NULL;
    }
    public function getUserByEmail(string $email)
    {
        $stmt = $this->connection->prepare('
        SELECT * FROM users WHERE email=:email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }
    public function getById(int $id)
    {
        $stmt = $this->connection->prepare('
        SELECT * FROM users WHERE id=:id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    public function update(int $id, array $data)
    {

        $query = "UPDATE users SET";
        $size = count($data);
        foreach ($data as $key => $value) {
            $query .= " $key = :$key";
            $size--;
            if ($size != 0)
                $query .= ",";
        }
        $query .= " WHERE id = :id RETURNING *;";
        $data['id'] = $id;
        $stmt = $this->connection->prepare($query);
        $stmt->execute($data);
        return $stmt->fetch();
    }

}
