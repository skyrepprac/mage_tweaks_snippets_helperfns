<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit','1024M');

class Mage_Improved_Helper_SalesOrder_Modifier extends Mage_Core_Helper_Abstract
{
	private $_orderFinalTax = 0.0000;
	private $_orderTotalTax = 0.0000;
	private $_orderId = 0;
	private $_orderIncId = 0000000000;
	private $_order = null;
	private $_orderItems = null;
	private $_orderProducts = array();
	private $_currStore = 'default';

	private static $_mageObjModels = array();

	public function init($orderIncrementId, $mageStore = null) {
		if(!isset($orderIncrementId) || $orderIncrementId == null || !preg_match('/[^0]/', $orderIncrementId)):
			self::$_mageObjModels['MageCoreSession']->addError("Sorry, the IncrementId \"{$orderIncrementId}\" is invalid !!!");
			exit("Sorry, the IncrementId \"{$orderIncrementId}\" is invalid !!!");
		endif;

		self::$_mageObjModels['OrderModel'] = Mage::getModel('sales/order');
		self::$_mageObjModels['OrderItemModel'] = Mage::getModel('sales/order_item');
		self::$_mageObjModels['OrderConvModel'] = Mage::getModel('sales/convert_order');
		self::$_mageObjModels['InvoiceModel'] = Mage::getModel('sales/order_invoice');
		self::$_mageObjModels['InvoiceItemModel'] = Mage::getModel('sales/order_invoice_item');
		self::$_mageObjModels['TaxCalcModel'] = Mage::getModel('tax/calculation');
		self::$_mageObjModels['TaxClassModel'] = Mage::getModel('tax/class');
		self::$_mageObjModels['ProductModel'] = Mage::getModel('catalog/product');
		self::$_mageObjModels['MageDbConn'] = Mage::getSingleton('core/resource');
		self::$_mageObjModels['MageTransactModel'] = Mage::getModel('core/resource_transaction');
		self::$_mageObjModels['MageCoreSession'] = Mage::getSingleton('core/session');

		$collection = self::$_mageObjModels['OrderModel']->getCollection()->addFieldToFilter('increment_id', $orderIncrementId);
		if ($collection->count() <= 0):
			self::$_mageObjModels['MageCoreSession']->addError("Sorry, the IncrementId \"{$orderIncrementId}\" doesn't exist in our database !!!");
			exit("Sorry, the IncrementId \"{$orderIncrementId}\" doesn't exist in our database !!!");
		endif;

		$this->_orderIncId = $orderIncrementId;
		$this->_order = self::$_mageObjModels['OrderModel']->loadByIncrementId($this->_orderIncId);
		$this->_orderId = $this->_order->getId();
		$this->_orderItems = $this->_order->getAllVisibleItems();
		foreach($this->_orderItems as $_orderItem) {
			$orderProductId = $_orderItem->getProduct()->getId();
			$this->_orderProducts[] = self::$_mageObjModels['ProductModel']->load($orderProductId);
		}
		if($mageStore !== null) $this->_currStore = Mage::app()->getStore($mageStore);
		else $this_currStore = Mage::app()->getStore($this->_currStore);
		return $this;
	}

	public function __destruct() {
		unset($this->_orderIncId, $this->_order, $this->_orderId, $this);
		return true;
	}

	protected function _construct() {}

	private function _coreDbConnection() {
		if(isset($resource)) unset($resource);
		$resource = self::$_mageObjModels['MageDbConn'];
		$coreConn['resource'] = $resource;
		$coreConn['read'] = $resource->getConnection('core_read');
		$coreConn['write'] = $resource->getConnection('core_write');
		return $coreConn;
	}

	protected function _getOrderItemUniqs($_orderItem) {
		$orderItem['itemId'] = $_orderItem->getItemId();
		$orderItem['productId'] = $_orderItem->getProduct()->getId();
		return $orderItem;
	}

