<?php

namespace SimpleCore\Log;

use SimpleCore\Log\TimerCore;
use PHPUnit\Framework\TestCase;

class TimerCoreTest extends TestCase
{

  public function testAddTime()
  {
    $timerCore = new TimerCore();
    $this->expectException(Exceptions\InvalidTimerUsage::class);
    $timerCore->addTime();

    $startTime = hrtime(true);
    $timerCore->startTime();
    $endTime = hrtime(true);
    $timerCore->addTime();
    $time = $timerCore->getTime();
    $ourTime = $endTime - $startTime;
    //this is quite tricky considering we are actual measuring time differences so I assume the difference should be positive and less than 1000 nanoseconds
    $this->assertGreaterThan(0, $ourTime - $time);
    $this->assertLessThan(1000, $ourTime - $time);
  }

  public function testGetTime()
  {
    $timerCore = new TimerCore();
    $this->assertEquals(0, $timerCore->getTime());
    $timerCore->startTime();
    $this->assertEquals(0, $timerCore->getTime());
    $timerCore->addTime();
    $this->assertGreaterThan(0, $timerCore->getTime());
  }

  public function testGetName()
  {
    $timerCore = new TimerCore();
    $this->assertEquals('CORE', $timerCore->getName());
  }

  public function testStartTime()
  {
    $timerCore = new TimerCore();
    $timerCore->startTime();
    $timerCore->addTime();
    usleep(1);
    $timerCore->startTime();
    $timerCore->addTime();
    //1000 is 1 microseconds
    $this->assertLessThan(1000, $timerCore->getTime());

    $timerCore = new TimerCore();
    $timerCore->startTime();
    $timerCore->addTime();
    usleep(1);
    $timerCore->addTime();
    $this->assertGreaterThan(1000, $timerCore->getTime());
  }
}
