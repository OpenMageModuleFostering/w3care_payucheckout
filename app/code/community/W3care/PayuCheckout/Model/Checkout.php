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
class W3care_PayuCheckout_Model_Checkout extends Mage_Payment_Model_Method_Abstract
{
	protected $_code  = 'payucheckout_checkout';

  protected $_isGateway 							= false;
  //protected $_canOrder 							= true;
  protected $_canAuthorize 						= false;
  protected $_canCapture 							= true;
  protected $_canCapturePartial 			= false;
  protected $_canRefund 							= false;
  protected $_canVoid 								= false;
  protected $_canUseInternal 					= false;
  protected $_canUseCheckout 					= true;
  protected $_canUseForMultishipping 	= false;
  //protected $_canSaveCc 							= false;
  protected $_canReviewPayment 				= true;
	
 	protected $_infoBlockType = 'payucheckout/info';
  protected $_formBlockType = 'payucheckout/form';
  protected $_paymentMethod = 'shared';
  //protected $_serviceProvider = 'payu_paisa';
   
  
  protected $_order;
	
  protected $_formFields = array();
  
  protected $_key;
  protected $_salt;
  protected $_debugMode;
  protected $_demoMode;
  protected $_orderStatus;
  
  /**
   * Constructor Function
   *
   */
  public function __construct(){
  	$this->_key					=	Mage::getStoreConfig('payment/payucheckout_checkout/key');
		$this->_salt				=	Mage::getStoreConfig('payment/payucheckout_checkout/salt');
		$this->_debugMode		=	Mage::getStoreConfig('payment/payucheckout_checkout/debug_mode');
		$this->_demoMode		=	Mage::getStoreConfig('payment/payucheckout_checkout/demo_mode');
		$this->_orderStatus	=	Mage::getStoreConfig('payment/payucheckout_checkout/order_status');
  }
  
  /**
   * Retrieve block type for display method information
   *
   * @return string
   */
	public function getInfoBlockType()
  {
  	return $this->_infoBlockType;
  }
  
  /**
   * Get url for Payu payment
   *
   * @return string
   */
  public function getPayuCheckoutUrl()
  {
  	
  	$url='https://test.payu.in/_payment';
		//if test/demo mode is on
		if($this->_demoMode==''){
  		$url='https://secure.payu.in/_payment';
		}
    return $url;
  }
 	
  /**
   * Populate form fields for cart items
   *
   */
  function setCartItemFields(){ 
  	
  	$items = $this->getOrder()->getAllItems();
  	
    if ($items) {
   
    	$i = 1;
      foreach($items as $item){
      	if ($item->getParentItem()) {
        	continue;
        }        
        $this->_formFields['c_prod_'.$i]				= $item->getSku();
        $this->_formFields['c_name_'.$i]        = $item->getName();
        $this->_formFields['c_description_'.$i] = $this->formatDescription($item->getDescription());
        $this->_formFields['c_price_'.$i]       = number_format($item->getPrice(), 2, '.', '');
        
        $i++;
      }
    }
   
  }
  
  /**
   * Populate form fields for billing details
   *
   */
  function setBillingFields(){
  	$billing = $this->getOrder()->getBillingAddress();
  	
  	$this->_formFields['firstname']	= $billing->getFirstname();
		$this->_formFields['Lastname']  = $billing->getLastname();
		$this->_formFields['City']      = $billing->getCity();
    $this->_formFields['State']     = $billing->getRegion();
		$this->_formFields['Country']   = $billing->getCountry();
    $this->_formFields['Zipcode']   = $billing->getPostcode();
    $this->_formFields['phone']     = $billing->getTelephone();
  }

  /**
   * Set all the return urls, when control come back form payment gateway
   *
   */
  function setUrlFields(){
  	$this->_formFields['surl']	= Mage::getBaseUrl().'payucheckout/payment/success/';  
		$this->_formFields['furl']	= Mage::getBaseUrl().'payucheckout/payment/failure/';
		$this->_formFields['curl']	= Mage::getBaseUrl().'payucheckout/payment/canceled/id/'.$this->getOrder()->getRealOrderId();
  }
  
