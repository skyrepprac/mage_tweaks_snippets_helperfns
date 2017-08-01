<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('html_errors', 1);
set_time_limit(0);
ini_set('memory_limit','1024M');
require_once __DIR__ . '/app/Mage.php';
umask(0);
Mage::app('default');
Mage::setIsDeveloperMode(true);

Varien_Profiler::enable();
Varien_Profiler::start('developer_task_product_image_info');

$targetDirName = "allProductImages_".date('m-d-Y_hia');

$targetDirPath = $targetDirectory = __DIR__.DS.$targetDirName.DS;
define('TARGET_DIR', $targetDirectory);
## define('$mageImageHelper', Mage::helper('catalog/image'));
if(!is_dir(TARGET_DIR)) mkdir(TARGET_DIR, 0777, true);

header('Content-Disposition: attachment; filename="custom_php.log"');

$mageImageHelper = Mage::helper('catalog/image');
$mageCoreHelper = Mage::helper('core');
$products = Mage::getModel('catalog/product')->getCollection();
foreach($products as $product) {
	$productId = $product->getId();
	$productName = $product->getName();
	$productSku = $product->getSku();
	$product = Mage::getModel('catalog/product')->load($productId);
	$productMediaConfig = Mage::getModel('catalog/product_media_config');
	$baseImagePath = $productMediaConfig->getMediaPath($product->getImage());
	$smallImagePath = $productMediaConfig->getMediaPath($product->getSmallImage());
	$thumbnailPath = $productMediaConfig->getMediaPath($product->getThumbnail());
	$imagesArray[$productSku]['baseImage'] = $baseImagePath;
	$imagesArray[$productSku]['smallImage'] = $smallImagePath;
	$imagesArray[$productSku]['thumbnailImage'] = $thumbnailPath;
	$imagesArray[$productSku]['baseImageSize'] = $mageImageHelper->init($product, 'image')->getOriginalWidth() . 'x' . $mageImageHelper->init($product, 'image')->getOriginalHeight();
	$imagesArray[$productSku]['smallImageSize'] = $mageImageHelper->init($product, 'small_image')->getOriginalWidth() . 'x' . $mageImageHelper->init($product, 'small_image')->getOriginalHeight();
	$imagesArray[$productSku]['thumbnailSize'] = $mageImageHelper->init($product, 'thumbnail')->getOriginalWidth() . 'x' . $mageImageHelper->init($product, 'thumbnail')->getOriginalHeight();
}

foreach($imagesArray as $folder => $image):
	$baseImageName = basename($image['baseImage']);
	$smallImageName = basename($image['smallImage']);
	$thumbnailName = basename($image['thumbnailImage']);

	// Split name and extension
	$baseImageInfo = pathinfo($baseImageName);
	$smallImageInfo = pathinfo($smallImageName);
	$thumbnailInfo = pathinfo($thumbnailName);

	if(isValidKey($baseImageInfo, 'extension') || isValidKey($smallImageInfo, 'extension') || isValidKey($thumbnailInfo, 'extension')) {
		$baseImageName = isValidKey($baseImageInfo, 'extension') ? $baseImageInfo['filename'] . "_" . $image['baseImageSize'] . "." . $baseImageInfo['extension'] : null;
		$smallImageName = isValidKey($smallImageInfo, 'extension') ? $smallImageInfo['filename'] . "_" . $image['smallImageSize'] . "." . $smallImageInfo['extension'] : null;
		$thumbnailName = isValidKey($smallImageInfo, 'extension') ? $thumbnailInfo['filename'] . "_" . $image['thumbnailSize'] . "." . $thumbnailInfo['extension'] : null;

		$finalDestDir = TARGET_DIR . $folder . DS;
		refinedMakeDir($finalDestDir);

		rawCopy($image['baseImage'], $finalDestDir . $baseImageName);
		rawCopy($image['smallImage'], $finalDestDir . $smallImageName);
		rawCopy($image['thumbnailImage'], $finalDestDir . $thumbnailName);
	} else {
		$content = array($folder, $baseImageInfo, $smallImageInfo, $thumbnailInfo);
		writeToLog($content);
		continue;
		## exit;
	}
endforeach;

exit(appendToZip($targetDirName . ".zip", TARGET_DIR));

function refinedMakeDir($dirPath) {
	if(!is_dir($dirPath)) {
		mkdir($dirPath, 0777, true);
		return true;
	} else return $dirPath;
}

function isValidKey($array, $key) {
	if(isset($array[$key]) && array_key_exists($key, $array)) return true;
	else return false;
}

function rawCopy($srcPath, $destPath) {
	try {
		copy($srcPath, $destPath);
		//echo "<pre/>File \"$srcPath\" copied to \"$destPath\" successfully !!!";
	} catch(Exception $e) {
		echo "<pre/>Error copying file:- " . $e->getMessage();
	}
}

function appendToZip($zipFileName, $folderToZip) {
	// Get real path for our folder
	$rootPath = $folderToZip;
	// Initialize archive object
	$zip = new ZipArchive();
	$zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
	// Create recursive directory iterator
	/** @var SplFileInfo[] $files */
	$files = new RecursiveIteratorIterator(
	    new RecursiveDirectoryIterator($rootPath),
	    RecursiveIteratorIterator::LEAVES_ONLY
	);
	foreach($files as $name => $file)
	{
	    // Skip directories (they would be added automatically)
	    if(!$file->isDir())
	    {
	        // Get real and relative path for current file
	        $filePath = $file->getRealPath();
	        $relativePath = substr($filePath, strlen($rootPath) + 1);
	        // Add current file to archive
	        $zip->addFile($filePath, $relativePath);
	    }
	}
	// Zip archive will be created only after closing object
	$zip->close();
}

function writeToLog($resource, $filename = null) {
	//Write action to txt log
	echo "<pre/>";print_r($resource);
}

Varien_Profiler::stop('developer_task_product_image_info');
Varien_Profiler::disable();