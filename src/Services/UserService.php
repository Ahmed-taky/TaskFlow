<?php
namespace App\Services;
use App\Repositories\UserRepository;
class UserService
{
    public function __construct(private UserRepository $userRepository)
    {
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
}
?>