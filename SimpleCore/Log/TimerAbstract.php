<?php

namespace SimpleCore\Log;

abstract class TimerAbstract implements TimerInterface
{
  protected string $name = '';
  private float $time = 0;
  private float $start = 0;

  public function getName(): string
  {
    return $this->name;
  }

  public function startTime(): void
  {
    $this->start = microtime(true);
  }

  public function addTime(): void
  {
    $this->time = microtime(true) - $this->start;
  }

  public function getTime(): float
  {
    return $this->time;
  }
}
