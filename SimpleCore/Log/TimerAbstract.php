<?php

namespace SimpleCore\Log;

use SimpleCore\Log\Exceptions\InvalidTimerUsage;

abstract class TimerAbstract implements TimerInterface
{
  protected string $name = '';
  private int $time = 0;
  private int $start = 0;

  public function getName(): string
  {
    return $this->name;
  }

  public function startTime(): void
  {
    $this->start = hrtime(true);
  }

  /**
   * @throws InvalidTimerUsage
   */
  public function addTime(): void
  {
    if ($this->start === 0) {
      throw new InvalidTimerUsage();
    }
    $this->time = hrtime(true) - $this->start;
  }

  public function getTime(): int
  {
    return $this->time;
  }
}
