<?php
namespace HexMakina\Logger;


/**
 * Extends PSR log levels to include success
 */
class LogLevel extends \Psr\Log\LogLevel
{
    const NICE = 'success';
}
