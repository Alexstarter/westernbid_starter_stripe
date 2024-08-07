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
 * This Controller simulate an external payment gateway
 */
class Westernbid_Starter_StripeExternalModuleFrontController extends ModuleFrontController
{
    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        if (false === $this->checkIfContextIsValid() || false === $this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        $customer = new Customer($this->context->cart->id_customer);

        if (false === Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        parent::initContent();

        $customer = new Customer($this->context->cart->id_customer);
        $addressId = Address::getFirstCustomerAddressId($customer->id);
        $address = new Address($addressId);
        $stateId = $address->id_state;
        $state = new State($stateId);

//        $amount = $this->context->cart->getOrderTotal(true, Cart::BOTH);
        $amount = $this->context->cart->getOrderTotal();
        $secret_key = Configuration::get(Westernbid_Starter_Stripe::STARTER_WB_STRIPE_SECRETKEY);
        $wb_login = Configuration::get(Westernbid_Starter_Stripe::STARTER_WB_STRIPE_LOGIN);


        $currency_id =$this->context->cart->id_currency; // Получаем ID валюты из корзины

        $currency = new Currency($currency_id);
        $conversion_rate = $currency->conversion_rate;

        $products = $this->context->cart->getProducts();

        $shipping_cost = $this->context->cart->getTotalShippingCost();



        if (sizeof($products) > 0 and is_array($products))
        {
            foreach ($products as $key=>$row)
            {
                $productId = $row['id_product'];
                $p = new Product($productId);
                $products[$key]['url'] = $p->getLink();

                $html_string = $products[$key]['description_short'];
                $plain_text = str_replace('"', '', strip_tags($html_string));

                $products[$key]['descr'] = $plain_text;
            }
        }

        $id_order_state = 2; // Цей ID може відрізнятися в залежності від конфігурації PrestaShop

        $total = (float)$this->context->cart->getOrderTotal(true, Cart::BOTH);

        // Параметри замовлення
        $payment_method = 'Western Bid Stripe';
        $order_status_id = Configuration::get('PS_OS_PREPARATION'); // Ідентифікатор статусу замовлення
        $currency_id = (int)$currency->id;

        // Виклик validateOrder для підтвердження замовлення
        $this->module->validateOrder($this->context->cart->id, $order_status_id, $total, $payment_method, null, array(), $currency_id, false, $customer->secure_key);

        $orderId = $this->module->currentOrder;
        $orderName = 'Order #' . $orderId;
        $invoice = $orderId;


        $wb_hash = md5($wb_login.$secret_key.$amount.$invoice);

        $success_url = $this->context->link->getPageLink(
            'order-detail',
            true,
            (int) $this->context->language->id,
            [
                'id_order' => $orderId,
            ]
        );

        $cancel_url = $this->context->link->getModuleLink('westernbid_starter_stripe', 'cancel', ['id_order' => $orderId], true);
        $callback_url = $this->context->link->getModuleLink('westernbid_starter_stripe', 'callback', ['id_order' => $orderId], true);

        $this->context->smarty->assign([
            'wb_login' => $wb_login,
            'wb_hash' => $wb_hash,
            'invoice' => $invoice,
            'email' => $customer->email,
            'phone' => $address->phone,
            'firstname' => $address->firstname,
            'lastname' => $address->lastname,
            'address1' => $address->address1,
            'address2' => $address->address2,
            'country' => $address->country,
            'city' => $address->city,
            'state' => $state->name,
            'zip' => $address->postcode,
            'item_name' => $orderName,
            'amount' => $amount,
            'currency_code' => $currency->iso_code,
            'products' => $products,
            'shipping_cost' => $shipping_cost,
            'cancel_url' => $cancel_url,
            'return_url' => $success_url,
            'callback_url' => $callback_url,
        ]);

        $this->setTemplate('module:westernbid_starter_stripe/views/templates/front/external.tpl');
    }

    /**
     * Check if the context is valid
     *
     * @return bool
     */
    private function checkIfContextIsValid()
    {
        return true === Validate::isLoadedObject($this->context->cart)
            && true === Validate::isUnsignedInt($this->context->cart->id_customer)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_invoice);
    }

    /**
     * Check that this payment option is still available in case the customer changed
     * his address just before the end of the checkout process
     *
     * @return bool
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        if (!Configuration::get(Westernbid_Starter_Stripe::STARTER_WB_STRIPE_ENABLED)) {
            return false;
        }

        $modules = Module::getPaymentModules();

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }
}
