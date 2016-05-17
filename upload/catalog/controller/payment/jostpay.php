<?php
###############################################################################
# PROGRAM     : JostPay OpenCart 2.00  Payment Module                           #
# DATE	      : 09-06-2015                       				              #
# AUTHOR      : EDIARO                                                #
# AUTHOR URI  : http://www.ediaro.com	                                      #
###############################################################################
class ControllerPaymentJostPay extends Controller 
{
	public function index()
	{
		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');
		$data['order_id'] = $order_id =  $this->session->data['order_id'];
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$data['action'] = 'https://ibank.gtbank.com/JostPay/Tranx.aspx';

		$data['ap_merchant'] = $this->config->get('jostpay_merchant_id');
		$data['ap_amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$data['ap_currency'] = $order_info['currency_code'];
		$data['ap_purchasetype'] = 'Item';
		$data['ap_itemname'] = $this->config->get('config_name') . ' - #' . $this->session->data['order_id'];
		$data['ap_itemcode'] = $this->session->data['order_id'];
		$data['ap_returnurl'] = $this->url->link('checkout/success');
		$data['ap_cancelurl'] = $this->url->link('checkout/checkout', '', 'SSL');	
		$data['notify_url'] = $this->url->link('payment/jostpay/callback', '', 'SSL');

		$data['jostpay_amount'] = $data['ap_amount'] * 100 ;
		$data['jostpay_currency_code']=($order_info['currency_code']=='USD')?840:566;
		$data['trans_id'] = $trans_id =  time(); // date("ymds");
		$data['full_name'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8')  . ' ' . html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
		
		$jostpay_HashKey =  $this->config->get('jostpay_hash_key');
		$data['jostpay_tranx_hash'] = hash ('sha512', $trans_id. $data['jostpay_amount'].$data['notify_url'].$jostpay_HashKey );
		
		$hash_data=$data['ap_merchant'].$trans_id. $data['jostpay_amount'].$data['jostpay_currency_code'].$data['order_id'].$data['notify_url'].$jostpay_HashKey;
		
		$data['jostpay_hash'] = hash ('sha512', $hash_data);
		
		
		
		$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('jostpay_pending_status_id'));
		if ($this->customer->isLogged())
		{
			$data['transaction_history_link']=$this->url->link('information/jostpay');
		}
	//CUSTOM DATABASE LOGGIN
		$sql="CREATE TABLE IF NOT EXISTS ".DB_PREFIX."jostpay(
				id int not null auto_increment,
				primary key(id),
				order_id INT NOT NULL,unique(order_id),
				date_time datetime,
				transaction_id VARCHAR(48),
				approved_amount VARCHAR(12),
				customer_email VARCHAR(68),
				response_description VARCHAR(225),
				response_code VARCHAR(5),
				transaction_amount varchar(12),
				customer_id INT
				)";
		$this->db->query($sql);
		$customer_id=$this->customer->isLogged()?$this->customer->getId():"";
		
		$this->db->query("INSERT INTO ".DB_PREFIX."jostpay
		(order_id,transaction_id,date_time,transaction_amount,
		customer_email,customer_id) 
		VALUES
		('$order_id','$trans_id',NOW(),'{$data['ap_amount']}',
		'".$this->db->escape($order_info['email'])."','$customer_id')");
		
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/jostpay.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/payment/jostpay.tpl', $data);
		} else {
			return $this->load->view('default/template/payment/jostpay.tpl', $data);
		}
	}
	
