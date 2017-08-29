<?php
class Mage_MageDev_Helper_Core_Functions extends Mage_Core_Helper_Data
{
	public function highlightWords($content, $word, $colors)
	{
		$color_index = 0;
		foreach( $words as $word ) {
			$content = self::_highlightWord($content, $word, $colors[$color_index]);
			$color_index = ( $color_index + 1 ) % count( $colors );
		}
		return $content;
	}

	private function _highlightWord($content, $word, $color)
	{
		$replace = '<span style="background-color: ' . $color . ';">' . $word . '</span>';
		$content = str_replace($word, $replace, $content);
		return $content;
	}

	/*public function searchCollection($collection)
	{
	}*/

	public function getBasePath($type = 'absolute')
	{
		if($type == 'relative') {
			//return realpath(__DIR__);
			return realpath(dirname(__FILE__));
			return substr($_SERVER['SCRIPT_NAME'], 0, strpos($_SERVER['SCRIPT_NAME'],basename($_SERVER['SCRIPT_NAME'])));
		} else {
			return Mage::getBaseDir();
		}
	}

	public function findInStaticContent($needle)
	{
		$staticBlocks = Mage::getModel('cms/block')
							->getCollection()
							->addFieldToSelect('identifier')
							/*->addFieldToFilter('content', array(
												array('like' => '% '.$needle.' %'),
												array('like' => '% '.$needle),
												array('like' => $needle.' %')
											));*/
							->addFieldToFilter('content', array('regexp' => '[[:<:]]'.$needle.'[[:>:]]'));
		$staticPages = Mage::getModel('cms/page')
							->getCollection()
							->addFieldToSelect('identifier')
							/*->addFieldToFilter('content', array(
												array('like' => '% '.$needle.' %'),
												array('like' => '% '.$needle),
												array('like' => $needle.' %')
											));*/
							->addFieldToFilter('content', array('regexp' => '[[:<:]]'.$needle.'[[:>:]]'));
		$searchResult['static_blocks'] = $staticBlocks->getData();
		$searchResult['static_pages'] = $staticPages->getData();
		return $searchResult;
	}

	public function getActualProducts()
	{
		$collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
		$collection->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
				->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
		return $collection;
	}

	public function getActualCategories()
	{
		$_helper = Mage::helper('catalog/category');
		$_categories = $_helper->getStoreCategories();
		if (count($_categories) > 0) {
		    foreach($_categories as $_category) {
		        $_category = Mage::getModel('catalog/category')->load($_category->getId());
		        $_subcategories = $_category->getChildrenCategories();
		        if (count($_subcategories) > 0) {
		            echo $_category->getName();
		            echo $_category->getId();
		            foreach($_subcategories as $_subcategory) {
		                 echo $_subcategory->getName();
		                 echo $_subcategory->getId();
		            }
		        }
		    }
		}
	}

	public function purgeMageCache()
	{
		$cacheDir = Mage::getBaseDir().DS."var".DS."cache";
		try {
			//CLEAN OVERALL CACHE
			flush();
			Mage::app()->cleanCache();
			// CLEAN IMAGE CACHE
			flush();
			Mage::getModel('catalog/product_image')->clearCache();
			//Remove all the cache directories
			print '<pre>All magento caches were cleared successfully</pre>';
			//$this->_purgeFolder($cacheDir);
			$this->_emptyFolder($cacheDir);
		} catch(Exception $e) {
			print($e->getMessage());
		}
	}

	private function _purgeFolder($folder)
	{
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($files as $fileinfo) {
			$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
			$todo($fileinfo->getRealPath());
		}
		rmdir($folder);
		return true;
	}

	private function _emptyFolder($folder)
	{
		$di = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
		$ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
		try {
			foreach ( $ri as $file ) {
				$file->isDir() ?  rmdir($file) : unlink($file);
			}
			return true;
		} catch(Exception $e) {
			return "<pre>Error in deleting directory $folder, actual error: ".$e->getMessage()."</pre>";
		}
	}

	public function fetchLoggedInUser() {
		if(Mage::isInstalled() && Mage::getSingleton('customer/session')->isLoggedIn()) {
			return Mage::getSingleton('customer/session')->getCustomer();
		} else {
			return false;
		}
	}

