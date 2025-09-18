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
    const STARTER_WB_STRIPE_CRON_TOKEN = 'STARTER_WB_STRIPE_CRON_TOKEN';
    const STARTER_WB_STRIPE_WAITING_EMAIL_ENABLED = 'STARTER_WB_STRIPE_WAITING_EMAIL_ENABLED';
    const STARTER_WB_STRIPE_PAYMENT_EMAIL_ENABLED = 'STARTER_WB_STRIPE_PAYMENT_EMAIL_ENABLED';
    const STARTER_WB_STRIPE_CANCEL_EMAIL_ENABLED = 'STARTER_WB_STRIPE_CANCEL_EMAIL_ENABLED';

    const MODULE_ADMIN_CONTROLLER = 'AdminConfigurePaymentWesternbidStripe';
    const STARTER_WB_STRIPE_WAITING_PAYMENT_STATE = 'STARTER_WB_STRIPE_WAITING_PAYMENT_STATE';
    const STARTER_WB_STRIPE_WAITING_PAYMENT_NAME = 'Ожидание оплаты WesternBid';
    const WAITING_TEMPLATE = 'wb_waiting_payment';
    const PAYMENT_TEMPLATE = 'wb_payment_success';
    const CANCEL_TEMPLATE = 'wb_payment_cancelled';
    const HOOKS = [
        'paymentOptions',
        'actionFrontControllerAfterInit',
        'actionValidateOrder',
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
            'cron',
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
            && (bool) Configuration::updateGlobalValue(static::STARTER_WB_STRIPE_AUTO_CANCEL_HOURS, '24')
            && (bool) Configuration::updateGlobalValue(static::STARTER_WB_STRIPE_CRON_TOKEN, Tools::passwdGen(32))
            && (bool) Configuration::updateGlobalValue(static::STARTER_WB_STRIPE_WAITING_EMAIL_ENABLED, '1')
            && (bool) Configuration::updateGlobalValue(static::STARTER_WB_STRIPE_PAYMENT_EMAIL_ENABLED, '1')
            && (bool) Configuration::updateGlobalValue(static::STARTER_WB_STRIPE_CANCEL_EMAIL_ENABLED, '1');
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
            && (bool) Configuration::deleteByName(static::STARTER_WB_STRIPE_AUTO_CANCEL_HOURS)
            && (bool) Configuration::deleteByName(static::STARTER_WB_STRIPE_CRON_TOKEN)
            && (bool) Configuration::deleteByName(static::STARTER_WB_STRIPE_WAITING_EMAIL_ENABLED)
            && (bool) Configuration::deleteByName(static::STARTER_WB_STRIPE_PAYMENT_EMAIL_ENABLED)
            && (bool) Configuration::deleteByName(static::STARTER_WB_STRIPE_CANCEL_EMAIL_ENABLED);
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

        $this->cancelExpiredOrders('front_hook', false);
    }

    /**
     * Send notification after order validation if order created with waiting state.
     *
     * @param array $params
     */
    public function hookActionValidateOrder($params)
    {
        if (empty($params['order']) || !$params['order'] instanceof Order) {
            return;
        }

        /** @var Order $order */
        $order = $params['order'];

        if ($order->module !== $this->name) {
            return;
        }

        if (!(bool) Configuration::get(static::STARTER_WB_STRIPE_WAITING_EMAIL_ENABLED)) {
            return;
        }

        $waitingState = (int) Configuration::get(static::STARTER_WB_STRIPE_WAITING_PAYMENT_STATE);

        if ($waitingState > 0 && (int) $order->current_state === $waitingState) {
            $this->sendWaitingPaymentEmail($order);
        }
    }

    /**
     * Send "waiting for payment" notification.
     *
     * @param Order $order
     */
    public function sendWaitingPaymentEmail(Order $order)
    {
        $this->sendOrderNotificationEmail($order, static::WAITING_TEMPLATE, 'Order %reference% received – awaiting payment confirmation', [
            '{download_url}' => $this->getOrderLink($order),
        ]);
    }

    /**
     * Send payment accepted notification.
     *
     * @param Order $order
     */
    public function sendPaymentAcceptedEmail(Order $order)
    {
        $this->sendOrderNotificationEmail($order, static::PAYMENT_TEMPLATE, 'Payment received for order %reference%', [
            '{download_url}' => $this->getOrderLink($order),
        ]);
    }

    /**
     * Send order cancelled notification.
     *
     * @param Order $order
     */
    public function sendOrderCancelledEmail(Order $order)
    {
        $retryUrl = $this->getOrderLink($order);

        $this->sendOrderNotificationEmail($order, static::CANCEL_TEMPLATE, 'Order %reference% cancelled', [
            '{retry_url}' => $retryUrl,
            '{download_url}' => $retryUrl,
        ]);
    }

    /**
     * Compose and send notification email.
     *
     * @param Order  $order
     * @param string $template
     * @param string $subjectTemplate
     * @param array  $additionalVars
     *
     * @return bool
     */
    private function sendOrderNotificationEmail(Order $order, $template, $subjectTemplate, array $additionalVars = [])
    {
        $customer = new Customer((int) $order->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            return false;
        }

        $idLang = (int) $order->id_lang;
        $language = new Language($idLang);
        $currency = new Currency((int) $order->id_currency);
        $link = $this->getContextLink();

        $vars = [
            '{firstname}' => $customer->firstname,
            '{lastname}' => $customer->lastname,
            '{order_reference}' => $order->reference,
            '{order_date}' => Tools::displayDate($order->date_add, $idLang),
            '{order_total}' => Tools::displayPrice($order->total_paid, $currency),
            '{order_url}' => $this->getOrderLink($order),
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
        ];

        $vars = array_merge($vars, $additionalVars);

        $subject = $this->buildMailSubject($template, $language, $order, $subjectTemplate);

        return Mail::Send(
            $idLang,
            $template,
            $subject,
            $vars,
            $customer->email,
            trim($customer->firstname . ' ' . $customer->lastname),
            null,
            null,
            null,
            null,
            $this->getMailTemplateBasePath(),
            false,
            (int) $order->id_shop
        );
    }

    /**
     * @param Order $order
     *
     * @return string
     */
    private function getOrderLink(Order $order)
    {
        $link = $this->getContextLink();

        return $link->getPageLink('order-detail', true, (int) $order->id_lang, [
            'id_order' => (int) $order->id,
        ]);
    }

    /**
     * @return Link
     */
    private function getContextLink()
    {
        if (null === $this->context || null === $this->context->link) {
            return new Link();
        }

        return $this->context->link;
    }

    /**
     * @return string
     */
    private function getMailTemplateBasePath()
    {
        return dirname(__FILE__) . '/mails/';
    }

    /**
     * @param string   $template
     * @param Language $language
     * @param Order    $order
     * @param string   $fallback
     *
     * @return string
     */
    private function buildMailSubject($template, Language $language, Order $order, $fallback)
    {
        $iso = Tools::strtolower($language->iso_code);
        $reference = $order->reference;

        $map = [
            static::WAITING_TEMPLATE => [
                'ru' => 'Заказ ' . $reference . ' получен — ожидаем оплату',
                'uk' => 'Замовлення ' . $reference . ' отримано — очікуємо оплату',
            ],
            static::PAYMENT_TEMPLATE => [
                'ru' => 'Оплата по заказу ' . $reference . ' принята',
                'uk' => 'Оплату за замовлення ' . $reference . ' підтверджено',
            ],
            static::CANCEL_TEMPLATE => [
                'ru' => 'Заказ ' . $reference . ' отменён',
                'uk' => 'Замовлення ' . $reference . ' скасовано',
            ],
        ];

        if (isset($map[$template][$iso])) {
            return $map[$template][$iso];
        }

        return str_replace('%reference%', $reference, $fallback);
    }

    /**
     * Cancel unpaid orders that were not paid within configured number of hours.
     *
     * @param string $contextName   Describes who triggered the cancellation (cron, hook, etc.)
     * @param bool   $logWhenEmpty  Log message even if no orders were cancelled
     *
     * @return array
     */
    public function cancelExpiredOrders($contextName = 'manual', $logWhenEmpty = true)
    {
        $autoCancelHours = (int) Configuration::get(static::STARTER_WB_STRIPE_AUTO_CANCEL_HOURS);

        if ($autoCancelHours <= 0) {
            if ($logWhenEmpty) {
                PrestaShopLogger::addLog(sprintf('[WesternBid Stripe] Auto cancel skipped (%s): feature disabled', $contextName));
            }

            return [
                'status' => 'skipped',
                'cancelled' => 0,
                'message' => 'Auto cancel disabled',
            ];
        }

        $waitingState = (int) Configuration::get(static::STARTER_WB_STRIPE_WAITING_PAYMENT_STATE);

        if ($waitingState <= 0) {
            if ($logWhenEmpty) {
                PrestaShopLogger::addLog(sprintf('[WesternBid Stripe] Auto cancel skipped (%s): waiting state missing', $contextName));
            }

            return [
                'status' => 'skipped',
                'cancelled' => 0,
                'message' => 'Waiting order state missing',
            ];
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
            if ($logWhenEmpty) {
                PrestaShopLogger::addLog(sprintf('[WesternBid Stripe] Auto cancel processed (%s): no orders to cancel', $contextName));
            }

            return [
                'status' => 'success',
                'cancelled' => 0,
                'message' => 'No orders to cancel',
            ];
        }

        $cancelState = (int) Configuration::get('PS_OS_CANCELED');
        $cancelledOrders = [];

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
            $orderHistory->add();

            if ((bool) Configuration::get(static::STARTER_WB_STRIPE_CANCEL_EMAIL_ENABLED)) {
                $this->sendOrderCancelledEmail($order);
            }

            $cancelledOrders[] = $orderId;
        }

        if (!empty($cancelledOrders)) {
            PrestaShopLogger::addLog(sprintf('[WesternBid Stripe] Auto cancel processed (%s): cancelled orders %s', $contextName, implode(', ', $cancelledOrders)));
        } elseif ($logWhenEmpty) {
            PrestaShopLogger::addLog(sprintf('[WesternBid Stripe] Auto cancel processed (%s): no orders cancelled (possibly updated meanwhile)', $contextName));
        }

        return [
            'status' => 'success',
            'cancelled' => count($cancelledOrders),
            'orders' => $cancelledOrders,
            'message' => empty($cancelledOrders) ? 'No orders cancelled' : 'Orders cancelled',
        ];
    }
}