	function notifyAdmin($title="")
	{
		if(!$this->config->get('jostpay_debug'))return;
		$post_data=json_encode($this->request->post);
		$msg="$title<br/>Post Data: $post_data";
		$this->log->write('JostPay :: Debug Info' . $msg);
	}
	
	
	public function callback() 
	{		
		if(empty($this->request->post['jostpay_cust_id']))
		{
			$order_id="";
			$order_info=array();
		}
		else
		{
			$order_id=$this->request->post['jostpay_cust_id'];		
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($order_id);
			$order_status_id = $this->config->get('jostpay_failed_status_id');	
			$ap_amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		}
	
	
		if(empty($order_info))
		{
			$info="Order info not found";
			$this->notifyAdmin($info);
		}
		elseif($this->request->post['jostpay_tranx_status_code']!='00')
		{
			$info=$this->request->post['jostpay_tranx_status_msg'];
			$this->notifyAdmin($info);
		}
		elseif(floatval($this->request->post['jostpay_tranx_amt'])<floatval($ap_amount))
		{
			$info="Amount paid {$this->request->post['jostpay_tranx_amt']} NGN is different from the expected payment amount $ap_amount NGN.";
		}
		else
		{
			$jostpay_tranx_id = $this->request->post['jostpay_tranx_id'];
			$order_status_id = $this->config->get('jostpay_completed_status_id');
			$info=$this->request->post['jostpay_tranx_status_msg'];
			$success=true;			
			
			if($this->config->get('jostpay_live_mode'))
			{
			$mertid=$this->config->get('jostpay_merchant_id');
			$hashkey=$this->config->get('jostpay_hash_key');
			$amount=$ap_amount * 100 ;
			
			$hash=hash("sha512",$mertid.$jostpay_tranx_id.$hashkey);
			
			$url="https://ibank.gtbank.com/JostPayService/gettransactionstatus.json?mertid=$mertid&amount=$amount&tranxid=$jostpay_tranx_id&hash=$hash";

			$ch = curl_init();			
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);			
				curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $url);
				
				$response = curl_exec($ch);
				$returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if($returnCode != 200)$response=curl_error($ch);
				curl_close($ch);
				
				
			if($returnCode == 200)$json=@json_decode($response,true);
			else
			{
				$json=null;
				$info="HTTP Error $returnCode: $response; Accessing jostpay confirmation page";
				//$order_status_id = $this->config->get('jostpay_pending_order_status_id');
			}
			
			
			if(empty($json))
			{
				if(empty($info))$info="Error verifying payment";
				$this->notifyAdmin($info);
			}
			else
			{
				if($json['ResponseCode']=='00')
				{
					$order_status_id = $this->config->get('jostpay_completed_status_id');
					$info=$json['ResponseDescription'];
					$success=true;
				}
				else
				{
					$success=false;
					$order_status_id = $this->config->get('jostpay_failed_status_id');	
					$info="ERROR: ".$json['ResponseDescription'];
				}
				
				$this->notifyAdmin("$info , Response: $response");
			}
			}//end live mode check
		}
		
		//todo: check if not already completed status.

		if(!empty($order_info))
		{
			$this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
			$status=empty($success)?'completed':'failed';
			
			$this->db->query("UPDATE ".DB_PREFIX."jostpay SET
				approved_amount='".$this->db->escape($this->request->post['jostpay_tranx_amt'])."',
				response_code='".$this->db->escape($this->request->post['jostpay_tranx_status_code'])."',
				response_description='".$this->db->escape($this->request->post['jostpay_tranx_status_msg'])."'
				WHERE order_id='$order_id'");
		}

       $this->document->setTitle("JostPay Order Payment: $info");
		

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

		$data['breadcrumbs'] = array(); 
		$data['breadcrumbs'][] = array(
			'text'			=> $this->language->get('text_home'),
			'href'			=> $this->url->link('common/home'),           
			'separator'		=> false
		);
		$data['breadcrumbs'][] = array(
			'text'			=> "JostPay Payment Callback",
			'href'      	=> "",
			'separator' 	=> '/'
		);   
      
		  
		$toecho= "
					<style type='text/css'>
					.errorMessage,.successMsg
					{
						color:#ffffff;
						font-size:18px;
						font-family:helvetica;
						border-radius:9px;
						display:inline-block;
						max-width:350px;
						border-radius: 8px;
						padding: 4px;
						margin:auto;
					}
					
					.errorMessage{background-color:#ff3300;}
					
					.successMsg{background-color:#00aa99;}
					
					body,html{min-width:100%;}
				</style>
				";
		$home_url=$this->url->link("common/home",'', 'SSL');
		if(!empty($success))
		{
		
			$toecho.="<div class='successMsg'>
					$info<br/>
					Your order has been successfully Processed <br/>
					ORDER ID: $order_id<br/>
					<a href='$home_url' style='color:#fff;'>CLICK TO RETURN HOME</a></div>";
		}
		else
		{
			$toecho.="<div class='errorMessage'>
					Your transaction was not successful<br/>
					REASON: $info<br/>
					ORDER ID: $order_id<br/>
					<a href='$home_url' >CLICK TO RETURN HOME</a></div>";
		}
		
		$data['oncallback']=true;
		$data['toecho']=$toecho;
		
		$this->response->setOutput($this->load->view('default/template/payment/jostpay.tpl', $data));		
	}
}