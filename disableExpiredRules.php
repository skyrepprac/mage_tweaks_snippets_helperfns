<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit','1024M');

require_once __DIR__ . '/app/Mage.php';
umask(0);
Mage::app('default');
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

Mage::setIsDeveloperMode(true);
Varien_Profiler::enable();

$salesRule = Mage::getModel('salesrule/rule');
$catalogRule = Mage::getModel('catalogrule/rule');
$today = strtotime('Today');

$catalogRules = $catalogRule->getCollection()->load();
$salesRules = $salesRule->getCollection()->load();

foreach($salesRules as $cartPriceRule) {
	if(!empty($cartPriceRule->getToDate()) && strtotime($cartPriceRule->getToDate()) < $today):
		try{
			$priceRuleName = $cartPriceRule->getName();
			$cartPriceRule->setState(false)->setIsActive(false)->save();
			echo "<pre/>";print "Shoppingcart Price rule {$priceRuleName} has been disabled !!!";
		} catch(Exception $e) {
			echo "<pre/>";print $e->getMessage();die;
		}
	endif;
}

// foreach($catalogRules as $prodPriceRule) {
// 	if(!empty($prodPriceRule->getToDate()) && strtotime($prodPriceRule->getToDate()) < $today):
// 		try{
// 			$priceRuleName = $prodPriceRule->getName();
// 			$prodPriceRule->setState(false)->save();
// 			echo "<pre/>";print "Catalog Price rule {$priceRuleName} has been disabled !!!";
// 		} catch(Exception $e) {
// 			echo "<pre/>";print $e->getMessage();die;
// 		}
// 	endif;
// }