<?php
/*------------------------------------------------------------------------------
  $Id$
  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com
  Copyright © 2011-2015 Belavier Commerce LLC
  This source file is subject to Open Software License (OSL 3.0)
  Lincence details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>
------------------------------------------------------------------------------*/

if ( !defined ( 'DIR_CORE' )) {
	header ( 'Location: static_pages/' );
}
class ControllerResponsesExtensionBpay extends AController {

	public function main() {
		include_once 'bppg_helper.php';
    	 $template_data['button_confirm'] = $this->language->get('button_confirm');
		$template_data['button_back'] = $this->language->get('button_back');
		$this->load->model('checkout/order');
		$this->load->model('extension/bpay');
		$this->loadModel('account/customer');
		$CUST_ID=$this->customer->getId();
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$template_data['BPAY_MERCHANT_KEY'] = $this->config->get('bpay_merchant_key');
		$template_data['BPAY_PROMOCODE_STATUS'] = $this->config->get('promocode_status');
		$template_data['BPAY_promocode_LOCAL_VALIDATION'] = $this->config->get('promocode_local_validation');
		$template_data['BPAY_PROMOCODE_VALUE'] = $this->config->get('promocode_value');
		$template_data['BPAY_MERCHANT_MID'] = $this->config->get('bpay_merchant_mid');
		$template_data['BPAY_MERCHANT_WEBSITE'] = $this->config->get('bpay_merchant_website');
		$template_data['BPAY_MERCHANT_CALLBACK_URL'] = trim($this->config->get('bpay_callback_url'));
		$template_data['BPAY_TRANSACTION_URL'] = $this->config->get('bpay_merchant_transaction_url');
		$template_data['BPAY_TRANSACTION_STATUS_URL'] = $this->config->get('bpay_merchant_transaction_status_url');
		$template_data['BPAY_CALLBACK'] = $this->config->get('bpay_callback');
		$template_data['MID'] = $template_data['BPAY_MERCHANT_MID'];
        $template_data['ORDER_ID'] = $this->session->data['order_id'].time();
		if($CUST_ID =='' || $CUST_ID==0){
			$template_data['CUST_ID'] = $order_info['email'];
		}
		else{
			$template_data['CUST_ID'] = $CUST_ID;
		}
        $template_data['INDUSTRY_TYPE_ID'] = $this->config->get('bpay_merchant_industry'); 
        $template_data['CHANNEL_ID'] = 'WEB';
        $template_data['TXN_AMOUNT'] = $this->currency->format($order_info['total'], $order_info['currency'], $order_info['value'], FALSE);
        $template_data['WEBSITE'] = $template_data['BPAY_MERCHANT_WEBSITE'];      
        
		$BPAY_DOMAIN = "#";
		if ($template_data['BPAY_ENVIRONMENT'] == 'live') {
			$BPAY_DOMAIN = '#';
		}
		
		$template_data['BPAY_STATUS_QUERY_URL']=$template_data['BPAY_TRANSACTION_STATUS_URL'];
		$template_data['BPAY_TXN_URL']=$template_data['BPAY_TRANSACTION_URL'];
        $paramList["MID"] = $template_data['MID'];
		$paramList["ORDER_ID"] = $template_data['ORDER_ID'];
		$paramList["CUST_ID"] = $template_data['CUST_ID'];
		$paramList["INDUSTRY_TYPE_ID"] = $template_data['INDUSTRY_TYPE_ID'];
		$paramList["CHANNEL_ID"] = $template_data['CHANNEL_ID'];
		$paramList["TXN_AMOUNT"] = $template_data['TXN_AMOUNT'];
		$paramList["WEBSITE"] = $template_data['WEBSITE'];
		
		$template_data['CALLBACK_URL'] =$template_data['BPAY_MERCHANT_CALLBACK_URL']!=''?$template_data['BPAY_MERCHANT_CALLBACK_URL']:$this->html->getSecureURL('extension/bpay/callback');
		$paramList["CALLBACK_URL"] = $template_data['CALLBACK_URL'];

		$template_data['customCallbackUrl']=$template_data['BPAY_MERCHANT_CUSTOM_CALLBACKURL'];
		$template_data['callBackUrl']=$template_data['BPAY_MERCHANT_CALLBACK_URL'];

		//salt
		$salt  = $template_data['BPAY_MERCHANT_KEY'];
	    //pay id
		$pay_id = $template_data['BPAY_MERCHANT_MID'];
       //Request Url

		$request_url = $template_data['BPAY_TXN_URL'];

		$pg_transaction = new BPPGModule;
		$pg_transaction->setPayId($pay_id);
		$pg_transaction->setPgRequestUrl($request_url);
		@$pg_transaction->setSalt($salt);
		$pg_transaction->setReturnUrl($template_data['BPAY_TRANSACTION_STATUS_URL']);
		$order_id = $order_info['order_id'];
		$customer_name = $order_info['payment_firstname']." ".$order_info['payment_lastname'];
		$pg_transaction->setCurrencyCode(356);
		$pg_transaction->setTxnType('SALE');
		$pg_transaction->setOrderId($order_id);
		@$pg_transaction->setCustEmail($order_info['email']);
		@$pg_transaction->setCustName($customer_name);
		@$pg_transaction->setCustPhone('Nan');
		@$pg_transaction->setAmount($template_data['TXN_AMOUNT']*100); // convert to Rupee from Paisa
		@$pg_transaction->setProductDesc('SALE');
		// @$pg_transaction->setCustStreetAddress1($_REQUEST['CUST_STREET_ADDRESS1']);
		// @$pg_transaction->setCustCity($_REQUEST['CUST_CITY']);
		// @$pg_transaction->setCustState($_REQUEST['CUST_STATE']);
		// @$pg_transaction->setCustCountry($_REQUEST['CUST_COUNTRY']);
		// @$pg_transaction->setCustZip($_REQUEST['CUST_ZIP']);
		// @$pg_transaction->setCustShipStreetAddress1($_REQUEST['CUST_SHIP_STREET_ADDRESS1']);
		// @$pg_transaction->setCustShipCity($_REQUEST['CUST_SHIP_CITY']);
		// @$pg_transaction->setCustShipState($_REQUEST['CUST_SHIP_STATE']);
		// @$pg_transaction->setCustShipCountry($_REQUEST['CUST_SHIP_COUNTRY']);
		// @$pg_transaction->setCustShipZip($_REQUEST['CUST_SHIP_ZIP']);
		// @$pg_transaction->setCustShipPhone($_REQUEST['CUST_SHIP_PHONE']);
		// @$pg_transaction->setCustShipName($_REQUEST['CUST_SHIP_NAME']);
		// if form is submitted
		
		 $postdata = $pg_transaction->createTransactionRequest();
		 $pg_transaction->redirectForm($postdata);

		//rscorp19

		$this->view->batchAssign( $template_data );
		$this->processTemplate('responses/bpay.tpl' );
		
	}

	
	public function callback() {
		include_once 'bppg_helper.php';
		$this->load->model('extension/bpay');
		$this->load->model('checkout/order');
		$this->loadLanguage('bpay/bpay');

		$template_data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('store_name'));
	
