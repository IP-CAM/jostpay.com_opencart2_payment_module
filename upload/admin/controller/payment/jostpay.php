<?php
###############################################################################
# PROGRAM     : JostPay OpenCart 2.00  Payment Module                        #
# DATE	      : 09-06-2015                       				              #
# AUTHOR      : EDIARO                                                #
# AUTHOR URI  : http://www.ediaro.com	                                      #
###############################################################################

class ControllerPaymentJostPay extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/jostpay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate())
		{
			$this->model_setting_setting->editSetting('jostpay', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');

		$data['entry_merchant_id'] = $this->language->get('entry_merchant_id');
		$data['entry_hash_key'] = $this->language->get('entry_hash_key');
		$data['entry_callback'] = $this->language->get('entry_callback');
		$data['entry_total'] = $this->language->get('entry_total');
		$data['entry_pending_order_status'] = $this->language->get('entry_pending_order_status');
		$data['entry_cancelled_order_status'] = $this->language->get('entry_cancelled_order_status');
		$data['entry_completed_order_status'] = $this->language->get('entry_completed_order_status');

		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_live_mode'] = $this->language->get('entry_live_mode');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['help_callback'] = $this->language->get('help_callback');
		$data['help_total'] = $this->language->get('help_total');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['merchant'])) {
			$data['error_merchant_id'] = $this->error['merchant'];
		} else {
			$data['error_merchant_id'] = '';
		}

		if (isset($this->error['security'])) {
			$data['error_hash_key'] = $this->error['security'];
		} else {
			$data['error_hash_key'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('payment/jostpay', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['action'] = $this->url->link('payment/jostpay', 'token=' . $this->session->data['token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['jostpay_merchant_id'])) {
			$data['jostpay_merchant_id'] = $this->request->post['jostpay_merchant_id'];
		} else {
			$data['jostpay_merchant_id'] = $this->config->get('jostpay_merchant_id');
		}

		if (isset($this->request->post['jostpay_hash_key'])) {
			$data['jostpay_hash_key'] = $this->request->post['jostpay_hash_key'];
		} else {
			$data['jostpay_hash_key'] = $this->config->get('jostpay_hash_key');
		}

		if (isset($this->request->post['jostpay_live_mode'])) {
			$data['jostpay_live_mode'] = $this->request->post['jostpay_live_mode'];
		} else {
			$data['jostpay_live_mode'] = $this->config->get('jostpay_live_mode');
		}

		$data['callback'] = HTTP_CATALOG . 'index.php?route=payment/jostpay/callback';

		if (isset($this->request->post['jostpay_total'])) {
			$data['jostpay_total'] = $this->request->post['jostpay_total'];
		} else {
			$data['jostpay_total'] = $this->config->get('jostpay_total');
		}

		if (isset($this->request->post['jostpay_pending_order_status_id'])) {
			$data['jostpay_pending_order_status_id'] = $this->request->post['jostpay_pending_order_status_id'];
		} else {
			$data['jostpay_pending_order_status_id'] = $this->config->get('jostpay_pending_order_status_id');
		}


		if (isset($this->request->post['jostpay_cancelled_order_status_id'])) {
			$data['jostpay_cancelled_order_status_id'] = $this->request->post['jostpay_cancelled_order_status_id'];
		} else {
			$data['jostpay_cancelled_order_status_id'] = $this->config->get('jostpay_cancelled_order_status_id');
		}


		if (isset($this->request->post['jostpay_completed_order_status_id'])) {
			$data['jostpay_completed_order_status_id'] = $this->request->post['jostpay_completed_order_status_id'];
		} else {
			$data['jostpay_completed_order_status_id'] = $this->config->get('jostpay_completed_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['jostpay_geo_zone_id'])) {
			$data['jostpay_geo_zone_id'] = $this->request->post['jostpay_geo_zone_id'];
		} else {
			$data['jostpay_geo_zone_id'] = $this->config->get('jostpay_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['jostpay_status'])) {
			$data['jostpay_status'] = $this->request->post['jostpay_status'];
		} else {
			$data['jostpay_status'] = $this->config->get('jostpay_status');
		}

		if (isset($this->request->post['jostpay_sort_order'])) {
			$data['jostpay_sort_order'] = $this->request->post['jostpay_sort_order'];
		} else {
			$data['jostpay_sort_order'] = $this->config->get('jostpay_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('payment/jostpay.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/jostpay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['jostpay_merchant_id']) {
			$this->error['merchant'] = $this->language->get('error_merchant_id');
		}

		if (!$this->request->post['jostpay_hash_key']) {
			$this->error['security'] = $this->language->get('error_hash_key');
		}

		return !$this->error;
	}
}