<?php
namespace SimpleCore\Log;

use HttpRequest;

class Summary
{
  private static ?Summary $instance = null;
  private array $_timeSummary = array();
  private array $_requestHeaders = array();

  private function __construct()
  {
  }

  public static function getInstance(): Summary
  {
    if (self::$instance === null) {
      self::$instance = new Summary();
    }
    return self::$instance;
  }

  public function registerTimer(TimerInterface $timer)
  {
    if (!array_key_exists($timer->getName(), $this->_timeSummary)) {
      $this->_timeSummary[$timer->getName()] = 0;
    }
    $this->_timeSummary[$timer->getName()] += $timer->getTime();
  }

  public function parseRequestObject(HttpRequest $httpRequest)
  {

  }
}
