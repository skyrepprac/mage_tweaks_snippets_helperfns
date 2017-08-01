<?php
$date = new DateTime();
define('DIRNAME_SUFFIX', $dirNamePrefix = $date->format('Y-m-d_H-i-s'));
?>
<?php if (!empty($_POST)):
	$path = $_POST['nested_dirs'];
	$text = trim($_POST['nested_dirs']);
	$textAr = explode("\n", $text);
	$textAr = array_filter($textAr, 'trim');
	$mainDir = "MageRewrite_" . DIRNAME_SUFFIX;
	foreach($textAr as $dirPath):
		$dirPath = $mainDir . DIRECTORY_SEPARATOR . $dirPath . DIRECTORY_SEPARATOR;
		try {
			mkdir($dirPath, 0777, true);
		} catch(Exception $e) {
			echo "<pre/>"."Error creating directory path ".$dirPath." is: ".$e->getMessage();
			continue;
		}
	endforeach;
else: ?>
	<form action=<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?> method="post">
		Nested Directories: <textarea style="border: 1px solid black;" name="nested_dirs" rows="7" cols="70"></textarea><br/> <input value="MKDIRs" type="submit">
	</form>
<?php endif; ?>
