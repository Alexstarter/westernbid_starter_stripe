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

class Westernbid_Starter_StripeWebhookModuleFrontController extends ModuleFrontController
{
    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        $output = Tools::file_get_contents('php://input');
        $data = [];
        parse_str($output, $data);

        if (empty($data) && !empty($output)) {
            $decoded = json_decode($output, true);

            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        if (empty($data)) {
            $data = $_POST;
        }

        $result = $this->module->handleWesternbidNotification($data, 'webhook');

        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        if (true === $result['success']) {
            $statusCode = 200;
        } else {
            $statusCode = 400;
        }

        if (!headers_sent()) {
            http_response_code($statusCode);
        }

        die(json_encode($result));
    }
}
