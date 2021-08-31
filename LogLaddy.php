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
use HexMakina\Debugger\Debugger;

class LogLaddy implements LoggerInterface
{
    use \Psr\Log\LoggerTrait;           // PSR implementation

    public const REPORTING_USER = 'user_messages';
    public const INTERNAL_ERROR = 'error';
    public const USER_EXCEPTION = 'exception';
    public const LOG_LEVEL_SUCCESS = 'ok';

    private $hasHaltingMessages = false;

  /**
   * Everything went fine, which is always nice.
   * LogLaddy is a bit more optimistic than PSRLog
   * @param string $message
   * @param array  $context
   *
   * @return void
   */
    public function nice($message, array $context = array())
    {
        $this->log(LogLevel::NICE, $message, $context);
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
        if ($throwable instanceof \Exception) {
            $this->alert(self::USER_EXCEPTION, [$throwable]);
        } elseif ($throwable instanceof \Error) {
            $this->notice(self::INTERNAL_ERROR, [$throwable]);
        } else {
            $this->critical('Caught an unknown Throwable. This breaks everything.', [$throwable]);
        }
    }

    public function systemHalted($level)
    {
        switch ($level) {
            case LogLevel::ERROR:
            case LogLevel::CRITICAL:
            case LogLevel::ALERT:
            case LogLevel::EMERGENCY:
                return true;
        }
        return false;
    }

  // -- Implementation of LoggerInterface::log(), all other methods are in LoggerTrait

    public function log($level, $message, array $context = [])
    {
        $display_error = null;

      // --- Handles Throwables (exception_handler())
        if ($message === self::INTERNAL_ERROR || $message === self::USER_EXCEPTION) {
            $this->hasHaltingMessages = true;
            if (($context = current($context)) !== false) {
                $display_error = Debugger::formatThrowable($context);
                $display_error .= PHP_EOL . Debugger::tracesToString($context->getTrace(), false);
                error_log($display_error);
                self::HTTP500($display_error);
            }
        } elseif ($this->systemHalted($level)) { // analyses error level
            $display_error = sprintf(
                PHP_EOL . '%s in file %s:%d' . PHP_EOL . '%s',
                $level,
                Debugger::formatFilename($context['file']),
                $context['line'],
                $message
            );

            error_log($display_error);

            $display_error .= PHP_EOL . Debugger::tracesToString($context['trace'], true);
            self::HTTP500($display_error);
        } else {// --- Handles user messages, through SESSION storage
            $this->reportToUser($level, $message, $context);
        }
    }

    public static function HTTP500($display_error)
    {
        Debugger::displayErrors($display_error);
        http_response_code(500);
    }
  // -- Allows to know if script must be halted after fatal error
  // TODO NEH.. not good
    public function hasHaltingMessages()
    {
        return $this->hasHaltingMessages === true;
    }

  // -- User messages

  // -- User messages:add one
    public function reportToUser($level, $message, $context = [])
    {
        if (!isset($_SESSION[self::REPORTING_USER])) {
            $_SESSION[self::REPORTING_USER] = [];
        }

        if (!isset($_SESSION[self::REPORTING_USER][$level])) {
            $_SESSION[self::REPORTING_USER][$level] = [];
        }

        $_SESSION[self::REPORTING_USER][$level][] = [$message, $context];
    }

  // -- User messages:get all
    public function getUserReport()
    {
        return $_SESSION[self::REPORTING_USER] ?? [];
    }

  // -- User messages:reset all
    public function cleanUserReport()
    {
        unset($_SESSION[self::REPORTING_USER]);
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
