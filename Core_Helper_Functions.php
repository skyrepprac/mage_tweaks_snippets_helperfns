<?php
class Mage_Extended_Helper_Core_Functions extends Mage_Core_Helper_Data
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

	public function purgeMageCache($silent = false)
	{
		$cacheDir = Mage::getBaseDir().DS."var".DS."cache";
		$logDir = Mage::getBaseDir().DS."var".DS."log";
		$reportDir = Mage::getBaseDir().DS."var".DS."report";
		$dirsToClear = array($cacheDir, /*$logDir, $reportDir*/);
		try {
			//CLEAN OVERALL CACHE
			flush();
			Mage::app()->cleanCache();
			// CLEAN IMAGE CACHE
			flush();
			Mage::getModel('catalog/product_image')->clearCache();
			Mage::getModel('core/design_package')->cleanMergedJsCss();
			Mage::dispatchEvent('clean_media_cache_after');
			//Remove all the cache directories
			$this->_emptyFolders($dirsToClear);
			if($silent === false) print '<pre>All magento caches were cleared successfully</pre>';
			//$this->_purgeFolder($cacheDir);
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

	private function _emptyFolders($folders = array())
	{
		foreach($folders as $folder) {
			$di = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
			$ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
			try {
				foreach($ri as $file) {
					$file->isDir() ?  rmdir($file) : unlink($file);
				}
				return true;
			} catch(Exception $e) {
				return "<pre>Error in deleting directory $folder, actual error: ".$e->getMessage()."</pre>";
			}
		}
	}

	public function fetchLoggedInUser()
	{

	}

	public function fetchAdminUser()
	{

	}

	public function filterInString()
	{

	}

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
}
