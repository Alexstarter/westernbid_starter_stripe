<?php

use PHPUnit\Framework\TestCase;

class AutoCancelTest extends TestCase
{
    protected function setUp(): void
    {
        Configuration::reset();
        Order::$orders = [];
        OrderHistory::$changes = [];
        PrestaShopLogger::$logs = [];
        Tools::$values = [];
    }

    public function testCancelExpiredOrdersCancelsEligibleOrders(): void
    {
        Configuration::updateValue(Westernbid_Starter_Stripe::STARTER_WB_STRIPE_AUTO_CANCEL_HOURS, 24);
        Configuration::updateValue(Westernbid_Starter_Stripe::STARTER_WB_STRIPE_WAITING_PAYMENT_STATE, 5);
        Configuration::updateValue('PS_OS_CANCELED', 6);
        Configuration::updateValue(Westernbid_Starter_Stripe::STARTER_WB_STRIPE_CANCEL_EMAIL_ENABLED, 1);

        Db::setInstance(new DummyDb([
            ['id_order' => 1],
            ['id_order' => 2],
        ]));

        Order::$orders = [
            1 => [
                'id' => 1,
                'id_customer' => 1,
                'id_lang' => 1,
                'id_currency' => 1,
                'current_state' => 5,
                'module' => 'westernbid_starter_stripe',
            ],
            2 => [
                'id' => 2,
                'id_customer' => 1,
                'id_lang' => 1,
                'id_currency' => 1,
                'current_state' => 4,
                'module' => 'other_module',
            ],
        ];

        $module = new class() extends Westernbid_Starter_Stripe {
            public $notifiedOrders = [];

            public function sendOrderCancelledEmail(Order $order)
            {
                $this->notifiedOrders[] = $order->id;
            }

            public function logEvent($event, array $context = [])
            {
                // prevent file operations during tests
            }
        };

        $result = $module->cancelExpiredOrders('test', true);

        $this->assertSame('success', $result['status']);
        $this->assertSame([1], $result['orders']);
        $this->assertSame(1, $result['cancelled']);
        $this->assertSame([['order' => 1, 'state' => 6]], OrderHistory::$changes);
        $this->assertSame([1], $module->notifiedOrders);
    }
}
