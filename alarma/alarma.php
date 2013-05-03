<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');

//header('Content-Type: application/json');

if ( !isset( $_COOKIE['ffid'] ) || !isset( $_COOKIE['ffuid'] ) ) die('no cookie');
// not authorized

$user = $_COOKIE['ffid'];
$uid = $_COOKIE['ffuid'];

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');
$sql = "SELECT oauth_token, oauth_token_secret FROM `$table` WHERE id='$user' AND uid='$uid'";
$data = $mysql->getData( $sql );

if ( !count($data) ) header('Location: /');

$token = $data[0]['oauth_token'];
$secret = $data[0]['oauth_token_secret'];
// get user token and secret

$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );

if ( $_POST['action'] == 'remove' && isset( $_POST['id'] ) ) {
	$id = $_POST['id'];
	$rm_query = "DELETE FROM `app_ffontime` WHERE id='$id' AND user='$user'";
	$mysql->runSql( $rm_query );
	if ( $mysql->errno() == 0 ) {
		$parsed_message = array(
			'action'	=> 'remove',
			'status'	=> 'success',
			'id'		=> $id,
			'timestamp'	=> time()
		);
		echo json_encode($parsed_message);
	}
	else {
		$parsed_message = array(
			'action'	=> 'remove',
			'status'	=> 'error',
			'id'		=> $id,
			'timestamp'	=> time()
		);
		echo json_encode($parsed_message);
	}
}
else {
	$parsed_message = array(
		'action'	=> 'null',
		'status'	=> 'null',
		'id'		=> 'null',
		'timestamp'	=> time()
	);
	echo json_encode($parsed_message);
}

$mysql->closeDb();
// close database connection
?>
