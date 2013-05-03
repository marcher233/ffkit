<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');

header("Content-type: text/html; charset=utf-8");

if ( !isset( $_COOKIE['ffid'] ) || !isset( $_COOKIE['ffuid'] ) ) echo '{"status":"error", "message":"您的登录已过期。请返回应用重新登录。", "api_remaining":"-", "api_reset_time":"-", "timestamp":"' . time() . '"}';
// not authorized

$user = $_COOKIE['ffid'];
$uid = $_COOKIE['ffuid'];
$update = $_GET['update'];
$userid = $_GET['user'];

while ( $userid != '' ) {

	$mysql = new SaeMysql();
	$mysql->setCharset('UTF8');
	$query = "SELECT oauth_token, oauth_token_secret FROM `$table` WHERE id='$user' and uid='$uid'";
	$data = $mysql->getLine( $query );

	if ( !count($data) )
		echo '{"status":"error","message":"信息出现错误。请退出应用后重新登录。","timestamp":"' . time() . '"}';

	$token = $data['oauth_token'];
	$secret = $data['oauth_token_secret'];
	//读取数据库中的token

	ini_set('max_execution_time', 0);
	set_time_limit(0);

	$today = date('m-d');
	$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
	$userinfo = json_decode( $ff_user -> show_user( $userid ), true );
	$birthday = $userinfo['birthday'];

	if ( $update == 'force' ) {
		$update_query = "UPDATE `$table` set birthday='$birthday' WHERE id='$userid'";
		$mysql->runSql( $update_query );
	}
	else {
		$update_query = "INSERT INTO `$table` (id, birthday) VALUES ('$userid','$birthday')";
		$mysql->runSql( $update_query );
	}

	if ( $mysql->errno() != 0 )
		die('{"status":"error","message":"<img src=\"ff-wrong.png\" style=\"border: 0;\" />数据库出错。请联系 @marcher，谢谢！","timestamp":"' . time() . '"}');

	echo '{"status":"success","message":"","id":"' . $userid . '","birthday":"' . $birthday . '","timestamp":"' . time() . '"}';
	return;
	
}

$mysql->closeDb();
echo '{"status":"success"}';
?>
