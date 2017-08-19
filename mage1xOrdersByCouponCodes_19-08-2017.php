<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit','1024M');
define("MAGE_ROOT", __DIR__);
require_once __DIR__ . '/app/Mage.php';
umask(0);
Mage::app('default');
Mage::setIsDeveloperMode(true);
Varien_Profiler::enable();

$coreMySQLi = connectMySQLiDb(MAGE_ROOT . "/app/etc/local.xml");
setMySQLiCharset($coreMySQLi, "utf8mb4");
setGlobalEncoding('UTF-8');
# echo "<pre/>Mysql Response:";print_r(queryMySQLServer($coreMySQLi));die;
//setMySQLiCharset($coreMySQLi, "latin1");
$mageCoreHelper = Mage::helper('core');
# var_dump(mysqli_get_charset($coreMySQLi));

$orders = Mage::getModel('sales/order')
			->getCollection()
			->addFieldToFilter('applied_rule_ids', array('eq' => array('1075284')))
			->addFieldToSelect('*');
$ordersToExport = array();
$itr = 0;
foreach($orders as $order) {
	$ordersToExport[$itr]['Order Id'] = $order->getIncrementId();
	# $ordersToExport[$itr]['applied_rule_ids'] = $order->getAppliedRuleIds();
	$ordersToExport[$itr]['Customer First Name'] = $mageCoreHelper->__($order->getCustomerFirstname());
	$ordersToExport[$itr]['Customer Last Name'] = $mageCoreHelper->__($order->getCustomerLastname());
	$ordersToExport[$itr]['Customer Email'] = $order->getCustomerEmail();
	$ordersToExport[$itr]['Order Created On'] = $order->getCreatedAt();
	$itr++;
}

header("Content-Type: application/csv; charset=utf-8");
header('Content-Disposition: attachment; filename=IndependenceDayDiscountOrders.csv');
$headers = array_keys($ordersToExport[0]);
$fp = fopen("php://output", "w+");
fputcsv($fp, $headers, ",");
foreach($ordersToExport as $row) {
	fputcsv($fp, $row, ",");
}
fclose($fp);

function setMySQLiCharset($mysqliConnObj, $charSet) {
	mysqli_set_charset($mysqliConnObj, "{$charSet}");
	mysqli_query($mysqliConnObj, "SET character_set_results={$charSet}");
	mysqli_query($mysqliConnObj, "SET names={$charSet}");
	mysqli_query($mysqliConnObj, "SET NAMES {$charSet}");
	mysqli_query($mysqliConnObj, "SET character_set_client={$charSet}");
	mysqli_query($mysqliConnObj, "SET character_set_connection={$charSet}");
	mysqli_query($mysqliConnObj, "SET collation_connection={$charSet}_general_ci");
	mysqli_query($mysqliConnObj, "SET collation_connection={$charSet}_unicode_ci");
	return true;
}

function connectMySQLiDb($configFilePath, $configParams = array()) {
	$xml = simplexml_load_file($configFilePath);
	$host = $xml->global->resources->default_setup->connection->host;
	$username = $xml->global->resources->default_setup->connection->username;
	$password = $xml->global->resources->default_setup->connection->password;
	$dbname = $xml->global->resources->default_setup->connection->dbname;
	$coreMySQLi = new mysqli($host, $username, $password, $dbname);
	if(mysqli_connect_errno()) {
		printf("Connect failed: %s\n", mysqli_connect_error());
		exit();
	}
	return $coreMySQLi;
}

function queryMySQLServer($mysqliConnObj) {
	$serverResponse['charset'] = mysqli_fetch_array(mysqli_query($mysqliConnObj, "SHOW VARIABLES LIKE '%character%'"), MYSQL_BOTH);
	$serverResponse['collation'] = mysqli_fetch_array(mysqli_query($mysqliConnObj, "SHOW VARIABLES LIKE '%collation%';"), MYSQL_BOTH);
	$serverResponse['default'] = mysqli_fetch_array(mysqli_query($mysqliConnObj, "SHOW VARIABLES LIKE '%default%'"), MYSQL_BOTH);
	return $serverResponse;
}

function setGlobalEncoding($encoding) {
	ini_set("default_charset", "{$encoding}");
	mb_internal_encoding("{$encoding}");
	iconv_set_encoding("internal_encoding", "{$encoding}");
	iconv_set_encoding("output_encoding", "{$encoding}");
}

/*
ALTER DATABASE [database_name] CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE [table_name] CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

Or if you're still on MySQL 5.5.2 or older which didn't support 4-byte UTF-8, use utf8 instead of utf8mb4:

ALTER DATABASE [database_name] CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE [table_name] CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;

Make a backup!

Then you need to set the default char sets on the database. This does not convert existing tables, it only sets the default for newly created tables.

ALTER DATABASE [database_name] CHARACTER SET utf8 COLLATE utf8_general_ci;

Then, you will need to convert the char set on all existing tables and their columns. This assumes that your current data is actually in the current char set. If your columns are set to one char set but your data is really stored in another then you will need to check the MySQL manual on how to handle this.

ALTER TABLE [table_name] CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
*/