  /**
   * Create hash encrypted string for the form
   *
   */
  function setHashField(){
  	if ($this->_debugMode==1) {
   		$requestInfo	= $this->_key.'|'.$this->_formFields['txnid'].'|'.$this->_formFields['amount'].'|'.$this->_formFields['productinfo'].'|'.$this->_formFields['firstname'].'|'.$this->_formFields['email'].'|'.$this->_formFields['udf1'].'|'.$this->_formFields['udf2'].'|||||||||'.$this->_salt;
          
			$debug = Mage::getModel('payucheckout/api_debug')
              ->setRequestBody($requestInfo)
              ->save();
				
			$debugId = $debug->getId();	
		
			$this->_formFields['udf1']	=	$debugId;
			
			$this->_formFields['Hash']  =   hash('sha512', $this->_key.'|'.$this->_formFields['txnid'].'|'.$this->_formFields['amount'].'|'.$this->_formFields['productinfo'].'|'.$this->_formFields['firstname'].'|'.$this->_formFields['email'].'|'.$this->_formFields['udf1'].'|'.$this->_formFields['udf2'].'|||||||||'.$this->_salt);
    }
		else
		{
			$this->_formFields['Hash']  =  hash('sha512', $this->_key.'|'.$this->_formFields['txnid'].'|'.$this->_formFields['amount'].'|'.$this->_formFields['productinfo'].'|'.$this->_formFields['firstname'].'|'.$this->_formFields['email'].'||'.$this->_formFields['udf2'].'|||||||||'.$this->_salt);
		}
  }
  
  /**
   * Populate param array for the payment gateway to send via POST method
   *
   * @return array
   */
  public function getFormFields()
  {
    $orderId = $this->getOrder()->getRealOrderId(); 
   
    $txnid = $orderId; 
	
		$this->_formFields['key']					= $this->_key;
		$this->_formFields['txnid']       = $txnid;
		$this->_formFields['udf2']        = $orderId;
		$this->_formFields['amount']      = number_format($this->getOrder()->getBaseGrandTotal(),2,'.','');  
		$this->_formFields['productinfo'] = 'Product Information';  
		$this->_formFields['email']       = $this->getOrder()->getCustomerEmail();
	  
		$this->_formFields['Pg']        	= 'CC';
		$this->_formFields['service_provider']	  =  $this->_serviceProvider;
		
		/*for now only on method allowed*/
		/*$this->_formFields['bankcode']  = $billing->getbankcode();
    $this->_formFields['ccnum']     = $billing->getccnum();
    $this->_formFields['ccvv']      = $billing->getccvv();   
    $this->_formFields['ccexpmon']  = $billing->getccexpmon();
    $this->_formFields['ccexpyr']   = $billing->getccexpyr();
    $this->_formFields['ccname']	  = $billing->getccname();*/
		
		$this->setUrlFields();
	  $this->setBillingFields();
	  $this->setCartItemFields();
    
	  $this->setHashField();
	  //echo "<pre>";//print_r($this->_formFields);//exit;
	  return $this->_formFields;
  }
  
  /**
   * fromat the string accordingly
   *
   * @param string $string
   * @return string
   */
  public function formatDescription($string) {
    $string = nl2br(strip_tags($string));
    $string = str_replace("<br />","<br>",$string);
    $string = str_replace("\""," inch",$string);        
    return $string;
  }

  /**
   * Get checkout session namespace
   *
   * @return Mage_Checkout_Model_Session
   */
  public function getCheckout()
  {
  	return Mage::getSingleton('checkout/session');
  }

  /**
   * Get current quote
   *
   * @return Mage_Sales_Model_Quote
   */
  public function getQuote()
  {
  	return $this->getCheckout()->getQuote();
  }
  
  /**
   * Get order model
   *
   * @return Mage_Sales_Model_Order
   */
  public function getOrder()
  {
    if (!$this->_order) {
      $paymentInfo = $this->getInfoInstance();
      $this->_order = Mage::getModel('sales/order')
                        ->loadByIncrementId($paymentInfo->getOrder()->getRealOrderId());
      
    }
    return $this->_order;
  }
	/**
	 * Get customer id
	 *
	 * @return string
	 */
  public function getCustomerId()
  {
    return Mage::getStoreConfig('payment/' . $this->getCode() . '/customer_id');
  }
	
  /**
	 * Get currency code
	 *
	 * @return string
	 */
  public function getAccepteCurrency()
  {
    return Mage::getStoreConfig('payment/' . $this->getCode() . '/currency');
  }
	
  /**
	 * Get Redirect url
	 *
	 * @return string
	 */
  public function getOrderPlaceRedirectUrl()
  {
    return Mage::getUrl('payucheckout/payment/redirect');
  }

  /**
   * Get debug flag
   *
   * @return string
   */
  public function getDebug()
  {
    return Mage::getStoreConfig('payment/' . $this->getCode() . '/debug_flag');
  }

  /**
   * Initiat capture to set status approve
   *
   * @param Varien_Object $payment
   * @param unknown_type $amount
   * @return self
   */
  public function capture(Varien_Object $payment, $amount)
  {
    $payment->setStatus(self::STATUS_APPROVED)
              ->setLastTransId($this->getTransactionId());

    return $this;
  }

  /**
   * Initiat cancel to decline payment
   *
   * @param Varien_Object $payment
   * @param unknown_type $amount
   * @return self
   */
  public function cancel(Varien_Object $payment)
  {
    $payment->setStatus(self::STATUS_DECLINED)
            ->setLastTransId($this->getTransactionId());

    return $this;
  }

