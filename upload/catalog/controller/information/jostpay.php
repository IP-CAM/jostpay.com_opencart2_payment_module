<?php
###############################################################################
# PROGRAM     : JostPay OpenCart 2.00  Payment Module                           #
# DATE	      : 09-06-2015                       				              #
# AUTHOR      : JOSTPAY                                                #
# AUTHOR URI  : http://www.jostpay.com	                                      #
###############################################################################
class ControllerInformationJostPay extends Controller
 {     
    public function index() 
	{
	   $this->load->model('checkout/order');
	  
       $this->document->setTitle('JostPay Transactions'); 

		$data['breadcrumbs'] = array(); // Breadcrumbs for your website. 
		$data['breadcrumbs'][] = array(
			'text'			=> 'Home',
			'href'			=> $this->url->link('common/home'),           
			'separator'		=> false
		);
		$data['breadcrumbs'][] = array(
			'text'			=> 'JostPay Transactions',
			'href'      	=> $this->url->link('information/jostpay'),
			'separator' 	=> '/'
		);   
		
		if(!empty($this->request->get['access_type']))
		{
			$access_type=$this->request->get['access_type'];
			$this->session->data['access_type']=$access_type;
		}
		else $access_type=empty($this->session->data['access_type'])?'':$this->session->data['access_type'];
		
		$sql="";
		$toecho="";
		
		$query=$this->db->query("SHOW TABLES LIKE '".DB_PREFIX."jostpay'");
		
		if(empty($query->rows))$toecho="<h3>This records does not exist yet.</h3>";
		elseif($access_type!='admin'&&!$this->customer->isLogged())$toecho="<h3>Please login first</h3>";
		else
		{
			
			if(!empty($this->request->get['order_id']))
			{
				$order_id=$this->request->get['order_id'];
				$query=$this->db->query("SELECT * FROM ".DB_PREFIX."jostpay WHERE order_id='".$this->db->escape($order_id)."' LIMIT 1");
				
				if(empty($query->row))$toecho="<h3>Order record not found!</h3>";
				elseif(!empty($query->row['response_code']))$toecho="<h3>Order $order_id has been already processed!</h3>";
				else
				{
					$mertid=$this->config->get('jostpay_merchant_id');
					$order_info = $this->model_checkout_order->getOrder($order_id);
					$amount=$query->row['transaction_amount'];
					$jostpay_tranx_id=$query->row['transaction_id'];
					$url="https://jostpay.com/api_v1?action=get_transaction&jostpay_id=$mertid&ref=$jostpay_tranx_id&amount=$amount";
					$ch = curl_init();
					//	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);			
					curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_URL, $url);
					
					$response = curl_exec($ch);
					$returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close($ch);
					
					if($returnCode == 200)
					{
						$json=@json_decode($response,true);
					}
					else
					{
						$success=false;
						$json=null;
						$info="Error ($returnCode) accessing jostpay confirmation page";
						//$order_status_id = $this->config->get('jostpay_pending_order_status_id');
					}
					
					
					if(!empty($json))
					{
						if($json['status_msg']=='COMPLETED')
						{
							$order_status_id = $this->config->get('jostpay_completed_status_id');
							$info="Payment Confirmation Successfull";
							$success=true;
						}
						else//transaction not completed for one reason or the other.
						{
							if($json['status_msg']=='FAILED')$order_status_id = $this->config->get('jostpay_failed_status_id');	
							else $order_status_id = $this->config->get('jostpay_pending_order_status_id');	
							$info="Payment Not Cofirmed: ".$json['info'];
						}
						
						if(!$order_info['order_status_id'])$this->model_checkout_order->confirm($order_id, $order_status_id);
						else $this->model_checkout_order->update($order_id, $order_status_id);		

						$this->db->query("UPDATE ".DB_PREFIX."jostpay SET
							approved_amount='".$this->db->escape($json['amount'])."',
							response_code='{$json['status']}',
							response_description='".$this->db->escape($json['info'])."'
							WHERE order_id='$order_id' LIMIT 1");
					}
					
					$toecho.=$info;
				}
			
			}
		
		
			if($access_type=='admin')$sql="SELECT * FROM ".DB_PREFIX."jostpay ";
			else $sql="SELECT * FROM ".DB_PREFIX."jostpay  WHERE customer_id='".$this->customer->getId()."'";
			
			$query=$this->db->query($sql);
			if(empty($query->rows))$toecho.="<h3>No record found for transactions made through JostPay</h3>";
			else
			{
			
			$num=count($query->rows);
			$perpage=10;
			$totalpages=ceil($num/$perpage);
			$p=empty($this->request->get['p'])?1:$this->request->get['p'];
			if($p<1)$p=1;
			if($p>$totalpages)$p=$totalpages;
			$offset=($p-1) *$perpage;
			$sql.=" ORDER BY id DESC LIMIT $offset,$perpage ";
			$query=$this->db->query($sql);
				$toecho.="
						<table style='width:100%;' class='table table-striped table-condensed' >
							<tr style='width:100%;text-align:center;'>
								<th>
									S/N
								</th>
								<th>
									EMAIL
								</th>
								<th>
									TRANSACTION
									REFERENCE
								</th>
								<th>
									TRANSACTION DATE
								</th>
								<th>
									TRANSACTION<br/>
									AMOUNT (=N=)
								</th>
								<th>
									APPROVED<br/>
									AMOUNT (=N=)
								</th>
								<th>
									TRANSACTION<br/>
									RESPONSE
								</th>
								<th>
									ACTION
								</th>
							</tr>";
				$sn=0;
				foreach($query->rows as $row)
				{
					$sn++;
					
					if(empty($row['response_code']))
					{
						$transaction_response='(pending)';
						$trans_action=$this->url->link('information/jostpay',"p=$p&order_id={$row['order_id']}");
						
						$trans_action="<a href='$trans_action' style='color:#ffffff;background-color:#38B0E3;padding:4px;border-radius:3px;margin:2px;text-decoration:none;display:inline-block;'>REQUERY</a>";
					}
					else
					{
						$transaction_response=$row['response_description'];
						$trans_action='NONE';						
					}
					
					$toecho.="<tr align='center'>
								<td>
									$sn
								</td>
								<td>
									{$row['customer_email']}
								</td>
								<td>
									{$row['transaction_id']}
								</td>
								<td>
									{$row['date_time']}
								</td>
								<td>
									{$row['transaction_amount']}
								</td>
								<td>
									{$row['approved_amount']}
								</td>
								<td>
									$transaction_response
								</td>
								<td>
									$trans_action
								</td>								
							 </tr>";
				}
				$toecho.="</table>";
				
				
				
		$pagination="";
		
			$prev=$p-1;
			$next=$p+1;
			
			if($prev>=1)$pagination.=" [<a href='".$this->url->link('information/jostpay',"p=$prev")."'>previous</a>] ";
			
			if($next<=$totalpages)$pagination.=" [<a href='".$this->url->link('information/jostpay',"p=$next")."'>next</a>] ";
		
		
		
		if($totalpages>2)
		{
			if($prev>1)$pagination.=" [<a href='".$this->url->link('information/jostpay',"p=1")."'>first</a>] ";
			if($next<$totalpages)$pagination.=" [<a href='".$this->url->link('information/jostpay',"p=$totalpages")."'>last</a>] ";
		}	
		
		$toecho.="<div>$pagination</div>";
				
			}
		
		}

		$data['toecho']	= $toecho;
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('default/template/information/jostpay.tpl', $data));	  
     }
}
?>