<?php

namespace HexMakina\LogLaddy;

/**
 * Extends PSR LoggerInterface to include "success" logger
 */
interface LoggerInterface extends  \Psr\Log\LoggerInterface
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

  public function report_to_user($level, $message, $context = []);
  public function get_user_report();
  public function clean_user_report();

  public function has_halting_messages();

}
