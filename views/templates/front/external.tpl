{**
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
 *}

{extends "$layout"}

{block name="content"}
  <section id="westernbid_starter-external" class="card card-block mb-2">
    <p>{l s='You pay with Stripe.' mod='westernbid_starter_stripe'}</p>

    <form action="https://shop.westernbid.info" method="post" class="form-horizontal mb-1">
      <input type="hidden" name="charset" value="utf-8">
      <input type="hidden" name="wb_login" value="{$wb_login}">
      <input type="hidden" name="wb_hash" value="{$wb_hash}">
      <input type="hidden" name="invoice" value="{$invoice}">
      <input type="hidden" name="gate" value="stripe.com">
      <input type="hidden" name="email" value="{$email}">
      <input type="hidden" name="phone" value="{$phone}">
      <input type="hidden" name="first_name" value="{$firstname}">
      <input type="hidden" name="last_name" value="{$lastname}">
      <input type="hidden" name="address1" value="{$address1}">
      <input type="hidden" name="address2" value="{$address2}">
      <input type="hidden" name="country" value="{$country}">
      <input type="hidden" name="city" value="{$city}">
      <input type="hidden" name="state" value="{$state}">
      <input type="hidden" name="zip" value="{$zip}">
      <input type="hidden" name="item_name" value="{$item_name}">
      <input type="hidden" name="amount" value="{$amount}">
      <input type="hidden" name="shipping" value="0">
      <input type="hidden" name="currency_code" value="{$currency_code}">
      <input type="hidden" name="item_name_1" value="{$item_name}">
      <input type="hidden" name="item_number_1" value="{$invoice}">
      <input type="hidden" name="url_1" value="https://2kolyory.com">
      <input type="hidden" name="description_1" value="{$item_name}">
      <input type="hidden" name="amount_1" value="{$amount}">
      <input type="hidden" name="quantity_1" value="1">
      <input type="hidden" name="return" value="{$return_url}">
      <input type="hidden" name="cancel_return" value="{$cancel_url}">
      <input type="hidden" name="notify_url" value="{$callback_url}">
      <div class="text-sm-center">
        <button type="submit" class="btn btn-primary">
          {l s='Pay' mod='westernbid_starter_stripe'}
        </button>
      </div>
    </form>
  </section>
{/block}
