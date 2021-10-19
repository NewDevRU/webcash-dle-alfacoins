<?php
/**
 * http://new-dev.ru/
 * author GoldSoft <newdevexpert@gmail.com>
 * Copyright (c) New-Dev.ru
 */

namespace WebCash;

defined('DATALIFEENGINE') or exit('Access Denied');

if (!$this->readSettingsFromFile()) {
	$this->setCfgValue('display_name', 'Платежный шлюз ALFAcoins');
	$this->setCfgValue('description', 'С помощью этого шлюза можно организовать прием платежей через платежную систему ALFAcoins.');
	$this->setCfgValue('api_name', WebCash::DEBUG_SETTINGS ? 'NewDev.ru' : '');
	$this->setCfgValue('api_password', WebCash::DEBUG_SETTINGS ? 'a4f341caf(2917986b0e2' : '');
	$this->setCfgValue('api_secret_key', WebCash::DEBUG_SETTINGS ? '2421f0df708a9e7d300b' : '');
	$this->setCfgValue('currency', 'RUB');
	$this->setCfgValue('test_mode', DEBUG_SETTINGS ? 1 : 0);
	$this->setCfgValue('api_type_new', WebCash::DEBUG_SETTINGS ? 'litecoin' : 'bitcoin');
	$this->setCfgValue('site_url', 'https://alfacoins.com/');
	$this->setCfgValue('access_allowed_usergroups', array());
	$this->setNoneFormControlCfgValue('public_cfg_fields', array(
		'currency',
		'test_mode',
	));
	
	$str = $this->checkout->getGatewayProcessingUrl($this->alias);
	
	$this->addHint(__FILE__.'1', 'Внимание, перед началом работы необходимо указать: имя проекта (Project title), секретный ключ (Security key), пароль (Password), которые надо скопировать из личного кабинета ALFAcoins <a href="'.$this->site_url.'" target="_blank">'.$this->site_url.'</a>, указав там адрес для получения HTTP-уведомлений (Notification URL): <a href="'.$str.'" target="_blank"><code>'.$str.'</code></a>. Параметр &laquo;Валюта аккаунта&raquo; должна совпадать с параметром личного кабинета &laquo;Fiat currency&raquo;, а параметр &laquo;Криптовалюта по умолчанию&raquo; должна быть разрешена в параметре &laquo;Cryptocurrency&raquo;.');
	
	$this->addHint(__FILE__.'2', 'При необходимости проверки уведомлений без оплаты можно включить тестовый режим модуля и в личном кабинете ALFAcoins выбрать значение &laquo;LTCT&raquo; в параметре &laquo;Cryptocurrency&raquo;. И хотя в этом случае выдается страница по адресу: <code>https://www.alfacoins.com/invoice/TEST</code></a> (404 ошибка) - платежный шлюз сразу отправляет уведомление на нужный адрес.');

	
	$this->setFieldsItem('api_name', array(
		'title' => 'Имя проекта',
		'hint' => 'Имя проекта API, который был создан в системе &laquo;ALFAcoins&raquo;',
		'type' => 'text',
		'required' => true,
	));
	
	$this->setFieldsItem('api_password', array(
		'title' => 'Пароль',
		'hint' => 'Пароль API, созданный в системе &laquo;ALFAcoins&raquo;',
		'type' => 'text',
		'required' => true,
	));
	
	$this->setFieldsItem('api_secret_key', array(
		'title' => 'Секретный ключ',
		'hint' => 'Используется  в системе &laquo;ALFAcoins&raquo;',
		'type' => 'text',
		'required' => true,
	));
	
	
	$arr = $this->wc_currency->getCurrenciesList();
	$arr = filter_allowed_keys($arr, array('RUB', 'USD', 'EUR', 'UAH'));
	
	$this->setFieldsItem('currency', array(
		'title' => 'Валюта аккаунта',
		'hint' => 'Валюта которая используется в аккаунте',
		'type' => 'select',
		'value' => array_keys($arr),
		'label' => array_values($arr),
		'required' => true,
	));
	
	
	$arr = array(
		'bitcoin' => 'Bitcoin',
		'litecoin' => 'Litecoin',
		'ethereum' => 'Ethereum',
		'bitcoincash' => 'Bitcoin Cash',
		'dash' => 'Dash',
		'xrp' => 'XRP',
		'litecointestnet' => 'Litecoin Testnet',
	);
	$this->setFieldsItem('api_type_new', array(
		'title' => 'Криптовалюта по умолчанию',
		'hint' => 'Криптовалюта по умолчанию, выбранная в способе оплаты, вы можете использовать все или только одну - можете настроить ее на странице настроек API ALFAcoins',
		'type' => 'select',
		'value' => array_keys($arr),
		'label' => array_values($arr),
		'required' => true,
	));
	
	$this->setFieldsItem('test_mode', array(
		'title' => 'Включение тестового режима',
		'hint' => 'Если включено - то платеж выполняется в тестовом режиме, средства реально не переводятся',
		'type' => 'checkbox',
	));
	
	$this->setFieldsItem('site_url', array(
		'title' => 'Сайт провайдера',
		'hint' => 'Данный адрес используется в списке шлюзов только для удобства работы',
		'type' => 'text',
	));
	
	
	$this->convertDefaultValues();
	$this->writeSettingsInFile(true);
}