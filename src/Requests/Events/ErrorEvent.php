<?php

namespace Rebing\Timber\Requests\Events;

use Exception;
use Monolog\Logger;
use Rebing\Timber\Requests\Contexts\CustomContext;

class ErrorEvent extends AbstractEvent
{
    const MAX_BACKTRACE_LENGTH = 20;

    private $exception;
    private $context;

    public function __construct(Exception $exception, array $context = [], string $logLevel = Logger::CRITICAL)
    {
        $this->exception = $exception;
        $this->context = $context;
        $this->logLevel = $logLevel;
    }

    public function getMessage(): string
    {
        $trace = $this->exception->getTrace();

        $message = 'Exception: "';
        $message .= $this->exception->getMessage();
        $message .= '" @ ';
        if (isset($trace[0])) {
            if (isset($trace[0]['class']) && $trace[0]['class'] != '') {
                $message .= $trace[0]['class'];
                $message .= array_get($trace, 'type', '->');
            }

            $message .= $trace[0]['function'];
        }

        return $message;
    }

    public function getEvent(): array
    {
        $backtrace = $this->getBacktrace();
        return [
            'error' => [
                'name'      => substr($this->exception->getMessage(), 0, 256),
                'message'   => $this->getMessage(),
                'backtrace' => $backtrace,
            ],
        ];
    }

    private function getBacktrace(): array
    {
        $backtrace = array_slice(array_map(function($frame) {
            return [
                'file'     => $frame['file'] ?? '_UNKNOWN_',
                'function' => $this->getTraceFunction($frame),
                'line'     => $frame['line'] ?? 1,
            ];
        }, $this->exception->getTrace()), 0, 20);

        return $backtrace ?? [['file' => '_no_trace_', 'line' => 1, 'function' => '_no_trace_']];
    }

    private function getTraceFunction(array $frame): string
    {
        $function = ($frame['class'] ?? '_UNKNOWN_') . ($frame['type'] ?? '->') . ($frame['function'] ?? '_UNKNOWN_');

        if (isset($frame['args'])) {
            $args = [];
            foreach ($frame['args'] as $arg) {
                if (is_string($arg)) {
                    $arg    = strlen($arg) < 252 ? $arg : (substr($arg, 0, 252) . '...');
                    $args[] = "'" . $arg . "'";
                } elseif (is_array($arg)) {
                    $args[] = "Array";
                } elseif (is_null($arg)) {
                    $args[] = 'NULL';
                } elseif (is_bool($arg)) {
                    $args[] = ($arg) ? "true" : "false";
                } elseif (is_object($arg)) {
                    $args[] = get_class($arg);
                } elseif (is_resource($arg)) {
                    $args[] = get_resource_type($arg);
                } else {
                    $args[] = $arg;
                }
            }
            $args     = join(", ", $args);
            $function .= '(' . $args . ')';
        }

        return substr($function, 0, 256);
    }

    public function getContext(): array
    {
        $defaultData = parent::getContext();
        $context = new CustomContext('messages', $this->context);
        return array_merge($defaultData, $context->getData());
    }
}