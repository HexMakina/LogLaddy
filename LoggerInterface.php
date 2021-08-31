<?php

namespace HexMakina\LogLaddy;

/**
 * Extends PSR LoggerInterface to include "success" logger
 */
interface LoggerInterface extends \Psr\Log\LoggerInterface
{
  /**
   * nice(): Detailed success information.
   *
   * @param string $message
   * @param array  $context
   *
   * @return void
   */
    public function nice($message, array $context = array());

    public function reportToUser($level, $message, $context = []);
    public function getUserReport();
    public function cleanUserReport();

    public function hasHaltingMessages();
}
