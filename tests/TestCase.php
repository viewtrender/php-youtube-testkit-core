<?php

declare(strict_types=1);

namespace Viewtrender\Youtube\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Viewtrender\Youtube\YoutubeDataApi;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        YoutubeDataApi::reset();
        parent::tearDown();
    }
}
