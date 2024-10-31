<?php

namespace Http\Client\Common\Plugin;

use Psr\Log\LoggerInterface;

final class TestLogger implements LoggerInterface
{
    public array $logMessages = [];

    public function emergency($message, array $context = []): void
    {
        $this->logMessages[] = ['emergency' => $message, 'context' => $context];
    }

    public function alert($message, array $context = []): void
    {
        $this->logMessages[] = ['alert' => $message, 'context' => $context];
    }

    public function critical($message, array $context = []): void
    {
        $this->logMessages[] = ['critical' => $message, 'context' => $context];
    }

    public function error($message, array $context = []): void
    {
        $this->logMessages[] = ['error' => $message, 'context' => $context];
    }

    public function warning($message, array $context = []): void
    {
        $this->logMessages[] = ['warning' => $message, 'context' => $context];
    }

    public function notice($message, array $context = []): void
    {
        $this->logMessages[] = ['notice' => $message, 'context' => $context];
    }

    public function info($message, array $context = []): void
    {
        $this->logMessages[] = ['info' => $message, 'context' => $context];
    }

    public function debug($message, array $context = []): void
    {
        $this->logMessages[] = ['debug' => $message, 'context' => $context];
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logMessages[] = [$level => $message, 'context' => $context];
    }
}
