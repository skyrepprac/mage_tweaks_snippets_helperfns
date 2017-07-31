<?php
class Mage_Extended_Helper_Core_Functions extends Mage_Core_Helper_Data
{
	public function __construct() {
		$collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
		$collection->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
				->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
		Mage::register('active_products_collection', $collection->getData());
	}

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
		foreach ( $ri as $file ) {
			$file->isDir() ?  rmdir($file) : unlink($file);
		}
		return true;
	}

	public function createBlockCacheKey(Mage_Core_Block_Abstract $block) {
		$class = get_class($block);
		$template = $block->getTemplateFile();
		$md5 = md5(var_export($block->getData(), true));
		$params = trim(implode(",", $block->getRequest()->getParams()), ',');
		$category=($block->getCurrentCategory())?$block->getCurrentCategory()->getName():'no-category';
		$store_id = Mage::app()->getStore()->getId();
		return 'customfuncs-'. $params . '-'. $category . '-' . $store_id . '-' . $class.'-'.$template.'-'.$md5;
	}

	private function _searchCatsForIds($catIds, $needles) {
		$matchsCount = 0;
		foreach($catIds as $element) {
			if(is_array($element)) {
				$matchsCount += $this->_findCatsForId($element, $needles);
			} else {
				if(in_array($element, $needles)) {
					$matchsCount++;
				}
			}
		}
		return $matchsCount;
	}

	protected function _countCatIdInCatIds($catIds, $catId) {
		//$key = null;
		$value = $catId;
		$catIdCount = count(array_filter($catIds, function($element) use($key, $value) {
			return $element[$key] == $value;
		}));
		$number = 15;
		//Kolla ifall denna har subtasks?
		echo count(array_filter($tasks, function($element) use ($number) {
			return $element['parent'] == $number;
		}));
		return $catIdCount;
	}

	protected function _arrayCount($array, $key, $value = NULL) {
		// count($array[*][$key])
		$c = 0;
		if (is_null($value)) {
			foreach($array as $i => $subarray) {
				$c += ($subarray[$key] != '');
			}
		} else {
			foreach($array as $i=>$subarray) {
				$c += ($subarray[$key] == $value);
			}
		}
		return $c;
	}
	
	public function applyRecursivePermissions($path, $permission = 0777, $reset = false) {
		$dir = new DirectoryIterator($path);
		foreach($dir as $item) {
			if($reset === true) {
				if($item->isDir()) $this->applyRecursivePermissions($item->getPathname(), 0755);
				else chmod($item->getPathname(), 0644);
				return $reset;
			} else {
				chmod($item->getPathname(), $permission);
				if($item->isDir() && !$item->isDot()) {
					$this->applyRecursivePermissions($item->getPathname(), $permission);
				}
			}
		}
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
}
