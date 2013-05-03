<?php
require_once('config.php');
require_once('oauth.php');
require_once('client.php');

if ( !isset($_COOKIE['tmp'][0]) || !isset($_COOKIE['tmp'][1]) || $_COOKIE['tmp'] == '' ) {
	header('Location: /');
	die();
}

function random($length) {
	$hash = '';
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz-_';
	$max = strlen($chars) - 1;
	mt_srand( (double)microtime() * 1000000 );
	for ( $i = 0; $i < $length; $i++ )
		$hash .= $chars[mt_rand(0, $max)];
	return $hash;
}

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');
$last_check = time();

//建造一个新的认证对象
$o = new OAuth( FF_AKEY , FF_SKEY , $_COOKIE['tmp'][0] , $_COOKIE['tmp'][1]  );
$last_key = $o -> getAccessToken(  $_COOKIE['tmp'][0] );
$token = $last_key['oauth_token'];
$secret = $last_key['oauth_token_secret'];

if ( $_COOKIE['redir'] != '' )
	$location = $_COOKIE['redir'];
else
	$location = '/';
SetCookie("redir", '', time(), '/', '');
SetCookie("tmp[0]", "", time(), '/', '');
SetCookie("tmp[1]", "", time(), '/', '');

$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
$api = json_decode( $ff_user -> rate_limit_status(), true );
$remaining = $api['remaining_hits'];
$reset_time = $api['reset_time_in_seconds'];

if ( !$remaining ) {
	header("Content-type: text/html; charset=utf-8");
	die('抱歉，由于饭否API的限制，你本小时内的<abbr title="饭否对用户每小时可以通过第三方应用访问饭否的次数做了限制，目前为每小时 1500 次。如果超过限额，需要在下一小时才能继续正常使用第三方应用。"><strong>1500次调用限额</strong></abbr>已到。请稍等 ' . date('i', $reset_time) . ' 分钟才能继续使用。<br /><br /><a href="/">返回首页</a>');
}

//获取用户信息
$id = json_decode( $ff_user -> show_user(), true );
$user = $id['id'];

if ( !$user ){
	header("Content-type: text/html; charset=utf-8");
	die('抱歉，出错了，请<a href="/">重新进行饭否授权</a>。<br />如果仍然存在问题，请 @marcher 。谢谢！');
}

$screen_name = $id['screen_name'];
$birthday = $id['birthday'];

$sql = "SELECT count(1) as count FROM `$table` WHERE id='$user' limit 1";
$data = $mysql->getLine( $sql );

if ( !$data['count'] ) {
	$uid = random(20);
	if ( $user ) {
		$insert_query = "INSERT INTO `$table` (id, birthday, oauth_token, oauth_token_secret, uid, last_check) VALUES ('$user','$birthday','$token','$secret','$uid', '$last_check')";
		$token_insert = $mysql->runSql( $insert_query );
		if ( !$token_insert ) {
			$update_query = "UPDATE `$table` SET oauth_token='$token', oauth_token_secret='$secret', uid='$uid', last_check='$last_check' WHERE id='$user'";
			$mysql->runSql( $update_query );
		}
	}
	else {
		header("Content-type: text/html; charset=utf-8");
		die('抱歉，出错了，请<a href="/">重新进行饭否授权</a>。<br />如果仍然存在问题，请 @marcher 。谢谢！');
	}
}
else {
	$sql = "SELECT uid FROM `$table` WHERE id='$user' limit 1";
	$oauth_code = $mysql->getLine( $sql );
	$uid = $oauth_code['uid'] ? $oauth_code['uid'] : random(20);
	$update_query = "UPDATE `$table` SET oauth_token='$token', oauth_token_secret='$secret', uid='$uid', last_check='$last_check' WHERE id='$user'";
	$mysql->runSql( $update_query );
}

// Set SSO Cookie for UYAN
$uffid = urlencode($user);
$uname = urlencode($id['screen_name']);
$uface = urlencode($id['profile_image_url']);
$ulink = urlencode('http://fanfou.com/' . $user);
$uyankey = 'BDSiRbFBFvEbh2r3';

$f = new SaeFetchurl();
$uyanurl = "http://api.uyan.cc/?mode=des&uid=".$uffid."&uname=".$uname."&uface=".$uface."&ulink=".$ulink."&key=BDSiRbFBFvEbh2r3";
$syncuyan = $f->fetch($uyanurl);
SetCookie("syncuyan", $syncuyan, time()+864000, '/', '');
// UYAN End

SetCookie("ffid", $user, time() + 3600 * 24 * 10, '/', '');
SetCookie("ffuid", $uid, time() + 3600 * 24 * 10, '/', '');

$mysql->closeDb();

header("Content-type: text/html; charset=utf-8");
header("Location: $location");

?>
<html>
<head>
<title>正在登入“饭否小工具”... - 物以类聚</title>
</head>
<body>
<?php include_once("ga.php"); ?>
正在登入...<noscript><a href="/">开始使用饭否小工具</a></noscript>
</body>

</html>