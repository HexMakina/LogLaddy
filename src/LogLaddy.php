<?php

/*
 * LogLaddy
 *
 * I carry a log – yes. Is it funny to you? It is not to me.
 * Behind all things are reasons. Reasons can even explain the absurd.
 *
 * LogLaddy manages error reporting
 * PSR-3 Compliant
 */

namespace HexMakina\LogLaddy;

// Debugger
use Psr\Log\{LogLevel, LoggerInterface};
use HexMakina\Debugger\Debugger;

class LogLaddy implements LoggerInterface
{
    public const OSD_SESSION_KEY = 'HexMakina:LogLaddy:OSD';
    private PHPErrorToPSRLevel $errorMappper;
    private array $messages;

    public function __construct()
    {
        $this->errorMappper = new PHPErrorToPSRLevel();
        $this->messages = [];

        $this->setHandlers();
    }

    /**
     * sets handler for errors (errorHandler()) and throwables (exceptionHandler())
     *      uses set_error_handler([$instance, 'errorHandler']);
     *      uses set_exception_handler([$instance, 'exceptionHandler']);
     * 
     * https://www.php.net/manual/en/function.set-error-handler
     */
    public function setHandlers(): void
    {
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
    }


    public function resetHandlers(): void
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Handles PHP errors and logs them using the specified error level.
     *
     * @param int $error The error code.
     * @param string $message The error message.
     * @param string $file The file where the error occurred (optional).
     * @param int $line The line number where the error occurred (optional).
     * @return bool Returns false to indicate that the error should not be handled by the default PHP error handler.
     */
    public function errorHandler(int $error, string $message, string $file = '', int $line = 0): bool
    {
        $level = $this->errorMappper->map($error);
        $context = ['file' => $file, 'line' => $line];

        $this->log($level, $message, $context);

        return false;
    }


    /**
     * Handles exceptions by logging them with the critical log level.
     *
     * @param \Throwable $throwable The exception to handle.
     * @return void
     */
    public function exceptionHandler(\Throwable $throwable): void
    {
        $message = $throwable->getMessage();
        $context = ['exception' => $throwable];
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        switch ($level) {
            case LogLevel::DEBUG:
                Debugger::visualDump($message, $level, true);
                break;

            case LogLevel::INFO:
            case LogLevel::NOTICE:
            case LogLevel::WARNING:
                Debugger::visualDump($message, $level, true);
                break;

            case LogLevel::ERROR:
            case LogLevel::CRITICAL:
            case LogLevel::ALERT:
            case LogLevel::EMERGENCY:
                if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
                    $message = $context['exception'];
                    $level = 'Uncaught ' . get_class($context['exception']);
                }

                Debugger::visualDump($message, $level, true);
                http_response_code(500);
                die;

            default:
                throw new \Psr\Log\InvalidArgumentException('UNDEFINED_LOGLEVEL_' . $level);
        }
    }

    private function osd($level, string $message, array $context = array())
    {
        $this->messages[$level] ??= [];
        $this->messages[$level][] = [$message, $context];
    }


    /**
     * System is unusable.
     */
    public function emergency($message, array $context = array())
    {
        $this->osd(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     */
    public function alert($message, array $context = array())
    {
        $this->osd(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     * Example: Application component unavailable, unexpected exception.
     */
    public function critical($message, array $context = array())
    {
        $this->osd(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     */
    public function error($message, array $context = array())
    {
        $this->osd(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     */
    public function warning($message, array $context = array())
    {
        $this->osd(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     */
    public function notice($message, array $context = array())
    {
        $this->osd(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     * Example: User logs in, SQL logs.
     */
    public function info($message, array $context = array())
    {
        $this->osd(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     */
    public function debug($message, array $context = array())
    {
        $this->osd(LogLevel::DEBUG, $message, $context);
    }
}
