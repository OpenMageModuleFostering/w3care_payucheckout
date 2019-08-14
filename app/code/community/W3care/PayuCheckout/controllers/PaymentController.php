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
 * Payucheckout Payment Controller
 *
 * @category    W3care
 * @package     W3care_PayuCheckout
 * @author	    W3care Developer <info@w3care.com>
 */
class W3care_PayuCheckout_PaymentController extends Mage_Core_Controller_Front_Action
{
	/**
   * Redirect Block
   * need to be redeclared
   */
  protected $_redirectBlockType = 'payucheckout/redirect';
  protected $_paymentInst = NULL;
	
	/**
	 * Get checkout session
	 *
	 * @return object
	 */
  public function getCheckout() {
  	return Mage::getSingleton('checkout/session');
  }
	
  /**
   * Set seesion expire
   *
   */
  protected function _expireAjax()
  {
    if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
   		$this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
      exit;
    }
  }
	
  /**
   * Payu Checkout selected for payment 
   * 
   */
  public function redirectAction()
  {
    $session = Mage::getSingleton('checkout/session');
    
    $order = Mage::getModel('sales/order');
    $order->loadByIncrementId($session->getLastRealOrderId());
    $order->addStatusToHistory($order->getStatus(), Mage::helper('payucheckout')->__('Customer was redirected to payu.'));
    $order->save();

    $this->getResponse()->setBody(
        										$this->getLayout()
            								->createBlock($this->_redirectBlockType)
            								->setOrder($order)
            								->toHtml()
    											);        

  }
  
  /**
   * Payment completed successfully
   * 
   */
	public function  successAction()
	{
		$response = $this->getRequest()->getPost();
		
		Mage::getModel('payucheckout/checkout')->processResponse($response);
		$this->_redirect('checkout/onepage/success', array('_secure'=>true));
	}

	/**
	 * Payment failed to complete
	 *
	 */
	public function failureAction()
	{
		$response = $this->getRequest()->getPost();
		
		Mage::getModel('payucheckout/checkout')->processResponse($response);
		
		$this->getCheckout()->clear();
		$this->_redirect('checkout/onepage/failure');
	}

	/**
	 * Payment canceled
	 *
	 */
	public function canceledAction()
	{
		$response = $this->getRequest()->getParams();
		Mage::getModel('payucheckout/checkout')->processResponse($response);
		$this->getCheckout()->clear();
		$this->loadLayout();
		$this->renderLayout();
	}
}