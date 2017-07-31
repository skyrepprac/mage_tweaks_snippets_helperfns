<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit','1024M');
require_once __DIR__ . '/app/Mage.php';
umask(0);
Mage::app('default');

## exit(phpinfo());
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

//$owner = "www-data";$group = "www-data";$folder = $targetDirectory;
//chown($folder, $owner);chgrp($folder, $group);

## foreach($imagesArray as $image) downloadWithCurl($image);
foreach($imagesArray as $image):
	$imageName = basename($image);
	rawCopy($image, TARGET_DIR . $imageName);
endforeach;

//Create zip of the target folder
exit(appendToZip($targetDirName . ".zip", TARGET_DIR));

function downloadWithCurl($file) {
	if(isValidImage($file)) {
		$fileName = basename($file);
		$fileHandle = fopen(TARGET_DIR . $fileName, 'w');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $file);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6)');
		curl_setopt($ch, CURLOPT_FILE, $fileHandle);
		## $fileData = curl_exec($ch);
		if($fileData = curl_exec($ch) === false) echo 'Curl error: ' . curl_error($ch) . "<br/>";
		fwrite($fileHandle, $fileData);
		fclose($fileHandle);
		curl_close($ch);
	} else {
		echo "<pre/>".$file . " - " . getimagesize($file);
		echo stream_get_meta_data($file);
	}
}

/*function isValidImage($url) {
	$size = getimagesize($url);
	return (strtolower(substr($size['mime'], 0, 5)) == 'image' ? true : false);
}*/

function isValidImage($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if(curl_exec($ch) !== false) return true;
    else return false;
}

function rawCopy($srcPath, $destPath) {
	try {
		copy($srcPath, $destPath);
		echo "<pre/>File \"$srcPath\" copied to \"$destPath\" successfully !!!";
	} catch(Exception $e) {
		echo "<pre/>Error copying file:- " . $e->getMessage();
	}
}

function addToZip($folderPath) {
	if ($handle = opendir($folderPath))
	{
		$zip = new ZipArchive();
		if($zip->open($zip_file, ZIPARCHIVE::CREATE)!==TRUE) {
			exit("cannot open <$zip_file>\n");
		}
		while(false !== ($file = readdir($handle))) {
			$zip->addFile($folderPath.'/'.$file);
			echo "$file\n";
		}
		closedir($handle);
		echo "numfiles: " . $zip->numFiles . "\n";
		echo "status:" . $zip->status . "\n";
		$zip->close();
		echo 'Zip File:'.$zip_file . "\n";
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

## find . -name "allProductImages_*" -type d -exec rm -r "{}" \;
## rm *.jpg
