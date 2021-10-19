<?php
/**
 * http://new-dev.ru/
 * author GoldSoft <newdevexpert@gmail.com>
 * Copyright (c) New-Dev.ru
 */

namespace WebCash;

defined('DATALIFEENGINE') or exit('Access Denied');

class Gateways_Alfacoins extends AddonSettings
{
    const API_URL = 'https://www.alfacoins.com/api';
    protected $alias = 'alfacoins';

    public function renderPaymentForm($invoice_id, $cs_key, $gw_item_id) {
        if ($str = $this->verifyGatewayEnable())
            return $str;

        if (!$checkout_store = $this->checkout->getDataStore($cs_key))
            return '';
		
        $service_info = $this->getServiceInfo();

        if (!$gw_item = safe_array_access($service_info, 'items', $gw_item_id))
            trigger_error(basename(__FILE__).', line: '.__LINE__, E_USER_ERROR);

        $amount = $this->wc_currency->convertMainCurrencyTo($checkout_store['amount'], $this->currency);
		
		if (!$a = $this->getMinAmountLimit()) {
			$this->webcash->siteMsgError('Не удалось получить значение минимальной суммы платежа');
			return;
		} elseif ($a > $amount) {
			$this->webcash->siteMsgError('Минимальная сумма платежа: '.$a);
			return;
		}
		
        $tpl = $this->webcash->getTplInstance();
        $tpl->assign('invoice_id', $invoice_id);
        $tpl->assign('amount', $amount);
        $tpl->assign('email', $this->helper->htmlspecialchars(safe_array_access($checkout_store, 'email')));
        $tpl->assign('params', $params);

        $tpl->assign('gw_item_id', $gw_item_id);
        $tpl->assign('gw_item', $gw_item);
        $tpl->assign('gateway_header', $service_info['name']);
        $tpl->assign('gw_alias', $this->alias);
        $tpl->assign('gateway_cfg', $this->getCfgPublicParams());
        $tpl->assign('addon_settings_link', $this->renderAddonSettingsLink());
        $tpl->assign('user_hash', $this->user->nonce);
        $tpl->load_template('/modules/webcash/gateways/alfacoins/checkout.tpl');

        $tpl->compile('content');

        return $tpl->result['content'];
    }

    public function createOrder($invoice_id) {
        if (!$invoice_row = $this->webcash->getRowById($this->webcash->gateway_invoices_table, $invoice_id)) {
            $this->helper->showMsgError('Нет такого инвойса');
        }
		
		$checkout_store = unserialize($invoice_row['checkout_store']);
		$description = ($this->webcash->site_url.' - '.to_utf8(safe_array_access($checkout_store, 'checkout_header')));
		
		$name = '';
		
		if ($this->user->isLoggedIn()) {
			if (!$name = safe_array_access($this->dle->member_id, 'fullname')) {
				$name = $this->user->login;
			}
		}
		
		if (!$name) {
			'User #'.$invoice_id;
		}
		

		$params = array(
			'name' => $this->api_name,
			'secret_key' => $this->api_secret_key,
			'password' => $this->getApiPassword(),
			'type' => $this->getApiTypeNew(),
			'amount' => $invoice_row['amount'], // must be float
			'order_id' => $invoice_id,
			'description' => $description,
			'currency' => $this->currency,
			'options' => array(
				'notificationURL' => $this->checkout->getGatewayProcessingUrl($this->alias),
				'redirectURL' => $this->checkout->successCheckoutUrl($invoice_id, $checkout_store['cs_key']),
				'payerName' => $name,
				'payerEmail' => $invoice_row['email'],
				'hide_warning' => 1,
			)
		);
		
		if ($this->test_mode) {
			$params['options']['hide_warning'] = 0;
			$params['options']['test'] = 1;
			$params['options']['status'] = 'completed';
		}
		
		
		try {
			$result = $this->curl(self::API_URL.'/create.json', $params);
			
			if (!empty($result['error'])) {
				$this->printError($result['error']);
				
				return false;
			} else {
				return $result['url'];
			}

		} catch (Exception $e) {
			$this->printError($e->getMessage());

			return false;
		}
	}
	
	private function getApiPassword() {
		return strtoupper(md5($this->api_password));
	}
	
	private function getApiTypeNew() {
		return $this->test_mode ? 'litecointestnet' : $this->api_type_new;
	}
	
