<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit','1024M');
require_once __DIR__ . '/app/Mage.php';
umask(0);
Mage::app('default');

$targetDirName = "allProductImages_".date('m-d-Y_hia');
$targetDirPath = $targetDirectory = __DIR__.DS.$targetDirName.DS;
define('TARGET_DIR', $targetDirectory);
if(!is_dir(TARGET_DIR)) mkdir(TARGET_DIR, 0777, true);
$products = Mage::getModel('catalog/product')->getCollection();
foreach($products as $product) {
	$productId = $product->getId();
	$productName = $product->getName();
	$productSku = $product->getSku();
	$product = Mage::getModel('catalog/product')->load($productId);
	$productMediaConfig = Mage::getModel('catalog/product_media_config');
	$baseImageUrl = $productMediaConfig->getMediaUrl($product->getImage());
	$smallImageUrl = $productMediaConfig->getMediaUrl($product->getSmallImage());
	$thumbnailUrl = $productMediaConfig->getMediaUrl($product->getThumbnail());
	$baseImagePath = $productMediaConfig->getMediaPath($product->getImage());
	$smallImagePath = $productMediaConfig->getMediaPath($product->getSmallImage());
	$thumbnailPath = $productMediaConfig->getMediaPath($product->getThumbnail());
	$imagesArray[] = $baseImagePath;
	$imagesArray[] = $smallImagePath;
	$imagesArray[] = $thumbnailPath;
}

foreach($imagesArray as $image):
	$imageName = basename($image);
	rawCopy($image, TARGET_DIR . $imageName);
endforeach;

exit(appendToZip($targetDirName . ".zip", TARGET_DIR));

function rawCopy($srcPath, $destPath) {
	try {
		copy($srcPath, $destPath);
		echo "<pre/>File \"$srcPath\" copied to \"$destPath\" successfully !!!";
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
	foreach ($files as $name => $file)
	{
	    // Skip directories (they would be added automatically)
	    if (!$file->isDir())
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
