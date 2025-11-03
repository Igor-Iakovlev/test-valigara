<?php

namespace tests;

use App\Data\FileOrder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FileOrderTest extends TestCase
{
    public static function orderIdProvider(): array
    {
        return [
            [16400],
            [16401],
            [16402],
            [16403],
            [16404],
        ];
    }

    #[DataProvider('orderIdProvider')]
    public function testLoad(int $orderId)
    {
        $order = new FileOrder($orderId);
        $order->load();
        $this->assertSame($orderId, (int) $order->data['order_id']);
    }

    public function testIncorrectFile()
    {
        $order = new FileOrder(16405);
        $this->expectException(RuntimeException::class);
        $order->load();
    }

    public function testNoFile()
    {
        $order = new FileOrder(111);
        $this->expectException(InvalidArgumentException::class);
        $order->load();
    }
}
