<?php
/**
 * http://new-dev.ru/
 * author GoldSoft <newdevexpert@gmail.com>
 * Copyright (c) New-Dev.ru
 */

use WebCash\WebCash;
use WebCash\Plugins_BalanceOut;
use WebCash\Plugins_Admin_BalanceOut;

defined('DATALIFEENGINE') or exit('Access Denied');

$webcash->alfacoins->readSettingsFromFile();


if ($action == 'checkout') {
	if ($email = POST('email') and filter_var($email, FILTER_VALIDATE_EMAIL)) {
		if ($invoice_id = (int)POST('invoice_id')) {
			if ($url = $webcash->alfacoins->createOrder($invoice_id)) {
				$webcash->helper->showMsgOk('Переход в платежную систему. Загрузка...', 'Информация', $url);	
			} else {
				$webcash->helper->showMsgError('Ошибка при создании платежа');
			}
		}
		
	} else {
		$webcash->helper->showMsgError('Укажите свой Емайл');
	}
	
}


$webcash->helper->showMsgError('Ошибка при выполнении запроса');