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
class Westernbid_Starter_StripeCallbackModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $output = Tools::file_get_contents("php://input");
        $data = array();
        parse_str($output, $data);
//	file_put_contents('output.txt', $output);

        $secret_key = Configuration::get(Westernbid_Starter_Stripe::STARTER_WB_STRIPE_SECRETKEY);
        $wb_login = Configuration::get(Westernbid_Starter_Stripe::STARTER_WB_STRIPE_LOGIN);
        $wb_result = $data['wb_result'];
        $mc_gross = $data['mc_gross'];
        $invoice = $data['invoice'];

        $wb_hash = md5($wb_login.$wb_result.$secret_key.$mc_gross.$invoice);

        if ($wb_hash == $data['wb_hash'])
        {
            $id_order = (int) Tools::getValue('id_order');
            // Completed
            if ($data['payment_status'] == 'Completed')
            {
                $orderHistory = new OrderHistory();
                $orderHistory->id_order = $id_order;
                $orderHistory->changeIdOrderState(
                    Configuration::get('PS_OS_PAYMENT'),
                    $id_order
                );

                $orderHistory->addWithemail(); 
            }
        }
        die();
    }
}
