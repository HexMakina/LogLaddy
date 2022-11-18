<?php

/*
 * LogLaddy
 *
 * I carry a log – yes. Is it funny to you? It is not to me.
 * Behind all things are reasons. Reasons can even explain the absurd.
 *
 * LogLaddy manages error reporting
 * PSR-3 Compliant, with a NICE bonus
 */

namespace HexMakina\LogLaddy;

// Debugger
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;
use HexMakina\Debugger\Debugger;
use HexMakina\BlackBox\StateAgentInterface;

class LogLaddy extends \Psr\Log\AbstractLogger
{

  /**
   * @var array<int,string> $level_mapping
   */
    private static array $level_mapping = [];

    private ?StateAgentInterface $state_agent;


    public function __construct(StateAgentInterface $stateAgent = null)
    {
        $this->state_agent = $stateAgent;
        $this->setHandlers();
    }

    public function setHandlers(): void
    {
        set_error_handler(fn(int $level, string $message, string $file = '', int $line = 0): bool => $this->errorHandler($level, $message, $file, $line));
        set_exception_handler(function (\Throwable $throwable) : void {
            $this->exceptionHandler($throwable);
        });
    }

    public function restoreHandlers(): void
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
      * handler for errors
      * use set_error_handler([$instance, 'errorHandler']);
      *
      * https://www.php.net/manual/en/function.set-error-handler
      *
      */
    public function errorHandler(int $level, string $message, string $file = '', int $line = 0): bool
    {
        $loglevel = self::mapErrorLevelToLogLevel($level);
        $this->{$loglevel}($message);

        return true;
    }

    /*
    * handler for throwables,
    * use set_exception_handler([$instance, 'exceptionHandler']);
    */
    public function exceptionHandler(\Throwable $throwable): void
    {
        $this->critical($throwable->getMessage(), ['exception' => $throwable]);
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
                if (is_null($this->state_agent)) {
                    Debugger::visualDump($message, $level, true);
                } else {
                    $this->state_agent->addMessage($level, $message, $context);
                }

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

    private static function mapErrorLevelToLogLevel(int $level): string
    {

      // http://php.net/manual/en/errorfunc.constants.php
        if (empty(self::$level_mapping)) {
            self::createErrorLevelMap();
        }

        if (!isset(self::$level_mapping[$level])) {
            throw new \Exception(sprintf('%s(%d): %d is unknown', __FUNCTION__, $level, $level));
        }

        return self::$level_mapping[$level];
    }

   /**  Error level meaning, from \Psr\Log\LogLevel.php
     *
     * const EMERGENCY = 'emergency';
     *                 // System is unusable.
     * const ALERT     = 'alert';
     *                 // Action must be taken immediately, Example: Entire website down, database unavailable, etc.
     * const CRITICAL  = 'critical';
     *                 // Application component unavailable, unexpected exception.
     * const ERROR     = 'error';
     *                 // Run time errors that do not require immediate action
     * const WARNING   = 'warning';
     *                 // Exceptional occurrences that are not errors, undesirable things that are not necessarily wrong
     * const NOTICE    = 'notice';
     *                 // Normal but significant events.
     * const INFO      = 'info';
     *                 // Interesting events. User logs in, SQL logs.
     * const DEBUG     = 'debug';
     *                 // Detailed debug information.
     *
     *
     *  Error level mapping from \Psr\Log\LogLevel.php & http://php.net/manual/en/errorfunc.constants.php
     */
    private static function createErrorLevelMap(): void
    {
        $critical = array_fill_keys(
            [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR],
            LogLevel::CRITICAL
        );

        $error = array_fill_keys(
            [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING],
            LogLevel::ERROR
        );

        $debug = array_fill_keys(
            [E_NOTICE, E_USER_NOTICE, E_STRICT,E_DEPRECATED,E_USER_DEPRECATED,E_ALL],
            LogLevel::DEBUG
        );

        self::$level_mapping = $critical + $error + $debug;
    }
}
