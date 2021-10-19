<?php

namespace SimpleCore\Log\Exceptions;

class InvalidTimerUsage extends \Exception
{
  protected $code = 1;
  protected $message = 'You must start the timer first';
}