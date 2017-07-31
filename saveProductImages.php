<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit','1024M');
require_once __DIR__ . '/app/Mage.php';
umask(0);
Mage::app('default');

$targetDirectory = __DIR__.DS."allProductImages_".date('m-d-Y_hia').DS;
define('TARGET_DIR', $targetDirectory);
if(!is_dir(TARGET_DIR)) mkdir(TARGET_DIR, 0777, true);

$products = Mage::getModel('catalog/product')->getCollection();
foreach($products as $product) {
	$productId = $product->getId();
	$productName = $product->getName();
	$productSku = $product->getSku();
	$product = Mage::getModel('catalog/product')->load($productId);
	$productMediaConfig = Mage::getModel('catalog/product_media_config');
	$baseImageUrl  = $productMediaConfig->getMediaUrl($product->getImage());
	$smallImageUrl = $productMediaConfig->getMediaUrl($product->getSmallImage());
	$thumbnailUrl  = $productMediaConfig->getMediaUrl($product->getThumbnail());
	$imagesArray[] = $baseImageUrl;
	$imagesArray[] = $smallImageUrl;
	$imagesArray[] = $thumbnailUrl;
}

//$owner = "www-data";$group = "www-data";$folder = $targetDirectory;
//chown($folder, $owner);chgrp($folder, $group);

foreach($imagesArray as $image) downloadWithCurl($image);

function downloadWithCurl($file) {
	$fileName = basename($file);
	$fileHandle = fopen(TARGET_DIR.$fileName, 'x');
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $file);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)');
	curl_setopt($ch, CURLOPT_FILE, $fileHandle);
	$fileData = curl_exec($ch);
	fwrite($fileHandle, $fileData);
	fclose($fileHandle);
	curl_close($ch);
}

## find . -name "allProductImages_*" -type d -exec rm -r "{}" \;
## rm *.jpg
