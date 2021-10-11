<?php
namespace SimpleCore\Exception;

class InvalidMethodCall extends \Exception
{
  protected $code = 100;

  #[Pure]
  public function __construct($message = "", \Throwable $previous = null)
  {
    parent::__construct($message, $this->code, $previous);
  }
}
