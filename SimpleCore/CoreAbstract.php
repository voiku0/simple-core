<?php
namespace SimpleCore;

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
    $logger = \Jules\Logger::getInstance();

    $class = explode('\\',get_class($this));
    $validator = str_replace('Application','Application\\Validator', get_class($this));
    $class[count($class)] = $class[count($class)-1];
    $class[count($class)-2] = 'Validator';
    if (in_array($name, get_class_methods(get_class($this)))) {
      if (is_callable(array($validator,$name))) {
        $startTime = microtime(true);
        $obj = new $validator();
        call_user_func_array(array($obj,$name),$arguments);
        $logger->addValidatorTime(microtime(true) - $startTime);
      }
      $startTime = microtime(true);
      $result = call_user_func_array(array($this, $name),$arguments);
      $logger->addApplicationTime(microtime(true) - $startTime);
      return $result;
    } else {
      throw new InvalidMethodCall('Method '.$name. ' not found in class '.get_class($this).' available methods: '.implode(", ", get_class_methods(get_class($this))));
    }
  }
}
