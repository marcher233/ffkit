<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');

ini_set('max_execution_time', 0);
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');

// check diff and follow new followers
$ff = new FFClient( FF_AKEY_ALARMA , FF_SKEY_ALARMA , OAUTH_TOKEN , OAUTH_TOKEN_SECRET );

$followers = json_decode( $ff -> followers(), true );
$friends = json_decode( $ff -> friends(), true );
$diff = array_diff( $followers, $friends );
print_r($diff);

foreach ( $followers as $key => $user )
	$mysql->runSql("insert into `app_ffontime_follower` (id) values ('$user')");

foreach ( $diff as $key => $user ) {
	$result = $mysql->getLine("select request from `app_ffontime_follower` where id='$user'");
	if ( !$result['request'] ) {
		$mysql->runSql("update `app_ffontime_follower` set request=1 where id='$user'");
		$ff -> follow( $user );
	}
}

?>