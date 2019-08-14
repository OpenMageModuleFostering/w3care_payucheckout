<?php
/*
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License (LGPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/lgpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@w3care.com so we can send you a copy immediately.
 *
 * @category   W3care
 * @package    W3care(payupaisa.in)
 * @copyright  Copyright (c) 2013 W3care Technologies
 * @license    http://www.gnu.org/licenses/lgpl-3.0.html GNU Lesser General Public License (LGPL 3.0)
 */

/**
 * Payucheckout Failure block
 *
 * @category    W3care
 * @package     W3care_PayuCheckout
 * @author	    W3care Developer <info@w3care.com>
 */
class W3care_PayuCheckout_Block_Failure extends Mage_Core_Block_Template
{
  /**
   *  Return Error message
   *
   *  @return	  string
   */
  public function getErrorMessage ()
  {
    $msg = Mage::getSingleton('checkout/session')->getPayuCheckoutErrorMessage();
    Mage::getSingleton('checkout/session')->unsPayuCheckoutErrorMessage();
    return $msg;
  }

  /**
   * Get back to cart url
   */
  public function getBackToCartUrl()
  {
    return Mage::getUrl('checkout/cart');
  }
}