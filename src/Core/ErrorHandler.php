<?php
namespace App\Core;

use App\Helpers\Response;
class ErrorHandler
{
    public static function handle(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            $code = (int) $e->getCode();
            if ($code < 400 || $code > 599) {
                $code = 500;
            }
            $message = $code >= 500
                ? 'Internal Server Error'
                : $e->getMessage();

            Response::json(false, $message, $code);
        }
    }
}