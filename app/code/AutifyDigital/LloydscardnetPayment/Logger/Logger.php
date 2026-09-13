<?php

/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Logger;

use Monolog\Logger as MonologLogger;
use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface
{
    /**
     * @var MonoLogLogger
     */
    private MonologLogger $logger;

    /**
     * Initialize the logger with the given name and optional handlers and processors.
     *
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(string $name, array $handlers = [], array $processors = [])
    {
        $this->logger = new MonologLogger($name, $handlers, $processors);
    }

    /**
     * Logs an emergency message.
     *
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * Logs an alert message.
     *
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * Logs a critical message.
     *
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Logs an error message.
     *
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * Logs a warning message.
     *
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Logs a notice message.
     *
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    /**
     * Logs an info message.
     *
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Logs a debug message.
     *
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * Logs a message with the given level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    /**
     * Gets the underlying Monolog logger instance.
     *
     * @return MonologLogger
     */
    public function getMonologLogger(): MonologLogger
    {
        return $this->logger;
    }
}
