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
 * Payucheckout Redirect block
 *
 * @category    W3care
 * @package     W3care_PayuCheckout
 * @author	    W3care Developer <info@w3care.com>
 */
class W3care_PayuCheckout_Block_Redirect extends Mage_Core_Block_Abstract
{
	/**
	 * Redirect to Payucheck out
	 *
	 * @return string $html
	 */
	protected function _toHtml()
  {
    $checkout = $this->getOrder()->getPayment()->getMethodInstance();

    $form = new Varien_Data_Form();
    $form->setAction($checkout->getPayuCheckoutUrl())
        ->setId('payucheckout_payment')
        ->setName('payucheckout_payment')
        ->setMethod('POST')
        ->setUseContainer(true);
	
    foreach ($checkout->getFormFields() as $field=>$value) {
    	$form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
    }
		
    $html = '<html><body>';
    $html.=  Mage::helper('payucheckout')->__('You will be redirected to PayuCheckout in a few seconds.');
    $html.= $form->toHtml();
    $html.= '<script type="text/javascript">document.getElementById("payucheckout_payment").submit();</script>';
    $html.= '</body></html>';

    return $html;
  }
}