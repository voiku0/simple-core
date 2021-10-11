<?php
namespace SimpleCore;

class Logger
{
  private static ?Logger $instance = null;
  private float $_coreTime = 0;
  private float $_validationTime = 0;

  private function __construct(){}
  public function getInstance(): Logger {
    if (self::$instance === null) {
      self::$instance = new Logger();
    }
    return self::$instance;
  }

  public function addCoreTime(float $time): void {
    $this->_coreTime += $time;
  }

  public function addValidationTime(float $time): void {
    $this->_validationTime += $time;
  }
}
