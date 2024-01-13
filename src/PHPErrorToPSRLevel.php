<?php

namespace HexMakina\LogLaddy;

use Psr\Log\LogLevel;

class PHPErrorToPSRLevel
{

    /**
     * @var array<int,string> $level_mapping
     */
    private static array $level_mapping = [];

    
    public function __construct()
    {
        if (empty(self::$level_mapping)) {
            self::createErrorLevelMap();
        }
    }

    public function map(int $error): string
    {
        if (!isset(self::$level_mapping[$error])) {
            throw new \Exception(sprintf('%s(%d): %d is unknown', __FUNCTION__, $error, $error));
        }

        return self::$level_mapping[$error];
    }

    /**  
     * Error level meaning, from \Psr\Log\LogLevel.php
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
     *  Error level mapping from \Psr\Log\LogLevel.php & http://php.net/manual/en/errorfunc.constants.php
     */
    private static function createErrorLevelMap(): void
    {
        $errorLevels = [
            LogLevel::CRITICAL  => [E_ERROR,    E_PARSE,        E_CORE_ERROR,       E_COMPILE_ERROR,    E_USER_ERROR,       E_RECOVERABLE_ERROR],
            LogLevel::ERROR     => [E_WARNING,  E_CORE_WARNING, E_COMPILE_WARNING,  E_USER_WARNING],
            LogLevel::DEBUG     => [E_NOTICE,   E_USER_NOTICE,  E_STRICT,           E_DEPRECATED,       E_USER_DEPRECATED,  E_ALL],
        ];

        self::$level_mapping = [];

        foreach ($errorLevels as $logLevel => $errors) {
            self::$level_mapping += array_fill_keys($errors, $logLevel);
        }

        // $critical = array_fill_keys(
        //     [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR],
        //     LogLevel::CRITICAL
        // );

        // $error = array_fill_keys(
        //     [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING],
        //     LogLevel::ERROR
        // );

        // $debug = array_fill_keys(
        //     [E_NOTICE, E_USER_NOTICE, E_STRICT, E_DEPRECATED, E_USER_DEPRECATED, E_ALL],
        //     LogLevel::DEBUG
        // );

        // self::$level_mapping = $critical + $error + $debug;
    }
}
