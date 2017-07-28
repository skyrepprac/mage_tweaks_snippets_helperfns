<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit','1024M');
require_once __DIR__ . '/app/Mage.php';
umask(0);
Mage::app('default');

$zeroPriceProducts = Mage::getModel('catalog/product')->getCollection()
						->addAttributeToFilter(
							array(
								array('attribute'=>'price', 'eq'=>'0'),
								array('attribute'=>'price', 'isnull'=>true),
							)
						);
$ids = $zeroPriceProducts->getAllIds();
try {
	/*Mage::getSingleton('catalog/product_action')->updateAttributes(
		$ids,
		array('status' => 2),
		0
	);*/
	Mage::getSingleton('catalog/product_action')->updateAttributes(
		$ids,
		array('status' => 2, 'visibility' => 1),
		0
	);
	echo "All products with 0 price have been disabled !!!";
} catch(Exception $e) {
	echo "Error occurred disabling the products: ".$e;
	exit(0);
}
