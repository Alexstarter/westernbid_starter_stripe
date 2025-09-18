<?php

use PHPUnit\Framework\TestCase;

class CancelProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        Configuration::reset();
        Configuration::updateValue(Westernbid_Starter_Stripe::STARTER_WB_STRIPE_SECRETKEY, 'secret');
    }

    public function testValidTokenAccepted(): void
    {
        $controller = new Westernbid_Starter_StripeCancelModuleFrontController();

        $timestamp = time();
        $payload = sprintf('%d|%d', 42, $timestamp);
        $expectedToken = hash_hmac('sha256', $payload, 'secret');

        $method = new ReflectionMethod($controller, 'isValidToken');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $expectedToken, 42, $timestamp);

        $this->assertTrue($result);
    }

    public function testTokenRejectedWhenExpired(): void
    {
        $controller = new Westernbid_Starter_StripeCancelModuleFrontController();

        $timestamp = time() - Westernbid_Starter_StripeCancelModuleFrontController::TOKEN_TTL - 1;
        $payload = sprintf('%d|%d', 42, $timestamp);
        $expectedToken = hash_hmac('sha256', $payload, 'secret');

        $method = new ReflectionMethod($controller, 'isValidToken');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $expectedToken, 42, $timestamp);

        $this->assertFalse($result);
    }
}
