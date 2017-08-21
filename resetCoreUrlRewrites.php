<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit','1024M');
require_once __DIR__ . '/app/Mage.php';
umask(0);
Mage::app('admin');
Mage::setIsDeveloperMode(true);
Varien_Profiler::enable();

$coreUrlRewrites = Mage::getModel('core/url_rewrite')->getCollection();
$mainIndexer = Mage::getModel('index/indexer');

foreach($coreUrlRewrites as $urlRewrite) {
	try {
		echo "<pre/>"; print get_class($urlRewrite);
		echo "<pre/>"; print_r(get_class_methods($urlRewrite));die;
		$urlRewrite->delete();
	} catch(Exception $e) {
		echo "<pre/>Error in deleting url-rewrite:";print_r($e->getMessage());die;
	}
}
//reindexAllProcesses($mainIndexer);

function reindexSingleProcess($indexer, $processCode) {
	$processes = $indexer->getProcessesCollection();
	$process = $indexer->getProcessByCode($processCode);
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

function reindexAllProcesses($indexer) {
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

Varien_Profiler::disable();
Mage::setIsDeveloperMode(false);