  /**
   * parse response POST array from gateway page and return payment status
   *
   * @return bool
   */
  public function parseResponse()
  {       
    return true;
  }

  /**
   * Return redirect block type
   *
   * @return string
   */
  public function getRedirectBlockType()
  {
      return $this->_redirectBlockType;
  }

  /**
   * Return payment method type string
   *
   * @return string
   */
  public function getPaymentMethodType()
  {
      return $this->_paymentMethod;
  }
	
  /**
   * Process the returned param from the payment gateway
   *
   * @param array $response
   */
	public function processResponse($response)
	{
		$order = Mage::getModel('sales/order');
	
		if(isset($response['status'])){
	  	$txnid		=	$response['txnid'];
	   	$orderid 	= $response['udf2'];
	   	
	   	if($response['status']=='success'){
				$order->loadByIncrementId($orderid);
	   		
	   		$status				=	$response['status'];
				$billing 			= $order->getBillingAddress();
				$amount      	= $response['amount'];
				$productinfo 	= $response['productinfo'];  
				$firstname   	= $response['firstname'];
				$email       	= $response['email'];
				
				$Udf1 = $response['udf1'];
		 		$Udf2 = $response['udf2'];
		 		$Udf3 = $response['udf3'];
		 		$Udf4 = $response['udf4'];
		 		$Udf5 = $response['udf5'];
		 		$Udf6 = $response['udf6'];
		 		$Udf7 = $response['udf7'];
		 		$Udf8 = $response['udf8'];
		 		$Udf9 = $response['udf9'];
		 		$Udf10 = $response['udf10'];
				
		 		$keyString =  $this->_key.'|'.$txnid.'|'.$amount.'|'.$productinfo.'|'.$firstname.'|'.$email.'|'.$Udf1.'|'.$Udf2.'|'.$Udf3.'|'.$Udf4.'|'.$Udf5.'|'.$Udf6.'|'.$Udf7.'|'.$Udf8.'|'.$Udf9.'|'.$Udf10;
				
				$keyArray 				= explode("|",$keyString);
				$reverseKeyArray 	= array_reverse($keyArray);
				$reverseKeyString	=	implode("|",$reverseKeyArray);
				
				$saltString     		= $this->_salt.'|'.$status.'|'.$reverseKeyString;
				$sentHashString 		= strtolower(hash('sha512', $saltString));
			 	$responseHashString	=	$_REQUEST['hash'];
				
			 	if($sentHashString==$responseHashString){
					
			 		$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
					$order->save();
					$this->_order = $order;
					$order->sendNewOrderEmail();
					
					
				}
				else{
					$order->setState(Mage_Sales_Model_Order::STATE_NEW, true);
					$order->cancel()->save();
				}
			
				$this->saveDebug($response); 
		  }
	   
	   	if($response['status']=='failure'){
	   		$order->loadByIncrementId($orderid);
	      $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
	       // Inventory updated 
		   	$this->updateInventory($orderid);
		   	$order->cancel()->save();
		   
		   	$this->saveDebug($response); 
	   	}
	   	elseif($response['status']=='pending'){
	    	$order->loadByIncrementId($orderid);
	      $order->setState(Mage_Sales_Model_Order::STATE_NEW, true);
	      // Inventory updated  
	      $this->updateInventory($orderid);
		   	$order->cancel()->save();
		 		$this->saveDebug($response);   
	   	}
	  }
    else{
    	$order->loadByIncrementId($response['id']);
	   	$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
	  	// Inventory updated 
	   	$order_id=$response['id'];
	   	$this->updateInventory($order_id);
	   	$order->cancel()->save();
	  }
	}
	
	/**
	 * Update inventory if payment not succeeds
	 *
	 * @param unknown_type $order_id
	 */
	public function updateInventory($order_id){
		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
    $items = $order->getAllItems();
		
    foreach ($items as $itemId => $item){
    	$ordered_quantity = $item->getQtyToInvoice();
	   	$sku=$item->getSku();
	   	$product = Mage::getModel('catalog/product')->load($item->getProductId());
	   	$qtyStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId())->getQty();
	  
	   	$updated_inventory=$qtyStock + $ordered_quantity;
				
	   	$stockData = $product->getStockItem();
	   	$stockData->setData('qty',$updated_inventory);
	   	$stockData->save(); 
   	} 
 	}
 	
 	/**
 	 * Save debug values to db
 	 *
 	 * @param int $debugId
 	 */
 	function saveDebug($response){
 		if ($this->_debugMode==1) {
 			$debugId	=	$response['udf1'];
			$data 		= array('response_body'=>implode(",",$response));
			$model 		= Mage::getModel('payucheckout/api_debug')->load($debugId)->addData($data);
			$model->setId($id)->save();
	  }
 	}
}