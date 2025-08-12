<?php

namespace Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Orchestra\Testbench\TestCase as TC;

class TestCase extends TC
{
    use MockeryPHPUnitIntegration;
}
