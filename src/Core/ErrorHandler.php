<?php
namespace App\Core;

use App\Helpers\Response;
use App\Core\Logger;
class ErrorHandler
{
    public function __construct(private Logger $logger)
    {

    }
    public function handle(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {

            $code = (int) $e->getCode();
            if ($code < 400 || $code > 599) {
                $this->logger->error($e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $code = 500;
            }
            $message = $code >= 500
                ? 'Internal Server Error'
                : $e->getMessage();

            Response::json(false, $message, $code);
        }
    }
}