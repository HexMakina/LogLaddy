<?php
namespace HexMakina\LogLaddy;


/**
 * Extends PSR log levels to include success
 */
class LogLevel extends \Psr\Log\LogLevel
{
    const NICE = 'success';
}
