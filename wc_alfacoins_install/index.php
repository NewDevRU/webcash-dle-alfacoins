	<?php
/**
 * http://new-dev.ru/
 * author GoldSoft <newdevexpert@gmail.com>
 * Copyright (c) New-Dev.ru
 */

header('Content-Type: text/html; charset=utf-8');

define('ROOT_DIR', dirname(dirname(__FILE__)));
define('ENGINE_DIR', ROOT_DIR.'/engine');
define('DATALIFEENGINE', true);

if (!file_exists(ENGINE_DIR.'/data/config.webcash.php')) {
	echo '<p class="message information">Ошибка! &laquo;/engine/data/config.webcash.php&raquo; отсутствует. Для работы данного дополнения необходимо скачать и установить <a href="https://new-dev.ru/27-webcash-sistema-oplaty.html" target="_blank">платежный модуль Webcash</a>.</p>';
	exit;
}

require_once ENGINE_DIR.'/modules/webcash/site/includes/init_dle.php';
require_once ROOT_DIR.'/wc_alfacoins_install/functions.php';
require_once ENGINE_DIR.'/modules/webcash/init.php';
require_once ENGINE_DIR.'/modules/webcash/admin/lib/sql_parse.php';
require_once ENGINE_DIR.'/modules/webcash/admin/lib/xcopy/xcopy.php';

echo_top_html();

$mode = empty($_GET['mode']) ? 'install' : $_GET['mode'];

if (!in_array($mode, array('install', 'uninstall', 'readme'))) exit('error mode');

require_once ROOT_DIR.'/wc_alfacoins_install/mode/'.$mode.'.php';

echo_bottom_html();