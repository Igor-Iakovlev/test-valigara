<?php

namespace tests;

use App\Data\FileBuyer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FileBuyerTest extends TestCase
{
    public function testLoad()
    {
        $buyer = (new FileBuyer())->load(29664);
        $this->assertSame('buyer@test.com', $buyer['email']);
    }

    public function testNoFile()
    {
        $this->expectException(InvalidArgumentException::class);
        $buyer = (new FileBuyer())->load(123);
    }
}
