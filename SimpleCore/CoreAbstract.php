<?php
namespace SimpleCore\Log;

use SimpleCore\Exception\InvalidMethodCall;

abstract class CoreAbstract
{
  /**
   * call validator prior to actually access the business layer (application)
   * @param $name
   * @param $arguments
   * @return array
   * @throws InvalidMethodCall
   */
  public function __call($name, $arguments): mixed
  {
    $logger = Summary::getInstance();
    $coreTime = new TimerCore();
    $class = explode('\\', get_class($this));
    $class[count($class)] = $class[count($class) - 1];
    if (in_array($name, get_class_methods(get_class($this)))) {
      $coreTime->startTime();
      $result = call_user_func_array(array($this, $name), $arguments);
      $coreTime->addTime();
      $logger->registerTimer($coreTime);
      return $result;
    } else {
      throw new InvalidMethodCall('Method ' . $name . ' not found in class ' . get_class($this) . ' available methods: ' . implode(", ", get_class_methods(get_class($this))));
    }
  }
}