	private function curl($url, $params) {
		$content = json_encode($params);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json; charset=UTF-8'));
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		$json_response = curl_exec($ch);

		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($status != 200) {
			throw new \Exception("Error: call to URL {$url} failed with status {$status}, response {$json_response}, curl_error ".curl_error($ch).", curl_errno ".curl_errno($ch));
		}
		
		curl_close($ch);

		$response = json_decode($json_response, TRUE);
		return $response;
	}
	
	public function getMinAmountLimit() {
		$response = $this->http->loadUrl(self::API_URL.'/limits.json?base='.$this->currency);
		$json = json_decode($response, true);
		return safe_array_access($json, $this->getApiTypeNew(), 'min_fiat_amount');
	}
	
    public function processing() {
        // проверка на наличие обязательных полей
        foreach(array(
                    'id',
                    'coin_received_amount',
                    'modified',
                    'received_amount',
                    'status',
                    'order_id',
                    'currency',
                    'hash',
                    'type',
                ) as $field) {
            if (!POST($field)) {
                $this->printError('Не указаны обязательные данные');
            }
        }
		
		
		// validate all used variables
		$_POST['coin_received_amount'] = nd_decpoint(round((float)$_POST['coin_received_amount'], 8));
		$_POST['id'] = (int)$_POST['id'];
		$_POST['received_amount'] = nd_decpoint(round((float)$_POST['received_amount'], 8));
		$_POST['order_id'] = (int)$_POST['order_id'];
		// uppercase
		$_POST['hash'] = preg_replace('#[^A-Z0-9]#', '', $_POST['hash']);
		// lowercase
		$_POST['currency'] = substr(preg_replace('#[^A-Z]#', '', $_POST['currency']), 0, 3);
		$_POST['status'] = preg_replace('#[^a-z]#', '', $_POST['status']);
		$_POST['type'] = preg_replace('#[^a-z]#', '', $_POST['type']);
		
		// проверка значения сигнатуры
		$arr = array(
			$this->api_name,
			$_POST['coin_received_amount'],
			$_POST['received_amount'],
			$_POST['currency'],
			$_POST['id'],
			$_POST['order_id'],
			$_POST['status'],
			$_POST['modified'],
			$this->getApiPassword()
		);
		
		$signature = strtoupper(md5(implode(':', $arr)));
		
        if ($_POST['hash'] != $signature) {
            $this->printError('Неверная подпись '.$_POST['hash']);
        }

		if ($_POST['currency'] != $this->currency) {
			$this->printError('Неверная валюта платежа '.$_POST['currency']);
		}
		
        $this->readSettingsFromFile();

		$amount = $_POST['received_amount'];
		$invoice_id = $_POST['order_id'];
		
        if (!$invoice_row = $this->webcash->getRowById($this->webcash->gateway_invoices_table, $invoice_id)) {
            $this->printError('Нет такого инвойса');
        }

        if ($invoice_row['state']) {
            $this->printError('Инвойс уже оплачен');
        }

        if ($invoice_row['gateway'] != $this->alias) {
            $this->printError('Инвойс не той платежной системы');
        }

        if ($invoice_row['amount'] > $amount) {
            $this->printError('Неверная сумма: '.$amount);
        }
		
		if ($_POST['status'] == 'completed') {
			$sender = safe_array_access($invoice_row, 'email') or $sender = $this->user->ip;
			$payment_id = $this->processAfterPayment($invoice_id, $sender);

			if ($payment_row = $this->webcash->getRowById($this->webcash->gateway_payments_table, $payment_id)) {
				$this->checkout->gatewaySuccessPayment($invoice_row, $payment_row);

				//fs_log("Инвойс #" . $invoice_id . " оплачен!");
				exit('OK');
			}
        }

        echo 'error';

        $this->printError('Error '.__LINE__);
    }

    public function updatePaymentStatus($invoice_id, $status) {
		
	}
	
    public function printError($text) {
        $text = 'Ошибка! '.$text;
        if (WebCash::DEBUG_SETTINGS) {
            fs_log('Merchant error ('.$this->alias.'): '.$text);
            echo $text;
        }

        exit;
    }

    public function processAfterPayment($invoice_id, $sender) {
        $gateway_details_arr = POST();
        $gateway_details_arr = array_map(array($this->helper, 'htmlspecialchars'), $gateway_details_arr);

        return $this->checkout->addPaymentToDb($invoice_id, $sender, $gateway_details_arr);
    }

    public function getServiceInfo() {
        $result = array(
            'name' => __('ALFAcoins'),
            'alias' => $this->alias,
            'items' => array(
                1 => array(
                    'title' => __('Перейти к оплате'),
                    'image' => 'alfacoins.png',
                ),
            ),
        );

        return $result;
    }
}