	public function resetOrderItemTax($_order = null) {
		foreach($this->_orderItems as $orderItem) {
			$itemUniqs = $this->_getOrderItemUniqs($orderItem);
			$product = $this->_getItemProduct($orderItem);
			$taxClassId = $product->getTaxClassId();
			$taxClass = self::$_mageObjModels['TaxClassModel']->load($taxClassId);
			$prodTaxClass = $taxClass->getClassName();
			if($orderItem->getTaxAmount() <= 0 && $orderItem->getTaxPercent() <= 0):
				if($prodTaxClass != "None"):
					$taxRateRequest = self::$_mageObjModels['TaxCalcModel']->getRateRequest(null, null, null, $this->_currStore);
					$taxRatePercent = self::$_mageObjModels['TaxCalcModel']->getRate($taxRateRequest->setProductClassId($taxClassId));
					if($taxRatePercent > 0) {
						$orderItemNewTax = $this->_calcOrderItemTax($orderItem);
						$orderItem->save();
						$orderTotalTax = $this->_calcOrderTax();
						$this->_modifyOrderTax($this->_order, $orderTotalTax);
						$this->_order->save();
						self::$_mageObjModels['MageCoreSession']->addSuccess("Tax of {$itemUniqs['itemId']} pertaining to product {$itemUniqs['productId']} has been re-calculated and reset successfully !!!");
					} else {
						self::$_mageObjModels['MageCoreSession']->addError("Tax of {$itemUniqs['itemId']} pertaining to product {$itemUniqs['productId']} cannot be re-calculated as there is no tax configured for that product !!!");
					}
				endif;
			endif;
		}
	}

	protected function _getItemProduct($_orderItem) {
		$productId = $_orderItem->getProduct()->getId();
		$itemProduct = self::$_mageObjModels['ProductModel']->load($productId);
		return $itemProduct;
	}

	protected function _calcOrderItemTax($_orderItem) {
		$itemDiscPrice = $this->_getItemDiscountedPrice($_orderItem);
		$orderItemTax = self::$_mageObjModels['TaxCalcModel']->calcTaxAmount($itemDiscPrice, $taxPercent, true);
		//$ordItemTotalTax = $orderItemTax * $orderItem->getQtyOrdered();
		$ordItemTotalTax = $orderItemTax;
		$_orderItem->setTaxAmount($ordItemTotalTax);
		return (float)$ordItemTotalTax;
	}

	protected function _calcOrderTax($_order = null) {
		foreach($this->_orderItems as $orderItem) {
			$orderItemTax = $this->_calcOrderItemTax($orderItem);
			$orderItemTaxes[] = $orderItemTax;
		}
		$orderTotalTax = (float)array_sum($orderItemTaxes);
		return $orderTotalTax;
	}

	protected function _modifyOrderTax($_order, $_orderTax) {
		$_order->setBaseTaxAmount($_orderTax);
		$_order->setBaseTaxInvoiced($_orderTax);
		$_order->setTaxAmount($_orderTax);
		$_order->setTaxInvoiced($_orderTax);
		return true;
	}

	protected function _getItemDiscountedPrice($_orderItem) {
		$itemDiscount = $_orderItem->getDiscountAmount();
		$itemPrice = $_orderItem->getProduct()->getPrice() * $_orderItem->getQtyOrdered();
		$itemDiscPrice = (!empty($itemDiscount) && $itemDiscount > 0) ? $itemPrice - $itemDiscount : $itemPrice;
		return (float)$itemDiscPrice;
	}
}

// Mage::getSingleton('core/session')->addSuccess('Success Message');
// Mage::getSingleton('core/session')->addError('Error Message');
// Mage::getSingleton('core/session')->addWarning('Warning Message');
// Mage::getSingleton('core/session')->addNotice('Notice Message');

# Usage:
// require_once __DIR__ . '/app/Mage.php';
// Varien_Profiler::enable();
// Mage::setIsDeveloperMode(true);
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// umask(0);
// Mage::app();

// $orderEditor = Mage::helper('improved/salesOrder_modifier')->init('100000049');
// $orderEditor->resetOrderItemTax();
// echo "<pre/>"; print_r($orderEditor); die("<br/>Data printed above !!!");
