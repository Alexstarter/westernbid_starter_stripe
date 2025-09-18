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

class Westernbid_Starter_StripeCronModuleFrontController extends ModuleFrontController
{
    /**
     * Disable header/footer rendering
     *
     * @var bool
     */
    public $display_header = false;
    public $display_footer = false;

    public function initContent()
    {
        parent::initContent();

        header('Content-Type: application/json');

        $token = Tools::getValue('token');
        $expectedToken = Configuration::get(Westernbid_Starter_Stripe::STARTER_WB_STRIPE_CRON_TOKEN);

        $tokensMatch = false;

        if ($expectedToken && $token) {
            $tokensMatch = function_exists('hash_equals')
                ? hash_equals($expectedToken, (string) $token)
                : $expectedToken === (string) $token;
        }

        if (!$tokensMatch) {
            header('HTTP/1.1 403 Forbidden');
            $this->ajaxDie(json_encode([
                'status' => 'error',
                'message' => 'Invalid token',
            ]));
        }

        $result = $this->module->cancelExpiredOrders('cron', true);

        $this->ajaxDie(json_encode($result));
    }
}
