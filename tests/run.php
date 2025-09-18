<?php
require __DIR__ . '/bootstrap.php';

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
    }
}

function runOrderStateSelectionTests()
{
    Configuration::reset();
    OrderState::$states = [];
    Configuration::updateValue('PS_LANG_DEFAULT', 1);

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
    assertSameValue(5, $result, 'Expected existing order state to be returned');

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
    assertSameValue(0, $result, 'Expected zero when order state not found');
}

function runCancelProtectionTests()
{
    Configuration::reset();
    Configuration::updateValue(Westernbid_Starter_Stripe::STARTER_WB_STRIPE_SECRETKEY, 'secret');

    $controller = new Westernbid_Starter_StripeCancelModuleFrontController();
    $method = new ReflectionMethod($controller, 'isValidToken');
    $method->setAccessible(true);

    $timestamp = time();
    $payload = sprintf('%d|%d', 42, $timestamp);
    $token = hash_hmac('sha256', $payload, 'secret');
    $result = $method->invoke($controller, $token, 42, $timestamp);
    assertSameValue(true, $result, 'Expected fresh token to be valid');

    $expiredTimestamp = time() - Westernbid_Starter_StripeCancelModuleFrontController::TOKEN_TTL - 1;
    $expiredPayload = sprintf('%d|%d', 42, $expiredTimestamp);
    $expiredToken = hash_hmac('sha256', $expiredPayload, 'secret');
    $expiredResult = $method->invoke($controller, $expiredToken, 42, $expiredTimestamp);
    assertSameValue(false, $expiredResult, 'Expected expired token to be rejected');
}

function runAutoCancelTests()
{
    Configuration::reset();
    Order::$orders = [];
    OrderHistory::$changes = [];
    PrestaShopLogger::$logs = [];
    Tools::$values = [];

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
            // skip filesystem interaction during tests
        }
    };

    $result = $module->cancelExpiredOrders('test', true);

    assertSameValue('success', $result['status'], 'Expected cancellation to succeed');
    assertSameValue([1], $result['orders'], 'Expected only order #1 to be cancelled');
    assertSameValue(1, $result['cancelled'], 'Expected one order cancelled');
    assertSameValue([['order' => 1, 'state' => 6]], OrderHistory::$changes, 'Expected order history to record cancellation');
    assertSameValue([1], $module->notifiedOrders, 'Expected cancellation email to be triggered');
}

try {
    runOrderStateSelectionTests();
    runCancelProtectionTests();
    runAutoCancelTests();
    echo "All tests passed\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Test failure: ' . $e->getMessage() . "\n");
    exit(1);
}
