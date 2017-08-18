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
setMySQLiCharset($coreMySQLi, "utf8");
setMySQLiCharset($coreMySQLi, "utf8mb4");
$mageCoreHelper = Mage::helper('core');
# var_dump(mysqli_get_charset($coreMySQLi));

$orders = Mage::getModel('sales/order')
			->getCollection()
			->addFieldToFilter('applied_rule_ids', array('eq' => array('[Unique Rule Id]')))
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