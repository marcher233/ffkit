<?php

//header("Content-Type: application/octet-stream");
//header("Content-Type: application/force-download");
//header("Content-Type: application/vnd.ms-excel");

header("Content-Type: plain/text; charset=utf-8");
header("Content-Disposition: attachment; filename=fanfou-export-" . $_POST['exp'] . "-" . date('Ymd_Hi') . ".csv");
// To force download csv file named fanfou-export
setlocale(LC_ALL, "zh_CN");

$list = array();
$list = array_combine(explode(',', $_POST['timestamp']), explode(',', $_POST['status']));

$file = fopen("php://output", "w");
// Write BOM first to make a utf8 csv file
fwrite($file, chr(0xEF).chr(0xBB).chr(0xBF));
foreach ($list as $timestamp => $message) {
	$message = urldecode($message);
	fputcsv($file, array($timestamp, $message));
}
//rewind($file);
//$content = stream_get_contents($file);
fclose($file);

?>