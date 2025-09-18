<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

/**
 * This Controller receive customer on cancelation on bank payment page
 *
 * @todo If you use a webhook or automatic response do not use redirection
 */
class Westernbid_Starter_StripeCancelModuleFrontController extends ModuleFrontController
{
    private const TOKEN_TTL = 900;

    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        // @todo Use a transaction identifier instead, this is just an example
        $id_order = (int) Tools::getValue('id_order');

        // Order is already saved in PrestaShop
        if (false === empty($id_order)) {
            $order = new Order($id_order);

            if (false === Validate::isLoadedObject($order)) {
                // Order not found
                Tools::redirect($this->context->link->getPageLink('index'));
            }

            if (false === $this->isRequestAuthorized($order)) {
                header('HTTP/1.1 403 Forbidden');

                exit;
            }

            $orderHistory = new OrderHistory();
            $orderHistory->id_order = $id_order;
            $orderHistory->changeIdOrderState(
                Configuration::get('PS_OS_CANCELED'),
                $id_order
            );
            $orderHistory->addWithemail();


            Tools::redirect($this->context->link->getPageLink('index'));
        }

        // Order not saved, redirect to Payment step
        Tools::redirect($this->context->link->getPageLink(
            'order',
            true,
            (int) $this->context->language->id,
            [
                'step' => 4,
            ]
        ));
    }

    /**
     * @param Order $order
     *
     * @return int
     */
    private function getNewState(Order $order)
    {
        if ($order->hasBeenPaid() || $order->isInPreparation()) {
            return (int) Configuration::get('PS_OS_CANCELED');
        }

        return (int) Configuration::get('PS_OS_ERROR');
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    private function isRequestAuthorized(Order $order)
    {
        $secureKey = (string) Tools::getValue('secure_key');

        if ('' !== $secureKey) {
            $customer = new Customer((int) $order->id_customer);

            if (Validate::isLoadedObject($customer) && hash_equals($customer->secure_key, $secureKey)) {
                return true;
            }
        }

        $token = (string) Tools::getValue('token');
        $timestamp = (int) Tools::getValue('timestamp');

        if ('' !== $token && 0 !== $timestamp) {
            return $this->isValidToken($token, (int) $order->id, $timestamp);
        }

        return false;
    }

    /**
     * @param string $token
     * @param int $orderId
     * @param int $timestamp
     *
     * @return bool
     */
    private function isValidToken($token, $orderId, $timestamp)
    {
        $secret = (string) Configuration::get(Westernbid_Starter_Stripe::STARTER_WB_STRIPE_SECRETKEY);

        if ('' === $secret) {
            return false;
        }

        if ($timestamp <= 0 || abs(time() - $timestamp) > static::TOKEN_TTL) {
            return false;
        }

        $payload = sprintf('%d|%d', $orderId, $timestamp);
        $expectedToken = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedToken, $token);
    }
}