		$template_data['charset'] = 'utf-8';
		$template_data['language'] = $this->language->get('code');
		$template_data['direction'] = $this->language->get('direction');
		$template_data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('store_name'));
		$template_data['text_response'] = $this->language->get('text_response');
		$template_data['text_success'] = $this->language->get('text_success');
        $template_data['text_success_wait'] = sprintf($this->language->get('text_success_wait'), $this->html->getSecureURL('checkout/success'));
		$template_data['text_failure'] = $this->language->get('text_failure');
		$template_data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->html->getSecureURL('checkout/cart'));
		$BPAYChecksum = "";
		$paramList = array();
		$isValidChecksum = "FALSE";
		$paramList = $_POST;
        //rscorp19
        //print_r($_POST);
		
		//rscorp19
		$BPAY_MERCHANT_KEY = $this->config->get('bpay_merchant_key');

        if (isset($_REQUEST['STATUS']) && ($_REQUEST['STATUS'] == 'Captured')) {
        	$this->load->model('checkout/order');
					$this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('bpay_order_status_id'));
					$this->redirect($this->html->getSecureURL('checkout/success'));
        }

        else{
           $template_data['continue'] = $this->html->getSecureURL('checkout/cart');
					$this->view->batchAssign( $template_data );
					$this->redirect($this->html->getSecureURL('checkout/cart'));

        }

	}
	
}
	