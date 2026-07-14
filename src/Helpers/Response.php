<?php
namespace App\Helpers;

class Response
{
    public static function json(bool $success, string $message, int $code = 200, array $data = [])
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode([
            "success" => $success,
            "message" => $message,
            "data" => $data
        ]);
    }

}
?>