	public function fetchAdminUser() {
		if(Mage::isInstalled() && Mage::getSingleton('admin/session')->getUser()) {
			return Mage::getSingleton('admin/session')->getUser();
		} else {
			return false;
		}
	}

	public function filterInString(){}

	public function randPassGen($length = 12) {
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < $length; $i++) {
		    $n = rand(0, $alphaLength);
		    $pass[] = $alphabet[$n];
		}
		return implode($pass); //turn the array into a string
	}

	public function getProdAttrVals($productId, $attrCode = NULL) {
		$product = Mage::getModel('catalog/product')->load($productId);
		$attributes = $product->getAttributes();
		foreach ($attributes as $attribute) {
			if($attrCode !== NULL && $attribute->getAttributeCode() == $attrCode) {
				$attributeLabel = $attribute->getFrontendLabel();
				$value = $attribute->getFrontend()->getValue($product);
				echo $attributeLabel . '-' . $label . '-' . $value; echo "<br />";
			} else {
				$attributeLabel = $attribute->getFrontendLabel();
				$value = $attribute->getFrontend()->getValue($product);
				echo $attributeLabel . '-' . $label . '-' . $value; echo "<br />";
			}
		}
	}

	public function custmTemplateBlock($moduleName, $blockType, $templatePath) {
		## $layout = Mage::app()->getLayout()->createBlock(''.$moduleName.'/'.$blockType.'')->toHtml();
		$layout = Mage::getSingleton('core/layout');
		$html = $layout
				->createBlock(''.$moduleName.'/'.$blockType.'')
				->setTemplate(''.$templatePath)
				->toHtml();
		return $html;
	}

	public function coreTemplateBlock($templatePath) {
		## $layout = Mage::app()->getLayout()->createBlock('core/template')->toHtml();
		$layout = Mage::getSingleton('core/layout');
		$html = $layout
				->createBlock('core/template')
				->setTemplate(''.$templatePath)
				->toHtml();
		return $html;
	}

	public function getHeaderBlock() {
		$headerBlock = Mage::app()
						->getLayout()
						->createBlock('page/html_header')
						->toHtml();
		return $headerBlock;
	}

	public function getFooterBlock() {
		$headerBlock = Mage::app()
						->getLayout()
						->createBlock('page/html_footer')
						->toHtml();
		return $headerBlock;
	}

	public function disableFreeProducts() {
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
		return true;
	}

	public function adminPathHints($enable = true)
	{
		//database read adapter
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		//database write adapter
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		//Functions of read and write adapter	
		//$readMethods = get_class_methods(get_class($read));foreach($readMethods as $r){echo $r."\n";}
		//$writeMethods = get_class_methods(get_class($write));foreach($writeMethods as $r){echo $r."\n";}
		if($enable === true) {
			$write->insert('core_config_data', array('scope' => "websites", 'scope_id' => 0, 'path' => 'dev/debug/template_hints', 'value' =>
			 1));
			$write->insert('core_config_data', array('scope' => "websites", 'scope_id' => 0, 'path' => 'dev/debug/template_hints_blocks', 'value' => 1));
		} else {
			$write->delete('core_config_data', "path='dev/debug/template_hints'");
			$write->delete('core_config_data', "path='dev/debug/template_hints_blocks'");
		}
		return $this->purgeMageCache();
	}

	public function reindexAllRequiredProcesses() {
		$indexer = Mage::getSingleton('index/indexer');
		$indexer = Mage::getModel('index/indexer');
		$processes = $indexer->getProcessesCollection();
		foreach ($processes as $process) {
			$processCode = $process->getIndexerCode();
			if($process->getStatus() == Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX) {
				try {
					$process->reindexEverything();
					echo "<pre/>";print "Process '{$processCode}' has been reindexed !!!";
				} catch(Exception $e) {
					echo "<pre/>Error in re-indexing process:";print $e->getMessage();die;
				}
			} else {
				echo "<pre/>";print "No re-index for process '{$processCode}' is needed !!!";
			}
		}
	}
}
