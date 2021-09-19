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
use HexMakina\Debugger\Debugger;
use HexMakina\BlackBox\StateAgentInterface;

class LogLaddy extends \Psr\Log\AbstractLogger
{
    private $state_agent = null;
    private static $level_mapping = null;


    public function __construct(StateAgentInterface $agent = null)
    {
        $this->state_agent = $agent;
        $this->setHandlers();
    }

    public function setHandlers()
    {
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
    }

    public function restoreHandlers()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /*
    * handler for errors
    * use set_error_handler('\HexMakina\kadro\Logger\LogLaddy::error_handler')
    */
    public function errorHandler($level, $message, $file = '', $line = 0)
    {

        $loglevel = self::mapErrorLevelToLogLevel($level);
        $this->{$loglevel}($message, debug_backtrace());
        // $this->{$loglevel}($message, ['file' => $file, 'line' => $line, 'trace' => debug_backtrace()]);
    }

    /*
    * static handlers for throwables,
    * use set_exception_handler('\HexMakina\kadro\Logger\LogLaddy::exception_handler');
    */
    public function exceptionHandler(\Throwable $throwable)
    {
        $this->critical(Debugger::formatThrowable($throwable), $throwable->getTrace());
    }

    public function log($level, $message, array $context = [])
    {
        switch ($level) {
            case LogLevel::DEBUG:
                Debugger::visualDump($context, $message, true);
                break;

            case LogLevel::INFO:
            case LogLevel::NOTICE:
            case LogLevel::WARNING:
                if (is_null($this->state_agent)) {
                    Debugger::visualDump($context, $message, true);
                } else {
                    $this->state_agent->addMessage($level, $message, $context);
                }
                break;

            default:

                echo Debugger::toHTML($message, $level, $context, true);
                http_response_code(500);
                break;
        }
    }


    // -- Error level mapping from \Psr\Log\LogLevel.php & http://php.net/manual/en/errorfunc.constants.php
    /** Error level meaning , from \Psr\Log\LogLevel.php
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
     */
    private static function mapErrorLevelToLogLevel($level): string
    {
      // http://php.net/manual/en/errorfunc.constants.php
        if (is_null(self::$level_mapping)) {
            self::$level_mapping =
              array_fill_keys([E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR], LogLevel::ALERT)
              + array_fill_keys([E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING], LogLevel::CRITICAL)
              + array_fill_keys([E_NOTICE, E_USER_NOTICE], LogLevel::ERROR)
              + array_fill_keys([E_STRICT,E_DEPRECATED,E_USER_DEPRECATED,E_ALL], LogLevel::DEBUG);
        }

        if (!isset(self::$level_mapping[$level])) {
            throw new \Exception(__FUNCTION__ . "($level): $level is unknown");
        }

        return self::$level_mapping[$level];
    }
}
