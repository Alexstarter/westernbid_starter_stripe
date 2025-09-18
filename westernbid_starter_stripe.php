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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Westernbid_Starter_Stripe extends PaymentModule
{
    
    const STARTER_WB_STRIPE_ENABLED = 'PAYMENT_STARTER_WB_STRIPE_ENABLED';

    const STARTER_WB_STRIPE_LOGIN = 'PAYMENT_STARTER_WB_STRIPE_LOGIN';
    const STARTER_WB_STRIPE_SECRETKEY = 'PAYMENT_STARTER_WB_STRIPE_SECRETKEY';
    const STARTER_WB_STRIPE_BLOCK_DOWNLOADS = 'STARTER_WB_STRIPE_BLOCK_DOWNLOADS';
    const STARTER_WB_STRIPE_AUTO_CANCEL_HOURS = 'STARTER_WB_STRIPE_AUTO_CANCEL_HOURS';

    const MODULE_ADMIN_CONTROLLER = 'AdminConfigurePaymentWesternbidStripe';
    const STARTER_WB_STRIPE_WAITING_PAYMENT_STATE = 'STARTER_WB_STRIPE_WAITING_PAYMENT_STATE';
    const STARTER_WB_STRIPE_WAITING_PAYMENT_NAME = 'Ожидание оплаты WesternBid';
    const HOOKS = [
        'paymentOptions',
        'actionFrontControllerAfterInit',
    ];

    public function __construct()
    {
        $this->name = 'westernbid_starter_stripe';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'STARTER.DESIGN';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_,
        ];
        $this->controllers = [
            'account',
            'cancel',
            'external',
            'validation',
        ];

        parent::__construct();

        $this->displayName = $this->l('Starter WesternBid Stripe Payment Module');
        $this->description = $this->l('Payment Module WesternBid Stripe for Prestashop made by STARTER.DESIGN');
    }

    /**
     * @return bool
     */
    public function install()
    {
        return (bool) parent::install()
            && (bool) $this->registerHook(static::HOOKS)
            && $this->installConfiguration()
            && $this->installOrderState()
            && $this->installTabs();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return (bool) parent::uninstall()
            && $this->uninstallOrderState()
            && $this->uninstallConfiguration()
            && $this->uninstallTabs();
    }

    /**
     * Module configuration page
     */
    public function getContent()
    {
        // Redirect to our ModuleAdminController when click on Configure button
        Tools::redirectAdmin($this->context->link->getAdminLink(static::MODULE_ADMIN_CONTROLLER));
    }

    /**
     * @param array $params
     *
     * @return array Should always return an array
     */
    public function hookPaymentOptions(array $params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        if (false === Validate::isLoadedObject($cart) || false === $this->checkCurrency($cart)) {
            return [];
        }

        $paymentOptions = [];


        if (Configuration::get(static::STARTER_WB_STRIPE_ENABLED)) {
            $paymentOptions[] = $this->getExternalPaymentOption();
        }


        return $paymentOptions;
    }


    /**
     * Factory of PaymentOption for External Payment
     *
     * @return PaymentOption
     */
    private function getExternalPaymentOption()
    {
        $externalOption = new PaymentOption();
        $externalOption->setModuleName($this->name);
        $externalOption->setCallToActionText($this->l('Pay by Stripe'));
        $externalOption->setAction($this->context->link->getModuleLink($this->name, 'external', [], true));

        $externalOption->setAdditionalInformation($this->context->smarty->fetch('module:westernbid_starter_stripe/views/templates/front/paymentOptionExternal.tpl'));

        return $externalOption;
    }



    /**
     * Install default module configuration
     *
     * @return bool
     */
    private function installConfiguration()
    {
        return (bool) Configuration::updateGlobalValue(static::STARTER_WB_STRIPE_ENABLED, '1')
            && (bool) Configuration::updateGlobalValue(static::STARTER_WB_STRIPE_BLOCK_DOWNLOADS, '1')
            && (bool) Configuration::updateGlobalValue(static::STARTER_WB_STRIPE_AUTO_CANCEL_HOURS, '24');
    }

    /**
     * Uninstall module configuration
     *
     * @return bool
     */
    private function uninstallConfiguration()
    {
        return (bool) Configuration::deleteByName(static::STARTER_WB_STRIPE_ENABLED)
            && (bool) Configuration::deleteByName(static::STARTER_WB_STRIPE_BLOCK_DOWNLOADS)
            && (bool) Configuration::deleteByName(static::STARTER_WB_STRIPE_AUTO_CANCEL_HOURS);
    }

    /**
     * Install order state used for unpaid WesternBid orders
     *
     * @return bool
     */
    private function installOrderState()
    {
        $orderStateId = (int) Configuration::get(static::STARTER_WB_STRIPE_WAITING_PAYMENT_STATE);

        if ($orderStateId) {
            $orderState = new OrderState($orderStateId);

            if (Validate::isLoadedObject($orderState)) {
                return true;
            }
        }

        $existingOrderStateId = $this->findExistingOrderStateId();

        if ($existingOrderStateId) {
            return (bool) Configuration::updateValue(static::STARTER_WB_STRIPE_WAITING_PAYMENT_STATE, (int) $existingOrderStateId);
        }

        $orderState = new OrderState();
        $orderState->module_name = $this->name;
        $orderState->send_email = 0;
        $orderState->color = '#34209E';
        $orderState->hidden = 0;
        $orderState->logable = 0;
        $orderState->invoice = 0;
        $orderState->delivery = 0;
        $orderState->shipped = 0;
        $orderState->paid = 0;
        $orderState->pdf_invoice = 0;
        $orderState->pdf_delivery = 0;
        $orderState->deleted = 0;
        $orderState->unremovable = 0;

        foreach (Language::getLanguages(false) as $language) {
            $orderState->name[$language['id_lang']] = static::STARTER_WB_STRIPE_WAITING_PAYMENT_NAME;
            $orderState->template[$language['id_lang']] = '';
        }

        if (!$orderState->add()) {
            return false;
        }

        return (bool) Configuration::updateValue(static::STARTER_WB_STRIPE_WAITING_PAYMENT_STATE, (int) $orderState->id);
    }

    /**
     * Remove module order state
     *
     * @return bool
     */
    private function uninstallOrderState()
    {
        $orderStateId = (int) Configuration::get(static::STARTER_WB_STRIPE_WAITING_PAYMENT_STATE);

        if ($orderStateId) {
            $orderState = new OrderState($orderStateId);

            if (Validate::isLoadedObject($orderState) && !$orderState->delete()) {
                return false;
            }

            Configuration::deleteByName(static::STARTER_WB_STRIPE_WAITING_PAYMENT_STATE);
        }

        return true;
    }

    /**
     * Try to find existing order state by configured name
     *
     * @return int
     */
    private function findExistingOrderStateId()
    {
        $defaultLanguageId = (int) Configuration::get('PS_LANG_DEFAULT');
        $orderStates = OrderState::getOrderStates($defaultLanguageId);

        foreach ($orderStates as $orderState) {
            if ($orderState['name'] === static::STARTER_WB_STRIPE_WAITING_PAYMENT_NAME) {
                return (int) $orderState['id_order_state'];
            }
        }

        return 0;
    }

    /**
     * Install Tabs
     *
     * @return bool
     */
    public function installTabs()
    {
        if (Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = static::MODULE_ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->active = true;
        $tab->id_parent = -1;
        $tab->name = array_fill_keys(
            Language::getIDs(false),
            $this->displayName
        );

        return (bool) $tab->add();
    }

    /**
     * Uninstall Tabs
     *
     * @return bool
     */
    public function uninstallTabs()
    {
        $id_tab = (int) Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER);

        if ($id_tab) {
            $tab = new Tab($id_tab);

            return (bool) $tab->delete();
        }

        return true;
    }

    /**
     * Check if currency is allowed in Payment Preferences
     *
     * @param Cart $cart
     *
     * @return bool
     */
    private function checkCurrency(Cart $cart)
    {
        $currency_order = new Currency($cart->id_currency);
        /** @var array $currencies_module */
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (empty($currencies_module)) {
            return false;
        }

        foreach ($currencies_module as $currency_module) {
            if ($currency_order->id == $currency_module['id_currency']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cancel unpaid orders that expired according to configuration
     */
    public function hookActionFrontControllerAfterInit()
    {
        if (!Configuration::get(static::STARTER_WB_STRIPE_BLOCK_DOWNLOADS)) {
            return;
        }

        $autoCancelHours = (int) Configuration::get(static::STARTER_WB_STRIPE_AUTO_CANCEL_HOURS);

        if ($autoCancelHours <= 0) {
            return;
        }

        $waitingState = (int) Configuration::get(static::STARTER_WB_STRIPE_WAITING_PAYMENT_STATE);

        if ($waitingState <= 0) {
            return;
        }

        $deadline = date('Y-m-d H:i:s', time() - ($autoCancelHours * 3600));

        $query = new DbQuery();
        $query->select('o.id_order');
        $query->from('orders', 'o');
        $query->where('o.current_state = ' . (int) $waitingState);
        $query->where("o.module = '" . pSQL($this->name) . "'");
        $query->where("o.date_add < '" . pSQL($deadline) . "'");

        $orders = Db::getInstance()->executeS($query);

        if (empty($orders)) {
            return;
        }

        $cancelState = (int) Configuration::get('PS_OS_CANCELED');

        foreach ($orders as $orderData) {
            $orderId = (int) $orderData['id_order'];

            if ($orderId <= 0) {
                continue;
            }

            $order = new Order($orderId);

            if (!Validate::isLoadedObject($order) || (int) $order->current_state !== $waitingState) {
                continue;
            }

            $orderHistory = new OrderHistory();
            $orderHistory->id_order = $orderId;
            $orderHistory->changeIdOrderState($cancelState, $orderId);
            $orderHistory->addWithemail();
        }
    }
}
