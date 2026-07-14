<?php
namespace App\Core;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;

class Logger
{
    private MonologLogger $logger;

    public function __construct()
    {
        $this->logger = new MonologLogger('App');

        $this->logger->pushHandler(
            new StreamHandler(
                __DIR__ . '/../../logs/app.log'
            )
        );
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