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

    public function __construct(StateAgentInterface $agent)
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
        $this->$loglevel($message, ['file' => $file, 'line' => $line, 'trace' => debug_backtrace()]);
    }

    /*
    * static handlers for throwables,
    * use set_exception_handler('\HexMakina\kadro\Logger\LogLaddy::exception_handler');
    */
    public function exceptionHandler(\Throwable $throwable)
    {
        $this->critical(Debugger::formatThrowable($throwable) , $throwable->getTrace());
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
            $this->state_agent->addMessage($level, $message, $context);
          break;

          case LogLevel::CRITICAL:
          case LogLevel::ALERT:
          case LogLevel::EMERGENCY:
            // if dev, show, else logto file
            echo Debugger::toHTML($message,$level,$context, true);
            http_response_code(500);
            die;
          break;
        }
    }


  // -- Error level mapping from \Psr\Log\LogLevel.php & http://php.net/manual/en/errorfunc.constants.php
  /** Error level meaning , from \Psr\Log\LogLevel.php
   * const EMERGENCY = 'emergency'; // System is unusable.
   * const ALERT     = 'alert'; // Action must be taken immediately, Example: Entire website down, database unavailable, etc.
   * const CRITICAL  = 'critical';  // Application component unavailable, unexpected exception.
   * const ERROR     = 'error'; // Run time errors that do not require immediate action
   * const WARNING   = 'warning'; // Exceptional occurrences that are not errors, undesirable things that are not necessarily wrong
   * const NOTICE    = 'notice'; // Normal but significant events.
   * const INFO      = 'info'; // Interesting events. User logs in, SQL logs.
   * const DEBUG     = 'debug'; // Detailed debug information.
  */
    private static function mapErrorLevelToLogLevel($level): string
    {
      // http://php.net/manual/en/errorfunc.constants.php
        $m = [];

        $m[E_ERROR] = $m[E_PARSE] = $m[E_CORE_ERROR] = $m[E_COMPILE_ERROR] = $m[E_USER_ERROR] = $m[E_RECOVERABLE_ERROR] = LogLevel::ALERT;
        $m[1] = $m[4] = $m[16] = $m[64] = $m[256] = $m[4096] = LogLevel::ALERT;

        $m[E_WARNING] = $m[E_CORE_WARNING] = $m[E_COMPILE_WARNING] = $m[E_USER_WARNING] = LogLevel::CRITICAL;
        $m[2] = $m[32] = $m[128] = $m[512] = LogLevel::CRITICAL;

        $m[E_NOTICE] = $m[E_USER_NOTICE] = LogLevel::ERROR;
        $m[8] = $m[1024] = LogLevel::ERROR;

        $m[E_STRICT] = $m[E_DEPRECATED] = $m[E_USER_DEPRECATED] = $m[E_ALL] = LogLevel::DEBUG;
        $m[2048] = $m[8192] = $m[16384] = $m[32767] = LogLevel::DEBUG;

        if (isset($m[$level])) {
            return $m[$level];
        }

        throw new \Exception(__FUNCTION__ . "($level): $level is unknown");
    }
}
