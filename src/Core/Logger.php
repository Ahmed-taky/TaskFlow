<?php
namespace App\Core;
use Monolog\Logger as MonologLogger;

class Logger
{
    public function __construct(private MonologLogger $logger)
    {
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }
}