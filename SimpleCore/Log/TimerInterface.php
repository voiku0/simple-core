<?php

namespace SimpleCore\Log;

interface TimerInterface
{
  public function getName(): string;

  public function startTime(): void;

  public function addTime(): void;

  public function getTime(): float;
}
