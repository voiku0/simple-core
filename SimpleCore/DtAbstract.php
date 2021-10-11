<?php

namespace SimpleCore;

use SimpleCore\Exception\InvalidMethodCall;

abstract class DtAbstract
{
  /**
   * @var int|null
   * this value is treated differently since is not part of the actual object
   */
  private ?int $_limit = null;

  /**
   * @var int|null
   * this value is treated differently since is not part of the actual object
   */
  private ?int $_offset = null;

  /**
   * child classes should implement removal of the primary key
   */
  public function __clone()
  {
    //don't copy the history
    foreach (get_object_vars($this) as $string => $value) {
      if (
        strstr($string, '_delete_on')
        || strstr($string, '_delete_by')
        || strstr($string, '_add_by')
        || strstr($string, '_add_on')
      ) {
        $this->{$string}(null);
      }
    }
  }

  /**
   * @param string $name
   * @return mixed
   */
  public function __get(string $name): mixed {
    return $this->$name;
  }

  /**
   * @param $name
   * @param $value
   * @throws InvalidMethodCall
   */
  public function __set($name, $value)
  {
    if (in_array($name, get_class_methods(get_class($this)))) {
      $this->{$name}($value);
    } else {
      throw new InvalidMethodCall('trying to set value for a property that doesn\'t exist: '.$name);
    }
  }

  /**
   * @param \stdClass $obj
   * @return DtAbstract
   */
  public function fromObject(\stdClass $obj):DtAbstract {
    $class = get_class($this);
    $dtObject = new $class();
    $objVars = get_object_vars($obj);
    foreach (get_object_vars($this) as $string => $value) {
      if (array_key_exists($string, $objVars)) {
        $dtObject->{$string}($objVars[$string]);
      }
    }
    return $dtObject;
  }

  /**
   * @param int|null $limit
   * @return $this
   */
  public function setLimit(int $limit = null):DtAbstract {
    $this->_limit = $limit;
    return $this;
  }

  /**
   * @param int|null $offset
   * @return $this
   */
  public function setOffset(int $offset = null):DtAbstract {
    $this->_offset = $offset;
    return $this;
  }

  /**
   * @return int|null
   */
  public function getLimit(): ?int{
    return $this->_limit;
  }

  /**
   * @return int|null
   */
  public function getOffset(): ?int{
    return $this->_offset;
  }
}
