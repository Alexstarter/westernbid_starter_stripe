<?php

use PHPUnit\Framework\TestCase;

class OrderStateSelectionTest extends TestCase
{
    protected function setUp(): void
    {
        Configuration::reset();
        OrderState::$states = [];
        Configuration::updateValue('PS_LANG_DEFAULT', 1);
    }

    public function testFindExistingOrderStateIdReturnsExistingState(): void
    {
        OrderState::$states = [
            5 => [
                'name' => [1 => Westernbid_Starter_Stripe::STARTER_WB_STRIPE_WAITING_PAYMENT_NAME],
                'template' => [1 => ''],
            ],
        ];

        $module = new Westernbid_Starter_Stripe();

        $method = new ReflectionMethod($module, 'findExistingOrderStateId');
        $method->setAccessible(true);
        $result = $method->invoke($module);

        $this->assertSame(5, $result);
    }

    public function testFindExistingOrderStateIdReturnsZeroWhenNotFound(): void
    {
        OrderState::$states = [
            7 => [
                'name' => [1 => 'Another state'],
                'template' => [1 => ''],
            ],
        ];

        $module = new Westernbid_Starter_Stripe();

        $method = new ReflectionMethod($module, 'findExistingOrderStateId');
        $method->setAccessible(true);
        $result = $method->invoke($module);

        $this->assertSame(0, $result);
    }
}
