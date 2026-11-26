<?php

namespace App\Tests\Entity;

use App\Entity\ImportOrder;
use App\Entity\ImportOrderItem;
use PHPUnit\Framework\TestCase;

class ImportOrderTest extends TestCase
{
    public function testGetTotalAmountSumsAllLines(): void
    {
        $order = new ImportOrder();
        $order->addItem((new ImportOrderItem())->setProductPrice('1000.00')->setQuantity(3));
        $order->addItem((new ImportOrderItem())->setProductPrice('250.50')->setQuantity(2));

        $this->assertSame('3501.00', $order->getTotalAmount());
    }

    public function testGetTotalAmountIsZeroWithoutItems(): void
    {
        $order = new ImportOrder();

        $this->assertSame('0.00', $order->getTotalAmount());
    }
}
